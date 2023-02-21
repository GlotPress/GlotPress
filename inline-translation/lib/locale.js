/**
 * Locale module
 */

/**
 * External dependencies
 */
var Jed = require( 'jed' );

function Locale( localeCode, languageName, pluralForms ) {
	var getPluralIndex = Jed.PF.compile( pluralForms ),
		nplurals_re = /nplurals\=(\d+);/,
		nplurals_matches = pluralForms.match( nplurals_re ),
		numberOfPlurals = 2;

	// Find the nplurals number
	if ( nplurals_matches.length > 1 ) {
		numberOfPlurals = nplurals_matches[ 1 ];
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
				if ( getPluralIndex( number ) == index ) {
					numbers.push( number );
					if ( numbers.length >= howMany ) {
						break;
					}
				}
			}
			return numbers;
		}
	};
}

module.exports = Locale;
