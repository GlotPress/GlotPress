jQuery(function($) {
	$gp.notices.init();
	$('#project_id').change(function() {
		var select = $(this);
		var project_id = $('option:selected', select).attr('value');
		if ( !project_id ) {
			$('#submit').prop('disabled', true);
			$('#preview').hide();
			return;
		}
		$gp.notices.notice($gp_mass_create_sets_options.loading);
		select.prop('disabled', true);
		$.ajax({type: "POST", url: $gp_mass_create_sets_options.url, data: {project_id: project_id}, dataType: 'json',
			success: function(data){
				select.prop('disabled', false);
				$gp.notices.clear();
				if (data.added.length || data.removed.length) $('#submit').prop('disabled', false);
				var preview = $('#preview');
				preview.html('<h3>Preview changes:</h3>');
				var preview_html = '';
				preview_html += '<ul>';
				function preview_html_for(kind, text) {
					var sets = data[kind];
					var html = '';
					html += '<li><span class="'+kind+'">'+text.replace('{count}', sets.length)+'</span>';
					if (sets.length) {
						html += '<ul>';
						$.each(sets, function() {
							html += '<li>'+$gp.esc_html(this.name)+' ('+this.locale+'/'+this.slug+')</li>';
						});
						html += '</ul>';
					}
					html += '</li>';
					return html;
				}
				preview_html += preview_html_for('added', '{count} set(s) will be added');
				preview_html += preview_html_for('removed', '{count} set(s) will be removed');
				preview_html += '</ul>';
				preview.append(preview_html);
				preview.fadeIn();
			},
			error: function(xhr, msg, error) {
				select.prop('disabled', false);
				msg = xhr.responsehtml? 'Error: '+ xhr.responsehtml : 'Error saving the translation!';
				$gp.notices.error(msg);
			}
		});
	});
	$('#submit').prop('disabled', true);
	$('#preview').hide();
});
