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
