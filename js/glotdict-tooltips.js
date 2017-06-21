/**
 * Create the tooltip for every terms added
 * 
 * @returns void
 */
function gd_terms_tooltip() {
  var lang = gd_get_lang();
  var plural = '';
  if (lang === false) {
	return false;
  }
  var data = gd_glossary_cached(lang);
  // Loop all the editor string views
  jQuery('.editor .original').each(function () {
	var editor_in_loop = this;
	// Clean from other span
	var editor = jQuery(editor_in_loop).html().replace(/<\/?span[^>]*>/g, "");
	jQuery(editor_in_loop).html(editor);
	jQuery.each(data, function (i, item) {
	  if (i !== '&' && i !== '') {
		gd_add_term_json(i, editor_in_loop, item);
		plural = pluralize.plural(i);
		if (plural !== i && !Array.isArray(data[plural])) {
		  gd_add_term_json(plural, editor_in_loop, item);
		}
	  }
	});
  });
  jQuery('.editor .original .glossary-word-glotdict').css({'cursor': 'help', 'border-bottom': '1px dashed'});
  // Generate the HTML code for the tooltips
  jQuery('.editor').tooltip({
	items: '.editor .original .glossary-word-glotdict',
	content: function () {
	  var content = jQuery('<ul>');
	  jQuery.each(jQuery(this).data('translations'), function (i, e) {
		var def = jQuery('<li>');
		def.append(jQuery('<span>', {text: sanitize_value(e.pos)}).addClass('pos'))
				.append(jQuery('<span>', {text: sanitize_value(e.translation)}).addClass('translation'))
				.append(jQuery('<span>', {text: sanitize_value(e.comment)}).addClass('comment'));
		content.append(def);
	  });
	  return content;
	}
  });
}

/**
 * Get the language for consistency
 * 
 * @returns string
 */
function gd_get_lang_consistency() {
  var lang = gd_get_lang();
  var reallang = ''
  if (lang === 'pt_BR') {
	reallang = 'pt-br';
  } else {
	var reallang = lang.split('_');
	if (typeof reallang[1] !== 'undefined') {
	  reallang = reallang[1].toLowerCase();
	}
  }
  return reallang;
}

/**
 * Add the term in the page with the HTML code compatible with GlotPress
 * 
 * @param {string} word The term.
 * @param {string} element The div box.
 * @param {string} item The glossary term.
 * @returns void
 */
function gd_add_term_json(word, element, item) {
  if (item !== '') {
	word = word.replace(/\)/g, "\\)").replace(/\(/g, "\\(");
	// The first part in [] check for term that don't have at the left that symbol
	// The second search for the term
	// The third like the first part
	var rgxp = new RegExp('(?=^|$|[^\W\"\(\/\-])\\b(' + word + ')\\b(?=^|$|[^\w\"\)\/\-])', 'gi');
	var print = JSON.stringify(item);
	print = print.replace(/\'/g, "").replace(/\"/g, "&quot;");
	if (!item.length) {
	  print = '[' + print + ']';
	}
	var repl = '<a target="_blank" href="https://translate.wordpress.org/consistency?search=$1&amp;set=' + gd_get_lang_consistency() + '%2Fdefault"><span class="glossary-word-glotdict" data-translations="' + print + '">$1</span></a>';
	var content_html = jQuery(element).html();
	var content = content_html.replace(rgxp, repl);
	if (content !== content_html) {
	  if (checkHTML(content)) {
		jQuery('#preview-' + jQuery(element).parents().eq(2).attr('row')).addClass('has-glotdict');
		jQuery(element).html(content);
	  }
	}
  }
}


jQuery("<style type='text/css'>.has-glotdict td:first-child,.has-glotdict th:first-child,.box.has-glotdict{border-left-width: 2px !important;border-left-color: blue !important;}</style>").appendTo("head");
jQuery("<div class='box has-glotdict'></div><div>Contain a Glossary term</div>").appendTo("#legend");

if (!gd_get_setting('use_gp_tooltip')) {
  if (jQuery('.filters-toolbar:last div:first').length > 0) {
	jQuery('.glossary-word').contents().unwrap();

	gd_terms_tooltip();
  }
} else {
  jQuery('.glossary-word').each(function () {
	var $this = jQuery(this);
	var line = $this.parent().parent().parent().parent().attr('row');
	jQuery('#preview-' + line).addClass('has-glotdict');
	$this.wrap('<a target="_blank" href="https://translate.wordpress.org/consistency?search=' + $this.text() + '&amp;set=' + gd_get_lang_consistency() + '%2Fdefault"></a>');
  });
}