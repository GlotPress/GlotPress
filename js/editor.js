$gp.editor = function($){ return {
	current: null,
	init: function(table) {
		$gp.init();
		$gp.editor.table = table;
		$gp.editor.install_hooks();
	},
	show: function(original_id) {
		if ($gp.editor.current) $gp.editor.hide();
		var editor = $('#editor-' + original_id);
		editor.preview = $('#preview-' + original_id);
		editor.original_id = original_id;
		$gp.editor.current = editor;
		$('#tabs-'+original_id).tabs({
			show: function(event, ui) { $('textarea:first', $(ui.panel)).focus(); return false; }
		});
		$('a.close', editor).click($gp.editor.hooks.hide);
		$('button.ok', editor).click($gp.editor.hooks.ok);
		editor.show();		
		editor.preview.hide();
		$('textarea:first', editor).focus();
	},
	hide: function(editor) {
		editor = editor? editor : $gp.editor.current;
		if (!editor) return;
		editor.hide();
		editor.preview.show();
		$gp.editor.current = null;
	},
	install_hooks: function() {
		$('a.edit', $gp.editor.table).click($gp.editor.hooks.show_by_original_attr);
		$('tr.preview', $gp.editor.table).dblclick($gp.editor.hooks.show_by_original_attr);		
	},
	update_preview: function() {
		if (!$gp.editor.current) return;
		$('td.translation', $gp.editor.current.preview).text($('textarea:first', $gp.editor.current).val());
	},
	save: function() {
		if (!$gp.editor.current) return;
		var editor = $gp.editor.current;
		//TODO: concurrent events will confuse the notice system
		$gp.notices.notice('Saving...');
		name = "translation["+editor.original_id+"][]";
		data = $("textarea[name='"+name+"']", editor).map(function() {
			return name+'='+encodeURIComponent($(this).val());
		}).get().join('&');
		$.ajax({type: "POST", url: '', data: data,
			success: function(msg){
				$gp.notices.success('Saved!');
			},
			error: function(xhr, msg, o){ $gp.notices.error(msg); },
		});
		$gp.editor.update_preview();
		$gp.editor.hide();
		//TODO: go to next untranslated, or at least next
	},
	hooks: {
		show_by_original_attr: function() {
			var original_id = $(this).attr('original');
			$gp.editor.show(original_id);
			return false;
		},
		hide: function() {
			$gp.editor.hide();
			return false;
		},
		ok: function() {
			$gp.editor.save();
			return false;
		},
	}
}}(jQuery);

jQuery(function($) {
	$gp.editor.init($('#translations'));
});