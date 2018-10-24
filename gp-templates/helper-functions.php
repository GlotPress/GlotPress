<?php
/**
 * Defines helper functions used by GlotPress.
 *
 * @package GlotPress
 * @since 1.0.0
 */

/**
 * Prepare an original string to be printed out in a translation row by adding encoding special
 * characters, adding glossary entires and other markup.
 *
 * @param string $text A single style handle to enqueue or an array or style handles to enqueue.
 *
 * @return string The prepared string for output.
 */
function prepare_original( $text ) {
	// Glossaries are injected into the translations prior to escaping and prepare_original() being run.
	$glossary_entries = array();
	$text = preg_replace_callback( '!(<span class="glossary-word"[^>]+>)!i', function( $m ) use ( &$glossary_entries ) {
		$item_number = count( $glossary_entries );
		$glossary_entries[ $item_number ] = $m[0];
		return "<span GLOSSARY={$item_number}>";
	}, $text );

	// Wrap full HTML tags with a notranslate class
	$text = preg_replace( '/(&lt;.+?&gt;)/', '<span class="notranslate">\\1</span>', $text );
	// Break out & back into notranslate for translatable attributes
	$text = preg_replace( '/(title|aria-label)=([\'"])([^\\2]+?)\\2/', '\\1=\\2</span>\\3<span class="notranslate">\\2', $text );
	// Wrap placeholders with notranslate
	$text = preg_replace( '/(%(\d+\$(?:\d+)?)?[bcdefgosuxEFGX])/', '<span class="notranslate">\\1</span>', $text );

	// Put the glossaries back!
	$text = preg_replace_callback( '!(<span GLOSSARY=(\d+)>)!', function( $m ) use ( $glossary_entries ) {
		return $glossary_entries[ $m[2] ];
	}, $text );

	$text = str_replace( array( "\r", "\n" ), "<span class='invisibles' title='" . esc_attr__( 'New line', 'glotpress' ) . "'>&crarr;</span>\n", $text );
	$text = str_replace( "\t", "<span class='invisibles' title='" . esc_attr__( 'Tab character', 'glotpress' ) . "'>&rarr;</span>\t", $text );

	return $text;
}

/**
 * Prepares a translation string to be printed out in a translation row by adding an 'extra' return/newline if
 * it starts with one.
 *
 * @since 2.4.0
 *
 * @param string $text A single style handle to enqueue or an array or style handles to enqueue.
 * @return string The prepared string for output.
 */
function gp_prepare_translation_textarea( $text ) {
	if ( gp_startswith( $text, "\r\n" ) ) {
		$text = "\r\n" . $text;
	} elseif ( gp_startswith( $text, "\n" ) ) {
		$text = "\n" . $text;
	}

	return $text;
}

/**
 * Sort a set of glossary entries by length for use in map_glossary_entries_to_translation_originals().
 *
 * @param array $glossary_entries An array of glossary entries to sort.
 *
 * @return array The sorted entries.
 */
function gp_sort_glossary_entries_terms( $glossary_entries ) {
	if ( empty ( $glossary_entries ) ) {
		return;
	}

	$glossary_entries_terms = array();

	// Create array of glossary terms, longest first.
	foreach ( $glossary_entries as $key => $value ) {
		$terms = array();

		$quoted_term = preg_quote( $value->term, '/' );

		$terms[] = $quoted_term;
		$terms[] = $quoted_term . 's';

		if ( 'y' === substr( $value->term, -1 ) ) {
			$terms[] = preg_quote( substr( $value->term, 0, -1 ), '/' ) . 'ies';
		} elseif ( 'f' === substr( $value->term, -1 ) ) {
			$terms[] = preg_quote( substr( $value->term, 0, -1 ), '/' ) . 'ves';
		} elseif ( 'fe' === substr( $value->term, -2 ) ) {
			$terms[] = preg_quote( substr( $value->term, 0, -2 ), '/' ) . 'ves';
		} else {
			if ( 'an' === substr( $value->term, -2 ) ) {
				$terms[] = preg_quote( substr( $value->term, 0, -2 ), '/' ) . 'en';
			}
			$terms[] = $quoted_term . 'es';
			$terms[] = $quoted_term . 'ed';
			$terms[] = $quoted_term . 'ing';
		}

		$glossary_entries_terms[ $key ] = implode( '|', $terms );
	}

	uasort( $glossary_entries_terms, function( $a, $b ) { return gp_strlen($a) < gp_strlen($b); } );

	return $glossary_entries_terms;
}

