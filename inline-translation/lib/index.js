/**
 * Community Translation core module
 */
'use strict';

/**
 * External dependencies
 */
var debug = require( 'debug' )( 'inline-translator' );

/**
 * Internal dependencies
 */
var TranslationPair = require( './translation-pair' ),
	Walker = require( './walker' ),
	Locale = require( './locale' ),
	Popover = require( './popover' ),
	GlotPress = require( './glotpress' );

/**
 * Local variables
 */
var debounceTimeout,
	currentlyWalkingTheDom = false,
	loadCSS, loadData, registerContentChangedCallback, registerDomChangedCallback,
	registerPopoverHandlers, findNewTranslatableTexts,
	glotPress, currentUserId, walker,
	translationData = {
		cssUrl: '/',
		currentUserId: false,
		localeCode: 'en',
		languageName: 'English',
		pluralForms: 'nplurals=2; plural=(n != 1)',
		contentChangedCallback: function() {},
	},
	translationUpdateCallbacks = [];
require( './jquery.webui-popover.js' );

module.exports = {

	load: function() {
		if ( 'undefined' === typeof window.gpInlineTranslationData ) {
			return false;
		}
		loadCSS();
		loadData( window.gpInlineTranslationData );
		registerPopoverHandlers();
		registerContentChangedCallback();
		findNewTranslatableTexts();
	},

	unload: function() {
		if ( debounceTimeout ) {
			window.clearTimeout( debounceTimeout );
		}
		if ( 'object' === typeof window.gpInlineTranslationData ) {
			window.gpInlineTranslationData.contentChangedCallback = function() {};
		}
		unRegisterPopoverHandlers();
		removeCssClasses();
	},

	registerTranslatedCallback: function( callback ) {
		translationUpdateCallbacks.push( callback );
	},

};

function notifyTranslated( newTranslationPair ) {
	debug( 'Notifying string translated', newTranslationPair.serialize() );
	translationUpdateCallbacks.forEach( function( hook ) {
		hook( newTranslationPair.serialize() );
	} );
}

loadCSS = function() {
	var s;
	if ( translationData.cssUrl ) {
		s = document.createElement( 'link' );
		s.setAttribute( 'rel', 'stylesheet' );
		s.setAttribute( 'type', 'text/css' );
		s.setAttribute( 'href', translationData.cssUrl );
		document.getElementsByTagName( 'head' )[ 0 ].appendChild( s );
	}
	jQuery( 'iframe' ).addClass( 'translator-untranslatable' );
};

loadData = function( translationDataFromJumpstart ) {
	if (
		typeof translationDataFromJumpstart === 'object' &&
			typeof translationDataFromJumpstart.localeCode === 'string'
	) {
		translationData = translationDataFromJumpstart;
	}

	translationData.locale = new Locale( translationData.localeCode, translationData.languageName, translationData.pluralForms );
	currentUserId = translationData.currentUserId;

	glotPress = new GlotPress( translationData.locale, translationData.translations );
	if ( 'undefined' !== typeof translationData.glotPress ) {
		glotPress.loadSettings( translationData.glotPress );
	} else {
		debug( 'Missing GlotPress settings' );
	}

	TranslationPair.setTranslationData( translationData );
	walker = new Walker( TranslationPair, jQuery, document );
};

registerContentChangedCallback = function() {
	if ( 'object' === typeof window.gpInlineTranslationData ) {
		debug( 'Registering translator contentChangedCallback' );
		window.gpInlineTranslationData.contentChangedCallback = function() {
			if ( debounceTimeout ) {
				window.clearTimeout( debounceTimeout );
			}
			debounceTimeout = window.setTimeout( findNewTranslatableTexts, 250 );
		};

		if ( typeof window.gpInlineTranslationData.stringsUsedOnPage === 'object' ) {
			registerDomChangedCallback();
		}
	}
};

