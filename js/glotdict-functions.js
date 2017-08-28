/* global glotdict_version, $gp, $gp_editor_options */

/**
 * Saniitize the value striping html
 * @param {string} value
 * @returns {string} sanitized
 */
function sanitize_value(value) {
  if (typeof value.replace === 'function') {
	return value.replace(/<![\s\S]*?--[ \t\n\r]*>/gi, '');
  }
  return value;
}

/**
 * Get the today with the format dd/mm/yyyy used for the update daily check
 * 
 * @returns String
 */
function gd_today() {
  var today = new Date();
  var todayn = today.getDate();
  if (todayn.length === 1) {
	todayn = '0' + todayn;
  }
  var monthn = today.getMonth() + 1;
  if (monthn.length === 1) {
	monthn = '0' + monthn;
  }
  return todayn + '/' + monthn + '/' + today.getFullYear();
}

/**
 * Get the the list of locales cached
 * 
 * @returns Array
 */
function gd_list_locales_cached() {
  var value = localStorage.getItem('gd_locales');
  if (value === '' || value === "undefined") {
	value = gd_locales();
  } else {
	value = JSON.parse(value);
  }
  if (typeof value === 'string') {
	value = JSON.parse(value);
  }
  return value;
}

/**
 * Get the list of locales avalaible
 * 
 * @returns Array
 */
function gd_locales() {
  window.glotdict_locales = ['ast', 'bel', 'bg_BG', 'cy', 'da_DK', 'de_DE', 'en_AU', 'en_CA', 'en_GB', 'es_ES', 'fi', 'fr_FR', 'he_IL', 'hi_IN', 'hr_HR', 'it_IT', 'ja', 'lt_LT', 'lv_LV', 'nl_BE', 'nl_NL', 'pt_BR', 'ro_RO', 'sv_SE', 'th', 'tr_TR', 'uk'];
  var locales_date_cache = localStorage.getItem('gd_locales_date');
  if (locales_date_cache === null || locales_date_cache !== gd_today()) {
	jQuery.ajax({
	  url: 'https://codeat.co/glotdict/dictionaries/' + glotdict_version + '.json',
	  dataType: 'text',
	  cache: false
	}).done(function (data) {
	  localStorage.setItem('gd_locales', data);
	  window.glotdict_locales = JSON.parse(data);
	  localStorage.setItem('gd_locales_date', gd_today());
	});
  }
  if (locales_date_cache !== null) {
	var temp_value = JSON.parse(localStorage.getItem('gd_locales'));
	if (typeof temp_value === 'string') {
	  temp_value = JSON.parse(temp_value);
	}
	window.glotdict_locales = Object.keys(temp_value);
  }
  return window.glotdict_locales;
}

/**
 * Get the language saved in GlotDict
 * 
 * @returns string
 */
function gd_get_lang() {
  if (typeof window.glotdict_lang === 'undefined') {
	var lang = localStorage.getItem('gd_language');
	if (lang === '' || lang === null) {
	  return '';
	}
	window.glotdict_lang = sanitize_value(lang);
  }
  return window.glotdict_lang;
}

/**
 * Add links for Translation global status and Language projects archive
 * @returns void
 */
function gd_add_project_links() {
  if (jQuery('.gp-content .breadcrumb li:last-child a').length > 0) {
	var lang = jQuery('.gp-content .breadcrumb li:last-child a').attr('href').split('/');
	lang = sanitize_value(lang[lang.length - 2]);
	jQuery('.gp-content').prepend('<a style="float:right" href="https://translate.wordpress.org/locale/' + lang + '/default">' + jQuery('.gp-content .breadcrumb li:last-child a').text() + ' Projects to Translate</a>');
	jQuery(jQuery('.gp-content h2')[0]).prepend('<a class="glossary-link" style="float:right;padding-left:5px;margin-left:5px;border-left: 1px solid black;" href="https://translate.wordpress.org/stats">Translation Global Status</a>');
  }
}

/**
 * Add the button to scroll to the row of the language choosen
 * @returns void
 */
function gd_add_button() {
  if (jQuery('title').text().substring(0, 27) === 'Translation status overview') {
	jQuery('.gp-content').prepend('<button style="float:right" class="gd_scroll">Scroll to ' + gd_get_lang() + '</button>');
	jQuery('.gd_scroll').on('click', function () {
	  var row = jQuery("#stats-table tr th a:contains('" + gd_get_lang() + "')");
	  row.html('<b>&nbsp;&nbsp;&nbsp;' + row.text() + '</b>');
	  jQuery('html, body').animate({scrollTop: row.offset().top - 50});
	});
  }
  if (jQuery('body.logged-in').length !== 0) {
	jQuery('.filters-toolbar:first div:last').append(' <input class="button gd-review" value="Review" type="button">');
  }
}

