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
	$text = preg_replace_callback(
		'!(<span class="glossary-word"[^>]+>)!i', function( $m ) use ( &$glossary_entries ) {
		$item_number = count( $glossary_entries );
		$glossary_entries[ $item_number ] = $m[0];
		return "<span GLOSSARY={$item_number}>";
		}, $text
	);

	// Wrap full HTML tags with a notranslate class
	$text = preg_replace( '/(&lt;.+?&gt;)/', '<span class="notranslate">\\1</span>', $text );
	// Break out & back into notranslate for translatable attributes
	$text = preg_replace( '/(title|aria-label)=([\'"])([^\\2]+?)\\2/', '\\1=\\2</span>\\3<span class="notranslate">\\2', $text );
	// Wrap placeholders with notranslate
	$text = preg_replace( '/(%(\d+\$(?:\d+)?)?[bcdefgosuxEFGX])/', '<span class="notranslate">\\1</span>', $text );

	// Put the glossaries back!
	$text = preg_replace_callback(
		'!(<span GLOSSARY=(\d+)>)!', function( $m ) use ( $glossary_entries ) {
		return $glossary_entries[ $m[2] ];
		}, $text
	);

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
	if ( empty( $glossary_entries ) ) {
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

	uasort(
		$glossary_entries_terms, function( $a, $b ) {
		return gp_strlen( $a ) < gp_strlen( $b );
		}
	);

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

	// Save our current singular/plural strings before attempting any markup change. Also escape now, since we're going to add some html.
	$translation->singular_glossary_markup = esc_translation( $translation->singular );
	$translation->plural_glossary_markup   = esc_translation( $translation->plural );

	// Search for glossary terms in our strings.
	$matching_entries = array();

	foreach ( $glossary_entries_terms as $i => $terms ) {
		$glossary_entry = $glossary_entries[ $i ];
		if ( preg_match_all( '/\b(' . $terms . ')\b/i', $translation->singular . ' ' . $translation->plural, $m ) ) {
			$locale_entry = '';
			if ( $glossary_entry->glossary_id !== $glossary->id ) {
				/* translators: Denotes an entry from the locale glossary in the tooltip */
				$locale_entry = _x( 'Locale Glossary', 'Bubble', 'glotpress' );
			}

			foreach ( $m[1] as $value ) {
				$matching_entries[ $value ][] = array(
					'translation'  => $glossary_entry->translation,
					'pos'          => $glossary_entry->part_of_speech,
					'comment'      => $glossary_entry->comment,
					'locale_entry' => $locale_entry,
				);
			}
		}
	}

	// Replace terms in strings with markup.
	foreach ( $matching_entries as $term => $glossary_data ) {
		$replacement = '<span class="glossary-word" data-translations="' . htmlspecialchars( wp_json_encode( $glossary_data ), ENT_QUOTES, 'UTF-8' ) . '">$1</span>';

		$regex = '/\b(' . preg_quote( $term, '/' ) . ')(?![^<]*<\/span>)\b/iu';
		$translation->singular_glossary_markup = preg_replace( $regex, $replacement, $translation->singular_glossary_markup );

		if ( $translation->plural ) {
			$translation->plural_glossary_markup = preg_replace( $regex, $replacement, $translation->plural_glossary_markup );
		}
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
	} elseif ( is_user_logged_in() ) {
		_e( 'You are not allowed to edit this translation.', 'glotpress' );
	} else {
		// translators: The placeholder is the login link
		printf( __( 'You <a href="%s">have to log in</a> to edit this translation.', 'glotpress' ), esc_url( wp_login_url( gp_url_current() ) ) );
	}
	?>
		</p>
	</div>
	<?php
}

function render_notes( $entry, $permissions ) {
	list( $can_edit, $can_approve ) = $permissions;
	$notes = GP::$notes->get_by_entry( $entry );
?>
	<dl>
		<dt>
			<?php echo __( 'Action Log:', 'glotpress' ) ?>
		</dt>
		<dd class="notes">
			<?php foreach($notes as $note) {
				render_note($note, $can_edit);
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
		echo '<dt><br>' . __( 'New Reviewer note:', 'glotpress' ) . '</dt>';
	?>
			<dt><textarea autocomplete="off" class="foreign-text" name="note[<?php echo esc_attr( $entry->row_id ); ?>]" id="note_<?php echo esc_attr( $entry->row_id ); ?>"></textarea></dt>
			<dt><button class="add-note" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'new-note-' . $entry->id ) ); ?>"><?php _e( 'Add note', 'glotpress' ); ?></button></dt>
	<?php
		}
	?>
	</dl>
<?php
}

function render_note( $note, $can_edit ) {
?>
	<div class="note">
		<?php gp_link_user(  get_userdata( $note->user_id )); ?>
		<?php _e('Commented', 'glotpress'); ?>
		<span class="date"><?php echo esc_html( sprintf( __( '%s ago', 'glotpress' ), human_time_diff( strtotime($note->date_added), time() ) ) );  ?></span>
		<a href="#" class="note-actions" >edit</a>
		<div class="note-body">
			<?php echo nl2br( esc_html( $note->note ) ); ?>	
		</div>
		<?php if ( $can_edit || $note->user_id === get_current_user_id() ): ?>
		<div class="note-body edit-note-body" style="display: none;">
			<textarea autocomplete="off" class="foreign-text" name="edit-note[<?php echo esc_attr( $note->id ); ?>]" id="edit-note-<?php echo esc_attr( $note->id ); ?>"><?php echo esc_html( $note->note ); ?></textarea>
			<button class="update-note" tabindex="-1" data-note-id="<?php echo esc_attr( $note->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'edit-note-' . $note->id ) ); ?>">
				<?php _e( 'Update Note', 'glotpress' ); ?>
			</button>
			<button class="update-cancel" tabindex="-1">
				<?php _e( 'Cancel', 'glotpress' ); ?>
			</button>
			<button class="delete-note" tabindex="-1" data-note-id="<?php echo esc_attr( $note->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'delete-note-' . $note->id ) ); ?>">
				<?php _e( 'Delete', 'glotpress' ); ?>
			</button>
		</div>
		<?php endif; ?>

	</div>
<?php
}

function display_status( $status ) {
	$status = preg_replace( '/^[+-]/', '', $status );
	return $status ? $status : __( 'untranslated', 'glotpress' );
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
		foreach ( $entry->references as $reference ) :
			list( $file, $line ) = array_pad( explode( ':', $reference ), 2, 0 );
			if ( $source_url = $project->source_url( $file, $line ) ) :
	?>
				<li><a target="_blank" tabindex="-1" href="<?php echo $source_url; ?>"><?php echo esc_html( $file ) . ':' . esc_html( $line ); ?></a></li>
	<?php
			else :
				echo '<li>' . esc_html( $file ) . ':' . esc_html( $line ) . '</li>';
			endif;
		endforeach;
	?>
	</ul></dt></dl>
	<?php
}
