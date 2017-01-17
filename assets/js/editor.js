/* global $gp_editor_options, $gp */
/* jscs:disable requireCamelCaseOrUpperCaseIdentifiers */
$gp.editor = (
	function( $ ) {
		return {
			current: null,
			init: function( table ) {
				$gp.init();
				$gp.editor.table = table;
				$gp.editor.install_hooks();
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
				$gp.editor.current = editor;
				editor.show();
				editor.preview.hide();
				$( 'tr:first', $gp.editor.table ).hide();
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
				$( 'tr:first', $gp.editor.table ).show();
				$gp.editor.current = null;
			},
			install_hooks: function() {
				$( $gp.editor.table )
					.on( 'click', 'a.edit', $gp.editor.hooks.show )
					.on( 'dblclick', 'tr.preview td', $gp.editor.hooks.show )
					.on( 'change', 'select.priority', $gp.editor.hooks.set_priority )
					.on( 'click', 'a.close', $gp.editor.hooks.hide )
					.on( 'click', 'a.copy', $gp.editor.hooks.copy )
					.on( 'click', 'a.discard-warning', $gp.editor.hooks.discard_warning )
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
					show: false
				} );
			},
			keydown: function( e ) {
				var target, container, approve, reject, copy;

				if ( 27 === e.keyCode || ( 90 === e.keyCode && e.shiftKey && e.ctrlKey ) ) { // Escape or Ctrl-Shift-Z = Cancel.
					$gp.editor.hide();
				} else if ( 33 === e.keyCode || ( 38 === e.keyCode && e.ctrlKey ) ) { // Page Down or Ctrl-Up Arrow = Previous editor.
					$gp.editor.prev();
				} else if ( 34 === e.keyCode || ( 40 === e.keyCode && e.ctrlKey ) ) { // Page Up or Ctrl-Down Arrow = Next editor.
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
				$gp.editor.next();
				old_current.preview.remove();
				old_current.remove();
				$gp.editor.current.preview.fadeIn( 800 );
			},
			save: function( button ) {
				var editor, textareaName, data = [], translations;

				if ( ! $gp.editor.current ) {
					return;
				}

				editor = $gp.editor.current;
				button.prop( 'disabled', true );
				$gp.notices.notice( 'Saving&hellip;' );

				data = {
					original_id: editor.original_id,
					_gp_route_nonce: button.data( 'nonce' )
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
					success: function( data ) {
						var original_id;

						button.prop( 'disabled', false );
						$gp.notices.success( 'Saved!' );

						for ( original_id in data ) {
							$gp.editor.replace_current( data[ original_id ] );
						}

						if ( $gp.editor.current.hasClass( 'no-warnings' ) ) {
							$gp.editor.next();
						} else {
							$gp.editor.current.preview.hide();
						}
					},
					error: function( xhr, msg ) {
						button.prop( 'disabled', false );
						msg = xhr.responseText ? 'Error: ' + xhr.responseText : 'Error saving the translation!';
						$gp.notices.error( msg );
					}
				} );
			},
			set_priority: function( select ) {
				var editor, data;

				if ( ! $gp.editor.current ) {
					return;
				}

				editor = $gp.editor.current;
				select.prop( 'disabled', true );
				$gp.notices.notice( 'Setting priority&hellip;' );

				data = {
					priority: $( 'option:selected', select ).val(),
					_gp_route_nonce: select.data( 'nonce' )
				};

				$.ajax( {
					type: 'POST',
					url: $gp_editor_options.set_priority_url.replace( '%original-id%', editor.original_id ),
					data: data,
					success: function() {
						var new_priority_class;

						select.prop( 'disabled', false );
						$gp.notices.success( 'Priority set!' );
						new_priority_class = 'priority-' + $( 'option:selected', select ).text();
						$gp.editor.current.addClass( new_priority_class );
						$gp.editor.current.preview.addClass( new_priority_class );
					},
					error: function( xhr, msg ) {
						select.prop( 'disabled', false );
						msg = xhr.responseText ? 'Error: ' + xhr.responseText : 'Error setting the priority!';
						$gp.notices.error( msg );
					}
				} );
			},
			set_status: function( button, status ) {
				var editor, data;

				if ( ! $gp.editor.current || ! $gp.editor.current.translation_id ) {
					return;
				}

				editor = $gp.editor.current;
				button.prop( 'disabled', true );
				$gp.notices.notice( 'Setting status to &#8220;' + status + '&#8221;&hellip;' );

				data = {
					translation_id: editor.translation_id,
					status: status,
					_gp_route_nonce: button.data( 'nonce' )
				};

				$.ajax( {
					type: 'POST',
					url: $gp_editor_options.set_status_url,
					data: data,
					success: function( data ) {
						button.prop( 'disabled', false );
						$gp.notices.success( 'Status set!' );
						$gp.editor.replace_current( data );
						$gp.editor.next();
					},
					error: function( xhr, msg ) {
						button.prop( 'disabled', false );
						msg = xhr.responseText ? 'Error: ' + xhr.responseText : 'Error setting the status!';
						$gp.notices.error( msg );
					}
				} );
			},
			discard_warning: function( link ) {
				var data;
				if ( ! $gp.editor.current ) {
					return;
				}

				$gp.notices.notice( 'Discarding&hellip;' );

				data = {
					translation_id: $gp.editor.current.translation_id,
					key: link.data( 'key' ),
					index: link.data( 'index' ),
					_gp_route_nonce: link.data( 'nonce' )

				};

				$.ajax( {
					type: 'POST',
					url: $gp_editor_options.discard_warning_url,
					data: data,
					success: function( data ) {
						$gp.notices.success( 'Saved!' );
						$gp.editor.replace_current( data );
					},
					error: function( xhr, msg ) {
						msg = xhr.responseText ? 'Error: ' + xhr.responseText : 'Error saving the translation!';
						$gp.notices.error( msg );
					}
				} );
			},
			copy: function( link ) {
				var chunks = link.parents( '.textareas' ).find( 'textarea' ).attr( 'id' ).split( '_' );
				var original_index = parseInt( chunks[ chunks.length - 1 ], 10 );
				var original_text = link.parents( '.textareas' ).prev().find( '.original' ).eq( original_index );

				if ( ! original_text.hasClass( 'original' ) ) {
					original_text = link.parents( '.strings' ).find( '.original' ).eq( original_index );
				}

				original_text = original_text.text();
				original_text = original_text.replace( /<span class=.invisibles.*?<\/span>/g, '' );
				link.parents( '.textareas' ).find( 'textarea' ).val( original_text ).focus();
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
				keydown: function( e ) {
					return $gp.editor.keydown( e );
				},
				copy: function() {
					$gp.editor.copy( $( this ) );
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
				}
			}
		};
	}( jQuery )
);

jQuery( function( $ ) {
	$gp.editor.init( $( '#translations' ) );
} );
