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
  
  function gd_select_language() {
	jQuery('.filters-toolbar:last div:first').append('<span class="separator">•</span><label for="gd-language-picker">Pick the glossary: </label><select id="gd-language-picker" class="glotdict_language"></select>');
	jQuery('.glossary-word').contents().unwrap();
	var lang = localStorage.getItem('gd_language');
	jQuery('.glotdict_language').append(jQuery('<option></option>'));
	jQuery.each(['ast', 'bg_BG', 'de_DE', 'en_AU', 'en_CA', 'es_ES', 'fi', 'fr_FR', 'he_IL', 'hi_IN', 'it_IT', 'ja', 'lt_LT', 'nl_NL', 'ro_RO', 'sk_SK', 'sv_SE', 'th', 'tr_TR'], function (key, value) {
	  var new_option = jQuery('<option></option>').attr('value', value).text(value);
	  if (lang === value) {
		new_option.attr('selected', true);
	  }
	  jQuery('.glotdict_language').append(new_option);
	});
	if (lang === '') {
	  jQuery('.filters-toolbar:last div:first').append('<h3 style="background-color:#ddd;padding:4px;width:130px;display: inline;margin-left:4px;">&larr; Set the glossary!</span>');
	}
	jQuery('.glotdict_language').change(function () {
	  localStorage.setItem('gd_language', jQuery('.glotdict_language option:selected').text());
	  location.reload();
	});
  }

  function gd_add_terms() {
	var lang = gd_get_lang();
	if (lang === false) {
	  console.log('GlotDict: missing lang');
	  return false;
	}
	jQuery.ajax({
	  url: glotdict_path + '/' + lang + '.json',
	  dataType: 'text'
	}).done(function (data) {
	  data = JSON.parse(data);
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
	}).fail(function (xhr, ajaxOptions, thrownError) {
	  console.error(thrownError);
	  console.log('GlotDict: error on loading ' + gd_get_lang() + '.json');
	});
  }

  function gd_hotkeys() {
	key.filter = function (event) {
	  var tagName = (event.target || event.srcElement).tagName;
	  key.setScope(/^(SELECT)$/.test(tagName) ? 'input' : 'other');
	  return true;
	}
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
});

