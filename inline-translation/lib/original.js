/**
 * Original module
 */
var Translation = require( './translation' );

function Original( original ) {
	var singular,
		plural = null,
		comment = null,
		originalId = null,
		glossaryMarkup = null;

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
				glossaryMarkup = data.singular_glossary_markup;
			} );
		},
		getId: function() {
			return originalId;
		},
		getComment: function() {
			return comment;
		},
		getGlossaryMarkup: function() {
			return glossaryMarkup;
		},
	};
}

module.exports = Original;
