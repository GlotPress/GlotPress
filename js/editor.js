$gp.editor = function($){ return {
	current: null,
	init: function(table) {
		$gp.init();
		$gp.editor.table = table;
		$gp.editor.install_hooks();
	},
	original_id_from_row_id: function(row_id) {
		return row_id.split('-')[0];
	},
	show: function(element) {
		var row_id = element.attr('row');
		var editor = $('#editor-' + row_id);
		if (!editor.length) return;
		if ($gp.editor.current) $gp.editor.hide();
		editor.preview = $('#preview-' + row_id);
		editor.row_id = row_id;
		editor.original_id = $gp.editor.original_id_from_row_id(row_id);
		$gp.editor.current = editor;
		$('a.close', editor).click($gp.editor.hooks.hide);
		$('button.ok', editor).click($gp.editor.hooks.ok);
		$('a.copy').click($gp.editor.hooks.copy);
		editor.show();
		editor.preview.hide();
		$('tr:first', $gp.editor.table).hide();
		$('textarea:first', editor).focus();
	},
	next: function() {
		if (!$gp.editor.current) return;
		//TODO: go to next page if needed
		$gp.editor.show($gp.editor.current.nextAll('tr.editor').eq(0));
	},
	hide: function(editor) {
		editor = editor? editor : $gp.editor.current;
		if (!editor) return;
		editor.hide();
		editor.preview.show();
		$('tr:first', $gp.editor.table).show();
		$gp.editor.current = null;
	},
	install_hooks: function() {
		$('a.edit', $gp.editor.table).click($gp.editor.hooks.show);
		$('tr.preview', $gp.editor.table).dblclick($gp.editor.hooks.show);
	},
	update_preview: function() {
		if (!$gp.editor.current) return;
		var p = $gp.editor.current.preview;
		$('td.translation', p).text($('textarea:first', $gp.editor.current).val());
		$.each( $.grep(p.attr('class').split(' '), function(x) {
			return x.substr(0, 7) == 'status-';
		}), function(status) {
			p.removeClass(status);
		} );
		// TODO: are we using update_preview() only when we add a new translation?
		p.addClass($gp_editor_options.can_approve? 'status-current' : 'status-waiting');
	},
	save: function(button) {
		if (!$gp.editor.current) return;
		var editor = $gp.editor.current;
		// TODO: concurrent events will confuse the notice system
		button.attr('disabled', 'disabled');
		$gp.notices.notice('Saving...');
		name = "translation["+editor.original_id+"][]";
		data = $("textarea[name='"+name+"']", editor).map(function() {
			return name+'='+encodeURIComponent($(this).val());
		}).get().join('&');
		$.ajax({type: "POST", url: $gp_editor_options.url, data: data,
			success: function(msg){
				button.attr('disabled', '');
				$gp.notices.success('Saved!');
				$gp.editor.update_preview();
				$gp.editor.next();
			},
			error: function(xhr, msg, error){
				button.attr('disabled', '');
				msg = xhr.responseText? 'Error: '+xhr.responseText : 'Error saving the translation!';
				$gp.notices.error(msg);
			},
		});
	},
	copy: function(link) {
		original_text = link.parents('.textareas').siblings('.original').html();
		if (!original_text) original_text = link.parents('.textareas').siblings('p:last').children('.original').html();
		link.parent('p').siblings('textarea').html(original_text).focus();
	},
	hooks: {
		show: function() {
			$gp.editor.show($(this));
			return false;
		},
		hide: function() {
			$gp.editor.hide();
			return false;
		},
		ok: function() {
			$gp.editor.save($(this));
			return false;
		},
		copy: function() {
			$gp.editor.copy($(this));
			return false;
		},
	}
}}(jQuery);

jQuery(function($) {
	$gp.editor.init($('#translations'));
});