/**
 * Add markup to a translation original to identify the glossary terms.
 *
 * @param GP_Translation $translation            A GP Translation object.
 * @param GP_Glossary    $glossary               A GP Glossary object.
 * @param array          $glossary_entries_terms A list of terms to highligh.
 *
 * @return obj The marked up translation entry.
 */
function map_glossary_entries_to_translation_originals( $translation, $glossary, $glossary_entries_terms = null ) {
	$glossary_entries = $glossary->get_entries();
	if ( empty( $glossary_entries ) ) {
		return $translation;
	}

	if ( null === $glossary_entries_terms || ! is_array( $glossary_entries_terms ) ) {
		$glossary_entries_terms = gp_sort_glossary_entries_terms( $glossary_entries );
	}

	$glossary_entries_terms_array = array();
	$glossary_term_reference      = array();

	// Since glossary entries terms have been returned as an array of strings, we need to break it up and create back references.
	foreach ( $glossary_entries_terms as $i => $terms ) {
		// Split the terms in to an array and make sure they are all lower case for later use.
		$term_list = explode( '|', $terms );
		$term_list = array_map( 'strtolower', $term_list );

		foreach ( $term_list as $term ) {
			// Add the term to the terms array.
			$glossary_entries_terms_array[] = $term;
			// Make a back reference to the term's glossary entry.
			$glossary_term_reference[ $term ][] = $i;
		}
	}

	// Split the singular string on word boundaries.
	$singular_split    = preg_split( '/\b/', $translation->singular );
	$singular_combined = '';

	// Loop through each chunk of the split to find glossary terms.
	foreach ( $singular_split as $chunk ) {
		// Create an escaped version for use later on.
		$escaped_chunk = esc_translation( $chunk );

		// Create a lower case version to compare with the glossary terms.
		$lower_chunk = strtolower( $chunk );

		// Search the glossary terms for a matching entry.
		if ( false !== array_search( $lower_chunk, $glossary_entries_terms_array, true ) ) {
			$glossary_data = array();

			// Add glossary data for each matching entry.
			foreach ( $glossary_term_reference[ $lower_chunk ] as $glossary_entry_id ) {
				// Get the glossary entry based on the back reference we created earlier.
				$glossary_entry = $glossary_entries[ $glossary_entry_id ];

				// If this is a locale glossary, make a note for the user.
				$locale_entry = '';
				if ( $glossary_entry->glossary_id !== $glossary->id ) {
					/* translators: Denotes an entry from the locale glossary in the tooltip */
					$locale_entry = _x( 'Locale Glossary', 'Bubble', 'glotpress' );
				}

				// Create the data to be added to the span.
				$glossary_data[] = array(
					'translation'  => $glossary_entry->translation,
					'pos'          => $glossary_entry->part_of_speech,
					'comment'      => $glossary_entry->comment,
					'locale_entry' => $locale_entry,
				);
			}

			// Add the span and chunk to our output.
			$singular_combined .= '<span class="glossary-word" data-translations="' . htmlspecialchars( wp_json_encode( $glossary_data ), ENT_QUOTES, 'UTF-8' ) . '">' . $escaped_chunk . '</span>';
		} else {
			// No term was found so just add the escaped chunk to the output.
			$singular_combined .= $escaped_chunk;
		}
	}

	// Assign the output to the translation.
	$translation->singular_glossary_markup = $singular_combined;

	// Add glossary terms to the plural if we have one.
	if ( $translation->plural ) {
		// Split the plural string on word boundaries.
		$plural_split    = preg_split( '/\b/', $translation->plural );
		$plural_combined = '';

		// Loop through each chunk of the split to find glossary terms.
		foreach ( $plural_split as $chunk ) {
			// Create an escaped version for use later on.
			$escaped_chunk = esc_translation( $chunk );

			// Create a lower case version to compare with the glossary terms.
			$lower_chunk = strtolower( $chunk );

			// Search the glossary terms for a matching entry.
			if ( false !== array_search( $lower_chunk, $glossary_entries_terms_array, true ) ) {
				$glossary_data = array();

				// Add glossary data for each matching entry.
				foreach ( $glossary_term_reference[ $lower_chunk ] as $glossary_entry_id ) {
					// Get the glossary entry based on the back reference we created earlier.
					$glossary_entry = $glossary_entries[ $glossary_entry_id ];

					// If this is a locale glossary, make a note for the user.
					$locale_entry = '';
					if ( $glossary_entry->glossary_id !== $glossary->id ) {
						/* translators: Denotes an entry from the locale glossary in the tooltip */
						$locale_entry = _x( 'Locale Glossary', 'Bubble', 'glotpress' );
					}

					// Create the data to be added to the span.
					$glossary_data[] = array(
						'translation'  => $glossary_entry->translation,
						'pos'          => $glossary_entry->part_of_speech,
						'comment'      => $glossary_entry->comment,
						'locale_entry' => $locale_entry,
					);
				}

				// Add the span and chunk to our output.
				$plural_combined .= '<span class="glossary-word" data-translations="' . htmlspecialchars( wp_json_encode( $glossary_data ), ENT_QUOTES, 'UTF-8' ) . '">' . $escaped_chunk . '</span>';
			} else {
				// No term was found so just add the escaped chunk to the output.
				$plural_combined .= $escaped_chunk;
			}
		}

		// Assign the output to the translation.
		$translation->plural_glossary_markup = $plural_combined;
	}

	return $translation;
}

