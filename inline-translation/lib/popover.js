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
		textareas = jQuery( el ).find( 'textarea' ),
		additional = jQuery( el ).find( 'div.additional' );
	el = textareas.get( 0 );
	if ( el ) {
		el.focus();

		if ( textareas.eq( 0 ).val() !== '' ) {
			// return;
		}
		if ( ! glotPress.shouldLoadSuggestions() ) {
			return;
		}
		additional.html( 'Loading suggested translation <span class="spinner is-active" style="float: none; margin: 0 0 0 5px;"></span>' );

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
			if ( response.suggestion ) {
				additional.html( '<details><summary>Modify Query</summary><textarea class="prompt" placeholder="Add a custom prompt..."></textarea><blockquote class="unmodifyable"></blockquote> <button class="button requery">Requery</button></details><ul class="suggestions"></ul>' );
				for ( i = 0; i < response.suggestion.length; i++ ) {
					li = jQuery( '<li><button class="button button-small copy">Copy</button><span></span>' );
					additional.find( 'ul.suggestions' ).append( li );
					li.find( 'span' ).text( response.suggestion[ i ] );
					li.find( 'button' ).on( 'click', function() {
						var textarea = textareas.get( 0 );
						var newText = jQuery( this ).closest( 'li' ).find( 'span' ).text();
						textarea.focus();
						textarea.select();

						// Replace all text with new text
						document.execCommand( 'insertText', false, newText );
						jQuery( textarea ).trigger( 'keyup' );
						return false;
					} );
				}
				additional.find( 'blockquote.unmodifyable' ).text( response.unmodifyable );
				additional.find( 'textarea.prompt' ).val( response.prompt );
				additional.find( 'button.requery' ).on( 'click', requery );
			} else {
				for ( i = 0; i < textareas.length; i++ ) {
					textareas.eq( i ).prop( 'placeholder', 'Please enter your translation' );
				}
				additional.text( '' );
			}
		};

		glotPress.getSuggestedTranslation( translationPair ).done( getSuggestionsResponse ).error( getSugesstionsError );
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
	originalHtml.find( 'strong.singular' ).text( translationPair.getOriginal().getSingular() );

	if ( plural ) {
		originalHtml.find( 'strong.plural' ).text( plural );
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
			'</div>' +
			'</div>' +
			'<button disabled class="button button-primary save">Save Translation</button>' +
			'</form>' +
			'<div class="additional"></div></div>'
			);
	}
}

module.exports = Popover;
