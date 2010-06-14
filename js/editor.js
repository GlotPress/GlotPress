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
		var row_id = element.attr('row');
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
		$('a.edit', $gp.editor.table).live('click', $gp.editor.hooks.show);
		$('a.close', 'tr.editor').live('click', $gp.editor.hooks.hide);
		$('a.copy', 'tr.editor').live('click', $gp.editor.hooks.copy);
		$('a.gtranslate', 'tr.editor').live('click', $gp.editor.hooks.google_translate);
		$('a.discard-warning', 'tr.editor').live('click', $gp.editor.hooks.discard_warning);
		$('button.ok', 'tr.editor').live('click', $gp.editor.hooks.ok);
		$('tr.preview', $gp.editor.table).live('dblclick', $gp.editor.hooks.show);
		$('select.priority', $gp.editor.table).live('change', $gp.editor.hooks.set_priority);
	},
	replace_current: function(html) {
		if (!$gp.editor.current) return;
		$gp.editor.current.after(html);
		var old_current = $gp.editor.current;
		$gp.editor.next();
		old_current.preview.remove();
		old_current.remove();
		$gp.editor.current.preview.fadeIn(800);
	},
	save: function(button) {
		if (!$gp.editor.current) return;		
		var editor = $gp.editor.current;
		button.attr('disabled', 'disabled');
		$gp.notices.notice('Saving&hellip;');
		name = "translation["+editor.original_id+"][]";
		data = $("textarea[name='"+name+"']", editor).map(function() {
			return name+'='+encodeURIComponent($(this).val());
		}).get().join('&');
		$.ajax({type: "POST", url: $gp_editor_options.url, data: data, dataType: 'json',
			success: function(data){
				button.attr('disabled', '');
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
				button.attr('disabled', '');
				msg = xhr.responseText? 'Error: '+ xhr.responseText : 'Error saving the translation!';
				$gp.notices.error(msg);
			}
		});
	},
	set_priority: function(select) {
		if (!$gp.editor.current) return;
		var editor = $gp.editor.current;
		select.attr('disabled', 'disabled');
		$gp.notices.notice('Setting priority&hellip;');
		data = {priority: $('option:selected', select).attr('value')};
		$.ajax({type: "POST", url: $gp_editor_options.set_priority_url.replace('%original-id%', editor.original_id), data: data,
			success: function(data){
				select.attr('disabled', '');
				$gp.notices.success('Priority set!');
				var new_priority_class = 'priority-'+$('option:selected', select).text();
				$gp.editor.current.addClass(new_priority_class);
				$gp.editor.current.preview.addClass(new_priority_class);
			},
			error: function(xhr, msg, error) {
				msg = xhr.responseText? 'Error: '+ xhr.responseText : 'Error setting the priority!';
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
		original_text = link.parents('.textareas').siblings('.original').html();
		if (!original_text) original_text = link.parents('.textareas').siblings('p:last').children('.original').html();
		original_text = original_text.replace(/<span class=.invisibles.*?<\/span>/g, '');
		link.parent('p').siblings('textarea').html(original_text).focus();
	},
	google_translate: function(link) {
		original_text = link.parents('.textareas').siblings('.original').html();
		if (!original_text) original_text = link.parents('.textareas').siblings('p:last').children('.original').html();
		if (!original_text) return;
		if (typeof google == 'undefined') {
			$gp.notices.error('Couldn&#8217;t load Google Translate library!');
			return;
		}
		$gp.notices.notice('Translating via Google Translate&hellip;');		
		google.language.translate({text: original_text, type: 'html'}, 'en', $gp_editor_options.google_translate_language, function(result) {
			if (!result.error) {
				// fix common google translate misbehaviours
				result.translation = result.translation.replace(/% (s|d)/gi, function(m, letter) {
					return '%'+letter.toLowerCase();
				});
				result.translation = result.translation.replace(/% (\d+) \$ (S|D)/gi, function(m, number, letter) {
					return '%'+number+'$'+letter.toLowerCase();
				});
				result.translation = result.translation.replace(/&lt;\/\s+([A-Z]+)&gt;/g, function(m, tag) {
					return '&lt;/'+tag.toLowerCase()+'&gt;';
				});

				link.parent('p').siblings('textarea').html(result.translation).focus();
				$gp.notices.success('Translated!');
			} else {
				$gp.notices.error('Error in translating via Google Translate: '+result.message+'!');
				link.parent('p').siblings('textarea').focus();
			}
		});		
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
		google_translate: function() {
			$gp.editor.google_translate($(this));
			return false;
		},
		discard_warning: function() {
			$gp.editor.discard_warning($(this));
			return false;
		},
		set_priority: function() {
			$gp.editor.set_priority($(this));
			return false;
		}
	}
}}(jQuery);

if (typeof google != 'undefined') google.load("language", "1");

jQuery(function($) {
	$gp.editor.init($('#translations'));
});