// This is a not very elegant but quite efficient way to check if the DOM has changed
// after the initial walking of the DOM
registerDomChangedCallback = function() {
	var checksRemaining = 10,
		lastBodySize = document.body.innerHTML.length,
		checkBodySize = function() {
			var bodySize;

			if ( --checksRemaining <= 0 ) {
				return;
			}

			bodySize = document.body.innerHTML.length;
			if ( lastBodySize !== bodySize ) {
				lastBodySize = bodySize;

				if ( debounceTimeout ) {
					window.clearTimeout( debounceTimeout );
				}
				debounceTimeout = window.setTimeout( findNewTranslatableTexts, 1700 );
			}
			window.setTimeout( checkBodySize, 500 );
		};

	window.setTimeout( checkBodySize, 500 );
};

registerPopoverHandlers = function() {
	jQuery( document ).on( 'keyup', 'textarea.translation', function() {
		var textareasWithInput,
			$form = jQuery( this ).parents( 'form.ct-new-translation' ),
			$allTextareas = $form.find( 'textarea' ),
			$button = $form.find( 'button' );

		textareasWithInput = $allTextareas.filter( function() {
			return this.value.length;
		} );

		// disable if no textarea has an input
		$button.prop( 'disabled', 0 === textareasWithInput.length );
	} );

	jQuery( document ).on( 'submit', 'form.ct-new-translation', function() {
		var $form = jQuery( this ),
			$node = jQuery( '.' + $form.data( 'nodes' ) ),
			translationPair = $form.data( 'translationPair' ),
			newTranslationStringsFromForm = $form.find( 'textarea' ).map( function() {
				return jQuery( this ).val();
			} ).get();

		function notEmpty( string ) {
			return string.trim().length > 0;
		}

		if ( ! newTranslationStringsFromForm.every( notEmpty ) ) {
			return false;
		}

		// We're optimistic
		// TODO: reset on failure.
		// TODO: use Jed to insert with properly replaced variables
		$node.addClass( 'translator-user-translated' ).removeClass( 'translator-untranslated' );
		if ( $node.children().length === 0 ) {
			$node.text( newTranslationStringsFromForm[ 0 ] );
		}

		// Reporting to GlotPress
		jQuery
			.when( translationPair.getOriginal().getId() )
			.done( function( originalId ) {
				var submittedTranslations = jQuery.makeArray( newTranslationStringsFromForm ),
					translation = {},
					warnings = '',
					warningsObj = {},
					outputWarningMessage = '';

				translation[ originalId ] = submittedTranslations;
				glotPress.submitTranslation( translation, translationPair ).done( function( data ) {
					if ( typeof data[ originalId ] === 'undefined' ) {
						return;
					}
					warnings = data[ originalId ][ 0 ].warnings;
					if ( warnings !== 'null' ) {
						warningsObj = JSON.parse( warnings )[ 0 ];

						jQuery.each( warningsObj, function( key, value ) {
							outputWarningMessage += value + '<br>';
						} );

						$form.find( '.warnings' ).html( '<p class="local-inline-warning"><b>Warnings: </b>' + outputWarningMessage + '</p>' );

						return;
					}

					$form.closest( '.webui-popover' ).hide();

					translationPair.updateAllTranslations( data[ originalId ], currentUserId );
					makeTranslatable( translationPair, $node );
					notifyTranslated( translationPair );

					if ( !! document.cookie.match( /inlinejumptonext=1/ ) ) {
						jQuery( '.translator-translatable.translator-untranslated:visible' ).webuiPopover( 'show' );
					}
				} ).fail( function() {
					debug( 'Submitting new translation failed', translation );
				} );
			} ).fail( function() {
				debug( 'Original cannot be found in GlotPress' );
			} );

		return false;
	} );

	jQuery( document ).on( 'submit', 'form.ct-existing-translation', function() {
		var enclosingNode = jQuery( this ),
			popover, webUiPopover,
			translationPair = enclosingNode.data( 'translationPair' );
		if ( 'object' !== typeof translationPair ) {
			debug( 'could not find translation for node', enclosingNode );
			return false;
		}

		popover = new Popover( translationPair, translationData.locale, glotPress );
		webUiPopover = enclosingNode.closest( '.webui-popover' );
		enclosingNode.parent().empty().append( popover.getTranslationHtml() ).find( 'textarea' ).get( 0 ).focus();
		webUiPopover.data( 'triggerElement' ).trigger( 'shown.webui.popover', [ webUiPopover ] );

		return false;
	} );

	jQuery( document ).on( 'submit', 'form.copy-translation', function() {
		var originals = jQuery( this ).next().find( 'div.original strong' );
		jQuery( this ).next().find( 'textarea' ).each( function( i ) {
			if ( ! originals[ i ] ) {
				return;
			}
			this.focus();
			this.select();

			// Replace all text with new text
			document.execCommand( 'insertText', false, originals[ i ].textContent );
		} );
		jQuery( this ).next().find( 'textarea' ).first().focus().trigger( 'keyup' );

		return false;
	} );
};

