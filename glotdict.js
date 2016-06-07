/* global key, glotdict_path */
'use strict';

jQuery(document).ready(function () {
  function gd_add_term(word, element, item) {
	if (item !== '') {
	  var rgxp = new RegExp('\\b(' + word + ')\\b(?![^<>()\"]*>|[^<]*<\/span>)', 'gi');
	  var print = JSON.stringify(item);
	  if (!item.length) {
		print = '[' + print + ']';
	  }
	  var repl = '<span class="glossary-word-glotdict" data-translations=\'' + print + '\'>$1</span>';
	  jQuery(element).html(jQuery(element).html().replace(rgxp, repl));
	}
  }

  function gd_get_lang() {
	var lang = localStorage.getItem('gd_language');
	if (lang === '' || lang === null) {
	  return false;
	}
	return lang;
  }
  
  function gd_today() {
	var today = new Date();
	var dd = today.getDate();
	var mm = today.getMonth() + 1;
	var yyyy = today.getFullYear();
	return dd + '/' + mm + '/' + yyyy;
  }

  function gd_syntax_cache() {
	return JSON.parse(JSON.parse(localStorage.getItem('gd_syntax')));
  }
  
  function gd_locales_cache(lang) {
	if (typeof lang === 'undefined') {
	  return;
	}
	var lang_date_cache = localStorage.getItem('gd_language_date');
	if (lang_date_cache !== gd_today()) {
	  var locales_cache = gd_syntax_cache();
	  if (locales_cache[lang].time === gd_today()) {
		window.gd_cache = JSON.parse(JSON.parse(localStorage.getItem('gd_language_file')));
	  }
	  jQuery.ajax({
		url: 'http://www.mte90.net/glotdict/dictionaries/' + glotdict_version + '/' + lang + '.json',
		dataType: 'text',
		async: false
	  }).done(function (data) {
		localStorage.setItem('gd_language_file', JSON.stringify(data));
		localStorage.setItem('gd_language_date', gd_today());
		window.gd_cache = JSON.parse(data);
	  }).fail(function (xhr, ajaxOptions, thrownError) {
		console.error(thrownError);
		console.log('GlotDict: error on loading ' + gd_get_lang() + '.json');
	  });
	} else {
	  window.gd_cache = JSON.parse(JSON.parse(localStorage.getItem('gd_language_file')));
	}
	return window.gd_cache;
  }
  
  function gd_locales() {
	var locales = ['ast', 'bg_BG', 'de_DE', 'en_AU', 'en_CA', 'es_ES', 'fi', 'fr_FR', 'he_IL', 'hi_IN', 'it_IT', 'ja', 'lt_LT', 'nl_NL', 'pt_BR', 'ro_RO', 'sv_SE', 'th', 'tr_TR'];
	var locales_date_cache = localStorage.getItem('gd_syntax_date');
	var locales_cache = gd_locales_cache();
	if (typeof locales_cache !== 'undefined') {
	  locales = Object.keys(locales_cache).map(function (key) {
		return key;
	  });
	}
	if (locales_date_cache !== gd_today()) {
	  jQuery.ajax({
		url: 'http://www.mte90.net/glotdict/dictionaries/' + glotdict_version + '.json',
		dataType: 'text'
	  }).done(function (data) {
		locales = Object.keys(JSON.parse(data)).map(function (key) {
		  return key;
		});
		localStorage.setItem('gd_syntax', JSON.stringify(data));
		localStorage.setItem('gd_syntax_date', gd_today());
	  }).fail(function (xhr, ajaxOptions, thrownError) {
		console.error(thrownError);
		console.log('GlotDict Syntax: error on loading the Glossary Syntax');
	  });
	}
	return locales;
  }

  function gd_select_language() {
	var lang = localStorage.getItem('gd_language');
	jQuery('.filters-toolbar:last div:first').append('<span class="separator">•</span><label for="gd-language-picker">Pick the glossary: </label><select id="gd-language-picker" class="glotdict_language"></select>');
	jQuery('.glotdict_language').append(jQuery('<option></option>'));
	var gd_locales_array = gd_locales();
	jQuery.each(gd_locales_array, function (key, value) {
	  var new_option = jQuery('<option></option>').attr('value', value).text(value);
	  if (lang === value) {
		new_option.attr('selected', true);
	  }
	  jQuery('.glotdict_language').append(new_option);
	});
	if (lang === '') {
	  jQuery('.filters-toolbar:last div:first').append('<h3 style="background-color:#ddd;padding:4px;width:130px;display: inline;margin-left:4px;">&larr; Set the glossary!</span>');
	  return;
	}
	jQuery('.glossary-word').contents().unwrap();
  }

  function gd_add_terms() {
	var lang = gd_get_lang();
	if (lang === false) {
	  console.log('GlotDict: missing lang');
	  return false;
	}
	var data = gd_locales_cache(lang);
	jQuery('.editor .original').each(function () {
	  var loop_editor = this;
	  jQuery.each(data, function (i, item) {
		if (i !== '&') {
		  gd_add_term(i, loop_editor, item);
		}
	  });
	});
	jQuery('.editor .original .glossary-word-glotdict').css({'cursor': 'help', 'border-bottom': '1px dashed'});
	jQuery('.editor').tooltip({
	  items: '.editor .original .glossary-word-glotdict',
	  content: function () {
		var content = jQuery('<ul>');
		jQuery.each(jQuery(this).data('translations'), function (i, e) {
		  var def = jQuery('<li>');
		  def.append(jQuery('<span>', {text: e.pos}).addClass('pos'));
		  def.append(jQuery('<span>', {text: e.translation}).addClass('translation'));
		  def.append(jQuery('<span>', {text: e.comment}).addClass('comment'));
		  content.append(def);
		});
		return content;
	  }
	});
  }

  function gd_hotkeys() {
	key.filter = function (event) {
	  var tagName = (event.target || event.srcElement).tagName;
	  key.setScope(/^(SELECT)$/.test(tagName) ? 'input' : 'other');
	  return true;
	};
	key('ctrl+enter', function () {
	  if (jQuery('.editor:visible').length > 0) {
		jQuery('.editor:visible .actions button.ok').trigger('click');
	  } else {
		alert('No opened string to add!');
	  }
	  return false;
	});
	key('ctrl+shift+z', function () {
	  if (jQuery('.editor:visible').length > 0) {
		jQuery('.editor:visible .actions a.close').trigger('click');
	  }
	  return false;
	});
	key('ctrl+shift+a', function () {
	  if (jQuery('.editor:visible .meta button.approve').length > 0) {
		jQuery('.editor:visible .meta button.approve').trigger('click');
	  } else {
		alert('No opened string to approve!');
	  }
	  return false;
	});
	key('ctrl+shift+b', function () {
	  if (jQuery('.editor:visible .copy').length > 0) {
		jQuery('.editor:visible .copy').trigger('click');
	  }
	  return false;
	});
	key('ctrl+shift+r', function () {
	  if (jQuery('.editor:visible .meta button.reject').length > 0) {
		jQuery('.editor:visible .meta button.reject').trigger('click');
	  } else {
		alert('No opened string to reject!');
	  }
	  return false;
	});
	key('ctrl+shift+f', function () {
	  jQuery('textarea.foreign-text:visible:first').val(function (index, text) {
		// Replace space-colon or nbsp-colon with just colon, then replace colons with nbsp-colon
		var s = text.replace(/( :|&nbsp;:)/g, ':').replace(/:/g, '&nbsp;:');
		// Fix http and https from the above replace
		s = s.replace(/http&nbsp;:/g, 'http:').replace(/https&nbsp;:/g, 'https:')
		// Replace space-question or nbsp-question with just question, then replace question with nbsp-question
		s = s.replace(/( \?|&nbsp;\?)/g, '?').replace(/\?/g, '&nbsp;?');
		// Replace space-exclamation or nbsp-exclamation with just exclamation, then replace exclamation with nbsp-exclamation
		s = s.replace(/( !|&nbsp;!)/g, '!').replace(/!/g, '&nbsp;!');
		// Replace space-%-space with nbsp-%-space
		s = s.replace(/( % )/g, '&nbsp;% ');
		// Replace space-dot-space or space-dot with just dot-space, same for comma
		s = s.replace(/( \. | \.)/g, '. ').replace(/( , | ,)/g, ', ');
		// Replace space-closebracket-space or space-closebracket with just closebracket-space, same for squarebracket
		s = s.replace(/( \) | \))/g, ') ').replace(/( ] | ])/g, '] ');
		// Replace space-openbracket-space or openbracket-space with just space-openbracket, same for squarebracket
		s = s.replace(/( \( |\( )/g, ' (').replace(/( \[ |\[ )/g, ' [');
		return s;
	  });
	  return false;
	});
	key('pageup', function () {
	  if (jQuery('.editor:visible').length > 0) {
		jQuery('.editor').next().trigger('click');
	  }
	  return false;
	});
	key('pagedown', function () {
	  if (jQuery('.editor:visible').length > 0) {
		jQuery('.editor').prev().trigger('click');
	  }
	  return false;
	});
  }

  if (jQuery('.filters-toolbar:last div:first').length > 0) {
	//Fix for PTE align
	if (jQuery('#bulk-actions-toolbar').length > 0) {
	  jQuery('#upper-filters-toolbar').css('clear', 'both');
	}
	gd_select_language();
	gd_add_terms();
	gd_hotkeys();
  }

  jQuery('.glotdict_language').change(function () {
	localStorage.setItem('gd_language', jQuery('.glotdict_language option:selected').text());
	localStorage.setItem('gd_language_date', '');
	location.reload();
  });
});
