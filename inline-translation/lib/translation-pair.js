/**
 * TranslationPair module
 */
var Original = require( './original' ),
	Translation = require( './translation' ),
	Popover = require( './popover' ),
	translationData;

function TranslationPair( locale, original, context, translation ) {
	var translations = [], selectedTranslation, glotPressProject,
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

	function addTranslation( translation ) {
		if ( 'object' !== typeof translation || translation.type !== 'Translation' ) {
			translation = new Translation( locale, translation.slice() );
		}

		if ( selectedTranslation.getTextItems().length !== translation.getTextItems().length ) {
			// translations have to match the existing number of translation items ( singular = 1, plural = dependent on language )
			return false;
		}

		translations.push( translation );
		selectedTranslation = translation;
	}

	function loadTranslations( newTranslations ) {
		var i, j, t, translation;

		translations = [];

		for ( i = 0; i < newTranslations.length; i++ ) {
			translation = [];
			for ( j = 0; ( t = newTranslations[ i ][ 'translation_' + j ] ); j++ ) {
				translation.push( t );
			}
			translation = new Translation( locale, translation.slice(), newTranslations[ i ] );
			addTranslation( translation );
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

		if ( 'number' === typeof currentUserId ) {
			currentUserId = currentUserId.toString();
		}

		sortTranslationsByDate();

		for ( var i = 0; i < translations.length; i++ ) {
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
		getContext: function() {
			return context;
		},
		getLocale: function() {
			return locale;
		},
		getScreenText: function() {
			return screenText;
		},
		setScreenText: function( _screenText ) {
			screenText = _screenText;
		},
		getTranslation: function() {
			return selectedTranslation;
		},
		getGlotPressProject: function() {
			return glotPressProject;
		},
		updateAllTranslations: function( newTranslations, currentUserId ) {
			if ( ! loadTranslations( newTranslations ) ) {
				return false;
			}

			if ( 'undefined' === typeof currentUserId ) {
				setSelectedTranslation( currentUserId );
			}
		},
		serialize: function() {
			// the parameters as array
			return {
				singular: original.getSingular(),
				plural: original.getPlural(),
				context: context,
				translations: selectedTranslation.serialize(),
				key: original.generateJsonHash( context )
			};
		},
		fetchOriginalAndTranslations: function( glotPress, currentUserId ) {
			var promise, sendContext;
			promise = original.fetchIdAndTranslations( glotPress, context )
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
		}
	};
}

function extractFromDataElement( dataElement ) {
	var translationPair,
		original = {
			singular: dataElement.data( 'singular' )
		};

	if ( dataElement.data( 'plural' ) ) {
		original.plural = dataElement.data( 'plural' );
	}

	if ( dataElement.data( 'context' ) ) {
		original.context = dataElement.data( 'context' );
	}

	translationPair = new TranslationPair( translationData.locale, original, original.context );
	translationPair.setScreenText( dataElement.text() );

	return translationPair;
}

function trim( text ) {
	if ( typeof text === 'undefined' ) {
		return '';
	}
	return text.replace( /(?:(?:^|\n)\s+|\s+(?:$|\n))/g, '' );
}

function extractWithStringsUsedOnPage( enclosingNode ) {

	var text, textWithoutSiblings, enclosingNodeWithoutSiblings, context;
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

function getTranslationPairForTextUsedOnPage( node, context ) {
	var original, placeholderRegex,
		contexts,
		translationPairs,
		translationPair, newPlaceholder,
		entry = false,
		nodeText, nodeHtml;

	nodeText = trim( node.text() );

	if ( ! nodeText.length || nodeText.length > 3000 ) {
		return false;
	}

	if ( typeof translationData.stringsUsedOnPage[ nodeText ] !== 'undefined' ) {
		entry = translationData.stringsUsedOnPage[ nodeText ];

		context = entry[ 1 ];
		if ( typeof context !== 'undefined' && context && context.length === 1 ) {
			context = context[ 0 ];
		}
		translationPair = new TranslationPair( translationData.locale, entry[ 0 ], context );
		translationPair.setScreenText( nodeText );

		return translationPair;
	}

	// html to support translate( '<a href="%$1s">Translatable Text</a>' )
	nodeHtml = trim( node.html() );

	for ( i = 0; i < translationData.placeholdersUsedOnPage.length; i++ ) {
		entry = translationData.placeholdersUsedOnPage[ i ];

		if ( entry.regex.test( nodeHtml ) ) {

			// We want the innermost node that matches, so
			if ( anyChildMatches( node, entry.regex ) ) {
				continue;
			}

			translationPair = new TranslationPair( translationData.locale, entry.original, entry.context );
			translationPair.setScreenText( nodeText );

			return translationPair;
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
	var key, entry, context,
		placeholdersUsedOnPage = [];

	translationData = newTranslationData;

	// convert regular expressions to RegExp objects for later use
	if ( typeof translationData.placeholdersUsedOnPage === 'object' ) {
		for ( key in translationData.placeholdersUsedOnPage ) {
			entry = translationData.placeholdersUsedOnPage[ key ];

			if ( typeof entry.regex === 'undefined' ) {
				entry = {
					original: entry[ 0 ],
					regex: new RegExp( '^\\s*' + entry[ 1 ] + '\\s*$' ),
					context: entry[ 2 ]
				};
			}
			placeholdersUsedOnPage.push( entry );
		}
	}
	translationData.placeholdersUsedOnPage = placeholdersUsedOnPage;
};

TranslationPair._test = {
	anyChildMatches: anyChildMatches
};

module.exports = TranslationPair;
