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

function TranslationPair( locale, original, context, domain, translation, regex ) {
	var translations = [],
		selectedTranslation, glotPressProject,
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

		if ( 'number' === typeof currentUserId ) {
			currentUserId = currentUserId.toString();
		}

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
		getRegex: function() {
			if ( regex ) {
				return regex;
			}
			regex = selectedTranslation.getTextItems()[ 0 ].getText();
			regex = new RegExp( regex.replace( /[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&' ), 'g' );
			return regex;
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

function extractFromDataElement( dataElement ) {
	var translationPair,
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

	translationPair = new TranslationPair( translationData.locale, original, original.context, original.domain );
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
		matchingTranslations = [];

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
			translationPair = new TranslationPair( translationData.locale, original, context, domain, translation, regex );
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

	nodeText = trim( node.text() );

	if ( ! nodeText.length || nodeText.length > 3000 ) {
		return false;
	}

	if ( typeof translationData.stringsUsedOnPage[ nodeText ] !== 'undefined' ) {
		translationPair = findMatchingTranslation( translationData.stringsUsedOnPage[ nodeText ], contextSpecifier, nodeText );
		if ( translationPair ) {
			return translationPair;
		}
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
