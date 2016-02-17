jQuery(function() {
   jQuery('table.translations')
        .on('click', 'a.edit', gd_add_terms)
        .on('dblclick', 'tr td', gd_add_terms);
        
    function gd_add_terms(e) {
        e.preventDefault();
	var row_id = jQuery(this).closest('tr').attr('row');
	var editor = jQuery('#editor-' + row_id);
	if (!editor.length) {
		return;
	}
	var editor_original = jQuery('#editor-' + row_id + ' .original');
	console.log(editor_original);
    }
});