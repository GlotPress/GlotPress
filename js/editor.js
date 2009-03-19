var $gp = function($) { return {
	error_element: 'gp-js-error',
	has_error: false,
	
	error: function(message) {
		var e = $('#'+$gp.error_element);
		e.html(message).css('left', ($(document).width() - e.width()) / 2).show();
		$gp.has_error = true;
	}	
}}(jQuery);

$gp.editor = function($){ return {
	current: null,
	show: function() {
		var original_id = $(this).attr('original');
		var editor = $('#editor-' + original_id);
		editor.preview = $('#preview-' + original_id);
		$gp.editor.current = editor;
		$('#tabs-'+original_id).tabs({
			show: function(event, ui) { $('textarea:first', $(ui.panel)).focus(); return false; }
		});
		$('a.close', editor).click($gp.editor.hide);
		$('button.ok', editor).click();
		editor.show();		
		editor.preview.hide();
		$('textarea:first', editor).focus();
	},
	hide: function() {
		current = $gp.editor.current;
		if (!current) return;
		current.hide();
		current.preview.show();
	},
	install_hooks: function(table) {
		$('a.edit', table).click($gp.editor.show);
		$('tr.preview', table).dblclick($gp.editor.show);		
	}
}}(jQuery);

jQuery(function($) {
	$gp.editor.install_hooks($('#translations'));
});