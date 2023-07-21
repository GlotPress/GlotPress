/* global $gp_glossary_options, $gp, wp */
/* eslint camelcase: "off", no-alert: "off" */
$gp.glossary = (
	function( $ ) {
		return {

			current: null,

			init: function( table ) {
				$gp.init();
				$gp.glossary.tablesorter();
				if ( '' === $gp_glossary_options.can_edit ) {
					return;
				}
				$gp.glossary.table = table;
				$gp.glossary.install_hooks();
			},

			install_hooks: function() {
				$( $gp.glossary.table )
					.on( 'click', 'a.edit', $gp.glossary.hooks.show )
					.on( 'dblclick', 'tr td', $gp.glossary.hooks.show )
					.on( 'click', 'button.cancel', $gp.glossary.hooks.hide )
					.on( 'click', 'button.delete', $gp.glossary.hooks.del )
					.on( 'click', 'button.save', $gp.glossary.hooks.ok );
			},

			show: function( event, element ) {
				var preview, row_id, editor;

				event.preventDefault();

				preview = element.closest( 'tr' );
				row_id = preview.data( 'id' );
				editor = $( '#editor-' + row_id );
				if ( ! editor.length ) {
					return;
				}
				if ( $gp.glossary.current ) {
					$gp.glossary.hide();
				}
				editor.preview = preview;
				editor.row_id = row_id;
				$gp.glossary.current = editor;
				editor.addClass( 'active' );
				editor.show();
				editor.preview.hide();
				if ( $( 'a.add-entry' ).hasClass( 'open' ) ) {
					$( 'a.add-entry' ).click();
				}
				$( 'input:first', editor ).focus();
			},

			hide: function( editor ) {
				editor = editor ? editor : $gp.glossary.current;
				if ( ! editor ) {
					return;
				}
				editor.hide();
				editor.preview.show();
				editor.removeClass( 'active' );
				$gp.glossary.current = null;
			},

			save: function( button ) {
				var editor, data;

				if ( ! $gp.glossary.current ) {
					return;
				}

				button.prop( 'disabled', true );
				$gp.notices.notice( wp.i18n.__( 'Saving&hellip;', 'glotpress' ) );

				editor = $gp.glossary.current;

				data = {
					_gp_route_nonce: button.data( 'nonce' ),
				};

				$( '#editor-' + editor.row_id ).find( 'input, select, textarea' ).each( function() {
					data[ $( this ).attr( 'name' ) ] = this.value;
				} );

				$.ajax( {
					type: 'POST',
					url: $gp_glossary_options.url,
					data: data,
					dataType: 'json',
					success: function( response ) {
						button.prop( 'disabled', false );
						$gp.notices.success( wp.i18n.__( 'Saved!', 'glotpress' ) );
						$gp.glossary.replace_current( response );
					},
					error: function( xhr, msg ) {
						button.prop( 'disabled', false );
						/* translators: %s: Error message. */
						msg = xhr.responseText ? wp.i18n.sprintf( wp.i18n.__( 'Error: %s', 'glotpress' ), xhr.responseText ) : wp.i18n.__( 'Error saving the glossary item!', 'glotpress' );
						$gp.notices.error( msg );
					},
				} );
			},

			del: function( event, button ) {
				var result, editor, data, preview;

				event.preventDefault();

				result = confirm( $gp_glossary_options.ge_delete_ays );
				if ( ! result ) {
					return;
				}
				editor = button.closest( 'tr' );
				preview = editor.prev( 'tr' );

				data = {
					_gp_route_nonce: button.data( 'nonce' ),
				};

				editor.find( 'input, select, textarea' ).each( function() {
					data[ $( this ).attr( 'name' ) ] = this.value;
				} );

				$.ajax( {
					type: 'POST',
					url: $gp_glossary_options.delete_url,
					data: data,
					success: function() {
						$gp.notices.success( wp.i18n.__( 'Deleted!', 'glotpress' ) );
						editor.fadeOut( 'fast', function() {
							this.remove();
						} );
						preview.remove();
						if ( 1 === $( 'tr', $gp.glossary.table ).length ) {
							$gp.glossary.table.remove();
						}
					},
					error: function( xhr, msg ) {
						/* translators: %s: Error message. */
						msg = xhr.responseText ? wp.i18n.sprintf( wp.i18n.__( 'Error: %s', 'glotpress' ), xhr.responseText ) : wp.i18n.__( 'Error deleting the glossary item!', 'glotpress' );
						$gp.notices.error( msg );
					},
				} );
			},

			replace_current: function( html ) {
				var old_current;

				if ( ! $gp.glossary.current ) {
					return;
				}

				$gp.glossary.current.after( html );
				old_current = $gp.glossary.current;
				old_current.preview.remove();
				old_current.remove();
				$gp.glossary.current.preview.fadeIn( 800 );
			},

			hooks: {
				show: function( event ) {
					$gp.glossary.show( event, $( this ) );
				},
				del: function( event ) {
					$gp.glossary.del( event, $( this ) );
				},
				hide: function() {
					$gp.glossary.hide();
					return false;
				},
				ok: function() {
					$gp.glossary.save( $( this ) );
					return false;
				},
			},

			tablesorter: function() {
				$( '#glossary' ).tablesorter( {
					theme: 'glotpress',
					sortList: [ [ 0, 0 ] ],
					cssChildRow: 'editor',
				} );
			},

		};
	}( jQuery )
);

jQuery( function( $ ) {
	$gp.glossary.init( $( '#glossary' ) );
} );