function textareas( $entry, $permissions, $index = 0 ) {
	list( $can_edit, $can_approve ) = $permissions;
	$disabled = $can_edit ? '' : 'disabled="disabled"';
	?>
	<div class="textareas">
		<?php
		if ( isset( $entry->warnings[ $index ] ) ) :
			$referenceable = $entry->warnings[ $index ];

			foreach ( $referenceable as $key => $value ) :
			?>
				<div class="warning secondary">
					<strong><?php _e( 'Warning:', 'glotpress' ); ?></strong> <?php echo esc_html( $value ); ?>

					<?php if ( $can_approve ) : ?>
						<a href="#" class="discard-warning" data-nonce="<?php echo esc_attr( wp_create_nonce( 'discard-warning_' . $index . $key ) ); ?>" data-key="<?php echo esc_attr( $key ); ?>" data-index="<?php echo esc_attr( $index ); ?>"><?php _e( 'Discard', 'glotpress' ); ?></a>
					<?php endif; ?>
				</div>
		<?php
			endforeach;

		endif;
		?>
		<blockquote class="translation"><em><small><?php echo prepare_original( esc_translation( gp_array_get( $entry->translations, $index ) ) ); // WPCS: XSS ok. ?></small></em></blockquote>
		<textarea class="foreign-text" name="translation[<?php echo esc_attr( $entry->original_id ); ?>][]" id="translation_<?php echo esc_attr( $entry->original_id ); ?>_<?php echo esc_attr( $index ); ?>" <?php echo $disabled; // WPCS: XSS ok. ?>><?php echo gp_prepare_translation_textarea( esc_translation( gp_array_get( $entry->translations, $index ) ) ); // WPCS: XSS ok. ?></textarea>

		<p>
			<?php
			if ( $can_edit ) {
				gp_entry_actions();
			}
			elseif ( is_user_logged_in() ) {
				_e( 'You are not allowed to edit this translation.', 'glotpress' );
			}
			else {
				printf( __( 'You <a href="%s">have to log in</a> to edit this translation.', 'glotpress' ), esc_url( wp_login_url( gp_url_current() ) ) );
			}
			?>
		</p>
	</div>
	<?php
}

/**
 * Render multiple notes
 *
 * @param GP_Translation $entry            The translation entry.
 * @param GP_Glossary    $permissions      Permissions of the user.
 *
 * @return void
 */
