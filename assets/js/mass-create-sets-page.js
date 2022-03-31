/* global $gp_mass_create_sets_options, $gp, __, sprintf */
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
				preview.html( '<h3>' + __( 'Preview changes:', 'glotpress' ) + '</h3>' );
				preview_html += '<ul>';
				select.prop( 'disabled', false );
				$gp.notices.clear();
				if ( data.added.length || data.removed.length ) {
					$( '#submit' ).prop( 'disabled', false );
				}
				function preview_html_for( kind, text ) {
					var sets = data[ kind ];
					var html = '';
					html += '<li><span class="' + kind + '">' + sprintf( text, sets.length ) + '</span>';
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
				/* translators: {count}: Number of translation sets. */
				preview_html += preview_html_for( 'added', __( '%s set(s) will be added', 'glotpress' ) );
				/* translators: {count}: Number of translation sets. */
				preview_html += preview_html_for( 'removed', __( '%s set(s) will be removed', 'glotpress' ) );
				preview_html += '</ul>';
				preview.append( preview_html );
				preview.fadeIn();
			},
			error: function( xhr, msg ) {
				select.prop( 'disabled', false );
				/* translators: %s: Error message. */
				msg = xhr.responsehtml ? sprintf( __( 'Error: %s', 'glotpress' ), xhr.responsehtml ) : __( 'Error saving the translation!', 'glotpress' );
				$gp.notices.error( msg );
			},
		} );
	} );
	$( '#submit' ).prop( 'disabled', true );
	$( '#preview' ).hide();
} );
