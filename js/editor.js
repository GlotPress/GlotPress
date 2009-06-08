$gp.editor = function($){ return {
	current: null,
	init: function(table) {
		$gp.init();
		$gp.editor.table = table;
		$gp.editor.install_hooks();
	},
	show: function(element) {
		var original_id = element.attr('original');
		var editor = $('#editor-' + original_id);
		if (!editor.length) return;
		if ($gp.editor.current) $gp.editor.hide();
		editor.preview = $('#preview-' + original_id);
		editor.original_id = original_id;
		$gp.editor.current = editor;
		$('a.close', editor).click($gp.editor.hooks.hide);
		$('button.ok', editor).click($gp.editor.hooks.ok);
		$('a.copy').click($gp.editor.hooks.copy);
		editor.show();
		editor.preview.hide();
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
		// TODO: is the next status always current?
		$.each( $.grep(p.attr('class').split(' '), function(x) {
			return x.substr(0, 7) == 'status-';
		}), function(status) {
			p.removeClass(status);
		} );
		p.addClass('status-current');
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
		// TODO: next untranslated
		$gp.editor.next();
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
			$gp.editor.save();
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