function removeCssClasses() {
	var classesToDrop = [
		'translator-checked',
		'translator-untranslated',
		'translator-translated',
		'translator-user-translated',
		'translator-untranslatable',
		'translator-dont-translate' ];

	jQuery( '.' + classesToDrop.join( ', .' ) ).removeClass( classesToDrop.join( ' ' ) );
}

function unRegisterPopoverHandlers() {
	jQuery( document ).off( 'submit', 'form.ct-existing-translation,form.ct-new-translation' );
	jQuery( '.translator-translatable' ).webuiPopover( 'destroy' );
}

function makeUntranslatable( translationPair, $node ) {
	debug( 'makeUntranslatable:', $node );
	$node.removeClass( 'translator-untranslated translator-translated translator-translatable translator-checking' );
	$node.addClass( 'translator-dont-translate' );
	$node.attr( 'title', 'Text-Domain: ' + translationPair.getDomain() );
}

function makeTranslatable( translationPair, node ) {
	translationPair.createPopover( node, glotPress );
	node.removeClass( 'translator-checking' ).addClass( 'translator-translatable' );
	if ( translationPair.isFullyTranslated() ) {
		if ( translationPair.isTranslationWaiting() ) {
			node.removeClass( 'translator-translated' ).addClass( 'translator-user-translated' );
		} else {
			node.removeClass( 'translator-user-translated' ).addClass( 'translator-translated' );
		}
		node.each( function() {
			var el = this;
			if ( el.childNodes.length > 1 || el.childNodes[ 0 ].nodeType !== 3 ) {
				if ( ! translationPair.getRegex().test( el.innerHTML ) ) {
					debug( 'Updating HTML translation', el.innerHTML, translationPair.getRegex(), translationPair.getRegex().test( el.innerHTML ), translationPair.getTranslation().getTextItems()[ 0 ].getText() );
					setTimeout( function() {
						el.innerHTML = translationPair.getReplacementText( el.innerHTML );
					}, 1 );
				}
				return;
			}
			if ( ! translationPair.getRegex().test( el.textContent ) ) {
				debug( 'Updating text translation', el.textContent, translationPair.getRegex(), translationPair.getTranslation().getTextItems()[ 0 ].getText() );
				setTimeout( function() {
					el.textContent = translationPair.getReplacementText( el.textContent );
				}, 1 );
			}
		} );
	} else {
		node.addClass( 'translator-untranslated' );
	}
}

findNewTranslatableTexts = function() {
	if ( currentlyWalkingTheDom ) {
		if ( debounceTimeout ) {
			window.clearTimeout( debounceTimeout );
		}
		debounceTimeout = window.setTimeout( findNewTranslatableTexts, 500 );
		return;
	}

	currentlyWalkingTheDom = true;

	walker.walkTextNodes( document.body, function( translationPair, enclosingNode ) {
		enclosingNode.addClass( 'translator-checking' );

		translationPair.fetchOriginalAndTranslations( glotPress, currentUserId )
			.fail(
				// Failure indicates that the string is not in GlotPress yet
				makeUntranslatable.bind( null, translationPair, enclosingNode )
			)
			.done(
				makeTranslatable.bind( null, translationPair, enclosingNode )
			);
	}, function() {
		currentlyWalkingTheDom = false;
	} );
};