function render_notes( $entry, $permissions ) {
	list( $can_edit, $can_approve ) = $permissions;
	$notes = GP::$notes->get_by_entry( $entry );
?>
	<dl>
		<dt>
			<?php echo esc_attr__( 'Action Log:', 'glotpress' ) ?>
		</dt>
		<dd class="notes">
			<?php foreach( $notes as $note ) {
				render_note( $note, $can_edit );
			}
			?>
		</dd>
	</dl>
	<dl>
	<?php

		if ( GP::$permission->current_user_can(
			'approve', 'translation', $entry->id, array(
				'translation' => $entry,
			)
		) ) {
		echo '<dt><br>' . esc_attr__( 'New Reviewer note:', 'glotpress' ) . '</dt>';
	?>
			<dt><textarea autocomplete="off" class="foreign-text" name="note[<?php echo esc_attr( $entry->row_id ); ?>]" id="note_<?php echo esc_attr( $entry->row_id ); ?>"></textarea></dt>
			<dt><button class="add-note" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'new-note-' . $entry->id ) ); ?>"><?php esc_attr_e( 'Add note', 'glotpress' ); ?></button></dt>
	<?php
		}
	?>
	</dl>
<?php
}

/**
 * Render the single note interface
 *
 * @param GP_Translation $note          The note object.
 * @param GP_Glossary    $can_edit      Permission of the user.
 *
 * @return void
 */
function render_note( $note, $can_edit ) {
?>
	<div class="note">
		<?php gp_link_user(  get_userdata( $note->user_id ) ); ?>
		<?php esc_attr_e( 'Commented', 'glotpress' ); ?>
		<span class="date"><?php echo esc_html( sprintf( __( '%s ago', 'glotpress' ), human_time_diff( strtotime($note->date_added), time() ) ) );  ?></span>
		<a href="#" class="note-actions" >edit</a>
		<div class="note-body">
			<?php echo nl2br( esc_html( $note->note ) ); ?>
		</div>
		<?php if ( $can_edit || $note->user_id === get_current_user_id() ): ?>
		<div class="note-body edit-note-body" style="display: none;">
			<textarea autocomplete="off" class="foreign-text" name="edit-note[<?php echo esc_attr( $note->id ); ?>]" id="edit-note-<?php echo esc_attr( $note->id ); ?>"><?php echo esc_html( $note->note ); ?></textarea>
			<button class="update-note" tabindex="-1" data-note-id="<?php echo esc_attr( $note->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'edit-note-' . $note->id ) ); ?>">
				<?php esc_attr_e( 'Update Note', 'glotpress' ); ?>
			</button>
			<button class="update-cancel" tabindex="-1">
				<?php esc_attr_e( 'Cancel', 'glotpress' ); ?>
			</button>
			<button class="delete-note" tabindex="-1" data-note-id="<?php echo esc_attr( $note->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'delete-note-' . $note->id ) ); ?>">
				<?php esc_attr_e( 'Delete', 'glotpress' ); ?>
			</button>
		</div>
		<?php endif; ?>

	</div>
<?php
}

function display_status( $status ) {
	$status_labels = [
		'current'  => _x( 'current', 'Single Status', 'glotpress' ),
		'waiting'  => _x( 'waiting', 'Single Status', 'glotpress' ),
		'fuzzy'    => _x( 'fuzzy', 'Single Status', 'glotpress' ),
		'old'      => _x( 'old', 'Single Status', 'glotpress' ),
		'rejected' => _x( 'rejected', 'Single Status', 'glotpress' ),
	];
	if ( isset( $status_labels[ $status ] ) ) {
		$status = $status_labels[ $status ];
	}
	$status = preg_replace( '/^[+-]/', '', $status);
	return $status ? $status : _x( 'untranslated', 'Single Status', 'glotpress' );
}

