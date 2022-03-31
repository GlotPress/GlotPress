/* global $gp_editor_options, $gp, wp, __ */
/* eslint camelcase: "off" */
const { sprintf } = wp.i18n;
$gp.editor = (
	function( $ ) {
		return {
			current: null,
			orginal_translations: null,
			init: function( table ) {
				var $previewRows;

				$gp.init();
				$gp.editor.table = table;
				$gp.editor.install_hooks();

				// Open the first editor if the current table has only one.
				$previewRows = $gp.editor.table.find( 'tr.preview' );
				if ( 1 === $previewRows.length ) {
					$gp.editor.show( $previewRows.eq( 0 ) );
				}
			},
			original_id_from_row_id: function( row_id ) {
				return row_id.split( '-' )[ 0 ];
			},
			translation_id_from_row_id: function( row_id ) {
				return row_id.split( '-' )[ 1 ];
			},
			show: function( element ) {
				var row_id = element.closest( 'tr' ).attr( 'row' );
				var editor = $( '#editor-' + row_id );
				var gmt_date_added = $( '#gmt-date-added-' + row_id );
				var local_date_added = $( '#local-date-added-' + row_id );
				var offset = new Date().getTimezoneOffset();
				var gmt_date = new Date( gmt_date_added.text() );
				var local_date = new Date( ( gmt_date - ( offset * 60 * 1000 ) ) );

				if ( ! editor.length ) {
					return;
				}
				if ( $gp.editor.current ) {
					$gp.editor.hide();
				}
				editor.preview = $( '#preview-' + row_id );
				editor.row_id = row_id;
				editor.original_id = $gp.editor.original_id_from_row_id( row_id );
				editor.translation_id = $gp.editor.translation_id_from_row_id( row_id );

				editor.orginal_translations = $( 'textarea[name="translation[' + editor.original_id + '][]"]', editor ).map( function() {
					return this.value;
				} ).get();

				$gp.editor.current = editor;

				local_date_added.text( local_date.toLocaleDateString() + ' ' + local_date.toLocaleTimeString() );

				editor.show();
				editor.preview.hide();
				$( 'textarea:first', editor ).focus();
			},
			prev: function() {
				var prev;
				if ( ! $gp.editor.current ) {
					return;
				}

				// TODO: go to previous page if needed
				prev = $gp.editor.current.prevAll( 'tr.editor' );
				if ( prev.length ) {
					$gp.editor.show( prev.eq( 0 ) );
				} else {
					$gp.editor.hide();
				}
			},
			next: function() {
				var next;

				if ( ! $gp.editor.current ) {
					return;
				}

				// TODO: go to next page if needed.
				next = $gp.editor.current.nextAll( 'tr.editor' );
				if ( next.length ) {
					$gp.editor.show( next.eq( 0 ) );
				} else {
					$gp.editor.hide();
				}
			},
			hide: function( editor ) {
				editor = editor ? editor : $gp.editor.current;
				if ( ! editor ) {
					return;
				}
				editor.hide();
				editor.preview.show();
				$gp.editor.current = null;
			},
			install_hooks: function() {
				$( $gp.editor.table )
					.on( 'click', 'a.edit', $gp.editor.hooks.show )
					.on( 'dblclick', 'tr.preview td', $gp.editor.hooks.show )
					.on( 'change', 'select.priority', $gp.editor.hooks.set_priority )
					.on( 'click', 'button.close', $gp.editor.hooks.cancel )
					.on( 'click', 'a.discard-warning', $gp.editor.hooks.discard_warning )
					.on( 'click', 'button.copy', $gp.editor.hooks.copy )
					.on( 'click', 'button.inserttab', $gp.editor.hooks.tab )
					.on( 'click', 'button.insertnl', $gp.editor.hooks.newline )
					.on( 'click', 'button.approve', $gp.editor.hooks.set_status_current )
					.on( 'click', 'button.reject', $gp.editor.hooks.set_status_rejected )
					.on( 'click', 'button.fuzzy', $gp.editor.hooks.set_status_fuzzy )
					.on( 'click', 'button.ok', $gp.editor.hooks.ok )
					.on( 'keydown', 'tr.editor textarea', $gp.editor.hooks.keydown );
				$( '#translations' ).tooltip( {
					items: '.glossary-word',
					content: function() {
						var content = $( '<ul>' );
						$.each( $( this ).data( 'translations' ), function( i, e ) {
							var def = $( '<li>' );
							if ( e.locale_entry ) {
								def.append( $( '<span>', { text: e.locale_entry } ).addClass( 'locale-entry bubble' ) );
							}
							def.append( $( '<span>', { text: e.pos } ).addClass( 'pos' ) );
							def.append( $( '<span>', { text: e.translation } ).addClass( 'translation' ) );
							def.append( $( '<span>', { text: e.comment } ).addClass( 'comment' ) );
							content.append( def );
						} );
						return content;
					},
					hide: false,
					show: false,
				} );

				$.valHooks.textarea = {
					get: function( elem ) {
						return elem.value.replace( /\r?\n/g, '\r\n' );
					},
				};
			},
			keydown: function( e ) {
				var target, container, approve, reject, copy;

				if ( 27 === e.keyCode || ( 90 === e.keyCode && e.shiftKey && e.ctrlKey ) ) { // Escape or Ctrl-Shift-Z = Cancel.
					$gp.editor.hide();
				} else if ( 33 === e.keyCode || ( 38 === e.keyCode && e.ctrlKey ) ) { // Page Up or Ctrl-Up Arrow = Previous editor.
					$gp.editor.prev();
				} else if ( 34 === e.keyCode || ( 40 === e.keyCode && e.ctrlKey ) ) { // Page Down or Ctrl-Down Arrow = Next editor.
					$gp.editor.next();
				} else if ( 13 === e.keyCode && e.shiftKey ) { // Shift-Enter = Save.
					target = $( e.target );

					if ( 0 === e.altKey && target.val().length ) {
						container = target.closest( '.textareas' ).prev();

						if ( container.children() ) {
							target.val( container.find( '.original' ).text() );
						} else {
							target.val( container.text() );
						}
					}

					if ( target.nextAll( 'textarea' ).length ) {
						target.nextAll( 'textarea' ).eq( 0 ).focus();
					} else {
						$gp.editor.save( target.parents( 'tr.editor' ).find( 'button.ok' ) );
					}
				} else if ( ( 13 === e.keyCode && e.ctrlKey ) || ( 66 === e.keyCode && e.shiftKey && e.ctrlKey ) ) { // Ctrl-Enter or Ctrl-Shift-B = Copy original.
					copy = $( '.editor:visible' ).find( '.copy' );

					if ( copy.length > 0 ) {
						copy.trigger( 'click' );
					}
				} else if ( ( 107 === e.keyCode && e.ctrlKey ) || ( 65 === e.keyCode && e.shiftKey && e.ctrlKey ) ) { // Ctrl-+ or Ctrl-Shift-A = Approve.
					approve = $( '.editor:visible' ).find( '.approve' );

					if ( approve.length > 0 ) {
						approve.trigger( 'click' );
					}
				} else if ( ( 109 === e.keyCode && e.ctrlKey ) || ( 82 === e.keyCode && e.shiftKey && e.ctrlKey ) ) { // Ctrl-- or Ctrl-Shift-R = Reject.
					reject = $( '.editor:visible' ).find( '.reject' );

					if ( reject.length > 0 ) {
						reject.trigger( 'click' );
					}
				} else if ( ( 192 === e.keyCode && e.ctrlKey ) || ( 192 === e.keyCode && e.shiftKey && e.ctrlKey ) ) { // Ctrl-~ or Ctrl-Shift-~ = Fuzzy.
					reject = $( '.editor:visible' ).find( '.fuzzy' );

					if ( reject.length > 0 ) {
						reject.trigger( 'click' );
					}
				} else {
					return true;
				}

				return false;
			},
			replace_current: function( html ) {
				var old_current;

				if ( ! $gp.editor.current ) {
					return;
				}
				$gp.editor.current.after( html );
				old_current = $gp.editor.current;
				old_current.attr( 'id', old_current.attr( 'id' ) + '-old' );
				old_current.preview.attr( 'id', old_current.preview.attr( 'id' ) + '-old' );
				$gp.editor.next();
				old_current.preview.remove();
				old_current.remove();
			},
			save: function( button ) {
				var editor, textareaName,
					data = [],
					translations;

				if ( ! $gp.editor.current ) {
					return;
				}

				editor = $gp.editor.current;
				button.prop( 'disabled', true );
				$gp.notices.notice( __( 'Saving&hellip;', 'glotpress' ) );

				data = {
					original_id: editor.original_id,
					_gp_route_nonce: button.data( 'nonce' ),
				};

				textareaName = 'translation[' + editor.original_id + '][]';
				translations = $( 'textarea[name="' + textareaName + '"]', editor ).map( function() {
					return this.value;
				} ).get();

				data[ textareaName ] = translations;

				$.ajax( {
					type: 'POST',
					url: $gp_editor_options.url,
					data: data,
					dataType: 'json',
					success: function( response ) {
						var original_id;

						button.prop( 'disabled', false );
						$gp.notices.success( __( 'Saved!', 'glotpress' ) );

						for ( original_id in response ) {
							$gp.editor.replace_current( response[ original_id ] );
						}

						if ( $gp.editor.current.hasClass( 'no-warnings' ) ) {
							$gp.editor.next();
						}
					},
					error: function( xhr, msg ) {
						button.prop( 'disabled', false );
						/* translators: %s: Error message. */
						msg = xhr.responseText ? sprintf( __( 'Error: %s', 'glotpress' ), xhr.responseText ) : __( 'Error saving the translation!', 'glotpress' );
						$gp.notices.error( msg );
					},
				} );
			},
			set_priority: function( select ) {
				var editor, data;

				if ( ! $gp.editor.current ) {
					return;
				}

				editor = $gp.editor.current;
				select.prop( 'disabled', true );
				$gp.notices.notice( __( 'Setting priority&hellip;', 'glotpress' ) );

				data = {
					priority: $( 'option:selected', select ).val(),
					_gp_route_nonce: select.data( 'nonce' ),
				};

				$.ajax( {
					type: 'POST',
					url: $gp_editor_options.set_priority_url.replace( '%original-id%', editor.original_id ),
					data: data,
					success: function() {
						var new_priority_class;

						select.prop( 'disabled', false );
						$gp.notices.success( __( 'Priority set!', 'glotpress' ) );
						new_priority_class = 'priority-' + $( 'option:selected', select ).text();
						$gp.editor.current.addClass( new_priority_class );
						$gp.editor.current.preview.addClass( new_priority_class );
					},
					error: function( xhr, msg ) {
						select.prop( 'disabled', false );
						/* translators: %s: Error message. */
						msg = xhr.responseText ? sprintf( __( 'Error: %s', 'glotpress' ), xhr.responseText ) : __( 'Error setting the priority!', 'glotpress' );
						$gp.notices.error( msg );
					},
				} );
			},
			set_status: function( button, status ) {
				var editor, data,
					translationChanged = false;

				if ( ! $gp.editor.current || ! $gp.editor.current.translation_id ) {
					return;
				}

				editor = $gp.editor.current;

				$( '[id*="translation_' + editor.original_id + '_"]' ).each( function() {
					if ( this.value !== this.defaultValue ) {
						translationChanged = true;
					}
				} );

				if ( translationChanged ) {
					$gp.notices.error( __( 'Translation has changed! Please add the new translation before changing its status.', 'glotpress' ) );
					return;
				}

				button.prop( 'disabled', true );
				/* translators: %s: Status name. */
				$gp.notices.notice( sprintf( __( 'Setting status to &#8220;%s&#8221;&hellip;', 'glotpress' ), status ) );

				data = {
					translation_id: editor.translation_id,
					status: status,
					_gp_route_nonce: button.data( 'nonce' ),
				};

				$.ajax( {
					type: 'POST',
					url: $gp_editor_options.set_status_url,
					data: data,
					success: function( response ) {
						button.prop( 'disabled', false );
						$gp.notices.success( __( 'Status set!', 'glotpress' ) );
						$gp.editor.replace_current( response );
						$gp.editor.next();
					},
					error: function( xhr, msg ) {
						button.prop( 'disabled', false );
						/* translators: %s: Error message. */
						msg = xhr.responseText ? sprintf( __( 'Error: %s', 'glotpress' ), xhr.responseText ) : __( 'Error setting the status!', 'glotpress' );
						$gp.notices.error( msg );
					},
				} );
			},
			discard_warning: function( link ) {
				var data;
				if ( ! $gp.editor.current ) {
					return;
				}

				$gp.notices.notice( __( 'Discarding&hellip;', 'glotpress' ) );

				data = {
					translation_id: $gp.editor.current.translation_id,
					key: link.data( 'key' ),
					index: link.data( 'index' ),
					_gp_route_nonce: link.data( 'nonce' ),

				};

				$.ajax( {
					type: 'POST',
					url: $gp_editor_options.discard_warning_url,
					data: data,
					success: function( response ) {
						$gp.notices.success( __( 'Saved!', 'glotpress' ) );
						$gp.editor.replace_current( response );
					},
					error: function( xhr, msg ) {
						/* translators: %s: Error message. */
						msg = xhr.responseText ? sprintf( __( 'Error: %s', 'glotpress' ), xhr.responseText ) : __( 'Error saving the translation!', 'glotpress' );
						$gp.notices.error( msg );
					},
				} );
			},
			copy: function( link ) {
				var chunks = link.parents( '.textareas' ).find( 'textarea' ).attr( 'id' ).split( '_' );
				var original_index = Math.min( parseInt( chunks[ chunks.length - 1 ], 10 ), 1 );
				var original_texts = link.parents( '.strings' ).find( '.original_raw' );
				var original_text = original_texts.eq( original_index ).text();

				link.parents( '.textareas' ).find( 'textarea' ).val( original_text ).focus();
			},
			tab: function( link ) {
				var text_area = link.parents( '.textareas' ).find( 'textarea' );
				var cursorPos = text_area.prop( 'selectionStart' );
				var v = text_area.val();
				var textBefore = v.substring( 0, cursorPos );
				var textAfter = v.substring( cursorPos, v.length );

				text_area.val( textBefore + '\t' + textAfter );

				text_area.focus();
				text_area[ 0 ].selectionEnd = cursorPos + 1;
			},
			newline: function( link ) {
				var text_area = link.parents( '.textareas' ).find( 'textarea' );
				var cursorPos = text_area.prop( 'selectionStart' );
				var v = text_area.val();
				var textBefore = v.substring( 0, cursorPos );
				var textAfter = v.substring( cursorPos, v.length );

				text_area.val( textBefore + '\n' + textAfter );

				text_area.focus();
				text_area[ 0 ].selectionEnd = cursorPos + 1;
			},
			hooks: {
				show: function() {
					$gp.editor.show( $( this ) );
					return false;
				},
				hide: function() {
					$gp.editor.hide();
					return false;
				},
				ok: function() {
					$gp.editor.save( $( this ) );
					return false;
				},
				cancel: function() {
					var i = 0;

					for ( i = 0; i < $gp.editor.current.orginal_translations.length; i++ ) {
						$( 'textarea[id="translation_' + $gp.editor.current.original_id + '_' + i + '"]' ).val( $gp.editor.current.orginal_translations[ i ] );
					}

					$gp.editor.hide();

					return false;
				},
				keydown: function( e ) {
					return $gp.editor.keydown( e );
				},
				copy: function() {
					$gp.editor.copy( $( this ) );
					return false;
				},
				tab: function() {
					$gp.editor.tab( $( this ) );
					return false;
				},
				newline: function() {
					$gp.editor.newline( $( this ) );
					return false;
				},
				discard_warning: function() {
					$gp.editor.discard_warning( $( this ) );
					return false;
				},
				set_status_current: function() {
					$gp.editor.set_status( $( this ), 'current' );
					return false;
				},
				set_status_rejected: function() {
					$gp.editor.set_status( $( this ), 'rejected' );
					return false;
				},
				set_status_fuzzy: function() {
					$gp.editor.set_status( $( this ), 'fuzzy' );
					return false;
				},
				set_priority: function() {
					$gp.editor.set_priority( $( this ) );
					return false;
				},
			},
		};
	}( jQuery )
);

jQuery( function( $ ) {
	$gp.editor.init( $( '#translations' ) );
} );
