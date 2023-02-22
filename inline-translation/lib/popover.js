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
		form.attr( 'data-nodes', nodeClass );
		form.data( 'translationPair', translationPair );

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
			enclosingNode.addClass( nodeClass ).webuiPopover( {
				title: getPopoverTitle(),
				content: jQuery( '<div>' ).append( getPopoverHtml() ).html(),
				onload: popoverOnload,
				translationPair: translationPair,
			} );
		},
		getTranslationHtml: function() {
			form = getInputForm( translationPair );
			return getPopoverHtml();
		},
	};
}

function popoverOnload( el ) {
	el = jQuery( el ).find( 'textarea' ).get( 0 );
	if ( el ) {
		el.focus();
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

	if ( translationPair.getContext() ) {
		form.find( 'p.context' ).text( translationPair.getContext() ).show();
	}

	if ( translationPair.getOriginal().getComment() ) {
		form.find( 'p.comment' ).text( translationPair.getOriginal().getComment() ).show();
	}

	item = translationPair.getTranslation().getTextItems();
	for ( i = 0; i < item.length; i++ ) {
		if ( i > 0 ) {
			pair = pair.eq( 0 ).clone();
		}

		pair.find( 'p' ).text( item[ i ].getCaption() );
		pair.find( 'textarea' ).text( item[ i ].getText() ).attr( 'placeholder', 'Could you help us and translate this to ' + locale.getLanguageName() + '? Thanks!' );

		if ( i > 0 ) {
			pairs.append( pair );
		}
	}

	return form;
}

function getOverview( translationPair ) {
	// TODO: add input checking and bail for empty or unexpected values

	var form = getHtmlTemplate( 'existing-translation' ).clone(),
		original = form.find( 'div.original' ),
		pair = form.find( 'div.pair' ),
		pairs = form.find( 'div.pairs' ),
		item, description, i;

	original.html( getOriginalHtml( translationPair ) );

	if ( translationPair.getContext() ) {
		form.find( 'p.context' ).text( translationPair.getContext() ).show();
	}

	if ( translationPair.getOriginal().getComment() ) {
		form.find( 'p.comment' ).text( translationPair.getOriginal().getComment() ).show();
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
				'<form class="ct-existing-translation">' +
			'<div class="original"></div>' +
			'<p class="context"></p>' +
			'<p class="comment"></p>' +
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
			'</form>'
			);

		case 'new-translation':
			return jQuery(
				'<form class="ct-new-translation">' +
			'<div class="original"></div>' +
			'<p class="context"></p>' +
			'<p class="comment"></p>' +
			'<p class="info"></p>' +
			'<div class="pairs">' +
			'<div class="pair">' +
			'<p></p>' +
			'<input type="hidden" class="original" name="original[]" />' +
			'<textarea dir="auto" class="translation" name="translation[]"></textarea>' +
			'</div>' +
			'</div>' +
			'<button disabled class="button button-primary">Submit translation</button>' +
			'</form>'
			);
	}
}

module.exports = Popover;
