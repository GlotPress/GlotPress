$gp.google_translate = function($){ return {
	current: null,
	init: function(table) {
		$gp.init();
		$gp.google_translate.table = table;
		$gp.google_translate.install_hooks();
	},
	install_hooks: function() {
		$($gp.google_translate.table).on('click', 'a.gtranslate', $gp.google_translate.hooks.google_translate)
	},
	google_translate: function(link) {
		original_text = link.parents('.textareas').siblings('.original').text();
		if (!original_text) original_text = link.parents('.textareas').siblings('p:last').children('.original').html();
		if (!original_text) return;

		$gp.notices.notice('Translating via Google Translate&hellip;');

		var url = 'https://www.googleapis.com/language/translate/v2?key=' + gp_google_translate.key + '&q=' + original_text + '&source=en&target=' + gp_google_translate.locale;

		$.getJSON( url, function(result) {
			if (!result.error) {
				var translated_text = result.data.translations[0].translatedText;

				// fix common google translate misbehaviours
				translated_text = translated_text.replace(/% (s|d)/gi, function(m, letter) {
					return '%'+letter.toLowerCase();
				});
				translated_text = translated_text.replace(/% (\d+) \$ (S|D)/gi, function(m, number, letter) {
					return '%'+number+'$'+letter.toLowerCase();
				});
				translated_text = translated_text.replace(/&lt;\/\s+([A-Z]+)&gt;/g, function(m, tag) {
					return '&lt;/'+tag.toLowerCase()+'&gt;';
				});

				link.parent('p').siblings('textarea').html(translated_text).focus();
				$gp.notices.success('Translated!');
			}
			else {
				$gp.notices.error('Error in translating via Google Translate: ' + result.error.message + ': ' + result.error.reason + '');
				link.parent('p').siblings('textarea').focus();
			}
		}).fail(function( jqxhr, textStatus, error ) {
			var result = jQuery.parseJSON( jqxhr.responseText );

			$gp.notices.error('Error in translating via Google Translate: ' + result.error.message );
			link.parent('p').siblings('textarea').focus();
		});
	},
	hooks: {
		google_translate: function() {
			$gp.google_translate.google_translate($(this));
			return false;
		}
	}
}}(jQuery);

if (typeof google != 'undefined') google.load("language", "1");

jQuery(function($) {
	$gp.google_translate.init($('#translations'));
});
