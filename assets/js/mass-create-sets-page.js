/* global $gp_mass_create_sets_options, $gp, wp */
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
				preview.html( '<h3>' + wp.i18n.__( 'Preview changes:', 'glotpress' ) + '</h3>' );
				preview_html += '<ul>';
				select.prop( 'disabled', false );
				$gp.notices.clear();
				if ( data.added.length || data.removed.length ) {
					$( '#submit' ).prop( 'disabled', false );
				}
				function preview_html_for( kind, text ) {
					var sets = data[ kind ];
					var html = '';
					html += '<li><span class="' + kind + '">' + text + '</span>';
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
				/* translators: %s: Number of translation sets. */
				preview_html += preview_html_for( 'added', wp.i18n.sprintf( wp.i18n._n( '%s set will be added', '%s sets will be added', data.added.length, 'glotpress' ), data.added.length ) );
				/* translators: %s: Number of translation sets. */
				preview_html += preview_html_for( 'removed', wp.i18n.sprintf( wp.i18n._n( '%s set will be removed', '%s sets will be removed', data.removed.length, 'glotpress' ), data.removed.length ) );
				preview_html += '</ul>';
				preview.append( preview_html );
				preview.fadeIn();
			},
			error: function( xhr, msg ) {
				select.prop( 'disabled', false );
				/* translators: %s: Error message. */
				msg = xhr.responsehtml ? wp.i18n.sprintf( wp.i18n.__( 'Error: %s', 'glotpress' ), xhr.responsehtml ) : wp.i18n.__( 'Error saving the translation!', 'glotpress' );
				$gp.notices.error( msg );
			},
		} );
	} );
	$( '#submit' ).prop( 'disabled', true );
	$( '#preview' ).hide();
} );
