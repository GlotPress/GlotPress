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
	translation_id_from_row_id: function(row_id) {
		return row_id.split('-')[1];
	},
	show: function(element) {
		var row_id = element.closest('tr').attr('row');
		var editor = $('#editor-' + row_id);
		if (!editor.length) return;
		if ($gp.editor.current) $gp.editor.hide();
		editor.preview = $('#preview-' + row_id);
		editor.row_id = row_id;
		editor.original_id = $gp.editor.original_id_from_row_id(row_id);
		editor.translation_id = $gp.editor.translation_id_from_row_id(row_id);
		$gp.editor.current = editor;
		editor.show();
		editor.preview.hide();
		$('tr:first', $gp.editor.table).hide();
		$('textarea:first', editor).focus();
	},
	prev: function() {
		if (!$gp.editor.current) return;
		//TODO: go to previous page if needed
		var prev = $gp.editor.current.prevAll('tr.editor');
		if (prev.length)
			$gp.editor.show(prev.eq(0));
		else
			$gp.editor.hide();
	},
	next: function() {
		if (!$gp.editor.current) return;
		//TODO: go to next page if needed
		var next = $gp.editor.current.nextAll('tr.editor');
		if (next.length)
			$gp.editor.show(next.eq(0));
		else
			$gp.editor.hide();
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
		$($gp.editor.table).on('click', 'a.edit', $gp.editor.hooks.show)
			.on('dblclick', 'tr.preview td', $gp.editor.hooks.show)
			.on('change', 'select.priority', $gp.editor.hooks.set_priority)
			.on('click', 'a.close', $gp.editor.hooks.hide)
			.on('click', 'a.copy', $gp.editor.hooks.copy)
			.on('click', 'a.discard-warning', $gp.editor.hooks.discard_warning)
			.on('click', 'button.approve', $gp.editor.hooks.set_status_current)
			.on('click', 'button.reject', $gp.editor.hooks.set_status_rejected)
			.on('click', 'button.ok', $gp.editor.hooks.ok)
			.on('keydown', 'tr.editor textarea', $gp.editor.hooks.keydown);
		$( '#translations' ).tooltip({
			items: '.glossary-word',
			content: function(){
				var content = $('<ul>');
				$.each( $(this).data('translations'), function( i, e ){
					var def = $('<li>');
					def.append( $('<span>', {text: e.pos }).addClass('pos') );
					def.append( $('<span>', {text: e.translation}).addClass('translation') );
					def.append( $('<span>', {text: e.comment}).addClass('comment') );
					content.append(def);
				});
				return content;
			},
			hide: false,
			show: false
		});
	},
	keydown: function(e) {
		if ( e.keyCode == 27 ) {
			$gp.editor.hide();
		}
		else if ( e.keyCode == 33 ) {
			$gp.editor.prev();
		}
		else if ( e.keyCode == 34 ) {
			$gp.editor.next();
		}
		else if ( e.keyCode == 13 && e.shiftKey ) {
			var target = $(e.target);

			if ( e.altKey && target.val().length == 0 ) {
				var container = target.closest('.textareas').prev();

				if ( container.children() ) {
					target.val( container.find('.original').text() );
				}
				else {
					target.val( container.text() );
				}
			}

			if ( target.nextAll('textarea').length ) {
				target.nextAll('textarea').eq(0).focus();
			}
			else {
				$gp.editor.save(target.parents('tr.editor').find('button.ok'));
			}
		} else {
			return true;
		}

		return false;
	},
	replace_current: function(html) {
		if (!$gp.editor.current) return;
		$gp.editor.current.after(html);
		var old_current = $gp.editor.current;
		old_current.attr('id', old_current.attr('id') + '-old');
		$gp.editor.next();
		old_current.preview.remove();
		old_current.remove();
		$gp.editor.current.preview.fadeIn(800);
	},
	save: function(button) {
		if (!$gp.editor.current) return;
		var editor = $gp.editor.current;
		button.prop('disabled', true);
		$gp.notices.notice('Saving&hellip;');
		name = "translation["+editor.original_id+"][]";
		data = $("textarea[name='"+name+"']", editor).map(function() {
			return name+'='+encodeURIComponent($(this).val());
		}).get().join('&');
		$.ajax({type: "POST", url: $gp_editor_options.url, data: data, dataType: 'json',
			success: function(data){
				button.prop('disabled', false);
				$gp.notices.success('Saved!');
				for(original_id in data) {
					$gp.editor.replace_current(data[original_id]);
				}
				if ($gp.editor.current.hasClass('no-warnings')) {
					$gp.editor.next();
				} else {
					$gp.editor.current.preview.hide();
				}
			},
			error: function(xhr, msg, error) {
				button.prop('disabled', false);
				msg = xhr.responseText? 'Error: '+ xhr.responseText : 'Error saving the translation!';
				$gp.notices.error(msg);
			}
		});
	},
	set_priority: function(select) {
		if (!$gp.editor.current) return;
		var editor = $gp.editor.current;
		select.prop('disabled', true);
		$gp.notices.notice('Setting priority&hellip;');
		data = {priority: $('option:selected', select).attr('value')};
		$.ajax({type: "POST", url: $gp_editor_options.set_priority_url.replace('%original-id%', editor.original_id), data: data,
			success: function(data){
				select.prop('disabled', false);
				$gp.notices.success('Priority set!');
				var new_priority_class = 'priority-'+$('option:selected', select).text();
				$gp.editor.current.addClass(new_priority_class);
				$gp.editor.current.preview.addClass(new_priority_class);
			},
			error: function(xhr, msg, error) {
				button.prop('disabled', false);
				msg = xhr.responseText? 'Error: '+ xhr.responseText : 'Error setting the priority!';
				$gp.notices.error(msg);
			}
		});
	},
	set_status: function(button, status) {
		if (!$gp.editor.current || !$gp.editor.current.translation_id) return;
		var editor = $gp.editor.current;
		button.prop('disabled', true);
		$gp.notices.notice('Setting status to &#8220;'+status+'&#8221;&hellip;');
		var data = {translation_id: editor.translation_id, status: status};

		$.ajax({type: "POST", url: $gp_editor_options.set_status_url, data: data,
			success: function(data){
				button.prop('disabled', false);
				$gp.notices.success('Status set!');
				$gp.editor.replace_current(data);
				$gp.editor.next();
			},
			error: function(xhr, msg, error) {
				button.prop('disabled', false);
				msg = xhr.responseText? 'Error: '+ xhr.responseText : 'Error setting the status!';
				$gp.notices.error(msg);
			}
		});
	},
	discard_warning: function(link) {
		if (!$gp.editor.current) return;
		$gp.notices.notice('Discarding&hellip;');
		data = {translation_id: $gp.editor.current.translation_id, key: link.attr('key'), index: link.attr('index')};
		$.ajax({type: "POST", url: $gp_editor_options.discard_warning_url, data: data,
			success: function(data) {
				$gp.notices.success('Saved!');
				$gp.editor.replace_current(data);
			},
			error: function(xhr, msg, error) {
				msg = xhr.responseText? 'Error: '+ xhr.responseText : 'Error saving the translation!';
				$gp.notices.error(msg);
			}
		});
	},
	copy: function(link) {
		var original_text = link.parents('.textareas').prev().find('.original');
		if ( ! original_text.hasClass('original') ) {
			original_text = link.parents('.strings').find('.original').last();
		}
		original_text = original_text.text();
		original_text = original_text.replace(/<span class=.invisibles.*?<\/span>/g, '');
		a = link.parents('.textareas').find('textarea').val(original_text).focus();
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
		keydown: function(e) {
			return $gp.editor.keydown(e);
		},
		copy: function() {
			$gp.editor.copy($(this));
			return false;
		},
		discard_warning: function() {
			$gp.editor.discard_warning($(this));
			return false;
		},
		set_status_current: function() {
			$gp.editor.set_status($(this), 'current');
			return false;
		},
		set_status_rejected: function() {
			$gp.editor.set_status($(this), 'rejected');
			return false;
		},
		set_priority: function() {
			$gp.editor.set_priority($(this));
			return false;
		}
	}
}}(jQuery);

jQuery(function($) {
	$gp.editor.init($('#translations'));
});
