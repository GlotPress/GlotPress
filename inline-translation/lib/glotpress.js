/**
 * Community Translation GlotPress module
 */
'use strict';

var batcher = require( './batcher.js' );

function GlotPress( locale, translations ) {
	var server = {
			url: '',
			project: '',
			translation_set_slug: 'default',
		},
		batch = batcher( fetchOriginals );
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
			return !! server.loadSuggestions;
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

		submitTranslation: function( translation, translationPair ) {
			return ajax( {
				url: server.restUrl + '/translation',

				data: {
					project: translationPair.getGlotPressProject(),
					translation_set_slug: server.translation_set_slug,
					locale_slug: locale.getLocaleCode(),
					translation: translation,
				},
			} );
		},

		getSuggestedTranslation: function( translationPair, data ) {
			return ajax( {
				url: server.restUrl + '/suggest-translation',

				data: Object.assign( data || {}, {
					project: translationPair.getGlotPressProject(),
					translation_set_slug: server.translation_set_slug,
					locale: locale.getLocaleCode(),
					localeName: locale.getLanguageName(),
					text: translationPair.getOriginal().objectify(),
				} ),
			} );
		},
	};
}

module.exports = GlotPress;
