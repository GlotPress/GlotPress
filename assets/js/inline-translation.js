( function( f ) {
	if ( typeof exports === 'object' && typeof module !== 'undefined' ) {
		module.exports = f();
	} else if ( typeof define === 'function' && define.amd ) {
		define( [], f );
	} else {
		var g; if ( typeof window !== 'undefined' ) {
			g = window;
		} else if ( typeof global !== 'undefined' ) {
			g = global;
		} else if ( typeof self !== 'undefined' ) {
			g = self;
		} else {
			g = this;
		}g.gpInlineTranslation = f();
	}
}( function() {
	var define, module, exports; return ( function() {
		function r( e, n, t ) {
			function o( i, f ) {
				if ( ! n[ i ] ) {
					if ( ! e[ i ] ) {
						var c = 'function' === typeof require && require; if ( ! f && c ) {
							return c( i, ! 0 );
						} if ( u ) {
							return u( i, ! 0 );
						} var a = new Error( "Cannot find module '" + i + "'" ); throw a.code = 'MODULE_NOT_FOUND', a;
					} var p = n[ i ] = { exports: {} }; e[ i ][ 0 ].call( p.exports, function( r ) {
						var n = e[ i ][ 1 ][ r ]; return o( n || r );
					}, p, p.exports, r, e, n, t );
				} return n[ i ].exports;
			} for ( var u = 'function' === typeof require && require, i = 0; i < t.length; i++ ) {
				o( t[ i ] );
			} return o;
		} return r;
	}() )( { 1: [ function( require, module, exports ) {
		/*
 * This is a utility function to help reduce the number of calls made to
 * the GlotPress database ( or generic backend ), especially as we're
 * loading a new page.
 * It takes a function that takes an array and callback, and generates a new
 * function that takes a single argument and returns a Deferred object.
 * i.e.
 * function ( arrayArgument, callback )
 * to:
 * function ( singleArgument ) { return jQuery.deferred( singleResult ) }
 *
 * Internally, the function collects up a series of these singleArgument
 * calls and makes a single call to the original function ( presumably the
 * backend ) after a brief delay.
 */

		function handleBatchedResponse( response, originalToCallbacksMap ) {
			var i, data, j, key;
			if ( 'undefined' === typeof response ) {
				return false;
			}

			if ( 'undefined' === typeof response[ 0 ] ) {
				response = [ response ];
			}

			for ( i = 0; ( data = response[ i ] ); i++ ) {
				if ( 'undefined' === typeof data || 'undefined' === typeof data.original ) {
					// if there is not a single valid original
					break;
				}

				key = data.original.hash;
				if ( 'undefined' === typeof originalToCallbacksMap[ key ] || !
				originalToCallbacksMap[ key ] ) {
					continue;
				}

				for ( j = 0; j < originalToCallbacksMap[ key ].length; j++ ) {
					originalToCallbacksMap[ key ][ j ].resolve( data );
				}

				originalToCallbacksMap[ key ] = null;
				delete originalToCallbacksMap[ key ];
			}

			// reject any keys that have not been handled
			for ( key in originalToCallbacksMap ) {
				if ( ! originalToCallbacksMap[ key ] ) {
					continue;
				}

				for ( j = 0; j < originalToCallbacksMap[ key ].length; j++ ) {
					originalToCallbacksMap[ key ][ j ].reject();
				}
			}
		}

		module.exports = function( functionToWrap ) {
			var batchDelay = 200,
				originalToCallbacksMap = {},
				batchedOriginals = [],
				batchTimeout,
				delayMore,
				resolveBatch;

			if ( 'function' !== typeof ( functionToWrap ) ) {
				return null;
			}

			delayMore = function() {
				if ( batchTimeout ) {
					window.clearTimeout( batchTimeout );
				}
				batchTimeout = window.setTimeout( resolveBatch, batchDelay );
			};

			// Actually make the call through the original function
			resolveBatch = function() {
				// Capture the data relevant to this request
				var originals = batchedOriginals.slice(),
					callbacks = originalToCallbacksMap;

				// Then clear out the data so it's ready for the next batch.
				batchTimeout = null;
				originalToCallbacksMap = {};
				batchedOriginals = [];

				if ( 0 === originals.length ) {
					return;
				}

				functionToWrap( originals, function( response ) {
					handleBatchedResponse( response, callbacks );
				} );
			};

			return function( original ) {
				var deferred = new jQuery.Deferred();
				if ( original.hash in originalToCallbacksMap ) {
					originalToCallbacksMap[ original.hash ].push( deferred );
				} else {
					batchedOriginals.push( original );
					originalToCallbacksMap[ original.hash ] = [ deferred ];
				}

				delayMore();

				return deferred;
			};
		};
	}, {} ], 2: [ function( require, module, exports ) {
		/**
		 * Community Translation GlotPress module
		 */
		'use strict';

		var batcher = require( './batcher.js' );

		function GlotPress( locale, translations ) {
			var server = {
					url: '',
					projects: [],
					translation_set_slug: 'default',
				},
				batch = batcher( fetchOriginals ),
				lastPrompt = '',
				glossaryMarkups = {};
			function ajax( options ) {
				options = jQuery.extend( {
					method: 'POST',
					data: {},
					beforeSend: function( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', server.nonce );
					},
				}, options );
				return jQuery.ajax( options );
			}

			function fetchOriginals( originals, callback ) {
				if ( ! server.projects.length ) {
					return callback( {} );
				}
				ajax( {
					url: server.restUrl + '/translations-by-originals',
					data: {
						projects: server.projects,
						translation_set_slug: server.translation_set_slug,
						locale_slug: locale.getLocaleCode(),
						original_strings: JSON.stringify( originals ),
					},
				} ).done( function( response ) {
					callback( response );
				} );
			}

			function hash( original ) {
				var key = '|' + original.singular;
				if ( 'undefined' !== typeof original.context ) {
					key = original.context + key;
				}
				key = '|' + key;
				if ( 'undefined' !== typeof original.domain ) {
					key = original.domain + key;
				}
				return key;
			}

			return {
				getPermalink: function( translationPair ) {
					var originalId = translationPair.getOriginal().getId(),
						projectUrl,
						translateSetSlug = server.translation_set_slug,
						translationId,
						url;

					projectUrl = server.url + 'projects/' + translationPair.getGlotPressProject();
					url = projectUrl + '/' + locale.getLocaleCode() + '/' + translateSetSlug + '?filters[original_id]=' + originalId;

					if ( 'undefined' !== typeof translationId ) {
						url += '&filters[translation_id]=' + translationId;
					}

					return url;
				},

				loadSettings: function( gpInstance ) {
					server = gpInstance;
				},

				shouldLoadSuggestions: function() {
					return !! server.openai_key;
				},

				getLastPrompt: function() {
					return lastPrompt;
				},

				queryByOriginal: function( original ) {
					var deferred;
					original.hash = hash( original );
					if ( original.hash in translations ) {
						deferred = new jQuery.Deferred();
						deferred.resolve( translations[ original.hash ] );
						return deferred;
					}

					return batch( original );
				},

				glossaryMarkup: function( translationPair ) {
					var data;
					if ( ! translationPair.getOriginal().getSingularGlossaryMarkup() && glossaryMarkups[ translationPair.getOriginal().getSingular() ] ) {
						translationPair.getOriginal().setSingularGlossaryMarkup( glossaryMarkups[ translationPair.getOriginal().getSingular() ] );
					}
					if ( ! translationPair.getOriginal().getPluralGlossaryMarkup() && glossaryMarkups[ translationPair.getOriginal().getPlural() ] ) {
						translationPair.getOriginal().setPluralGlossaryMarkup( glossaryMarkups[ translationPair.getOriginal().getPlural() ] );
					}
					if ( translationPair.getOriginal().getSingularGlossaryMarkup() ) {
						return new jQuery.Deferred().resolve( translationPair.getOriginal().objectify() );
					}
					data = {
						project: translationPair.getGlotPressProject(),
						translation_set_slug: server.translation_set_slug,
						locale_slug: locale.getLocaleCode(),
						original: translationPair.getOriginal().objectify(),
					};

					return ajax( {
						url: server.restUrl + '/glossary-markup',
						data: data,
					} ).then( function( response ) {
						if ( response.singular_glossary_markup ) {
							glossaryMarkups[ translationPair.getOriginal().getSingular() ] = response.singular_glossary_markup;
							translationPair.getOriginal().setSingularGlossaryMarkup( response.singular_glossary_markup );
						}
						if ( response.plural_glossary_markup ) {
							glossaryMarkups[ translationPair.getOriginal().getPlural() ] = response.plural_glossary_markup;
							translationPair.getOriginal().setPluralGlossaryMarkup( response.plural_glossary_markup );
						}
					} );
				},

				submitTranslation: function( translation, translationPair ) {
					var data = {
						project: translationPair.getGlotPressProject(),
						translation_set_slug: server.translation_set_slug,
						locale_slug: locale.getLocaleCode(),
						translation: translation,
					};
					window.parent.postMessage( { type: 'relay', message: 'new-translation', data: data }, 'https://playground.wordpress.net/' );
					return ajax( {
						url: server.restUrl + '/translation',
						data: data,
					} );
				},

				getSuggestedTranslation: function( translationPair, data ) {
					var messages,
						original = [ translationPair.getOriginal().getSingular() ],
						prompt = ( data && data.prompt ) || '',
						language = locale.getLanguageName();

					if ( [ 'German' ].includes( language ) ) {
						language = 'informal ' + language;
					}

					if ( server.openai_prompt ) {
						prompt += server.openai_prompt;
					}

					if ( ! ( data && data.prompt ) && translationPair.getOriginal().getSingularGlossaryMarkup() ) {
						jQuery.each( jQuery( '<div>' + translationPair.getOriginal().getSingularGlossaryMarkup() ).find( '.glossary-word' ), function( k, word ) {
							jQuery.each( jQuery( word ).data( 'translations' ), function( i, e ) {
								prompt += 'Translate "' + word.textContent + '" as "' + e.translation + '" when it is a ' + e.pos;
								if ( e.comment ) {
									prompt += ' (' + e.comment + ')';
								}
								prompt += '. ';
							} );
						} );
					}

					lastPrompt = prompt;

					if ( prompt ) {
						prompt += '. Given these conditions, ';
					}

					prompt += 'Translate the text in this JSON to ' + language + ' and always respond as pure JSON list (no outside comments!) in the format (append to the list if you have multiple suggestions): ';

					if ( translationPair.getOriginal().getPlural() ) {
						original.push( translationPair.getOriginal().getPlural() );
						prompt += '[["singular translation","plural translation"]]';
					} else {
						prompt += '["translation"]';
					}

					messages = [
						{
							role: 'user',
							content: prompt + '\n\n' + JSON.stringify( original ),
						},
					];

					return jQuery.ajax( {
						url: 'https://api.openai.com/v1/chat/completions',
						type: 'POST',
						headers: {
							Authorization: 'Bearer ' + server.openai_key,
						},
						data: JSON.stringify( {
							model: 'gpt-3.5-turbo',
							messages: messages,
							max_tokens: 1000,
						} ),
						contentType: 'application/json; charset=utf-8',
						dataType: 'json',
					} );
				},
			};
		}

		module.exports = GlotPress;
	}, { './batcher.js': 1 } ], 3: [ function( require, module, exports ) {
		/**
		 * Community Translation core module
		 */
		'use strict';

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
			}

			TranslationPair.setTranslationData( translationData );
			walker = new Walker( TranslationPair, jQuery, document );
		};

		registerContentChangedCallback = function() {
			if ( 'object' === typeof window.gpInlineTranslationData ) {
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
			jQuery( document ).on( 'input', 'textarea.translation', function() {
				var textareasWithInput,
					$form = jQuery( this ).parents( 'form.ct-new-translation' ),
					$allTextareas = jQuery( this ),
					$button = $form.find( 'button' ),
					translationPair = $form.data( 'translationPair' ),
					newPlaceholders = getPlaceholdersLink( translationPair, $allTextareas.val() );
				jQuery( this ).siblings( 'div.placeholders' ).html( newPlaceholders ).css( 'display', 'block' );

				textareasWithInput = $allTextareas.filter( function() {
					return this.value.length;
				} );

				// disable if no textarea has an input
				$button.prop( 'disabled', 0 === textareasWithInput.length );
			} );

			jQuery( document.body ).on( 'focus', 'textarea.translation', function() {
				var currentText = '',
					newPlaceholders = '',
					textareas = jQuery( 'textarea[name="translation[]"]' ),
					$form = jQuery( this ).parents( 'form.ct-new-translation' ),
					translationPair = $form.data( 'translationPair' );

				textareas.map( function() {
					currentText = jQuery( this ).val();
					newPlaceholders = getPlaceholdersLink( translationPair, currentText );
					jQuery( this ).siblings( '.placeholders' ).html( newPlaceholders );
					return true;
				} );
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
							if ( data[ originalId ][ 0 ].warnings !== undefined && data[ originalId ][ 0 ].warnings ) {
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
						} );
					} );

				return false;
			} );

			jQuery( document ).on( 'submit', 'form.ct-existing-translation', function() {
				var enclosingNode = jQuery( this ),
					popover, webUiPopover,
					translationPair = enclosingNode.data( 'translationPair' );
				if ( 'object' !== typeof translationPair ) {
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
							setTimeout( function() {
								el.innerHTML = translationPair.getReplacementText( el.innerHTML );
							}, 1 );
						}
						return;
					}
					if ( ! translationPair.getRegex().test( el.textContent ) ) {
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

		function getPlaceholdersLink( translationPair, textAreaContent ) {
			var placeholdersLink = '';
			var placeholders = translationPair.getOriginal().getPlaceholders();
			var index = 0;

			if ( placeholders.length ) {
				placeholdersLink = placeholders.map( function( match ) {
					if ( ! ( textAreaContent.indexOf( match ) === -1 ) ) {
						index = textAreaContent.indexOf( match );
						textAreaContent = textAreaContent.slice( 0, index ) + textAreaContent.slice( index + match.length );
						return '<a class="placeholder-exist inline-placeholder" href="#">' + match + '</a>';
					}
					return '<a class="inline-placeholder" href="#">' + match + '</a>';
				} ).join( '' );
			}
			return placeholdersLink;
		}
	}, { './glotpress': 2, './jquery.webui-popover.js': 4, './locale': 5, './popover': 7, './translation-pair': 8, './walker': 10 } ], 4: [ function( require, module, exports ) {
		/*
 *  webui popover plugin  - v1.2.2
 *  A lightWeight popover plugin with jquery ,enchance the  popover plugin of bootstrap with some awesome new features. It works well with bootstrap ,but bootstrap is not necessary!
 *  https://github.com/sandywalker/webui-popover
 *
 *  Made by Sandy Duan
 *  Under MIT License
 */

		( function( $, window, document, undefined ) {
			'use strict';

			// Create the defaults once
			var pluginName = 'webuiPopover';
			var pluginClass = 'webui-popover';
			var pluginType = 'webui.popover';
			var defaults = {
				placement: 'auto',
				width: 'auto',
				height: 'auto',
				trigger: 'click', //hover,click,sticky,manual
				style: '',
				delay: {
					show: null,
					hide: null,
				},
				async: {
					before: null, //function(that, xhr){}
					success: null, //function(that, xhr){}
				},
				cache: true,
				multi: false,
				arrow: true,
				title: '',
				content: '',
				closeable: false,
				padding: true,
				url: '',
				type: 'html',
				animation: null,
				template: '<div class="webui-popover">' +
			'<div class="arrow"></div>' +
			'<div class="webui-popover-inner">' +
			'<a href="#" class="close">&times;</a>' +
			'<h3 class="webui-popover-title"></h3>' +
			'<div class="webui-popover-content"><i class="icon-refresh"></i> <p>&nbsp;</p></div>' +
			'</div>' +
			'</div>',
				backdrop: false,
				dismissible: true,
				onShow: null,
				onHide: null,
				abortXHR: true,
				autoHide: false,
				offsetTop: 0,
				offsetLeft: 0,
			};

			var _srcElements = [];
			var backdrop = $( '<div class="webui-popover-backdrop"></div>' );
			var _globalIdSeed = 0;
			var _isBodyEventHandled = false;
			var _offsetOut = -2000; // the value offset  out of the screen
			var $document = $( document );

			var toNumber = function( numeric, fallback ) {
				return isNaN( numeric ) ? ( fallback || 0 ) : Number( numeric );
			};

			var getPopFromElement = function( $element ) {
				return $element.data( 'plugin_' + pluginName );
			};

			var hideAllPop = function() {
				for ( var i = 0; i < _srcElements.length; i++ ) {
					_srcElements[ i ].webuiPopover( 'hide' );
				}
				$document.trigger( 'hiddenAll.' + pluginType );
			};

			// The actual plugin constructor
			function WebuiPopover( element, options ) {
				this.$element = $( element );
				if ( options ) {
					if ( typeof options.delay === 'string' || typeof options.delay === 'number' ) {
						options.delay = {
							show: options.delay,
							hide: options.delay,
						}; // bc break fix
					}
				}
				this.options = $.extend( {}, defaults, options );
				this._defaults = defaults;
				this._name = pluginName;
				this._targetclick = false;
				this.init();
				_srcElements.push( this.$element );
			}

			WebuiPopover.prototype = {
				//init webui popover
				init: function() {
					//init the event handlers
					if ( this.getTrigger() === 'click' ) {
						this.$element.off( 'click touchend' ).on( 'click touchend', $.proxy( this.toggle, this ) );
					} else if ( this.getTrigger() === 'rightclick' ) {
						this.$element.off( 'contextmenu' ).on( 'contextmenu', $.proxy( this.toggle, this ) );
						// this.$eventDelegate.off( 'click' ).on( 'click', function( e ) {
						// 	if ( e.ctrlKey ) {
						// 		return false;
						// 	} // ctrl-click in safari should only be handled by the contextmenu handler;
						// } );
		   } else if ( this.getTrigger() === 'hover' ) {
						this.$element
							.off( 'mouseenter mouseleave click' )
							.on( 'mouseenter', $.proxy( this.mouseenterHandler, this ) )
							.on( 'mouseleave', $.proxy( this.mouseleaveHandler, this ) );
					}
					this._poped = false;
					this._inited = true;
					this._opened = false;
					this._idSeed = _globalIdSeed;
					if ( this.options.backdrop ) {
						backdrop.appendTo( document.body ).hide();
					}
					_globalIdSeed++;
					if ( this.getTrigger() === 'sticky' ) {
						this.show();
					}
				},
				/* api methods and actions */
				destroy: function() {
					var index = -1;

					for ( var i = 0; i < _srcElements.length; i++ ) {
						if ( _srcElements[ i ] === this.$element ) {
							index = i;
							break;
						}
					}

					_srcElements.splice( index, 1 );

					this.hide();
					this.$element.data( 'plugin_' + pluginName, null );
					if ( this.getTrigger() === 'click' ) {
						this.$element.off( 'click' );
					} else if ( this.getTrigger() === 'hover' ) {
						this.$element.off( 'mouseenter mouseleave' );
					}
					if ( this.$target ) {
						this.$target.remove();
					}
				},
				/*
			param: force    boolean value, if value is true then force hide the popover
			param: event    dom event,
		*/
				hide: function( force, event ) {
					if ( ! force && this.getTrigger() === 'sticky' ) {
						return;
					}

					if ( ! this._opened ) {
						return;
					}
					if ( event ) {
						event.preventDefault();
						event.stopPropagation();
					}

					if ( this.xhr && this.options.abortXHR === true ) {
						this.xhr.abort();
						this.xhr = null;
					}

					var e = $.Event( 'hide.' + pluginType );
					this.$element.trigger( e, [ this.$target ] );
					if ( this.$target ) {
						this.$target.removeClass( 'in' ).addClass( this.getHideAnimation() );
						var that = this;
						setTimeout( function() {
							that.$target.hide();
						}, 1 );
					}
					if ( this.options.backdrop ) {
						backdrop.hide();
					}
					this._opened = false;
					this.$element.trigger( 'hidden.' + pluginType, [ this.$target ] );

					if ( this.options.onHide ) {
						this.options.onHide( this.$target );
					}
				},
				resetAutoHide: function() {
					var that = this;
					var autoHide = that.getAutoHide();
					if ( autoHide ) {
						if ( that.autoHideHandler ) {
							clearTimeout( that.autoHideHandler );
						}
						that.autoHideHandler = setTimeout( function() {
							that.hide();
						}, autoHide );
					}
				},
				toggle: function( e ) {
					if ( e ) {
						e.preventDefault();
						e.stopPropagation();
					}
					this[ this.getTarget().hasClass( 'in' ) ? 'hide' : 'show' ]();
				},
				hideAll: function() {
					hideAllPop();
				},
				/*core method ,show popover */
				show: function() {
					var
						$target = this.getTarget().removeClass().addClass( pluginClass ).addClass( 'translator-exclude' ).addClass( this._customTargetClass );
					if ( ! this.options.multi ) {
						this.hideAll();
					}
					if ( this._opened ) {
						return;
					}
					// use cache by default, if not cache setted  , reInit the contents
					if ( ! this.getCache() || ! this._poped || this.content === '' ) {
						this.content = '';
						this.setTitle( this.getTitle() );
						if ( ! this.options.closeable ) {
							$target.find( '.close' ).off( 'click' ).remove();
						}
						if ( ! this.isAsync() ) {
							this.setContent( this.getContent() );
						} else {
							this.setContentASync( this.options.content );
						}
						$target.show();
					}
					this.displayContent();

					if ( this.options.onShow ) {
						this.options.onShow( $target );
					}

					this.bindBodyEvents();
					if ( this.options.backdrop ) {
						backdrop.show();
					}
					this._opened = true;
					this.resetAutoHide();
				},
				displayContent: function() {
					var
					//element postion
						elementPos = this.getElementPosition(),
						//target postion
						$target = this.getTarget().removeClass().addClass( pluginClass ).addClass( this._customTargetClass ),
						//target content
						$targetContent = this.getContentElement(),
						//target Width
						targetWidth = $target[ 0 ].offsetWidth,
						//target Height
						targetHeight = $target[ 0 ].offsetHeight,
						//placement
						placement = 'bottom',
						e = $.Event( 'show.' + pluginType );
					//if (this.hasContent()){
					this.$element.trigger( e, [ $target ] );
					//}
					if ( this.options.width !== 'auto' ) {
						$target.width( this.options.width );
					}
					if ( this.options.height !== 'auto' ) {
						$targetContent.height( this.options.height );
					}

					if ( this.options.style ) {
						this.$target.addClass( pluginClass + '-' + this.options.style );
					}

					//init the popover and insert into the document body
					if ( ! this.options.arrow ) {
						$target.find( '.arrow' ).remove();
					}
					$target.detach().css( {
						top: _offsetOut,
						left: _offsetOut,
						display: 'block',
					} );

					if ( this.getAnimation() ) {
						$target.addClass( this.getAnimation() );
					}
					$target.appendTo( document.body );

					placement = this.getPlacement( elementPos );

					//This line is just for compatible with knockout custom binding
					this.$element.trigger( 'added.' + pluginType );

					this.initTargetEvents();

					if ( ! this.options.padding ) {
						if ( this.options.height !== 'auto' ) {
							$targetContent.css( 'height', $targetContent.outerHeight() );
						}
						this.$target.addClass( 'webui-no-padding' );
					}
					targetWidth = $target[ 0 ].offsetWidth;
					targetHeight = $target[ 0 ].offsetHeight;

					var postionInfo = this.getTargetPositin( elementPos, placement, targetWidth, targetHeight );

					this.$target.css( postionInfo.position ).addClass( placement ).addClass( 'in' );

					var that = this;
					var resizeHandler = function() {
			    elementPos = that.getElementPosition();
			    postionInfo = that.getTargetPositin( elementPos, placement, targetWidth, targetHeight );
			    that.$target.css( postionInfo.position ).addClass( placement );
					};
					var resizeId;
					$( window ).on( 'resize', function() {
			    clearTimeout( resizeId );
			    resizeId = setTimeout( resizeHandler, 100 );
					} );
					if ( this.options.type === 'iframe' ) {
						var $iframe = $target.find( 'iframe' );
						$iframe.width( $target.width() ).height( $iframe.parent().height() );
					}

					if ( ! this.options.arrow ) {
						this.$target.css( {
							margin: 0,
						} );
					}
					if ( this.options.arrow ) {
						var $arrow = this.$target.find( '.arrow' );
						$arrow.removeAttr( 'style' );
						if ( postionInfo.arrowOffset ) {
							//hide the arrow if offset is negative
							if ( postionInfo.arrowOffset.left === -1 || postionInfo.arrowOffset.top === -1 ) {
								$arrow.hide();
							} else {
								$arrow.css( postionInfo.arrowOffset );
							}
						}
					}
					this._poped = true;
					this.$element.trigger( 'shown.' + pluginType, [ this.$target ] );
				},

				isTargetLoaded: function() {
					return this.getTarget().find( 'i.glyphicon-refresh' ).length === 0;
				},

				/*getter setters */
				getTriggerElement: function() {
					return this.$element;
				},
				getTarget: function() {
					if ( ! this.$target ) {
						var id = pluginName + this._idSeed;
						this.$target = $( this.options.template )
							.attr( 'id', id )
							.data( 'trigger-element', this.getTriggerElement() );
						this._customTargetClass = this.$target.attr( 'class' ) !== pluginClass ? this.$target.attr( 'class' ) : null;
						this.getTriggerElement().attr( 'data-target', id );
					}
					return this.$target;
				},
				getTitleElement: function() {
					return this.getTarget().find( '.' + pluginClass + '-title' );
				},
				getContentElement: function() {
					if ( ! this.$contentElement ) {
						this.$contentElement = this.getTarget().find( '.' + pluginClass + '-content' );
					}
					return this.$contentElement;
				},
				getTitle: function() {
					return this.$element.attr( 'data-title' ) || this.options.title || this.$element.attr( 'title' );
				},
				getUrl: function() {
					return this.$element.attr( 'data-url' ) || this.options.url;
				},
				getAutoHide: function() {
					return this.$element.attr( 'data-auto-hide' ) || this.options.autoHide;
				},
				getOffsetTop: function() {
					return toNumber( this.$element.attr( 'data-offset-top' ) ) || this.options.offsetTop;
				},
				getOffsetLeft: function() {
					return toNumber( this.$element.attr( 'data-offset-left' ) ) || this.options.offsetLeft;
				},
				getCache: function() {
					var dataAttr = this.$element.attr( 'data-cache' );
					if ( typeof ( dataAttr ) !== 'undefined' ) {
						switch ( dataAttr.toLowerCase() ) {
							case 'true':
							case 'yes':
							case '1':
								return true;
							case 'false':
							case 'no':
							case '0':
								return false;
						}
					}
					return this.options.cache;
				},
				getTrigger: function() {
					return this.$element.attr( 'data-trigger' ) || this.options.trigger;
				},
				getDelayShow: function() {
					var dataAttr = this.$element.attr( 'data-delay-show' );
					if ( typeof ( dataAttr ) !== 'undefined' ) {
						return dataAttr;
					}
					return this.options.delay.show === 0 ? 0 : this.options.delay.show || 100;
				},
				getHideDelay: function() {
					var dataAttr = this.$element.attr( 'data-delay-hide' );
					if ( typeof ( dataAttr ) !== 'undefined' ) {
						return dataAttr;
					}
					return this.options.delay.hide === 0 ? 0 : this.options.delay.hide || 100;
				},
				getAnimation: function() {
					var dataAttr = this.$element.attr( 'data-animation' );
					return dataAttr || this.options.animation;
				},
				getHideAnimation: function() {
					var ani = this.getAnimation();
					return ani ? ani + '-out' : 'out';
				},
				setTitle: function( title ) {
					var $titleEl = this.getTitleElement();
					if ( title ) {
						$titleEl.html( title );
					} else {
						$titleEl.remove();
					}
				},
				hasContent: function() {
					return this.getContent();
				},
				getContent: function() {
					if ( this.getUrl() ) {
						switch ( this.options.type ) {
							case 'iframe':
								this.content = $( '<iframe frameborder="0"></iframe>' ).attr( 'src', this.getUrl() );
								break;
							case 'html':
								try {
									this.content = $( this.getUrl() );
									if ( ! this.content.is( ':visible' ) ) {
										this.content.show();
									}
								} catch ( error ) {
									throw new Error( 'Unable to get popover content. Invalid selector specified.' );
								}
								break;
						}
					} else if ( ! this.content ) {
						var content = '';
						if ( typeof this.options.content === 'function' ) {
							content = this.options.content.apply( this.$element[ 0 ], [ this ] );
						} else {
							content = this.options.content;
						}
						this.content = this.$element.attr( 'data-content' ) || content;
						if ( ! this.content ) {
							var $next = this.$element.next();

							if ( $next && $next.hasClass( pluginClass + '-content' ) ) {
								this.content = $next;
							}
						}
					}
					return this.content;
				},
				setContent: function( content ) {
					var $target = this.getTarget();
					var $ct = this.getContentElement();
					if ( typeof content === 'string' ) {
						$ct.html( content );
					} else if ( content instanceof jQuery ) {
						content.removeClass( pluginClass + '-content' );
						$ct.html( '' );
						content.appendTo( $ct );
					}
					this.$target = $target;
				},
				isAsync: function() {
					return this.options.type === 'async';
				},
				setContentASync: function( content ) {
					var that = this;
					if ( this.xhr ) {
						return;
					}
					this.xhr = $.ajax( {
						url: this.getUrl(),
						type: 'GET',
						cache: this.getCache(),
						beforeSend: function( xhr ) {
							if ( that.options.async.before ) {
								that.options.async.before( that, xhr );
							}
						},
						success: function( data ) {
							that.bindBodyEvents();
							if ( content && typeof content === 'function' ) {
								that.content = content.apply( that.$element[ 0 ], [ data ] );
							} else {
								that.content = data;
							}
							that.setContent( that.content );
							var $targetContent = that.getContentElement();
							$targetContent.removeAttr( 'style' );
							that.displayContent();
							if ( that.options.async.success ) {
								that.options.async.success( that, data );
							}
						},
						complete: function() {
							that.xhr = null;
						},
					} );
				},

				bindBodyEvents: function() {
					if ( this.options.dismissible && ( this.getTrigger() === 'click' || this.getTrigger() === 'rightclick' ) && ! _isBodyEventHandled ) {
						$document.off( 'keyup.webui-popover' ).on( 'keyup.webui-popover', $.proxy( this.escapeHandler, this ) );
						$document.off( 'click.webui-popover touchend.webui-popover' ).on( 'click.webui-popover touchend.webui-popover', $.proxy( this.bodyClickHandler, this ) );
					}
				},

				/* event handlers */
				mouseenterHandler: function() {
					var self = this;
					if ( self._timeout ) {
						clearTimeout( self._timeout );
					}
					self._enterTimeout = setTimeout( function() {
						if ( ! self.getTarget().is( ':visible' ) ) {
							self.show();
						}
					}, this.getDelayShow() );
				},
				mouseleaveHandler: function() {
					var self = this;
					clearTimeout( self._enterTimeout );
					//key point, set the _timeout  then use clearTimeout when mouse leave
					self._timeout = setTimeout( function() {
						self.hide();
					}, this.getHideDelay() );
				},
				escapeHandler: function( e ) {
					if ( e.keyCode === 27 ) {
						this.hideAll();
					}
				},

				bodyClickHandler: function( e ) {
					_isBodyEventHandled = true;
					var canHide = true;
					for ( var i = 0; i < _srcElements.length; i++ ) {
						var pop = getPopFromElement( _srcElements[ i ] );
						if ( pop._opened ) {
							var popX1 = pop.getTarget().offset().left;
							var popY1 = pop.getTarget().offset().top;
							var popX2 = pop.getTarget().offset().left + pop.getTarget().width();
							var popY2 = pop.getTarget().offset().top + pop.getTarget().height();
							var inPop = e.pageX >= popX1 && e.pageX <= popX2 && e.pageY >= popY1 && e.pageY <= popY2;
							if ( inPop ) {
								canHide = false;
								break;
							}
						}
					}
					if ( canHide ) {
						hideAllPop();
					}
				},

				/*
		targetClickHandler: function() {
			this._targetclick = true;
		},
		*/

				//reset and init the target events;
				initTargetEvents: function() {
					if ( this.getTrigger() === 'hover' ) {
						this.$target
							.off( 'mouseenter mouseleave' )
							.on( 'mouseenter', $.proxy( this.mouseenterHandler, this ) )
							.on( 'mouseleave', $.proxy( this.mouseleaveHandler, this ) );
					}
					this.$target.find( '.close' ).off( 'click' ).on( 'click', $.proxy( this.hide, this, true ) );
					//this.$target.off('click.webui-popover').on('click.webui-popover', $.proxy(this.targetClickHandler, this));
				},
				/* utils methods */
				//caculate placement of the popover
				getPlacement: function( pos ) {
					var
						placement,
						de = document.documentElement,
						db = document.body,
						clientWidth = de.clientWidth,
						clientHeight = de.clientHeight,
						windowHeight = $( window ).height(),
						windowScrollHeight = $( window ).scrollTop(),
						scrollTop = Math.max( db.scrollTop, de.scrollTop ),
						scrollLeft = Math.max( db.scrollLeft, de.scrollLeft ),
						pageX = Math.max( 0, pos.left - scrollLeft ),
						pageY = Math.max( 0, pos.top - scrollTop );
					//arrowSize = 20;

					//if placement equals auto，caculate the placement by element information;
					if ( typeof ( this.options.placement ) === 'function' ) {
						placement = this.options.placement.call( this, this.getTarget()[ 0 ], this.$element[ 0 ] );
					} else {
						placement = this.$element.data( 'placement' ) || this.options.placement;
					}

					var isH = placement === 'horizontal';
					var isV = placement === 'vertical';
					var detect = placement === 'auto' || isH || isV;

					if ( detect ) {
						if ( pageX < clientWidth / 3 ) {
							if ( pageY < clientHeight / 3 ) {
								placement = isH ? 'right-bottom' : 'bottom-right';
							} else if ( pageY < clientHeight * 2 / 3 ) {
								if ( isV ) {
									placement = pageY <= clientHeight / 2 ? 'bottom-right' : 'top-right';
								} else {
									placement = 'right';
								}
							} else {
								placement = isH ? 'right-top' : 'top-right';
							}
							//placement= pageY>targetHeight+arrowSize?'top-right':'bottom-right';
						} else if ( pageX < clientWidth * 2 / 3 ) {
							if ( pageY < clientHeight / 3 ) {
								if ( isH ) {
									placement = pageX <= clientWidth / 2 ? 'right-bottom' : 'left-bottom';
								} else {
									placement = 'bottom';
								}
							} else if ( pageY < clientHeight * 2 / 3 ) {
								if ( isH ) {
									placement = pageX <= clientWidth / 2 ? 'right' : 'left';
								} else {
									placement = pageY <= clientHeight / 2 ? 'bottom' : 'top';
								}
							} else if ( isH ) {
								placement = pageX <= clientWidth / 2 ? 'right-top' : 'left-top';
							} else {
								placement = 'top';
							}
						} else {
							//placement = pageY>targetHeight+arrowSize?'top-left':'bottom-left';
							if ( pageY < clientHeight / 3 ) {
								placement = isH ? 'left-bottom' : 'bottom-left';
							} else if ( pageY < clientHeight * 2 / 3 ) {
								if ( isV ) {
									placement = pageY <= clientHeight / 2 ? 'bottom-left' : 'top-left';
								} else {
									placement = 'left';
								}
							} else {
								placement = isH ? 'left-top' : 'top-left';
							}
						}
						if ( isV ) {
							if ( ( pos.top - windowScrollHeight ) > windowHeight / 3 * 2 ) {
								placement = placement.replace( 'bottom', 'top' );
							} else {
								placement = placement.replace( 'top', 'bottom' );
							}
						}
					} else if ( placement === 'auto-top' ) {
						if ( pageX < clientWidth / 3 ) {
							placement = 'top-right';
						} else if ( pageX < clientWidth * 2 / 3 ) {
							placement = 'top';
						} else {
							placement = 'top-left';
						}
					} else if ( placement === 'auto-bottom' ) {
						if ( pageX < clientWidth / 3 ) {
							placement = 'bottom-right';
						} else if ( pageX < clientWidth * 2 / 3 ) {
							placement = 'bottom';
						} else {
							placement = 'bottom-left';
						}
					} else if ( placement === 'auto-left' ) {
						if ( pageY < clientHeight / 3 ) {
							placement = 'left-top';
						} else if ( pageY < clientHeight * 2 / 3 ) {
							placement = 'left';
						} else {
							placement = 'left-bottom';
						}
					} else if ( placement === 'auto-right' ) {
						if ( pageY < clientHeight / 3 ) {
							placement = 'right-top';
						} else if ( pageY < clientHeight * 2 / 3 ) {
							placement = 'right';
						} else {
							placement = 'right-bottom';
						}
					}
					return placement;
				},
				getElementPosition: function() {
					return $.extend( {}, this.$element.offset(), {
						width: this.$element[ 0 ].offsetWidth,
						height: this.$element[ 0 ].offsetHeight,
					} );
				},

				getTargetPositin: function( elementPos, placement, targetWidth, targetHeight ) {
					var pos = elementPos,
						de = document.documentElement,
						db = document.body,
						clientWidth = de.clientWidth,
						clientHeight = de.clientHeight,
						elementW = this.$element.outerWidth(),
						elementH = this.$element.outerHeight(),
						scrollTop = Math.max( db.scrollTop, de.scrollTop ),
						scrollLeft = Math.max( db.scrollLeft, de.scrollLeft ),
						position = {},
						arrowOffset = null,
						arrowSize = this.options.arrow ? 20 : 0,
						padding = 10,
						fixedW = elementW < arrowSize + padding ? arrowSize : 0,
						fixedH = elementH < arrowSize + padding ? arrowSize : 0,
						refix = 0,
						pageH = clientHeight + scrollTop,
						pageW = clientWidth + scrollLeft;

					var validLeft = pos.left + pos.width / 2 - fixedW > 0;
					var validRight = pos.left + pos.width / 2 + fixedW < pageW;
					var validTop = pos.top + pos.height / 2 - fixedH > 0;
					var validBottom = pos.top + pos.height / 2 + fixedH < pageH;

					switch ( placement ) {
						case 'bottom':
							position = {
								top: pos.top + pos.height,
								left: pos.left + pos.width / 2 - targetWidth / 2,
							};
							break;
						case 'top':
							position = {
								top: pos.top - targetHeight,
								left: pos.left + pos.width / 2 - targetWidth / 2,
							};
							break;
						case 'left':
							position = {
								top: pos.top + pos.height / 2 - targetHeight / 2,
								left: pos.left - targetWidth,
							};
							break;
						case 'right':
							position = {
								top: pos.top + pos.height / 2 - targetHeight / 2,
								left: pos.left + pos.width,
							};
							break;
						case 'top-right':
							position = {
								top: pos.top - targetHeight,
								left: validLeft ? pos.left - fixedW : padding,
							};
							arrowOffset = {
								left: validLeft ? Math.min( elementW, targetWidth ) / 2 + fixedW : _offsetOut,
							};
							break;
						case 'top-left':
							refix = validRight ? fixedW : -padding;
							position = {
								top: pos.top - targetHeight,
								left: pos.left - targetWidth + pos.width + refix,
							};
							arrowOffset = {
								left: validRight ? targetWidth - Math.min( elementW, targetWidth ) / 2 - fixedW : _offsetOut,
							};
							break;
						case 'bottom-right':
							position = {
								top: pos.top + pos.height,
								left: validLeft ? pos.left - fixedW : padding,
							};
							arrowOffset = {
								left: validLeft ? Math.min( elementW, targetWidth ) / 2 + fixedW : _offsetOut,
							};
							break;
						case 'bottom-left':
							refix = validRight ? fixedW : -padding;
							position = {
								top: pos.top + pos.height,
								left: pos.left - targetWidth + pos.width + refix,
							};
							arrowOffset = {
								left: validRight ? targetWidth - Math.min( elementW, targetWidth ) / 2 - fixedW : _offsetOut,
							};
							break;
						case 'right-top':
							refix = validBottom ? fixedH : -padding;
							position = {
								top: pos.top - targetHeight + pos.height + refix,
								left: pos.left + pos.width,
							};
							arrowOffset = {
								top: validBottom ? targetHeight - Math.min( elementH, targetHeight ) / 2 - fixedH : _offsetOut,
							};
							break;
						case 'right-bottom':
							position = {
								top: validTop ? pos.top - fixedH : padding,
								left: pos.left + pos.width,
							};
							arrowOffset = {
								top: validTop ? Math.min( elementH, targetHeight ) / 2 + fixedH : _offsetOut,
							};
							break;
						case 'left-top':
							refix = validBottom ? fixedH : -padding;
							position = {
								top: pos.top - targetHeight + pos.height + refix,
								left: pos.left - targetWidth,
							};
							arrowOffset = {
								top: validBottom ? targetHeight - Math.min( elementH, targetHeight ) / 2 - fixedH : _offsetOut,
							};
							break;
						case 'left-bottom':
							position = {
								top: validTop ? pos.top - fixedH : padding,
								left: pos.left - targetWidth,
							};
							arrowOffset = {
								top: validTop ? Math.min( elementH, targetHeight ) / 2 + fixedH : _offsetOut,
							};
							break;
					}
					position.top += this.getOffsetTop();
					position.left += this.getOffsetLeft();

					return {
						position: position,
						arrowOffset: arrowOffset,
					};
				},
			};
			$.fn[ pluginName ] = function( options, noInit ) {
				var results = [];
				var $result = this.each( function() {
					var webuiPopover = $.data( this, 'plugin_' + pluginName );
					if ( ! webuiPopover ) {
						if ( ! options ) {
							webuiPopover = new WebuiPopover( this, null );
						} else if ( typeof options === 'string' ) {
							if ( options !== 'destroy' ) {
								if ( ! noInit ) {
									webuiPopover = new WebuiPopover( this, null );
									results.push( webuiPopover[ options ]() );
								}
							}
						} else if ( typeof options === 'object' ) {
							webuiPopover = new WebuiPopover( this, options );
						}
						$.data( this, 'plugin_' + pluginName, webuiPopover );
					} else if ( options === 'destroy' ) {
						webuiPopover.destroy();
					} else if ( typeof options === 'string' ) {
						results.push( webuiPopover[ options ]() );
					}
				} );

				return ( results.length ) ? results : $result;
			};
		}( jQuery, window, document ) );
	}, {} ], 5: [ function( require, module, exports ) {
		/**
		 * Locale module
		 */

		/**
		 * External dependencies
		 */
		var Jed = require( 'jed' );

		function Locale( localeCode, languageName, pluralForms ) {
			var getPluralIndex = Jed.PF.compile( pluralForms ),
				npluralsRe = /nplurals\=(\d+);/,
				npluralsMatches = pluralForms.match( npluralsRe ),
				numberOfPlurals = 2;

			// Find the nplurals number
			if ( npluralsMatches.length > 1 ) {
				numberOfPlurals = npluralsMatches[ 1 ];
			}

			return {
				getLocaleCode: function() {
					return localeCode;
				},
				getLanguageName: function() {
					return languageName;
				},
				getInfo: function() {
					return localeCode;
				},
				getPluralCount: function() {
					return numberOfPlurals;
				},
				// port from GlotPress locales.php:numbers_for_index
				getNumbersForIndex: function( index ) {
					var number,
						howMany = 3,
						testUpTo = 1000,
						numbers = [];
					for ( number = 0; number < testUpTo; ++number ) {
						if ( getPluralIndex( number ) === index ) {
							numbers.push( number );
							if ( numbers.length >= howMany ) {
								break;
							}
						}
					}
					return numbers;
				},
			};
		}

		module.exports = Locale;
	}, { jed: 11 } ], 6: [ function( require, module, exports ) {
		/**
		 * Original module
		 */
		var Translation = require( './translation' );

		function Original( original ) {
			var singular,
				plural = null,
				comment = null,
				originalId = null,
				singularGlossaryMarkup = null,
				pluralGlossaryMarkup = null;

			if ( 'string' === typeof original ) {
				singular = original;
			} else if (
				'object' === typeof original &&
			'string' === typeof original.singular
			) {
				singular = original.singular;
				plural = original.plural;
			} else {
				singular = original[ 0 ];
				plural = original[ 1 ];
			}

			if ( 'undefined' === typeof plural || '' === plural ) {
				plural = null;
			}

			if ( 'undefined' !== typeof original.originalId ) {
				originalId = original.originalId;
			}

			if ( 'undefined' !== typeof original.comment ) {
				comment = original.comment;
			}

			function objectify( context, domain ) {
				var result = {
					singular: singular,
				};

				if ( plural ) {
					result.plural = plural;
				}

				if ( context ) {
					result.context = context;
				}

				if ( domain ) {
					result.domain = domain;
				}

				return result;
			}

			return {
				type: 'Original',
				getSingular: function() {
					return singular;
				},
				getPlural: function() {
					return plural;
				},
				generateJsonHash: function( context ) {
					if ( 'string' === typeof context && '' !== context ) {
						return context + '\u0004' + singular;
					}

					return singular;
				},
				getEmptyTranslation: function( locale ) {
					var i,
						forms = [ '' ];

					if ( plural !== null ) {
						for ( i = 1; i < locale.getPluralCount(); i++ ) {
							forms.push( '' );
						}
					}

					return new Translation( locale, forms );
				},
				objectify: objectify,
				fetchIdAndTranslations: function( glotPress, context, domain ) {
					return glotPress.queryByOriginal( objectify( context, domain ) ).done( function( data ) {
						originalId = data.original_id;
						if ( typeof data.original_comment === 'string' ) {
							comment = data.original_comment.replace( /^translators: /, '' );
						}
						singularGlossaryMarkup = data.singular_glossary_markup;
						pluralGlossaryMarkup = data.plural_glossary_markup;
					} );
				},
				getId: function() {
					return originalId;
				},
				getComment: function() {
					return comment;
				},
				getSingularGlossaryMarkup: function() {
					return singularGlossaryMarkup;
				},
				getPluralGlossaryMarkup: function() {
					return pluralGlossaryMarkup;
				},
				setSingularGlossaryMarkup: function( markup ) {
					singularGlossaryMarkup = markup;
					return singularGlossaryMarkup;
				},
				setPluralGlossaryMarkup: function( markup ) {
					pluralGlossaryMarkup = markup;
					return pluralGlossaryMarkup;
				},
				getPlaceholders: function() {
					var regexPattern = /%(\d\$)?([sd])/g;
					var matchedPlaceholders = Array.from( singular.matchAll( regexPattern ) );
					var placeholders = matchedPlaceholders.map( function( match ) {
						return match[ 0 ];
					} );

					return placeholders;
				},
			};
		}

		module.exports = Original;
	}, { './translation': 9 } ], 7: [ function( require, module, exports ) {
		/**
		 * Popover module
		 */

		var locale;
		function Popover( translationPair, _locale, glotPress ) {
			var form, nodeClass, getPopoverHtml, getPopoverTitle;
			locale = _locale;

			if ( translationPair.isFullyTranslated() ) {
				form = getOverview( translationPair );
			} else {
				form = getInputForm( translationPair );
			}

			nodeClass = 'translator-original-' + translationPair.getOriginal().getId();

			getPopoverHtml = function() {
				form.find( 'form' ).attr( 'data-nodes', nodeClass );
				form.find( 'form' ).data( 'translationPair', translationPair );

				return form;
			};

			getPopoverTitle = function() {
				return 'Translate to ' +
			locale.getLanguageName() +
			// '<a title="Help & Instructions" target="_blank" href="">' +
			// 	'<span class="dashicons dashicons-editor-help"></span>' +
			// '</a>' +
			'<a title="View in GlotPress" href="' + glotPress.getPermalink( translationPair ) + '" target="_blank" class="gpPermalink">' +
				'<span class="dashicons dashicons-external"></span>' +
			'</a>';
			};

			return {
				attachTo: function( enclosingNode ) {
					if ( enclosingNode.hasClass( nodeClass ) ) {
						enclosingNode.webuiPopover( 'destroy' );
					}
					enclosingNode.addClass( nodeClass ).webuiPopover( {
						title: getPopoverTitle(),
						width: 400,
						delay: 0,
						placement: 'vertical',
						content: function() {
							return jQuery( '<div>' ).append( getPopoverHtml() );
						},
						trigger: 'rightclick',
						translationPair: translationPair,
					} ).on( 'shown.webui.popover', function( popover, el ) {
						popoverOnload( el, translationPair, glotPress );
					} );
				},
				getTranslationHtml: function() {
					form = getInputForm( translationPair );
					return getPopoverHtml();
				},
			};
		}

		function popoverOnload( el, translationPair, glotPress ) {
			var getSuggestionsResponse, getSugesstionsError, requery, i, li,
				popover = jQuery( el ),
				textareas = jQuery( el ).find( 'textarea' ),
				additional = jQuery( el ).find( 'div.additional' ),
				getSuggestions = function() {};

			glotPress.glossaryMarkup( translationPair ).then( function() {
				popover.find( 'div.original' ).html( getOriginalHtml( translationPair ) );
				getSuggestions();
			} );

			el = textareas.get( 0 );
			if ( el ) {
				el.focus();
				if ( textareas.eq( 0 ).val() !== '' ) {
					// Only load suggestions when there is no translation yet.
					return;
				}

				if ( ! glotPress.shouldLoadSuggestions() ) {
					return;
				}

				requery = function() {
					glotPress.getSuggestedTranslation( translationPair, {
						prompt: additional.find( 'textarea.prompt' ).val(),
					} ).done( getSuggestionsResponse ).error( getSugesstionsError );
					additional.html( 'Loading suggested translation <span class="spinner is-active" style="float: none; margin: 0 0 0 5px;"></span>' );
					return false;
				};

				getSugesstionsError = function( response ) {
					var error = response.responseJSON;
					additional.html( 'Error loading suggestions: ' + error.message + '. <button class="requery button button-small">Retry</button>' );
					additional.find( 'button.requery' ).css( 'float', 'left' ).on( 'click', requery );
				};

				getSuggestionsResponse = function( response ) {
					var suggestions = [];
					if ( response.choices && response.choices[ 0 ] && response.choices[ 0 ].message && response.choices[ 0 ].message.content ) {
						suggestions = JSON.parse( response.choices[ 0 ].message.content );
						additional.html( '<details><summary>Modify Query</summary><textarea class="prompt" placeholder="Add a custom prompt..."></textarea><blockquote class="unmodifyable"></blockquote> <button class="button requery">Requery</button></details><ul class="suggestions"></ul>' );
						for ( i = 0; i < suggestions.length; i++ ) {
							li = jQuery( '<li><button class="button button-small copy">Copy</button><span></span>' );
							additional.find( 'ul.suggestions' ).append( li );
							li.find( 'span' ).text( suggestions[ i ] );
							li.find( 'button' ).on( 'click', ( function( suggestion ) {
								return function() {
									var j;
									for ( j = 0; j < textareas.length; j++ ) {
										textareas.eq( j ).focus();
										textareas.eq( j ).select();
										// Replace all text with new text
										document.execCommand( 'insertText', false, suggestion[ j ] );
										textareas.eq( j ).trigger( 'keyup' );
									}
									return false;
								};
							}( Array.isArray( suggestions[ i ] ) ? suggestions[ i ] : [ suggestions[ i ] ] ) ) );
						}
						additional.find( 'blockquote.unmodifyable' ).text( 'Given this, translate the following text to ' + locale.getLanguageName() + ':' );
						additional.find( 'textarea.prompt' ).val( glotPress.getLastPrompt() );
						additional.find( 'button.requery' ).on( 'click', requery );
					} else {
						for ( i = 0; i < textareas.length; i++ ) {
							textareas.eq( i ).prop( 'placeholder', 'Please enter your translation' );
						}
						additional.text( '' );
					}
				};
				getSuggestions = function() {
					additional.html( 'Loading suggested translation <span class="spinner is-active" style="float: none; margin: 0 0 0 5px;"></span>' );
					glotPress.getSuggestedTranslation( translationPair ).done( getSuggestionsResponse ).error( getSugesstionsError );
				};
			}
		}

		function getOriginalHtml( translationPair ) {
			var originalHtml,
				plural = translationPair.getOriginal().getPlural();
			if ( plural ) {
				originalHtml = 'Singular: <strong class="singular"></strong>' +
			'<br/>Plural:  <strong class="plural"></strong>';
			} else {
				originalHtml = '<strong class="singular"></strong>';
			}

			originalHtml = jQuery( '<div>' + originalHtml );
			if ( translationPair.getOriginal().getSingularGlossaryMarkup() ) {
				originalHtml.find( 'strong.singular' ).html( translationPair.getOriginal().getSingularGlossaryMarkup() );
			} else {
				originalHtml.find( 'strong.singular' ).text( translationPair.getOriginal().getSingular() );
			}

			if ( plural ) {
				if ( translationPair.getOriginal().getPluralGlossaryMarkup() ) {
					originalHtml.find( 'strong.plural' ).html( translationPair.getOriginal().getPluralGlossaryMarkup() );
				} else {
					originalHtml.find( 'strong.plural' ).text( translationPair.getOriginal().getPlural() );
				}
			}
			return originalHtml;
		}

		function getInputForm( translationPair ) {
			// TODO: add input checking and bail for empty or unexpected values

			var form = getHtmlTemplate( 'new-translation' ).clone(),
				original = form.find( 'div.original' ),
				pair = form.find( 'div.pair' ),
				pairs = form.find( 'div.pairs' ),
				item, i;

			original.html( getOriginalHtml( translationPair ) );
			exposeOtherOriginals( form, translationPair );

			if ( translationPair.getContext() ) {
				form.find( 'p.context' ).text( translationPair.getContext() ).css( 'display', 'block' );
			}

			if ( translationPair.getOriginal().getComment() ) {
				form.find( 'p.comment' ).text( translationPair.getOriginal().getComment() ).css( 'display', 'block' );
			}

			if ( translationPair.getOriginal().getPlaceholders() ) {
				form.find( 'div.placeholders' ).css( 'display', 'block' );
			}

			item = translationPair.getTranslation().getTextItems();
			for ( i = 0; i < item.length; i++ ) {
				if ( i > 0 ) {
					pair = pair.eq( 0 ).clone();
				}

				pair.find( 'p' ).text( item[ i ].getCaption() );
				pair.find( 'textarea' ).text( item[ i ].getText() ).attr( 'placeholder', 'Please enter a translation in ' + locale.getLanguageName() );

				if ( i > 0 ) {
					pairs.append( pair );
				}
			}

			return form;
		}

		function exposeOtherOriginals( form, translationPair ) {
			var i,
				search = {};
			if ( translationPair.getOtherOriginals().length ) {
				form.find( 'p.other-originals' ).css( 'display', 'block' );
				search[ translationPair.getOriginal().getSingular() ] = true;
				for ( i = 0; i < translationPair.getOtherOriginals().length; i++ ) {
					search[ translationPair.getOtherOriginals()[ i ] ] = true;
				}
				for ( i = 0; i < translationPair.getTranslation().getTextItems().length; i++ ) {
					search[ translationPair.getTranslation().getTextItems()[ i ].getText() ] = true;
				}
				form.on( 'click', 'p.other-originals a', function() {
					jQuery( '#gp-show-translation-list' ).trigger( 'search', Object.keys( search ).join( ' || ' ) );
					return false;
				} );
			}
		}

		function getOverview( translationPair ) {
			// TODO: add input checking and bail for empty or unexpected values

			var form = getHtmlTemplate( 'existing-translation' ).clone(),
				original = form.find( 'div.original' ),
				pair = form.find( 'div.pair' ),
				pairs = form.find( 'div.pairs' ),
				item, description, i;

			original.html( getOriginalHtml( translationPair ) );
			exposeOtherOriginals( form, translationPair );

			if ( translationPair.getContext() ) {
				form.find( 'p.context' ).text( translationPair.getContext() ).css( 'display', 'block' );
			}

			if ( translationPair.getOriginal().getComment() ) {
				form.find( 'p.comment' ).text( translationPair.getOriginal().getComment() ).css( 'display', 'block' );
			}

			item = translationPair.getTranslation().getTextItems();
			for ( i = 0; i < item.length; i++ ) {
				if ( i > 0 ) {
					pair = pair.eq( 0 ).clone();
				}

				description = item[ i ].getInfoText();
				if ( description !== '' ) {
					pair.find( 'span.type' ).text( description + ': ' );
				}
				pair.find( 'span.translation' ).text( item[ i ].getText() );
				if ( i > 0 ) {
					pairs.append( pair );
				}
			}

			return form;
		}

		function getHtmlTemplate( popoverType ) {
			switch ( popoverType ) {
				case 'existing-translation':
					return jQuery(
						'<div><form class="ct-existing-translation">' +
			'<div class="original"></div>' +
			'<p class="context"></p>' +
			'<p class="comment"></p>' +
			'<p class="other-originals">Multiple originals match, <a href="">show them</a></p>' +
			'<hr />' +
			'<p class="info"></p>' +
			'<div class="pairs">' +
			'<div class="pair">' +
			'<p dir="auto">' +
			'<span class="type"></span><span class="translation"></span>' +
			'</p>' +
			'</div>' +
			'</div>' +
			'<button class="button button-primary">New Translation</button>' +
			'</form></div>'
					);

				case 'new-translation':
					return jQuery(
						'<div>' +
			'<form class="copy-translation">' +
			'<button class="local-copy-btn" aria-label="Copy original text">' +
			'<span class="screen-reader-text">Copy</span><span aria-hidden="true" class="dashicons dashicons-admin-page"></span>' +
			'</button>' +
			'</form>' +
			'<form class="ct-new-translation">' +
			'<div class="original"></div>' +
			'<p class="warnings"></p>' +
			'<p class="context"></p>' +
			'<p class="comment"></p>' +
			'<p class="other-originals">Multiple originals match, <a href="">show them</a></p>' +
			'<p class="info"></p>' +
			'<div class="pairs">' +
			'<div class="pair">' +
			'<p></p>' +
			'<input type="hidden" class="original" name="original[]" />' +
			'<textarea dir="auto" class="translation" name="translation[]"></textarea>' +
			'<div class="placeholders"></div>' +
			'</div>' +
			'</div>' +
			'<button disabled class="button button-primary save">Save Translation</button>' +
			'</form>' +
			'<div class="additional"></div></div>'
					);
			}
		}

		module.exports = Popover;
	}, {} ], 8: [ function( require, module, exports ) {
		/**
		 * TranslationPair module
		 */

		/**
		 * Internal dependencies
		 */

		var Original = require( './original' ),
			Translation = require( './translation' ),
			Popover = require( './popover' );

		/**
		 * Local variables
		 */
		var translationData;

		function TranslationPair( locale, original, context, domain, translation, foundRegex, otherOriginals ) {
			var translations = [],
				regex, originalRegex, selectedTranslation, glotPressProject,
				screenText = false;

			if ( 'object' !== typeof original || original.type !== 'Original' ) {
				original = new Original( original );
			}

			if ( 'object' === typeof translation ) {
				if ( translation.type !== 'Translation' ) {
					translation = new Translation( locale, translation );
				}

				translations.push( translation );
			} else {
				translation = original.getEmptyTranslation( locale );
			}

			selectedTranslation = translation;

			function addTranslation( _translation ) {
				if ( 'object' !== typeof _translation || _translation.type !== 'Translation' ) {
					_translation = new Translation( locale, _translation.slice() );
				}

				if ( selectedTranslation.getTextItems().length !== _translation.getTextItems().length ) {
					// translations have to match the existing number of translation items ( singular = 1, plural = dependent on language )
					return false;
				}

				translations.push( _translation );
				selectedTranslation = _translation;
			}

			function loadTranslations( newTranslations ) {
				var i, j, t, _translation;

				translations = [];

				for ( i = 0; i < newTranslations.length; i++ ) {
					_translation = [];
					for ( j = 0; ( t = newTranslations[ i ][ 'translation_' + j ] ); j++ ) {
						_translation.push( t );
					}
					_translation = new Translation( locale, _translation.slice(), newTranslations[ i ] );
					addTranslation( _translation );
				}
			}

			function sortTranslationsByDate() {
				if ( translations.length <= 1 ) {
					return;
				}

				translations.sort( function( a, b ) {
					return b.getComparableDate() - a.getComparableDate();
				} );
			}

			function setSelectedTranslation( currentUserId ) {
				var i;

				// Reset the regex matcher.
				regex = null;

				sortTranslationsByDate();
				for ( i = 0; i < translations.length; i++ ) {
					if ( translations[ i ].getUserId() === currentUserId && translations[ i ].getStatus() ) {
						selectedTranslation = translations[ i ];
						return;
					}

					if ( translations[ i ].isCurrent() ) {
						selectedTranslation = translations[ i ];
					}
				}
			}

			function setGlotPressProject( project ) {
				return ( glotPressProject = project );
			}

			return {
				type: 'TranslationPair',
				createPopover: function( enclosingNode, glotPress ) {
					var popover = new Popover( this, locale, glotPress );
					popover.attachTo( enclosingNode );
				},
				isFullyTranslated: function() {
					return selectedTranslation.isFullyTranslated();
				},
				isTranslationWaiting: function() {
					return selectedTranslation.isWaiting();
				},
				getOriginal: function() {
					return original;
				},
				getOtherOriginals: function() {
					return typeof otherOriginals === 'undefined' ? [] : otherOriginals;
				},
				getContext: function() {
					return context;
				},
				getLocale: function() {
					return locale;
				},
				getDomain: function() {
					return domain;
				},
				getScreenText: function() {
					return screenText;
				},
				getReplacementText: function( oldText ) {
					var replacementTranslation = this.getTranslation().getTextItems()[ 0 ].getText(),
						c = 0,
						matches = [],
						simpleOriginalRegex = new RegExp( '^\\s*' + this.getOriginalRegexString() + '\\s*$' );

					if ( simpleOriginalRegex.test( oldText ) ) {
						matches = oldText.match( simpleOriginalRegex );
					} else if ( foundRegex && foundRegex.test( oldText ) ) {
						matches = oldText.match( foundRegex );
					}

					return replacementTranslation.replace( /%(?:(\d)\$)?[sd]/g, function() {
						++c;
						return matches[ typeof arguments[ 1 ] === 'undefined' ? c : Number( arguments[ 1 ] ) ];
					} );
				},
				getOriginalRegex: function() {
					var regexString;
					if ( typeof originalRegex !== 'undefined' && originalRegex ) {
						return originalRegex;
					}
					regexString = this.getOriginalRegexString();

					if ( foundRegex ) {
						regexString += '|' + foundRegex.source.substr( 4, foundRegex.source.length - 8 );
					}
					originalRegex = new RegExp( '^\\s*' + regexString + '\\s*$' );
					return originalRegex;
				},
				getOriginalRegexString: function() {
					var regexString;
					regexString = getRegexString( original.getSingular() );
					if ( original.getPlural() ) {
						regexString += '|' + getRegexString( original.getSingular() );
					}
					return regexString;
				},
				getRegex: function() {
					if ( typeof regex !== 'undefined' && regex ) {
						return regex;
					}
					regex = selectedTranslation.getTextItems().map( function( item ) {
						return getRegexString( item.getText() );
					} ).join( '|' );
					regex = new RegExp( '^\\s*' + regex + '\\s*$' );
					return regex;
				},
				setScreenText: function( _screenText ) {
					screenText = _screenText;
				},
				getTranslation: function() {
					return selectedTranslation;
				},
				setGlotPressProject: function( project ) {
					return ( glotPressProject = project );
				},
				getGlotPressProject: function() {
					return glotPressProject;
				},
				updateAllTranslations: function( newTranslations, currentUserId ) {
					loadTranslations( newTranslations );

					if ( 'undefined' !== typeof currentUserId ) {
						setSelectedTranslation( currentUserId );
					}
				},
				serialize: function() {
					// the parameters as array
					return {
						singular: original.getSingular(),
						plural: original.getPlural(),
						context: context,
						domain: domain,
						translations: selectedTranslation.serialize(),
						key: original.generateJsonHash( context ),
					};
				},
				fetchOriginalAndTranslations: function( glotPress, currentUserId ) {
					var promise;
					promise = original.fetchIdAndTranslations( glotPress, context, domain )
						.done( function( data ) {
							if ( 'undefined' === typeof data.translations ) {
								return;
							}

							loadTranslations( data.translations );
							setSelectedTranslation( currentUserId );

							if ( typeof data.project !== 'undefined' ) {
								setGlotPressProject( data.project );
							}
						} );
					return promise;
				},
			};
		}

		function getRegexString( text ) {
			var regexString = text;
			regexString = regexString.replace( /[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&' );
			regexString = regexString.replace( /%([0-9]\\*\$)?s/g, '(.{0,500}?)' );
			regexString = regexString.replace( /%([0-9]\\*\$)?d/g, '([0-9]{0,15}?)' );
			regexString = regexString.replace( /%%/g, '%' );
			return regexString;
		}

		function extractFromDataElement( dataElement ) {
			var translationPair, translation,
				original = {
					singular: dataElement.data( 'singular' ),
				};

			if ( dataElement.data( 'plural' ) ) {
				original.plural = dataElement.data( 'plural' );
			}

			if ( dataElement.data( 'context' ) ) {
				original.context = dataElement.data( 'context' );
			}

			if ( dataElement.data( 'domain' ) ) {
				original.domain = dataElement.data( 'domain' );
			}

			if ( dataElement.data( 'original-id' ) ) {
				original.originalId = dataElement.data( 'original-id' );
			}

			if ( dataElement.data( 'translation' ) ) {
				translation = dataElement.data( 'translation' );
			}

			translationPair = new TranslationPair( translationData.locale, original, original.context, original.domain, translation );

			translationPair.setScreenText( dataElement.text() );
			if ( dataElement.data( 'project' ) ) {
				translationPair.setGlotPressProject( dataElement.data( 'project' ) );
			}
			return translationPair;
		}

		function trim( text ) {
			if ( typeof text === 'undefined' ) {
				return '';
			}
			return text.replace( /(?:(?:^|\n)\s+|\s+(?:$|\n))/g, '' );
		}

		function extractWithStringsUsedOnPage( enclosingNode ) {
			var text, textWithoutSiblings, context, translationPair;
			if (
				typeof translationData.stringsUsedOnPage !== 'object' ||
			// not meant to be translatable:
			enclosingNode.is( 'style,script' ) ||
			enclosingNode.closest( '#querylist' ).length
			) {
				return false;
			}

			if ( enclosingNode.is( '[data-i18n-context]' ) ) {
				context = enclosingNode.data( 'i18n-context' );
			} else {
				context = enclosingNode.closest( '[data-i18n-context]' );
				if ( context.length ) {
					context = context.data( 'i18n-context' );
				} else {
					context = false;
				}
			}

			translationPair = getTranslationPairForTextUsedOnPage( enclosingNode, context );

			if ( false === translationPair ) {
				// remove adjescent nodes for text that is used without immidiately surrounding tag
				enclosingNode = enclosingNode.clone( true );
				textWithoutSiblings = trim( enclosingNode.find( '*' ).remove().end().text() );
				if ( text !== textWithoutSiblings ) {
					translationPair = getTranslationPairForTextUsedOnPage( enclosingNode, context );
				}
			}

			return translationPair;
		}

		function anyChildMatches( node, regex ) {
			var i, children;

			if ( typeof regex === 'string' ) {
				regex = new RegExp( regex );
			}

			if ( regex instanceof RegExp ) {
				children = node.children();
				for ( i = 0; i < children.length; i++ ) {
					if ( regex.test( children[ i ].innerHTML ) ||
					regex.test( children[ i ].textContent ) ) {
						return true;
					}
				}
			}

			return false;
		}

		function findMatchingTranslation( entry, contextSpecifier, translation, regex ) {
			var contextKey, contextKeySplit, domain, context, original, translationPair,
				matchingTranslations = {};

			for ( contextKey in entry ) {
				if ( ! entry.hasOwnProperty( contextKey ) ) {
					continue;
				}
				original = entry[ contextKey ];

				if ( translationData.translations[ contextKey + '|' + original ] ) {
					matchingTranslations[ contextKey ] = original;
				}
			}

			// If we didn't find any matching translations, we'll use them anyway.
			if ( Object.keys( matchingTranslations ).length === 0 ) {
				matchingTranslations = entry;
			}

			for ( contextKey in matchingTranslations ) {
				if ( ! matchingTranslations.hasOwnProperty( contextKey ) ) {
					continue;
				}
				original = matchingTranslations[ contextKey ];

				contextKeySplit = contextKey.split( '|' );
				domain = contextKeySplit.shift();
				context = contextKeySplit.shift();

				if ( ! contextSpecifier || ( contextSpecifier && context === contextSpecifier ) ) {
					delete matchingTranslations[ contextKey ];
					translationPair = new TranslationPair( translationData.locale, original, context, domain, translation, regex, Object.values( matchingTranslations ) );
					translationPair.setScreenText( translation );

					return translationPair;
				}
			}
			return null;
		}

		function getTranslationPairForTextUsedOnPage( node, contextSpecifier ) {
			var translationPair,
				entry = false,
				nodeText, nodeHtml, i;

			if ( node.get().length === 1 && node.get( 0 ).childNodes.length === 1 ) {
				nodeText = trim( node.get( 0 ).textContent );

				if ( ! nodeText.length || nodeText.length > 3000 ) {
					return false;
				}

				if ( typeof translationData.stringsUsedOnPage[ nodeText ] !== 'undefined' ) {
					translationPair = findMatchingTranslation( translationData.stringsUsedOnPage[ nodeText ], contextSpecifier, nodeText, new RegExp( '^\\s*' + getRegexString( nodeText ) + '\\s*$' ) );
					if ( translationPair ) {
						return translationPair;
					}
				}
			}

			// html to support translate( '<a href="%$1s">Translatable Text</a>' )
			nodeHtml = node.html();

			for ( i = 0; i < translationData.placeholdersUsedOnPage.length; i++ ) {
				entry = translationData.placeholdersUsedOnPage[ i ];

				if ( entry.regex.test( nodeHtml ) ) {
					// We want the innermost node that matches, so
					if ( anyChildMatches( node, entry.regex ) ) {
						continue;
					}
					translationPair = findMatchingTranslation( entry.originals, contextSpecifier, nodeHtml, entry.regex );
					if ( translationPair ) {
						return translationPair;
					}
				}
			}

			return false;
		}

		TranslationPair.extractFrom = function( enclosingNode ) {
			if ( typeof translationData !== 'object' ) {
				return false;
			}

			if ( enclosingNode.is( 'data.translatable' ) ) {
				return extractFromDataElement( enclosingNode );
			}

			if ( enclosingNode.closest( 'data.translatable' ).length ) {
				return extractFromDataElement( enclosingNode.closest( 'data.translatable' ) );
			}

			return extractWithStringsUsedOnPage( enclosingNode );
		};

		TranslationPair.setTranslationData = function( newTranslationData ) {
			var key, originals,
				placeholdersUsedOnPage = [];

			translationData = newTranslationData;

			// convert regular expressions to RegExp objects for later use
			if ( typeof translationData.placeholdersUsedOnPage === 'object' ) {
				for ( key in translationData.placeholdersUsedOnPage ) {
					originals = translationData.placeholdersUsedOnPage[ key ];
					placeholdersUsedOnPage.push( {
						originals: originals,
						regex: new RegExp( '^\\s*' + key + '\\s*$' ),
					} );
				}
			}
			translationData.placeholdersUsedOnPage = placeholdersUsedOnPage;
		};

		TranslationPair._test = {
			anyChildMatches: anyChildMatches,
		};

		module.exports = TranslationPair;
	}, { './original': 6, './popover': 7, './translation': 9 } ], 9: [ function( require, module, exports ) {
		/**
		 * Translation module
		 *
		 * @param {string} locale
		 * @param {Array}  items
		 * @param {Object} glotPressMetadata
		 */
		function Translation( locale, items, glotPressMetadata ) {
			var Item, i, status, translationId, userId, dateAdded,
				dateAddedUnixTimestamp = 0;

			if ( 'object' === typeof glotPressMetadata ) {
				if ( 'undefined' !== glotPressMetadata.status ) {
					status = glotPressMetadata.status;
				}
				if ( 'undefined' !== glotPressMetadata.translation_id ) {
					translationId = glotPressMetadata.translation_id;
				}
				if ( 'undefined' !== glotPressMetadata.user_id ) {
					userId = glotPressMetadata.user_id;
				}
				if ( 'undefined' !== glotPressMetadata.date_added ) {
					dateAdded = glotPressMetadata.date_added;
				}
			}

			if ( 'string' !== typeof status ) {
				status = 'current';
			}

			if ( isNaN( translationId ) ) {
				translationId = false;
			}

			if ( isNaN( userId ) ) {
				userId = false;
			}

			if ( dateAdded ) {
				dateAddedUnixTimestamp = getUnixTimestamp( dateAdded );
			}

			function getUnixTimestamp( mysqlDate ) {
				var dateParts = mysqlDate.split( '-' );
				var timeParts = dateParts[ 2 ].substr( 3 ).split( ':' );

				return new Date(
					dateParts[ 0 ],
					dateParts[ 1 ] - 1,
					dateParts[ 2 ].substr( 0, 2 ),
					timeParts[ 0 ],
					timeParts[ 1 ],
					timeParts[ 2 ]
				);
			}

			Item = function( j, text ) {
				return {
					isTranslated: function() {
						return text.length > 0;
					},
					getCaption: function() {
						var numbers;

						if ( items.length === 1 ) {
							return '';
						}

						if ( items.length === 2 ) {
							if ( j === 0 ) {
								return 'Singular';
							}
							return 'Plural';
						}

						numbers = locale.getNumbersForIndex( j );

						if ( numbers.length ) {
							return 'For numbers like: ' + numbers.join( ', ' );
						}

						return '';
					},
					getInfoText: function() {
						var numbers;

						if ( items.length === 1 ) {
							return '';
						}

						if ( items.length === 2 ) {
							if ( i === 0 ) {
								return 'Singular';
							}
							return 'Plural';
						}

						numbers = locale.getNumbersForIndex( i );

						if ( numbers.length ) {
							return numbers.join( ', ' );
						}

						return '';
					},
					getText: function() {
						return text;
					},
				};
			};

			if ( 'object' !== typeof items || 'number' !== typeof items.length ) {
				return false;
			}

			for ( i = 0; i < items.length; i++ ) {
				items[ i ] = new Item( i, items[ i ] );
			}

			return {
				type: 'Translation',
				isFullyTranslated: function() {
					for ( i = 0; i < items.length; i++ ) {
						if ( false === items[ i ].isTranslated() ) {
							return false;
						}
					}
					return true;
				},
				isCurrent: function() {
					return 'current' === status;
				},
				isWaiting: function() {
					return 'waiting' === status || 'fuzzy' === status;
				},
				getStatus: function() {
					return status;
				},
				getDate: function() {
					return dateAdded;
				},
				getComparableDate: function() {
					return dateAddedUnixTimestamp;
				},
				getUserId: function() {
					return Number( userId );
				},
				getTextItems: function() {
					return items;
				},
				serialize: function() {
					var serializedItems = [];

					for ( i = 0; i < items.length; i++ ) {
						serializedItems.push( items[ i ].getText() );
					}
					return serializedItems;
				},
			};
		}

		module.exports = Translation;
	}, {} ], 10: [ function( require, module, exports ) {
		function acceptableNode( node ) {
			if ( null === node.parentElement ) {
				return NodeFilter.FILTER_REJECT;
			}
			if ( [ 'SCRIPT', 'STYLE', 'BODY', 'HTML' ].includes( node.parentElement.nodeName ) ) {
				return NodeFilter.FILTER_REJECT;
			}
			if ( node.parentElement.classList.contains( 'translator-checked' ) ) {
				return NodeFilter.FILTER_REJECT;
			}
			return NodeFilter.FILTER_ACCEPT;
		}

		module.exports = function( TranslationPair, jQuery, document ) {
			return {
				walkTextNodes: function( origin, callback, finishedCallback ) {
					var node, walker,
						found = true,
						i = 5;

					if ( typeof document === 'object' ) {
						while ( found ) {
							walker = document.createTreeWalker( origin, NodeFilter.SHOW_TEXT, acceptableNode );
							node = walker.currentNode;
							found = false;
							while ( node ) {
								if ( acceptableNode( node ) === NodeFilter.FILTER_REJECT ) {
									// break;
								}
								walk( node );
								found = true;
								node = walker.nextNode();
							}

							if ( --i < 0 ) {
								break;
							}
						}
					} else {
						jQuery( origin ).find( '*' ).contents().filter( function() {
							if ( this.nodeType !== 3 ) {
								return false; // Node.TEXT_NODE
							}
							return acceptableNode( this ) === NodeFilter.FILTER_ACCEPT;
						} ).each( function() {
							walk( this );
						} );
					}

					if ( typeof finishedCallback === 'function' ) {
						finishedCallback();
					}

					function walk( textNode ) {
						var translationPair,
							enclosingNode;
						if ( [ 'SCRIPT', 'STYLE', 'BODY', 'HTML' ].includes( textNode.parentElement.nodeName ) ) {
							return false;
						}
						enclosingNode = jQuery( textNode.parentElement );

						enclosingNode.addClass( 'translator-checked' );

						if (
							enclosingNode.closest( '.webui-popover' ).length ||
					enclosingNode.hasClass( 'translator-exclude' ) ||
					enclosingNode.closest( '.translator-exclude' ).length
						) {
							return false;
						}

						translationPair = TranslationPair.extractFrom( enclosingNode );
						if ( false === translationPair ) {
							enclosingNode.addClass( 'translator-dont-translate' );
							return false;
						}

						if ( typeof callback === 'function' ) {
							callback( translationPair, enclosingNode );
						}

						return true;
					}
				},
			};
		};
	}, {} ], 11: [ function( require, module, exports ) {
		/**
		 * @preserve jed.js https://github.com/SlexAxton/Jed
		 */
		/*
-----------
A gettext compatible i18n library for modern JavaScript Applications

by Alex Sexton - AlexSexton [at] gmail - @SlexAxton

MIT License

A jQuery Foundation project - requires CLA to contribute -
https://contribute.jquery.org/CLA/

Jed offers the entire applicable GNU gettext spec'd set of
functions, but also offers some nicer wrappers around them.
The api for gettext was written for a language with no function
overloading, so Jed allows a little more of that.

Many thanks to Joshua I. Miller - unrtst@cpan.org - who wrote
gettext.js back in 2008. I was able to vet a lot of my ideas
against his. I also made sure Jed passed against his tests
in order to offer easy upgrades -- jsgettext.berlios.de
*/
		( function( root, undef ) {
			// Set up some underscore-style functions, if you already have
		// underscore, feel free to delete this section, and use it
		// directly, however, the amount of functions used doesn't
		// warrant having underscore as a full dependency.
		// Underscore 1.3.0 was used to port and is licensed
		// under the MIT License by Jeremy Ashkenas.
			var ArrayProto = Array.prototype,
				ObjProto = Object.prototype,
				slice = ArrayProto.slice,
				hasOwnProp = ObjProto.hasOwnProperty,
				nativeForEach = ArrayProto.forEach,
				breaker = {};

			// We're not using the OOP style _ so we don't need the
			// extra level of indirection. This still means that you
			// sub out for real `_` though.
			var _ = {
				forEach: function( obj, iterator, context ) {
					var i, l, key;
					if ( obj === null ) {
						return;
					}

					if ( nativeForEach && obj.forEach === nativeForEach ) {
						obj.forEach( iterator, context );
					} else if ( obj.length === +obj.length ) {
						for ( i = 0, l = obj.length; i < l; i++ ) {
							if ( i in obj && iterator.call( context, obj[ i ], i, obj ) === breaker ) {
								return;
							}
						}
					} else {
						for ( key in obj ) {
							if ( hasOwnProp.call( obj, key ) ) {
								if ( iterator.call( context, obj[ key ], key, obj ) === breaker ) {
									return;
								}
							}
						}
					}
				},
				extend: function( obj ) {
					this.forEach( slice.call( arguments, 1 ), function( source ) {
						for ( var prop in source ) {
							obj[ prop ] = source[ prop ];
						}
					} );
					return obj;
				},
			};
			// END Miniature underscore impl

			// Jed is a constructor function
			var Jed = function( options ) {
			// Some minimal defaults
				this.defaults = {
					locale_data: {
						messages: {
							'': {
								domain: 'messages',
								lang: 'en',
								plural_forms: 'nplurals=2; plural=(n != 1);',
							},
						// There are no default keys, though
						},
					},
					// The default domain if one is missing
					domain: 'messages',
					// enable debug mode to log untranslated strings to the console
					debug: false,
				};

				// Mix in the sent options with the default options
				this.options = _.extend( {}, this.defaults, options );
				this.textdomain( this.options.domain );

				if ( options.domain && ! this.options.locale_data[ this.options.domain ] ) {
					throw new Error( 'Text domain set to non-existent domain: `' + options.domain + '`' );
				}
			};

			// The gettext spec sets this character as the default
			// delimiter for context lookups.
			// e.g.: context\u0004key
			// If your translation company uses something different,
			// just change this at any time and it will use that instead.
			Jed.context_delimiter = String.fromCharCode( 4 );

			function getPluralFormFunc( plural_form_string ) {
				return Jed.PF.compile( plural_form_string || 'nplurals=2; plural=(n != 1);' );
			}

			function Chain( key, i18n ) {
				this._key = key;
				this._i18n = i18n;
			}

			// Create a chainable api for adding args prettily
			_.extend( Chain.prototype, {
				onDomain: function( domain ) {
					this._domain = domain;
					return this;
				},
				withContext: function( context ) {
					this._context = context;
					return this;
				},
				ifPlural: function( num, pkey ) {
					this._val = num;
					this._pkey = pkey;
					return this;
				},
				fetch: function( sArr ) {
					if ( {}.toString.call( sArr ) != '[object Array]' ) {
						sArr = [].slice.call( arguments, 0 );
					}
					return ( sArr && sArr.length ? Jed.sprintf : function( x ) {
						return x;
					} )(
						this._i18n.dcnpgettext( this._domain, this._context, this._key, this._pkey, this._val ),
						sArr
					);
				},
			} );

			// Add functions to the Jed prototype.
			// These will be the functions on the object that's returned
			// from creating a `new Jed()`
			// These seem redundant, but they gzip pretty well.
			_.extend( Jed.prototype, {
			// The sexier api start point
				translate: function( key ) {
					return new Chain( key, this );
				},

				textdomain: function( domain ) {
					if ( ! domain ) {
						return this._textdomain;
					}
					this._textdomain = domain;
				},

				gettext: function( key ) {
					return this.dcnpgettext.call( this, undef, undef, key );
				},

				dgettext: function( domain, key ) {
					return this.dcnpgettext.call( this, domain, undef, key );
				},

				dcgettext: function( domain, key /*, category */ ) {
				// Ignores the category anyways
					return this.dcnpgettext.call( this, domain, undef, key );
				},

				ngettext: function( skey, pkey, val ) {
					return this.dcnpgettext.call( this, undef, undef, skey, pkey, val );
				},

				dngettext: function( domain, skey, pkey, val ) {
					return this.dcnpgettext.call( this, domain, undef, skey, pkey, val );
				},

				dcngettext: function( domain, skey, pkey, val/*, category */ ) {
					return this.dcnpgettext.call( this, domain, undef, skey, pkey, val );
				},

				pgettext: function( context, key ) {
					return this.dcnpgettext.call( this, undef, context, key );
				},

				dpgettext: function( domain, context, key ) {
					return this.dcnpgettext.call( this, domain, context, key );
				},

				dcpgettext: function( domain, context, key/*, category */ ) {
					return this.dcnpgettext.call( this, domain, context, key );
				},

				npgettext: function( context, skey, pkey, val ) {
					return this.dcnpgettext.call( this, undef, context, skey, pkey, val );
				},

				dnpgettext: function( domain, context, skey, pkey, val ) {
					return this.dcnpgettext.call( this, domain, context, skey, pkey, val );
				},

				// The most fully qualified gettext function. It has every option.
				// Since it has every option, we can use it from every other method.
				// This is the bread and butter.
				// Technically there should be one more argument in this function for 'Category',
				// but since we never use it, we might as well not waste the bytes to define it.
				dcnpgettext: function( domain, context, singular_key, plural_key, val ) {
				// Set some defaults

					plural_key = plural_key || singular_key;

					// Use the global domain default if one
					// isn't explicitly passed in
					domain = domain || this._textdomain;

					var fallback;

					// Handle special cases

					// No options found
					if ( ! this.options ) {
					// There's likely something wrong, but we'll return the correct key for english
					// We do this by instantiating a brand new Jed instance with the default set
					// for everything that could be broken.
						fallback = new Jed();
						return fallback.dcnpgettext.call( fallback, undefined, undefined, singular_key, plural_key, val );
					}

					// No translation data provided
					if ( ! this.options.locale_data ) {
						throw new Error( 'No locale data provided.' );
					}

					if ( ! this.options.locale_data[ domain ] ) {
						throw new Error( 'Domain `' + domain + '` was not found.' );
					}

					if ( ! this.options.locale_data[ domain ][ '' ] ) {
						throw new Error( 'No locale meta information provided.' );
					}

					// Make sure we have a truthy key. Otherwise we might start looking
					// into the empty string key, which is the options for the locale
					// data.
					if ( ! singular_key ) {
						throw new Error( 'No translation key found.' );
					}

					var key = context ? context + Jed.context_delimiter + singular_key : singular_key,
						locale_data = this.options.locale_data,
						dict = locale_data[ domain ],
						defaultConf = ( locale_data.messages || this.defaults.locale_data.messages )[ '' ],
						pluralForms = dict[ '' ].plural_forms || dict[ '' ][ 'Plural-Forms' ] || dict[ '' ][ 'plural-forms' ] || defaultConf.plural_forms || defaultConf[ 'Plural-Forms' ] || defaultConf[ 'plural-forms' ],
						val_list,
						res;

					var val_idx;
					if ( val === undefined ) {
					// No value passed in; assume singular key lookup.
						val_idx = 0;
					} else {
					// Value has been passed in; use plural-forms calculations.

						// Handle invalid numbers, but try casting strings for good measure
						if ( typeof val !== 'number' ) {
							val = parseInt( val, 10 );

							if ( isNaN( val ) ) {
								throw new Error( 'The number that was passed in is not a number.' );
							}
						}

						val_idx = getPluralFormFunc( pluralForms )( val );
					}

					// Throw an error if a domain isn't found
					if ( ! dict ) {
						throw new Error( 'No domain named `' + domain + '` could be found.' );
					}

					val_list = dict[ key ];

					// If there is no match, then revert back to
					// english style singular/plural with the keys passed in.
					if ( ! val_list || val_idx > val_list.length ) {
						if ( this.options.missing_key_callback ) {
							this.options.missing_key_callback( key, domain );
						}
						res = [ singular_key, plural_key ];

						// collect untranslated strings
						if ( this.options.debug === true ) {
							console.log( res[ getPluralFormFunc( pluralForms )( val ) ] );
						}
						return res[ getPluralFormFunc()( val ) ];
					}

					res = val_list[ val_idx ];

					// This includes empty strings on purpose
					if ( ! res ) {
						res = [ singular_key, plural_key ];
						return res[ getPluralFormFunc()( val ) ];
					}
					return res;
				},
			} );

			// We add in sprintf capabilities for post translation value interolation
			// This is not internally used, so you can remove it if you have this
			// available somewhere else, or want to use a different system.

			// We _slightly_ modify the normal sprintf behavior to more gracefully handle
			// undefined values.

			/**
			  sprintf() for JavaScript 0.7-beta1
			  http://www.diveintojavascript.com/projects/javascript-sprintf
			  
			  Copyright (c) Alexandru Marasteanu <alexaholic [at) gmail (dot] com>
			  All rights reserved.
			  
			  Redistribution and use in source and binary forms, with or without
			  modification, are permitted provided that the following conditions are met:
			 * Redistributions of source code must retain the above copyright
			  notice, this list of conditions and the following disclaimer.
			 * Redistributions in binary form must reproduce the above copyright
			  notice, this list of conditions and the following disclaimer in the
			  documentation and/or other materials provided with the distribution.
			 * Neither the name of sprintf() for JavaScript nor the
			  names of its contributors may be used to endorse or promote products
			  derived from this software without specific prior written permission.
			  
			  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
			  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
			  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
			  DISCLAIMED. IN NO EVENT SHALL Alexandru Marasteanu BE LIABLE FOR ANY
			  DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
			  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
			  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
			  ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
			  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
			  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
			 */
			var sprintf = ( function() {
				function get_type( variable ) {
					return Object.prototype.toString.call( variable ).slice( 8, -1 ).toLowerCase();
				}
				function str_repeat( input, multiplier ) {
					for ( var output = []; multiplier > 0; output[ --multiplier ] = input ) {/* do nothing */}
					return output.join( '' );
				}

				var str_format = function() {
					if ( ! str_format.cache.hasOwnProperty( arguments[ 0 ] ) ) {
						str_format.cache[ arguments[ 0 ] ] = str_format.parse( arguments[ 0 ] );
					}
					return str_format.format.call( null, str_format.cache[ arguments[ 0 ] ], arguments );
				};

				str_format.format = function( parse_tree, argv ) {
					var cursor = 1,
						tree_length = parse_tree.length,
						node_type = '',
						arg,
						output = [],
						i, k, match, pad, pad_character, pad_length;
					for ( i = 0; i < tree_length; i++ ) {
						node_type = get_type( parse_tree[ i ] );
						if ( node_type === 'string' ) {
							output.push( parse_tree[ i ] );
						} else if ( node_type === 'array' ) {
							match = parse_tree[ i ]; // convenience purposes only
							if ( match[ 2 ] ) { // keyword argument
								arg = argv[ cursor ];
								for ( k = 0; k < match[ 2 ].length; k++ ) {
									if ( ! arg.hasOwnProperty( match[ 2 ][ k ] ) ) {
										throw ( sprintf( '[sprintf] property "%s" does not exist', match[ 2 ][ k ] ) );
									}
									arg = arg[ match[ 2 ][ k ] ];
								}
							} else if ( match[ 1 ] ) { // positional argument (explicit)
								arg = argv[ match[ 1 ] ];
							} else { // positional argument (implicit)
								arg = argv[ cursor++ ];
							}

							if ( /[^s]/.test( match[ 8 ] ) && ( get_type( arg ) != 'number' ) ) {
								throw ( sprintf( '[sprintf] expecting number but found %s', get_type( arg ) ) );
							}

							// Jed EDIT
							if ( typeof arg === 'undefined' || arg === null ) {
								arg = '';
							}
							// Jed EDIT

							switch ( match[ 8 ] ) {
								case 'b': arg = arg.toString( 2 ); break;
								case 'c': arg = String.fromCharCode( arg ); break;
								case 'd': arg = parseInt( arg, 10 ); break;
								case 'e': arg = match[ 7 ] ? arg.toExponential( match[ 7 ] ) : arg.toExponential(); break;
								case 'f': arg = match[ 7 ] ? parseFloat( arg ).toFixed( match[ 7 ] ) : parseFloat( arg ); break;
								case 'o': arg = arg.toString( 8 ); break;
								case 's': arg = ( ( arg = String( arg ) ) && match[ 7 ] ? arg.substring( 0, match[ 7 ] ) : arg ); break;
								case 'u': arg = Math.abs( arg ); break;
								case 'x': arg = arg.toString( 16 ); break;
								case 'X': arg = arg.toString( 16 ).toUpperCase(); break;
							}
							arg = ( /[def]/.test( match[ 8 ] ) && match[ 3 ] && arg >= 0 ? '+' + arg : arg );
							pad_character = match[ 4 ] ? match[ 4 ] == '0' ? '0' : match[ 4 ].charAt( 1 ) : ' ';
							pad_length = match[ 6 ] - String( arg ).length;
							pad = match[ 6 ] ? str_repeat( pad_character, pad_length ) : '';
							output.push( match[ 5 ] ? arg + pad : pad + arg );
						}
					}
					return output.join( '' );
				};

				str_format.cache = {};

				str_format.parse = function( fmt ) {
					var _fmt = fmt,
						match = [],
						parse_tree = [],
						arg_names = 0;
					while ( _fmt ) {
						if ( ( match = /^[^\x25]+/.exec( _fmt ) ) !== null ) {
							parse_tree.push( match[ 0 ] );
						} else if ( ( match = /^\x25{2}/.exec( _fmt ) ) !== null ) {
							parse_tree.push( '%' );
						} else if ( ( match = /^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec( _fmt ) ) !== null ) {
							if ( match[ 2 ] ) {
								arg_names |= 1;
								var field_list = [],
									replacement_field = match[ 2 ],
									field_match = [];
								if ( ( field_match = /^([a-z_][a-z_\d]*)/i.exec( replacement_field ) ) !== null ) {
									field_list.push( field_match[ 1 ] );
									while ( ( replacement_field = replacement_field.substring( field_match[ 0 ].length ) ) !== '' ) {
										if ( ( field_match = /^\.([a-z_][a-z_\d]*)/i.exec( replacement_field ) ) !== null ) {
											field_list.push( field_match[ 1 ] );
										} else if ( ( field_match = /^\[(\d+)\]/.exec( replacement_field ) ) !== null ) {
											field_list.push( field_match[ 1 ] );
										} else {
											throw ( '[sprintf] huh?' );
										}
									}
								} else {
									throw ( '[sprintf] huh?' );
								}
								match[ 2 ] = field_list;
							} else {
								arg_names |= 2;
							}
							if ( arg_names === 3 ) {
								throw ( '[sprintf] mixing positional and named placeholders is not (yet) supported' );
							}
							parse_tree.push( match );
						} else {
							throw ( '[sprintf] huh?' );
						}
						_fmt = _fmt.substring( match[ 0 ].length );
					}
					return parse_tree;
				};

				return str_format;
			}() );

			var vsprintf = function( fmt, argv ) {
				argv.unshift( fmt );
				return sprintf.apply( null, argv );
			};

			Jed.parse_plural = function( plural_forms, n ) {
				plural_forms = plural_forms.replace( /n/g, n );
				return Jed.parse_expression( plural_forms );
			};

			Jed.sprintf = function( fmt, args ) {
				if ( {}.toString.call( args ) == '[object Array]' ) {
					return vsprintf( fmt, [].slice.call( args ) );
				}
				return sprintf.apply( this, [].slice.call( arguments ) );
			};

			Jed.prototype.sprintf = function() {
				return Jed.sprintf.apply( this, arguments );
			};
			// END sprintf Implementation

			// Start the Plural forms section
			// This is a full plural form expression parser. It is used to avoid
			// running 'eval' or 'new Function' directly against the plural
			// forms.
			//
			// This can be important if you get translations done through a 3rd
			// party vendor. I encourage you to use this instead, however, I
			// also will provide a 'precompiler' that you can use at build time
			// to output valid/safe function representations of the plural form
			// expressions. This means you can build this code out for the most
			// part.
			Jed.PF = {};

			Jed.PF.parse = function( p ) {
				var plural_str = Jed.PF.extractPluralExpr( p );
				return Jed.PF.parser.parse.call( Jed.PF.parser, plural_str );
			};

			Jed.PF.compile = function( p ) {
			// Handle trues and falses as 0 and 1
				function imply( val ) {
					return ( val === true ? 1 : val ? val : 0 );
				}

				var ast = Jed.PF.parse( p );
				return function( n ) {
					return imply( Jed.PF.interpreter( ast )( n ) );
				};
			};

			Jed.PF.interpreter = function( ast ) {
				return function( n ) {
					var res;
					switch ( ast.type ) {
						case 'GROUP':
							return Jed.PF.interpreter( ast.expr )( n );
						case 'TERNARY':
							if ( Jed.PF.interpreter( ast.expr )( n ) ) {
								return Jed.PF.interpreter( ast.truthy )( n );
							}
							return Jed.PF.interpreter( ast.falsey )( n );
						case 'OR':
							return Jed.PF.interpreter( ast.left )( n ) || Jed.PF.interpreter( ast.right )( n );
						case 'AND':
							return Jed.PF.interpreter( ast.left )( n ) && Jed.PF.interpreter( ast.right )( n );
						case 'LT':
							return Jed.PF.interpreter( ast.left )( n ) < Jed.PF.interpreter( ast.right )( n );
						case 'GT':
							return Jed.PF.interpreter( ast.left )( n ) > Jed.PF.interpreter( ast.right )( n );
						case 'LTE':
							return Jed.PF.interpreter( ast.left )( n ) <= Jed.PF.interpreter( ast.right )( n );
						case 'GTE':
							return Jed.PF.interpreter( ast.left )( n ) >= Jed.PF.interpreter( ast.right )( n );
						case 'EQ':
							return Jed.PF.interpreter( ast.left )( n ) == Jed.PF.interpreter( ast.right )( n );
						case 'NEQ':
							return Jed.PF.interpreter( ast.left )( n ) != Jed.PF.interpreter( ast.right )( n );
						case 'MOD':
							return Jed.PF.interpreter( ast.left )( n ) % Jed.PF.interpreter( ast.right )( n );
						case 'VAR':
							return n;
						case 'NUM':
							return ast.val;
						default:
							throw new Error( 'Invalid Token found.' );
					}
				};
			};

			Jed.PF.extractPluralExpr = function( p ) {
			// trim first
				p = p.replace( /^\s\s*/, '' ).replace( /\s\s*$/, '' );

				if ( ! /;\s*$/.test( p ) ) {
					p = p.concat( ';' );
				}

				var nplurals_re = /nplurals\=(\d+);/,
					plural_re = /plural\=(.*);/,
					nplurals_matches = p.match( nplurals_re ),
					res = {},
					plural_matches;

				// Find the nplurals number
				if ( nplurals_matches.length > 1 ) {
					res.nplurals = nplurals_matches[ 1 ];
				} else {
					throw new Error( 'nplurals not found in plural_forms string: ' + p );
				}

				// remove that data to get to the formula
				p = p.replace( nplurals_re, '' );
				plural_matches = p.match( plural_re );

				if ( ! ( plural_matches && plural_matches.length > 1 ) ) {
					throw new Error( '`plural` expression not found: ' + p );
				}
				return plural_matches[ 1 ];
			};

			/* Jison generated parser */
			Jed.PF.parser = ( function() {
				var parser = { trace: function trace() { },
					yy: {},
					symbols_: { error: 2, expressions: 3, e: 4, EOF: 5, '?': 6, ':': 7, '||': 8, '&&': 9, '<': 10, '<=': 11, '>': 12, '>=': 13, '!=': 14, '==': 15, '%': 16, '(': 17, ')': 18, n: 19, NUMBER: 20, $accept: 0, $end: 1 },
					terminals_: { 2: 'error', 5: 'EOF', 6: '?', 7: ':', 8: '||', 9: '&&', 10: '<', 11: '<=', 12: '>', 13: '>=', 14: '!=', 15: '==', 16: '%', 17: '(', 18: ')', 19: 'n', 20: 'NUMBER' },
					productions_: [ 0, [ 3, 2 ], [ 4, 5 ], [ 4, 3 ], [ 4, 3 ], [ 4, 3 ], [ 4, 3 ], [ 4, 3 ], [ 4, 3 ], [ 4, 3 ], [ 4, 3 ], [ 4, 3 ], [ 4, 3 ], [ 4, 1 ], [ 4, 1 ] ],
					performAction: function anonymous( yytext, yyleng, yylineno, yy, yystate, $$, _$ ) {
						var $0 = $$.length - 1;
						switch ( yystate ) {
							case 1: return { type: 'GROUP', expr: $$[ $0 - 1 ] };
								break;
							case 2:this.$ = { type: 'TERNARY', expr: $$[ $0 - 4 ], truthy: $$[ $0 - 2 ], falsey: $$[ $0 ] };
								break;
							case 3:this.$ = { type: 'OR', left: $$[ $0 - 2 ], right: $$[ $0 ] };
								break;
							case 4:this.$ = { type: 'AND', left: $$[ $0 - 2 ], right: $$[ $0 ] };
								break;
							case 5:this.$ = { type: 'LT', left: $$[ $0 - 2 ], right: $$[ $0 ] };
								break;
							case 6:this.$ = { type: 'LTE', left: $$[ $0 - 2 ], right: $$[ $0 ] };
								break;
							case 7:this.$ = { type: 'GT', left: $$[ $0 - 2 ], right: $$[ $0 ] };
								break;
							case 8:this.$ = { type: 'GTE', left: $$[ $0 - 2 ], right: $$[ $0 ] };
								break;
							case 9:this.$ = { type: 'NEQ', left: $$[ $0 - 2 ], right: $$[ $0 ] };
								break;
							case 10:this.$ = { type: 'EQ', left: $$[ $0 - 2 ], right: $$[ $0 ] };
								break;
							case 11:this.$ = { type: 'MOD', left: $$[ $0 - 2 ], right: $$[ $0 ] };
								break;
							case 12:this.$ = { type: 'GROUP', expr: $$[ $0 - 1 ] };
								break;
							case 13:this.$ = { type: 'VAR' };
								break;
							case 14:this.$ = { type: 'NUM', val: Number( yytext ) };
								break;
						}
					},
					table: [ { 3: 1, 4: 2, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 1: [ 3 ] }, { 5: [ 1, 6 ], 6: [ 1, 7 ], 8: [ 1, 8 ], 9: [ 1, 9 ], 10: [ 1, 10 ], 11: [ 1, 11 ], 12: [ 1, 12 ], 13: [ 1, 13 ], 14: [ 1, 14 ], 15: [ 1, 15 ], 16: [ 1, 16 ] }, { 4: 17, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 5: [ 2, 13 ], 6: [ 2, 13 ], 7: [ 2, 13 ], 8: [ 2, 13 ], 9: [ 2, 13 ], 10: [ 2, 13 ], 11: [ 2, 13 ], 12: [ 2, 13 ], 13: [ 2, 13 ], 14: [ 2, 13 ], 15: [ 2, 13 ], 16: [ 2, 13 ], 18: [ 2, 13 ] }, { 5: [ 2, 14 ], 6: [ 2, 14 ], 7: [ 2, 14 ], 8: [ 2, 14 ], 9: [ 2, 14 ], 10: [ 2, 14 ], 11: [ 2, 14 ], 12: [ 2, 14 ], 13: [ 2, 14 ], 14: [ 2, 14 ], 15: [ 2, 14 ], 16: [ 2, 14 ], 18: [ 2, 14 ] }, { 1: [ 2, 1 ] }, { 4: 18, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 4: 19, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 4: 20, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 4: 21, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 4: 22, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 4: 23, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 4: 24, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 4: 25, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 4: 26, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 4: 27, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 6: [ 1, 7 ], 8: [ 1, 8 ], 9: [ 1, 9 ], 10: [ 1, 10 ], 11: [ 1, 11 ], 12: [ 1, 12 ], 13: [ 1, 13 ], 14: [ 1, 14 ], 15: [ 1, 15 ], 16: [ 1, 16 ], 18: [ 1, 28 ] }, { 6: [ 1, 7 ], 7: [ 1, 29 ], 8: [ 1, 8 ], 9: [ 1, 9 ], 10: [ 1, 10 ], 11: [ 1, 11 ], 12: [ 1, 12 ], 13: [ 1, 13 ], 14: [ 1, 14 ], 15: [ 1, 15 ], 16: [ 1, 16 ] }, { 5: [ 2, 3 ], 6: [ 2, 3 ], 7: [ 2, 3 ], 8: [ 2, 3 ], 9: [ 1, 9 ], 10: [ 1, 10 ], 11: [ 1, 11 ], 12: [ 1, 12 ], 13: [ 1, 13 ], 14: [ 1, 14 ], 15: [ 1, 15 ], 16: [ 1, 16 ], 18: [ 2, 3 ] }, { 5: [ 2, 4 ], 6: [ 2, 4 ], 7: [ 2, 4 ], 8: [ 2, 4 ], 9: [ 2, 4 ], 10: [ 1, 10 ], 11: [ 1, 11 ], 12: [ 1, 12 ], 13: [ 1, 13 ], 14: [ 1, 14 ], 15: [ 1, 15 ], 16: [ 1, 16 ], 18: [ 2, 4 ] }, { 5: [ 2, 5 ], 6: [ 2, 5 ], 7: [ 2, 5 ], 8: [ 2, 5 ], 9: [ 2, 5 ], 10: [ 2, 5 ], 11: [ 2, 5 ], 12: [ 2, 5 ], 13: [ 2, 5 ], 14: [ 2, 5 ], 15: [ 2, 5 ], 16: [ 1, 16 ], 18: [ 2, 5 ] }, { 5: [ 2, 6 ], 6: [ 2, 6 ], 7: [ 2, 6 ], 8: [ 2, 6 ], 9: [ 2, 6 ], 10: [ 2, 6 ], 11: [ 2, 6 ], 12: [ 2, 6 ], 13: [ 2, 6 ], 14: [ 2, 6 ], 15: [ 2, 6 ], 16: [ 1, 16 ], 18: [ 2, 6 ] }, { 5: [ 2, 7 ], 6: [ 2, 7 ], 7: [ 2, 7 ], 8: [ 2, 7 ], 9: [ 2, 7 ], 10: [ 2, 7 ], 11: [ 2, 7 ], 12: [ 2, 7 ], 13: [ 2, 7 ], 14: [ 2, 7 ], 15: [ 2, 7 ], 16: [ 1, 16 ], 18: [ 2, 7 ] }, { 5: [ 2, 8 ], 6: [ 2, 8 ], 7: [ 2, 8 ], 8: [ 2, 8 ], 9: [ 2, 8 ], 10: [ 2, 8 ], 11: [ 2, 8 ], 12: [ 2, 8 ], 13: [ 2, 8 ], 14: [ 2, 8 ], 15: [ 2, 8 ], 16: [ 1, 16 ], 18: [ 2, 8 ] }, { 5: [ 2, 9 ], 6: [ 2, 9 ], 7: [ 2, 9 ], 8: [ 2, 9 ], 9: [ 2, 9 ], 10: [ 2, 9 ], 11: [ 2, 9 ], 12: [ 2, 9 ], 13: [ 2, 9 ], 14: [ 2, 9 ], 15: [ 2, 9 ], 16: [ 1, 16 ], 18: [ 2, 9 ] }, { 5: [ 2, 10 ], 6: [ 2, 10 ], 7: [ 2, 10 ], 8: [ 2, 10 ], 9: [ 2, 10 ], 10: [ 2, 10 ], 11: [ 2, 10 ], 12: [ 2, 10 ], 13: [ 2, 10 ], 14: [ 2, 10 ], 15: [ 2, 10 ], 16: [ 1, 16 ], 18: [ 2, 10 ] }, { 5: [ 2, 11 ], 6: [ 2, 11 ], 7: [ 2, 11 ], 8: [ 2, 11 ], 9: [ 2, 11 ], 10: [ 2, 11 ], 11: [ 2, 11 ], 12: [ 2, 11 ], 13: [ 2, 11 ], 14: [ 2, 11 ], 15: [ 2, 11 ], 16: [ 2, 11 ], 18: [ 2, 11 ] }, { 5: [ 2, 12 ], 6: [ 2, 12 ], 7: [ 2, 12 ], 8: [ 2, 12 ], 9: [ 2, 12 ], 10: [ 2, 12 ], 11: [ 2, 12 ], 12: [ 2, 12 ], 13: [ 2, 12 ], 14: [ 2, 12 ], 15: [ 2, 12 ], 16: [ 2, 12 ], 18: [ 2, 12 ] }, { 4: 30, 17: [ 1, 3 ], 19: [ 1, 4 ], 20: [ 1, 5 ] }, { 5: [ 2, 2 ], 6: [ 1, 7 ], 7: [ 2, 2 ], 8: [ 1, 8 ], 9: [ 1, 9 ], 10: [ 1, 10 ], 11: [ 1, 11 ], 12: [ 1, 12 ], 13: [ 1, 13 ], 14: [ 1, 14 ], 15: [ 1, 15 ], 16: [ 1, 16 ], 18: [ 2, 2 ] } ],
					defaultActions: { 6: [ 2, 1 ] },
					parseError: function parseError( str, hash ) {
						throw new Error( str );
					},
					parse: function parse( input ) {
						var self = this,
							stack = [ 0 ],
							vstack = [ null ], // semantic value stack
							lstack = [], // location stack
							table = this.table,
							yytext = '',
							yylineno = 0,
							yyleng = 0,
							recovering = 0,
							TERROR = 2,
							EOF = 1;

						//this.reductionCount = this.shiftCount = 0;

						this.lexer.setInput( input );
						this.lexer.yy = this.yy;
						this.yy.lexer = this.lexer;
						if ( typeof this.lexer.yylloc === 'undefined' ) {
							this.lexer.yylloc = {};
						}
						var yyloc = this.lexer.yylloc;
						lstack.push( yyloc );

						if ( typeof this.yy.parseError === 'function' ) {
							this.parseError = this.yy.parseError;
						}

						function popStack( n ) {
							stack.length = stack.length - 2 * n;
							vstack.length = vstack.length - n;
							lstack.length = lstack.length - n;
						}

						function lex() {
							var token;
							token = self.lexer.lex() || 1; // $end = 1
							// if token isn't its numeric value, convert
							if ( typeof token !== 'number' ) {
								token = self.symbols_[ token ] || token;
							}
							return token;
						}

						var symbol, preErrorSymbol, state, action, a, r,
							yyval = {},
							p, len, newState, expected;
						while ( true ) {
						// retreive state number from top of stack
							state = stack[ stack.length - 1 ];

							// use default actions if available
							if ( this.defaultActions[ state ] ) {
								action = this.defaultActions[ state ];
							} else {
								if ( symbol == null ) {
									symbol = lex();
								}
								// read action for current state and first input
								action = table[ state ] && table[ state ][ symbol ];
							}

							// handle parse error
							_handle_error:
							if ( typeof action === 'undefined' || ! action.length || ! action[ 0 ] ) {
								if ( ! recovering ) {
								// Report error
									expected = [];
									for ( p in table[ state ] ) {
										if ( this.terminals_[ p ] && p > 2 ) {
											expected.push( "'" + this.terminals_[ p ] + "'" );
										}
									}
									var errStr = '';
									if ( this.lexer.showPosition ) {
										errStr = 'Parse error on line ' + ( yylineno + 1 ) + ':\n' + this.lexer.showPosition() + '\nExpecting ' + expected.join( ', ' ) + ", got '" + this.terminals_[ symbol ] + "'";
									} else {
										errStr = 'Parse error on line ' + ( yylineno + 1 ) + ': Unexpected ' +
                                  ( symbol == 1 /*EOF*/ ? 'end of input'
                                  	: ( "'" + ( this.terminals_[ symbol ] || symbol ) + "'" ) );
									}
									this.parseError( errStr,
										{ text: this.lexer.match, token: this.terminals_[ symbol ] || symbol, line: this.lexer.yylineno, loc: yyloc, expected: expected } );
								}

								// just recovered from another error
								if ( recovering == 3 ) {
									if ( symbol == EOF ) {
										throw new Error( errStr || 'Parsing halted.' );
									}

									// discard current lookahead and grab another
									yyleng = this.lexer.yyleng;
									yytext = this.lexer.yytext;
									yylineno = this.lexer.yylineno;
									yyloc = this.lexer.yylloc;
									symbol = lex();
								}

								// try to recover from error
								while ( 1 ) {
								// check for error recovery rule in this state
									if ( ( TERROR.toString() ) in table[ state ] ) {
										break;
									}
									if ( state == 0 ) {
										throw new Error( errStr || 'Parsing halted.' );
									}
									popStack( 1 );
									state = stack[ stack.length - 1 ];
								}

								preErrorSymbol = symbol; // save the lookahead token
								symbol = TERROR; // insert generic error symbol as new lookahead
								state = stack[ stack.length - 1 ];
								action = table[ state ] && table[ state ][ TERROR ];
								recovering = 3; // allow 3 real symbols to be shifted before reporting a new error
							}

							// this shouldn't happen, unless resolve defaults are off
							if ( action[ 0 ] instanceof Array && action.length > 1 ) {
								throw new Error( 'Parse Error: multiple actions possible at state: ' + state + ', token: ' + symbol );
							}

							switch ( action[ 0 ] ) {
								case 1: // shift
								//this.shiftCount++;

									stack.push( symbol );
									vstack.push( this.lexer.yytext );
									lstack.push( this.lexer.yylloc );
									stack.push( action[ 1 ] ); // push state
									symbol = null;
									if ( ! preErrorSymbol ) { // normal execution/no error
										yyleng = this.lexer.yyleng;
										yytext = this.lexer.yytext;
										yylineno = this.lexer.yylineno;
										yyloc = this.lexer.yylloc;
										if ( recovering > 0 ) {
											recovering--;
										}
									} else { // error just occurred, resume old lookahead f/ before error
										symbol = preErrorSymbol;
										preErrorSymbol = null;
									}
									break;

								case 2: // reduce
								//this.reductionCount++;

									len = this.productions_[ action[ 1 ] ][ 1 ];

									// perform semantic action
									yyval.$ = vstack[ vstack.length - len ]; // default to $$ = $1
									// default location, uses first token for firsts, last for lasts
									yyval._$ = {
										first_line: lstack[ lstack.length - ( len || 1 ) ].first_line,
										last_line: lstack[ lstack.length - 1 ].last_line,
										first_column: lstack[ lstack.length - ( len || 1 ) ].first_column,
										last_column: lstack[ lstack.length - 1 ].last_column,
									};
									r = this.performAction.call( yyval, yytext, yyleng, yylineno, this.yy, action[ 1 ], vstack, lstack );

									if ( typeof r !== 'undefined' ) {
										return r;
									}

									// pop off stack
									if ( len ) {
										stack = stack.slice( 0, -1 * len * 2 );
										vstack = vstack.slice( 0, -1 * len );
										lstack = lstack.slice( 0, -1 * len );
									}

									stack.push( this.productions_[ action[ 1 ] ][ 0 ] ); // push nonterminal (reduce)
									vstack.push( yyval.$ );
									lstack.push( yyval._$ );
									// goto new state = table[STATE][NONTERMINAL]
									newState = table[ stack[ stack.length - 2 ] ][ stack[ stack.length - 1 ] ];
									stack.push( newState );
									break;

								case 3: // accept
									return true;
							}
						}

						return true;
					} };/* Jison generated lexer */
				var lexer = ( function() {
					var lexer = ( { EOF: 1,
						parseError: function parseError( str, hash ) {
							if ( this.yy.parseError ) {
								this.yy.parseError( str, hash );
							} else {
								throw new Error( str );
							}
						},
						setInput: function( input ) {
							this._input = input;
							this._more = this._less = this.done = false;
							this.yylineno = this.yyleng = 0;
							this.yytext = this.matched = this.match = '';
							this.conditionStack = [ 'INITIAL' ];
							this.yylloc = { first_line: 1, first_column: 0, last_line: 1, last_column: 0 };
							return this;
						},
						input: function() {
							var ch = this._input[ 0 ];
							this.yytext += ch;
							this.yyleng++;
							this.match += ch;
							this.matched += ch;
							var lines = ch.match( /\n/ );
							if ( lines ) {
								this.yylineno++;
							}
							this._input = this._input.slice( 1 );
							return ch;
						},
						unput: function( ch ) {
							this._input = ch + this._input;
							return this;
						},
						more: function() {
							this._more = true;
							return this;
						},
						pastInput: function() {
							var past = this.matched.substr( 0, this.matched.length - this.match.length );
							return ( past.length > 20 ? '...' : '' ) + past.substr( -20 ).replace( /\n/g, '' );
						},
						upcomingInput: function() {
							var next = this.match;
							if ( next.length < 20 ) {
								next += this._input.substr( 0, 20 - next.length );
							}
							return ( next.substr( 0, 20 ) + ( next.length > 20 ? '...' : '' ) ).replace( /\n/g, '' );
						},
						showPosition: function() {
							var pre = this.pastInput();
							var c = new Array( pre.length + 1 ).join( '-' );
							return pre + this.upcomingInput() + '\n' + c + '^';
						},
						next: function() {
							if ( this.done ) {
								return this.EOF;
							}
							if ( ! this._input ) {
								this.done = true;
							}

							var token,
								match,
								col,
								lines;
							if ( ! this._more ) {
								this.yytext = '';
								this.match = '';
							}
							var rules = this._currentRules();
							for ( var i = 0; i < rules.length; i++ ) {
								match = this._input.match( this.rules[ rules[ i ] ] );
								if ( match ) {
									lines = match[ 0 ].match( /\n.*/g );
									if ( lines ) {
										this.yylineno += lines.length;
									}
									this.yylloc = { first_line: this.yylloc.last_line,
										last_line: this.yylineno + 1,
										first_column: this.yylloc.last_column,
										last_column: lines ? lines[ lines.length - 1 ].length - 1 : this.yylloc.last_column + match[ 0 ].length };
									this.yytext += match[ 0 ];
									this.match += match[ 0 ];
									this.matches = match;
									this.yyleng = this.yytext.length;
									this._more = false;
									this._input = this._input.slice( match[ 0 ].length );
									this.matched += match[ 0 ];
									token = this.performAction.call( this, this.yy, this, rules[ i ], this.conditionStack[ this.conditionStack.length - 1 ] );
									if ( token ) {
										return token;
									}
									return;
								}
							}
							if ( this._input === '' ) {
								return this.EOF;
							}
							this.parseError( 'Lexical error on line ' + ( this.yylineno + 1 ) + '. Unrecognized text.\n' + this.showPosition(),
								{ text: '', token: null, line: this.yylineno } );
						},
						lex: function lex() {
							var r = this.next();
							if ( typeof r !== 'undefined' ) {
								return r;
							}
							return this.lex();
						},
						begin: function begin( condition ) {
							this.conditionStack.push( condition );
						},
						popState: function popState() {
							return this.conditionStack.pop();
						},
						_currentRules: function _currentRules() {
							return this.conditions[ this.conditionStack[ this.conditionStack.length - 1 ] ].rules;
						},
						topState: function() {
							return this.conditionStack[ this.conditionStack.length - 2 ];
						},
						pushState: function begin( condition ) {
							this.begin( condition );
						} } );
					lexer.performAction = function anonymous( yy, yy_, $avoiding_name_collisions, YY_START ) {
						var YYSTATE = YY_START;
						switch ( $avoiding_name_collisions ) {
							case 0:/* skip whitespace */
								break;
							case 1:return 20;
								break;
							case 2:return 19;
								break;
							case 3:return 8;
								break;
							case 4:return 9;
								break;
							case 5:return 6;
								break;
							case 6:return 7;
								break;
							case 7:return 11;
								break;
							case 8:return 13;
								break;
							case 9:return 10;
								break;
							case 10:return 12;
								break;
							case 11:return 14;
								break;
							case 12:return 15;
								break;
							case 13:return 16;
								break;
							case 14:return 17;
								break;
							case 15:return 18;
								break;
							case 16:return 5;
								break;
							case 17:return 'INVALID';
								break;
						}
					};
					lexer.rules = [ /^\s+/, /^[0-9]+(\.[0-9]+)?\b/, /^n\b/, /^\|\|/, /^&&/, /^\?/, /^:/, /^<=/, /^>=/, /^</, /^>/, /^!=/, /^==/, /^%/, /^\(/, /^\)/, /^$/, /^./ ];
					lexer.conditions = { INITIAL: { rules: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17 ], inclusive: true } }; return lexer;
				}() );
				parser.lexer = lexer;
				return parser;
			}() );
			// End parser

			// Handle node, amd, and global systems
			if ( typeof exports !== 'undefined' ) {
				if ( typeof module !== 'undefined' && module.exports ) {
					exports = module.exports = Jed;
				}
				exports.Jed = Jed;
			} else {
				if ( typeof define === 'function' && define.amd ) {
					define( function() {
						return Jed;
					} );
				}
				// Leak a global regardless of module system
				root.Jed = Jed;
			}
		}( this ) );
	}, {} ] }, {}, [ 3 ] )( 3 );
} ) );
