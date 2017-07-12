/* global key, glotdict_version, $gp, pluralize */
'use strict';

var glotdict_version = "1.0.1";

if (jQuery('.filters-toolbar:last div:first').length > 0) {
  gd_hotkeys();
  //Fix for PTE align
  if (jQuery('#bulk-actions-toolbar').length > 0) {
	jQuery('#upper-filters-toolbar').css('clear', 'both');
  }
  if (jQuery('.preview').length === 1) {
	jQuery('.preview .action').trigger('click');
  }

  jQuery("<style type='text/css'>.has-glotdict td:first-child,.has-glotdict th:first-child,.box.has-glotdict{border-left-width: 2px !important;border-left-color: blue !important;}.discard-glotdict{float:right;}</style>").appendTo("head");
  jQuery("<div class='box has-glotdict'></div><div>Contain a Glossary term</div>").appendTo("#legend");

  jQuery('.glossary-word').each(function () {
	var $this = jQuery(this);
	var line = $this.parents().eq(3).attr('row');
	if (typeof line === 'undefined') {
	  line = $this.parents().eq(4).attr('row');
	}
	jQuery('#preview-' + line).addClass('has-glotdict');
	$this.wrap('<a target="_blank" href="https://translate.wordpress.org/consistency?search=' + $this.text() + '&amp;set=' + gd_get_lang_consistency() + '%2Fdefault"></a>');
  });

  gd_locales_selector();

  jQuery($gp.editor.table).onFirst('click', 'button.ok', gd_validate_visible);
}

gd_add_project_links();
gd_add_button();

jQuery('.glotdict_language').change(function () {
  localStorage.setItem('gd_language', jQuery('.glotdict_language option:selected').text());
  localStorage.setItem('gd_glossary_date', '');
  gd_locales();
  location.reload();
});

jQuery('.glossary-word').contextmenu(function (e) {
  var info = jQuery(this).data('translations');
  jQuery('.editor:visible textarea').val(jQuery('.editor:visible textarea').val() + info[0].translation);
  e.preventDefault();
  return false;
});

jQuery('.gp-content').on('click', '.discard-glotdict', function (e) {
  var $this = jQuery(this);
  var row = $this.data('row');
  jQuery('#editor-' + row).data('discard', 'true');
  $this.parent().remove();
  e.preventDefault();
  return false;
});

jQuery('.gp-content').on('click', '.gd-review', function (e) {
  gd_run_review();
});