/* global $gp_mass_create_sets_options, $gp */
/* eslint camelcase: "off" */
jQuery( function( $ ) {
	$gp.notices.init();
	$( '#project_id' ).change( function() {
		var select = $( this );
		var project_id = $( 'option:selected', select ).attr( 'value' );
		if ( ! project_id ) {
			$( '#submit' ).prop( 'disabled', true );
			$( '#preview' ).hide();
			return;
		}
		$gp.notices.notice( $gp_mass_create_sets_options.loading );
		select.prop( 'disabled', true );
		$.ajax( { type: 'POST', url: $gp_mass_create_sets_options.url, data: { project_id: project_id }, dataType: 'json',
			success: function( data ) {
				var preview = $( '#preview' );
				var preview_html = '';
				preview.html( '<h3>' + $gp.l10n.preview_changes_colon + '</h3>' );
				preview_html += '<ul>';
				select.prop( 'disabled', false );
				$gp.notices.clear();
				if ( data.added.length || data.removed.length ) {
					$( '#submit' ).prop( 'disabled', false );
				}
				function preview_html_for( kind, text ) {
					var sets = data[ kind ];
					var html = '';
					html += '<li><span class="' + kind + '">' + text.replace( '{count}', sets.length ) + '</span>';
					if ( sets.length ) {
						html += '<ul>';
						$.each( sets, function() {
							html += '<li>' + $gp.esc_html( this.name ) + ' (' + this.locale + '/' + this.slug + ')</li>';
						} );
						html += '</ul>';
					}
					html += '</li>';
					return html;
				}
				preview_html += preview_html_for( 'added', $gp.l10n.sets_will_be_added );
				preview_html += preview_html_for( 'removed', $gp.l10n.sets_will_be_removed );
				preview_html += '</ul>';
				preview.append( preview_html );
				preview.fadeIn();
			},
			error: function( xhr, msg ) {
				select.prop( 'disabled', false );
				msg = xhr.responsehtml ? $gp.l10n.error_colon + xhr.responsehtml : $gp.l10n.error_saving_translation;
				$gp.notices.error( msg );
			},
		} );
	} );
	$( '#submit' ).prop( 'disabled', true );
	$( '#preview' ).hide();
} );
