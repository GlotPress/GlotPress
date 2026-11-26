/* global $gp_glossary_options, $gp, confirm, document, HTMLFormElement, wp */
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

				// Adding an entry asks for the base form of a term entered in a suffixed form, like saving one.
				$( document ).on( 'submit', 'form', $gp.glossary.hooks.add );
			},

			/**
			 * Asks for the base form of a term entered in a suffixed form, like `user` for `users`, before saving it.
			 * The entry is saved once the term was answered, with the button to save it.
			 *
			 * @param {Object}     fields  The fields of the entry.
			 * @param {() => void} proceed Saves the entry.
			 */
			check_base_terms: function( fields, proceed ) {
				var term_field = fields.find( 'input[name$="[term]"]' ),
					part_of_speech = fields.find( 'select[name$="[part_of_speech]"]' ).val(),
					term = term_field.val().trim();

				fields.find( '.glossary-base-terms' ).remove();

				// Terms with multiple words don't get suffixes, and an entry whose term didn't change isn't asked again.
				if ( '' === term || /\s/.test( term ) || term === term_field.prop( 'defaultValue' ).trim() || term + '|' + part_of_speech === fields.data( 'base-terms-answered' ) ) {
					proceed();
					return;
				}

				$.ajax( {
					type: 'GET',
					url: $gp_glossary_options.base_terms_url,
					data: {
						term: term,
						part_of_speech: part_of_speech,
					},
					dataType: 'json',
					success: function( base_terms ) {
						var question, message,
							// Answering only sets the term, which is saved with the button to save the entry.
							answer = function( answered_term ) {
								term_field.val( answered_term ).trigger( 'focus' );
								fields.data( 'base-terms-answered', answered_term + '|' + part_of_speech );
								question.remove();
							};

						if ( ! Array.isArray( base_terms ) || ! base_terms.length ) {
							proceed();
							return;
						}

						/* translators: %s: The glossary term as entered. */
						message = wp.i18n.__( 'Glossary terms are entered in their base form, which also matches %s.', 'glotpress' ).split( '%s' );
						question = $( '<span class="glossary-base-terms"></span>' ).append(
							document.createTextNode( ' ' + message[ 0 ] ),
							$( '<strong></strong>' ).text( term ),
							document.createTextNode( message.slice( 1 ).join( '' ) ),
						);
						base_terms.forEach( function( base_term ) {
							/* translators: %s: The base form of the glossary term. */
							question.append( ' ', $( '<button type="button" class="button is-small"></button>' ).text( wp.i18n.sprintf( wp.i18n.__( 'Use "%s"', 'glotpress' ), base_term ) ).on( 'click', function() {
								answer( base_term );
							} ) );
						} );
						/* translators: %s: The glossary term as entered. */
						question.append( ' ', $( '<button type="button" class="button is-small"></button>' ).text( wp.i18n.sprintf( wp.i18n.__( 'Keep "%s"', 'glotpress' ), term ) ).on( 'click', function() {
							answer( term );
						} ) );

						// Inline after the field, so the fields below don't move.
						term_field.after( question );
						question.find( 'button' ).first().trigger( 'focus' );
					},
					error: function() {
						// The question is only a suggestion, so the entry is saved as entered.
						proceed();
					},
				} );
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
				$gp.notices.progress( wp.i18n.__( 'Saving&hellip;', 'glotpress' ) );

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
					var button = $( this );

					$gp.glossary.check_base_terms( button.closest( 'tr' ).find( 'dl' ), function() {
						$gp.glossary.save( button );
					} );
					return false;
				},
				add: function( event ) {
					var form = this,
						fields = $( form ).find( 'input[name="new_glossary_entry[term]"]' ).closest( 'dl' );

					if ( ! fields.length ) {
						return;
					}

					event.preventDefault();
					$gp.glossary.check_base_terms( fields, function() {
						// The submit button is named `submit`, which hides the form's own submit().
						HTMLFormElement.prototype.submit.call( form );
					} );
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
