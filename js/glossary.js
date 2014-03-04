$gp.glossary = function($){ return {

	current: null,

	init: function(table) {
		$gp.init();
		if ( $gp_glossary_options.can_edit === '' ) {
			return;
		}
		$gp.glossary.table = table;
		$gp.glossary.install_hooks();
	},

	install_hooks: function() {
		$($gp.glossary.table).on('click', 'a.edit', $gp.glossary.hooks.show)
			.on('dblclick', 'tr td', $gp.glossary.hooks.show)
			.on('click', 'a.cancel', $gp.glossary.hooks.hide)
			.on('click', 'button.delete', $gp.glossary.hooks.del)
			.on('click', 'button.save', $gp.glossary.hooks.ok);
	},

	show: function(e, element) {
		e.preventDefault();
		var preview = element.closest('tr');
		var row_id = preview.data('id');
		var editor = $('#editor-' + row_id);
		if (!editor.length) {
			return;
		}
		if ($gp.glossary.current) {
			$gp.glossary.hide();
		}
		editor.preview = preview;
		editor.row_id = row_id;
		$gp.glossary.current = editor;
		editor.addClass( 'active' );
		editor.show();
		editor.preview.hide();
		if ( $('a.add-entry').hasClass('open') ) {
			$('a.add-entry').click();
		}
		$('input:first', glossary).focus();
	},

	hide: function(editor) {
		editor = editor? editor : $gp.glossary.current;
		if (!editor) {
			return;
		}
		editor.hide();
		editor.preview.show();
		editor.removeClass( 'active' );
		$gp.glossary.current = null;
	},

	save: function(button) {
		if (!$gp.glossary.current) {
			return;
		}

		button.prop('disabled', true);
		$gp.notices.notice('Saving&hellip;');

		var editor = $gp.glossary.current;
		var name = "glossary_entry["+editor.row_id+"]";

		var data = $("#editor-" + editor.row_id).find('input, select, textarea').map(function() {
			return $(this).attr('name')+'='+encodeURIComponent($(this).val());
		}).get().join('&');
		$.ajax({type: "POST", url: $gp_glossary_options.url, data: data, dataType: 'json',
			success: function(data){
				button.prop('disabled', false);
				$gp.notices.success('Saved!');
				$gp.glossary.replace_current(data);
			},
			error: function(xhr, msg, error) {
				button.prop('disabled', false);
				msg = xhr.responseText? 'Error: '+ xhr.responseText : 'Error saving the glossary item!';
				$gp.notices.error(msg);
			}
		});
	},

	del: function(e, element) {
		e.preventDefault();
		result = confirm( $gp_glossary_options.ge_delete_ays );
		if ( !result ) {
			return;
		} else {
			var editor = element.closest('tr');
			var preview = editor.prev('tr');
			var row_id = preview.data('id');
			var data = editor.find('input, select, textarea').map(function() {
				return $(this).attr('name')+'='+encodeURIComponent($(this).val());
			}).get().join('&');
			$.ajax({type: "POST", url: $gp_glossary_options.delete_url, data: data,
				success: function(data){
					$gp.notices.success('Deleted!');
					editor.fadeOut('fast', function(){
						this.remove();
					});
					preview.remove();
					if ( $('tr', $gp.glossary.table).length == 1 ) {
						$gp.glossary.table.remove();
					}
				},
				error: function(xhr, msg, error) {
					msg = xhr.responseText? 'Error: '+ xhr.responseText : 'Error deleting the glossary item!';
					$gp.notices.error(msg);
				}
			});
		}
	},

	replace_current: function(html) {
		if (!$gp.glossary.current) {
			return;
		}
		$gp.glossary.current.after(html);
		var old_current = $gp.glossary.current;
		old_current.preview.remove();
		old_current.remove();
		$gp.glossary.current.preview.fadeIn(800);
	},

	hooks: {
		show: function( event ) {
			$gp.glossary.show(event, $(this));
		},
		del: function( event ) {
			$gp.glossary.del(event, $(this));
		},
		hide: function() {
			$gp.glossary.hide();
			return false;
		},
		ok: function() {
			$gp.glossary.save($(this));
			return false;
		}
	}

}}(jQuery);

jQuery(function($) {
	$gp.glossary.init($('#glossary'));
});