/**
 * Print the locales selector
 * 
 * @returns void
 */
function gd_locales_selector() {
  var lang = gd_get_lang();
  jQuery('.filters-toolbar:last div:first').append('<span class="separator">•</span><label for="gd-language-picker">Pick locale: </label><select id="gd-language-picker" class="glotdict_language"></select>');
  jQuery('.glotdict_language').append(jQuery('<option></option>'));
  var gd_locales_array = gd_locales();
  jQuery.each(gd_locales_array, function (key, value) {
	var new_option = jQuery('<option></option>').attr('value', value).text(value);
	if (lang === value) {
	  new_option.attr('selected', true);
	}
	jQuery('.glotdict_language').append(new_option);
  });
  if (lang === '' || lang === false) {
	jQuery('.filters-toolbar:last div:first').append('<h3 style="background-color:#ddd;padding:4px;width:130px;display:inline;margin-left:4px;color:red;">&larr; Set the glossary!</h3>')
			.append('<br><h2 style="background-color:#fff;padding:0;display:block;text-align:center;margin-top: 6px;">Welcome to GlotDict! Discover the features and the hotkeys on the <a href="https://github.com/Mte90/GlotDict/blob/master/README.md#features" target="_blank">Readme</a>.</h2>');
	return;
  }
}

/**
 * Add a border and a legend for old strings (at least 6 months)
 * 
 * @returns void
 */
function gd_mark_old_strings() {
  jQuery('tr.preview').each(function () {
	var id = jQuery(this).attr('row');
	var date_timestamp = Date.parse(jQuery('#editor-' + id + ' .meta dd:eq(1)').html().slice(0, -3).trim());
	date_timestamp = new Date(date_timestamp);
	var today = new Date();
	var months = today.getMonth() - date_timestamp.getMonth() + (12 * (today.getFullYear() - date_timestamp.getFullYear()));
	if (months > 6) {
	  jQuery(this).addClass('has-old-string');
	}
  });
}

/**
 * Highlight in preview the non-breaking-space
 * https://github.com/GlotPress/GlotPress-WP/issues/801
 * 
 * @returns {void}
 */
function gd_non_breaking_space_highlight() {
  if (!gd_get_setting('no_non_breaking_space')) {
	jQuery('tr.preview > td.translation.foreign-text').each(function () {
	  var translation_item = jQuery(this).text();
	  if (translation_item.indexOf(' ') > -1) {
		var translation_highlighted = '';
		for (var i = 0; i < translation_item.length; i++) {
		  if (translation_item[i] === ' ') {
			translation_highlighted += '<span style="background-color:yellow"> </span>';
		  } else {
			translation_highlighted += translation_item[i];
		  }
		}
		jQuery(this).html(translation_highlighted);
	  }
	});
  }
}

/**
 * Get the language for consistency
 * 
 * @returns string
 */
function gd_get_lang_consistency() {
  var lang = gd_get_lang();
  var reallang = '';
  if (lang === 'pt_BR') {
	reallang = 'pt-br';
  } else if (lang === 'en_CA') {
	reallang = 'en-ca';
  } else {
	reallang = lang.split('_');
	if (typeof reallang[1] !== 'undefined') {
	  reallang = reallang[1].toLowerCase();
	}
  }
  return reallang;
}

/**
 * Check if the string is the same
 * 
 * @param {String} myString
 * @returns {Boolean}
 */
function gd_is_uppercase(myString) {
  return (myString === myString.toUpperCase());
}

/**
 * Stop event propagation
 * 
 * @param {Object} e
 * @returns {void}
 */
function gd_stoppropagation(e) {
  if (typeof e === 'object') {
	e.stopImmediatePropagation();
  }
}

/**
 * Add a layover
 * 
 * @returns {void}
 */
function gd_add_layover() {
  if (jQuery('table#translations').length > 0) {
	jQuery('body').append('<div class="gd-layover"></div>');
	jQuery('.gd-layover').append('<div class="gd-loader"></div>');
  }
}

/**
 * Remove the layover
 * 
 * @returns {void}
 */
function gd_remove_layover() {
  jQuery('.gd-layover').remove();
}