/* global key, glotdict_version, $gp, pluralize */
'use strict';

var glotdict_version = "1.0.1";

if (jQuery('.filters-toolbar:last div:first').length > 0) {
  gd_hotkeys();
  //Fix for PTE align
  if (jQuery('#bulk-actions-toolbar').length > 0) {
	jQuery('#upper-filters-toolbar').css('clear', 'both');
	gd_add_column();
	jQuery('#bulk-actions-toolbar').clone().css('float', 'none').insertBefore('#legend');
	jQuery('form.filters-toolbar.bulk-actions').submit(function () {
	  var row_ids = jQuery('input:checked', jQuery('table#translations th.checkbox')).map(function () {
		return jQuery(this).parents('tr.preview').attr('row');
	  }).get().join(',');
	  jQuery('input[name="bulk[row-ids]"]', jQuery(this)).val(row_ids);
	});
  }
  if (jQuery('.preview').length === 1) {
	jQuery('.preview .action').trigger('click');
  }

  jQuery("<style type='text/css'>.has-glotdict td:first-child,.has-glotdict th:first-child,.box.has-glotdict{border-left-width: 2px !important;border-left-color: blue !important;}.has-old-string td:last-child,.has-old-string th:last-child,.box.has-old-string{border-right-width: 2px !important;border-right-color: black !important;}.discard-glotdict{float:right;}</style>").appendTo("head");
  jQuery("<div class='box has-glotdict'></div><div>Contain a Glossary term</div><div class='box has-old-string'></div><div>The string is at least 6 months old</div>").appendTo("#legend");

  jQuery('.glossary-word').each(function () {
	var $this = jQuery(this);
	var line = $this.parents().eq(3).attr('row');
	if (typeof line === 'undefined') {
	  line = $this.parents().eq(4).attr('row');
	}
	jQuery('#preview-' + line).addClass('has-glotdict');
	$this.wrap('<a target="_blank" href="https://translate.wordpress.org/consistency?search=' + $this.text() + '&amp;set=' + gd_get_lang_consistency() + '%2Fdefault"></a>');
  });
  
  gd_mark_old_strings();

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
  if (jQuery('#editor-' + row + ' .gd-warning').length === 0) {
	jQuery.removeData('#editor-' + row, 'discard');
  }
  e.preventDefault();
  return false;
});

jQuery('.gp-content').on('click', '.gd-review', function (e) {
  jQuery(this).val('Review in progress');
  gd_run_review();
  jQuery(this).removeClass('gd-review').addClass('gd-review-done');
});

jQuery('.gp-content').on('click', '.gd-review-done', function (e) {
  alert('For a new Review or stop the review you need a refresh of the page!');
});

gd_wait_table_alter();