function references( $project, $entry ) {

	/**
	 * Filter whether to show references of a translation string on a translation row.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean           $references Whether to show references.
	 * @param GP_Project        $project    The current project.
	 * @param Translation_Entry $entry      Translation entry object.
	 */
	$show_references = apply_filters( 'gp_show_references', (bool) $entry->references, $project, $entry );

	if ( ! $show_references ) {
		return;
	}
	?>
	<dl><dt>
	<?php _e( 'References:', 'glotpress' ); ?>
	<ul class="refs">
		<?php
		foreach( $entry->references as $reference ):
			list( $file, $line ) = array_pad( explode( ':', $reference ), 2, 0 );
			if ( $source_url = $project->source_url( $file, $line ) ):
				?>
				<li><a target="_blank" tabindex="-1" href="<?php echo $source_url ; ?>"><?php echo $file.':'.$line ?></a></li>
				<?php
			else :
				echo "<li>$file:$line</li>";
			endif;
		endforeach;
		?>
	</ul></dt></dl>
<?php
}

/**
 * Output the bulk actions toolbar in the translations page.
 *
 * @param string $bulk_action     The URL to submit the form to.
 * @param string $can_write       Can the current user write translations to the database.
 * @param string $translation_set The current translation set.
 * @param string $location        The location of this toolbar, used to make id's unique for each instance on a page.
 */
function gp_translations_bulk_actions_toolbar( $bulk_action, $can_write, $translation_set, $location = 'top' ) {
?>
<form id="bulk-actions-toolbar-<?php echo esc_attr( $location ); ?>" class="filters-toolbar bulk-actions" action="<?php echo $bulk_action; // WPCS: XSS Ok. ?>" method="post">
	<div>
	<select name="bulk[action]" id="bulk-action-<?php echo esc_attr( $location ); ?>" class="bulk-action">
		<option value="" selected="selected"><?php _e( 'Bulk Actions', 'glotpress' ); ?></option>
		<option value="approve"><?php _ex( 'Approve', 'Action', 'glotpress' ); ?></option>
		<option value="reject"><?php _ex( 'Reject', 'Action', 'glotpress' ); ?></option>
		<option value="fuzzy"><?php _ex( 'Fuzzy', 'Action', 'glotpress' ); ?></option>
	<?php if ( $can_write ) : ?>
		<option value="set-priority" class="hide-if-no-js"><?php _e( 'Set Priority', 'glotpress' ); ?></option>
	<?php endif; ?>
		<?php

		/**
		 * Fires inside the bulk action menu for translation sets.
		 *
		 * Printing out option elements here will add those to the translation
		 * bulk options drop down menu.
		 *
		 * @since 1.0.0
		 *
		 * @param GP_Translation_Set $set The translation set.
		 */
		do_action( 'gp_translation_set_bulk_action', $translation_set );
		?>
	</select>
	<?php if ( $can_write ) : ?>
	<select name="bulk[priority]" id="bulk-priority-<?php echo esc_attr( $location ); ?>" class="bulk-priority hidden">
	<?php
	$labels = [
		'hidden' => _x( 'hidden', 'Priority', 'glotpress' ),
		'low'    => _x( 'low', 'Priority', 'glotpress' ),
		'normal' => _x( 'normal', 'Priority', 'glotpress' ),
		'high'   => _x( 'high', 'Priority', 'glotpress' ),
	];

	foreach ( GP::$original->get_static( 'priorities' ) as $value => $label ) {
		if ( isset( $labels[ $label ] ) ) {
			$label = $labels[ $label ];
		}

		echo "\t<option value='" . esc_attr( $value ) . "' " . selected( 'normal', $value, false ) . '>' . esc_html( $label ) . "</option>\n"; // WPCS: XSS Ok.
	}
	?>
	</select>
	<?php endif; ?>
	<input type="hidden" name="bulk[redirect_to]" value="<?php echo esc_attr( gp_url_current() ); ?>" id="bulk-redirect_to-<?php echo esc_attr( $location ); ?>" />
	<input type="hidden" name="bulk[row-ids]" value="" id="bulk-row-ids-<?php echo esc_attr( $location ); ?>" />
	<input type="submit" class="button" value="<?php esc_attr_e( 'Apply', 'glotpress' ); ?>" />
	</div>
	<?php
		$nonce = gp_route_nonce_field( 'bulk-actions', false );
		$nonce = str_replace( 'id="_gp_route_nonce"', 'id="_gp_route_nonce_' . esc_attr( $location ) . '"', $nonce );
		echo $nonce; // WPCS: XSS Ok.
	?>
</form>
<?php
}
