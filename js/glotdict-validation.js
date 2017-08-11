/* global key, glotdict_version, $gp, $gp_editor_options */

/**
 * Run the review
 * 
 * @returns void
 */
function gd_run_review() {
  jQuery('tr.preview').each(function () {
	var $preview = jQuery(this);
	var editor = '#editor-' + $preview.attr('row') + ':not(.untranslated)';
	var howmany = gd_validate('', editor);
	if (howmany > 0) {
	  $preview.find('.checkbox').css({'background': 'red'});
	}
  });
}

/**
 * Check if in the translations there aren't the translation suggested
 * 
 * @param {object} e The event.
 * @param {object} selector The selector.
 * 
 * @returns void
 */
function gd_search_glossary_on_translation(e, selector) {
  var howmany = 0;
  if (gd_get_setting('no_glossary_term_check')) {
	return;
  }
  var discard = gd_get_discard_link(selector);
  jQuery(selector).each(function () {
	var $editor = jQuery(this);
	var translation = jQuery('textarea', $editor).val();
	jQuery('.glossary-word', $editor).each(function () {
	  var translations = jQuery(this).data('translations');
	  var reset = '';
	  var term = jQuery(this).html();
	  jQuery(translations).each(function (index) {
		if (translations[index].translation === 'N/A') {
		  return true;
		}
		if (translation.search(new RegExp(translations[index].translation, 'gi')) === -1) {
		  howmany++;
		  reset = reset + '"<b>' + translations[index].translation + '</b>", ';
		}
	  });
	  if (reset !== '') {
		jQuery('.textareas', $editor).prepend(gd_get_warning('The translation is missing of the translation term ' + reset + 'for "<i>' + term + '</i>"', discard));
	  }
	});
  });
  if (howmany !== 0) {
	gd_stoppropagation(e);
  }
  return howmany;
}

/**
 * Validation is good to save time!
 * 
 * @param {object} e The event.
 * @param {string} selector The selector.
 * 
 * @returns void
 */
function gd_validate(e, selector) {
  var howmany = 0;
  if (typeof jQuery(selector).data('discard') === 'undefined') {
	jQuery('.gd-warning', selector).remove();
	howmany += jQuery('.warning:not(.gd-warning)', selector).length;
	howmany += gd_search_glossary_on_translation(e, selector);
	var newtext = jQuery('textarea', selector).val();
	var discard = gd_get_discard_link(selector);
	if (typeof newtext === 'undefined' || newtext === '') {
	  jQuery('.textareas', selector).prepend(gd_get_warning('The translation seems empty!', discard));
	  howmany++;
	  return;
	}
	var originaltext = jQuery('.original', selector).text();
	var lastcharoriginaltext = originaltext.slice(-1);
	var firstcharoriginaltext = originaltext.charAt(0);
	var hellipseoriginaltext = originaltext.slice(-3) === '...';
	var lastcharnewtext = newtext.slice(-1);
	var firstcharnewtext = newtext.charAt(0);
	var last_dot = [';', '.', '!', ':', '、', '。', '؟', '？', '！'];
	if (hellipseoriginaltext) {
	  if (!gd_get_setting('no_final_dot')) {
		if (jQuery('textarea', selector).val().slice(-3) === '...' || lastcharnewtext !== ';' && lastcharnewtext !== '.') {
		  jQuery('.textareas', selector).prepend(gd_get_warning('The translation contains a final <b>...</b> that should be translated as <b><code>&amp;hellip;</code></b>', discard));
		  howmany++;
		}
	  }
	} else {
	  if (!gd_get_setting('no_final_other_dots')) {
		if (jQuery.inArray(lastcharoriginaltext, last_dot) > 0 && jQuery.inArray(lastcharnewtext, last_dot) === -1) {
		  jQuery('.textareas', selector).prepend(gd_get_warning('The translation is missing an ending <b>.</b> or <b>?</b> or <b>!</b>', discard));
		  howmany++;
		}
	  }
	}
	if (!gd_get_setting('no_initial_uppercase')) {
	  if (gd_is_uppercase(firstcharoriginaltext) && !gd_is_uppercase(firstcharnewtext)) {
		jQuery('.textareas', selector).prepend(gd_get_warning('The translation is missing an initial uppercase letter for "<i>' + firstcharnewtext + '</i>"', discard));
		howmany++;
	  }
	}
  }
  if (e === '') {

  }
  if (howmany !== 0) {
	gd_stoppropagation(e);
  }
  return howmany;
}

function gd_validate_visible(e) {
  var selector = '.editor:visible:not(.untranslated)';
  gd_validate(e, selector);
}

/**
 * Get the discard link
 * 
 * @param {String} selector
 * @returns {String}
 */
function gd_get_discard_link(selector) {
  return ' <a href="#" class="discard-glotdict" data-row="' + jQuery(selector).attr('row') + '">Discard</a>';
}

/**
 * Get the warning link
 * 
 * @param {String} text
 * @param {String} discard
 * @returns {String}
 */
function gd_get_warning(text, discard) {
  return '<div class="warning secondary gd-warning"><strong>Warning:</strong> ' + text + '</strong>' + discard + '</div>';
}
