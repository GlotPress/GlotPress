jQuery(function($) {
	next_editor = function(current_editor) {
		
	},
	show_editor = function() {
		var original_id = $(this).attr('original');
		var editor = $('#editor-' + original_id);
		var preview = $('#preview-' + original_id);
		$('#tabs-'+original_id).tabs({
			show: function(event, ui) { $('textarea:first', $(ui.panel)).focus(); return false; }
		});
		var hide_editor = function() {
			editor.hide();
			preview.show();
		}
		$('a.close', editor).click(hide_editor);
		$('button.ok', editor).click(next_editor);
		editor.show();		
		preview.hide();
		$('textarea:first', editor).focus();
	};
	$('a.edit').click(show_editor);
	$('tr.preview').dblclick(show_editor);
});