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

jQuery("<style type='text/css'>.has-glotdict td:first-child,.has-glotdict th:first-child,.box.has-glotdict{border-left-width: 2px !important;border-left-color: blue !important;}</style>").appendTo("head");
jQuery("<div class='box has-glotdict'></div><div>Contain a Glossary term</div>").appendTo("#legend");

  jQuery('.glossary-word').each(function () {
	var $this = jQuery(this);
	var line = $this.parent().parent().parent().parent().attr('row');
	jQuery('#preview-' + line).addClass('has-glotdict');
	$this.wrap('<a target="_blank" href="https://translate.wordpress.org/consistency?search=' + $this.text() + '&amp;set=' + gd_get_lang_consistency() + '%2Fdefault"></a>');
  });