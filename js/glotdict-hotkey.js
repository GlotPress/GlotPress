/* global key, glotdict_version, $gp, $gp_editor_options */

/**
 * Add the hotkeys in GlotPress
 * 
 * @returns void
 */
function gd_hotkeys() {
  jQuery($gp.editor.table).off('keydown', 'tr.editor textarea', $gp.editor.hooks.keydown);
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
  key('ctrl+shift+f', function () {
	if (jQuery('.editor:visible .fuzzy').length > 0) {
	  jQuery('.editor:visible .fuzzy').trigger('click');
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
	  s = s.replace(/http&nbsp;:/g, 'http:').replace(/https&nbsp;:/g, 'https:');
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
	$gp.editor.prev();
	return false;
  });
  key('pagedown', function () {
	$gp.editor.next();
	return false;
  });
  key('ctrl+alt+r', function () {
	localStorage.removeItem('gd_language');
	localStorage.removeItem('gd_locales');
	localStorage.removeItem('gd_locales_date');
	location.reload();
	return false;
  });
}
