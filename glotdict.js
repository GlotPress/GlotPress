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
        add_term('saved', '#editor-' + row_id + ' .original');
    }
    
    function add_term(word, element) {
        element = document.querySelector(element);
        var rgxp = new RegExp(word, 'g');
        var repl = "<span class='glossary-word' data-translations=\"[{'translation':'salvato','pos':'noun','comment':''}]\">" + word + '</span>';
        element.innerHTML = element.innerHTML.replace(rgxp, repl);
    }
});