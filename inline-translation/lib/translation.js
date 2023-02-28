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
			return userId;
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
