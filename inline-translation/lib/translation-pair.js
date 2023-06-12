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

function TranslationPair( locale, original, context, domain, usedTranslationText ) {
	var translations = [],
		translation = usedTranslationText,
		entry, selectedTranslation, glotPressProject,
		regex = null,
		screenText = false;

	if ( 'object' === typeof original && 'number' === typeof original.original_id ) {
		entry = original;

		setGlotPressProject( entry.project );

		original = new Original( {
			singular: original.singular,
			plural: original.plural,
			domain: original.domain,
			context: original.context,
			originalId: original.original_id,
			comment: original.original_comment,
		} );
	}

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
			if ( typeof regex !== 'undefined' && regex ) {
				return regex;
			}
			regex = selectedTranslation.getTextItems().map( function( item ) {
				return getRegexString( item.getText() );
			} ).join( '|' );
			regex = new RegExp( '^\\s*' + regex + '\\s*$' );
			return regex;
		},
		getReplacementText: function( oldText ) {
			var replacementTranslation = this.getTranslation().getTextItems()[ 0 ].getText(),
				c = 0,
				matches,
				nodeText = oldText.split( /\u200b/ );
			matches = nodeText[ 1 ].match( getRegexString( usedTranslationText ) );
			if ( null !== matches ) {
				nodeText[ 1 ] = replacementTranslation.replace( /%(?:(\d)\$)?[sd]/g, function() {
					++c;
					return matches[ typeof arguments[ 1 ] === 'undefined' ? c : Number( arguments[ 1 ] ) ];
				} );
			}

			return nodeText.join( '\u200b' );
		},
		getOriginalRegexString: function() {
			var regexString;
			regexString = getRegexString( original.getSingular() );
			if ( original.getPlural() ) {
				regexString += '|' + getRegexString( original.getSingular() );
			}
			return regexString;
		},
		setScreenText: function( _screenText ) {
			screenText = _screenText.replace( /&amp;/g, '&' );
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
			if ( entry ) {
				loadTranslations( entry.translations );
				setSelectedTranslation( currentUserId );
				promise = new jQuery.Deferred();
				promise.resolve();
				return promise;
			}
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
	regexString = regexString.replace( /&/g, '&(?:amp;)?' );
	regexString = regexString.replace( /&\(\?:amp;\)\?amp;/g, '&(?:amp;)?' );
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

function extractWithUtf8Tags( enclosingNode ) {
	var translationPair, id, nodeText, j, original;

	nodeText = enclosingNode.html().split( /\u200b/ )[ 1 ];
	if ( undefined === nodeText ) {
		return false;
	}
	id = '';
	for ( j = 0; j < nodeText.length; j++ ) {
		if ( '\udc7f' === nodeText.charAt( j ) ) {
			break;
		}
		switch ( nodeText.charAt( j ) ) {
			case '\udc30': id += '0'; break;
			case '\udc31': id += '1'; break;
			case '\udc32': id += '2'; break;
			case '\udc33': id += '3'; break;
			case '\udc34': id += '4'; break;
			case '\udc35': id += '5'; break;
			case '\udc36': id += '6'; break;
			case '\udc37': id += '7'; break;
			case '\udc38': id += '8'; break;
			case '\udc39': id += '9'; break;
		}
	}
	nodeText = nodeText.substr( id.length + 1 );
	if ( ! id.length ) {
		return false;
	}
	id = parseInt( id, 10 );
	if ( typeof translationData.translations[ id ] !== 'undefined' ) {
		original = translationData.translations[ id ];
		translationPair = new TranslationPair( translationData.locale, original, original.context, original.domain, original.translation );
		translationPair.setScreenText( nodeText );
		return translationPair;
	}

	return false;
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

	return extractWithUtf8Tags( enclosingNode );
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
