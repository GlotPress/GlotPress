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
	$text             = preg_replace_callback(
		'!(<span class="glossary-word"[^>]+>)!i',
		function( $m ) use ( &$glossary_entries ) {
			$item_number                      = count( $glossary_entries );
			$glossary_entries[ $item_number ] = $m[0];
			return "<span GLOSSARY={$item_number}>";
		},
		$text
	);

	// Wrap full HTML tags with a notranslate class.
	$text = preg_replace( '/(&lt;.+?&gt;)/', '<span class="notranslate">\\1</span>', $text );
	// Break out & back into notranslate for translatable attributes.
	$text = preg_replace( '/(title|aria-label)=([\'"])([^\\2]+?)\\2/', '\\1=\\2</span>\\3<span class="notranslate">\\2', $text );
	// Wrap placeholders with notranslate.
	$text = preg_replace( '/(%(\d+\$(?:\d+)?)?[bcdefgosuxEFGX])/', '<span class="notranslate">\\1</span>', $text );

	// Put the glossaries back!
	$text = preg_replace_callback(
		'!(<span GLOSSARY=(\d+)>)!',
		function( $m ) use ( $glossary_entries ) {
			return $glossary_entries[ $m[2] ];
		},
		$text
	);

	$text = str_replace( array( "\r", "\n" ), "<span class='invisibles' title='" . esc_attr__( 'New line', 'glotpress' ) . "'>&crarr;</span>\n", $text );
	$text = str_replace( "\t", "<span class='invisibles' title='" . esc_attr__( 'Tab character', 'glotpress' ) . "'>&rarr;</span>\t", $text );

	return $text;
}

/**
 * Prepares a translation string to be printed out in a translation row by adding an 'extra' return/newline if
 * it starts with one.
 *
 * @since 3.0.0
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
 * Adds suffixes for use in map_glossary_entries_to_translation_originals().
 *
 * @param array $glossary_entries An array of glossary entries to sort.
 *
 * @return array The suffixed entries.
 */
function gp_glossary_add_suffixes( $glossary_entries ) {
	if ( empty( $glossary_entries ) ) {
		return;
	}

	// Plurals of singular nouns.
	$noun_plural_suffixes = array(
		// Suffix for nouns ending in a sibilant: Add '-es'.
		'ss'  => 'sses',
		'z'   => 'zes',
		'x'   => 'xes',
		'sh'  => 'shes',
		'ch'  => 'ches',
		'tch' => 'tches',
		// Suffix '-y' preceeded by vowel: Add '-s'.
		'ay'  => 'ays',
		'ey'  => 'eys',
		'oy'  => 'oys',
		'uy'  => 'uys',
		// Suffix '-y': Remove '-y' and add '-ies'.
		'y'   => 'ies',
		// Suffix '-an': Remove '-an' and add '-en'.
		'an'  => 'en',
	);

	// Third-person singular for verbs ending in a sibilant. Suffix: '-es'. Ending with '-o' preceeded by consonant, not added (examples: 'go', 'do').
	$verb_third_person_suffixes = array(
		'ss'  => 'sses',
		'z'   => 'zes',
		'x'   => 'xes',
		'sh'  => 'shes',
		'ch'  => 'ches',
		'tch' => 'tches',
	);

	// Past simple tense and past participle of most verbs. Suffix '-ed'.
	$verb_past_suffixes = array(
		'e' => 'ed',
	);

	// Present participle and gerund of verbs. Suffix '-ing'.
	$verb_present_suffixes = array(
		'ee' => 'eeing',
		're' => 'ring',
		'e'  => 'ing',
	);

	// Present participle and gerund of verbs. Suffix '-ing'.
	$verb_to_noun_suffixes = array(
		// Suffix '-ion' cases.
		'ate'    => 'ation',
		'ize'    => 'ization',
		'ify'    => 'ification',
		'efy'    => 'efaction',
		'aim'    => 'amation',
		'pt'     => 'ption',
		'scribe' => 'scription',
		'aim'    => 'amation',
		'ceive'  => 'ception',
		'sume'   => 'sumption',
		'ct'     => 'ction',
		'ete'    => 'etion',
		'ute'    => 'ution',
		'olve'   => 'olution',
		'ite'    => 'ition',
		'it'     => 'ition',
		'ose'    => 'osition',
		// Suffix '-ve' cases.
		//'f'      => 'fs',
		//'fe'     => 'ves',
	);

	$glossary_entries_suffixes = array();

	// Create array of glossary terms, longest first.
	foreach ( $glossary_entries as $key => $value ) {
		$term = strtolower( $value->term );
		$type = $value->part_of_speech;

		// Check if is multiple word term.
		if ( preg_match( '/\s/', $term ) ) {

			// Don't add suffix to terms with multiple words.
			$glossary_entries_suffixes[ $term ] = array();
			continue;
		}

		$suffixes = array();

		if ( 'noun' === $type ) { // Add known suffixes for single word nouns.

			$noun_known = false;

			foreach ( $noun_plural_suffixes as $ending => $suffix ) {
				// Form the plural for nouns ending with known ending.
				if ( substr( $term, - strlen( $ending ) ) === $ending ) {
					$glossary_entries_suffixes[ substr( $term, 0, - strlen( $ending )) ][] = $suffix;
					$noun_known = true;
					break;
				}
			}

			// Check if term isn't plural or don't end with '-s'.
			if ( ! $noun_known && 's' !== substr( $term, -1 ) ) {
				$glossary_entries_suffixes[ $term ][] = 's';
			}

		} elseif ( 'verb' === $type ) { // Add known suffixes for verbs.

			// Verb in third-person.
			$verb_third_person = false;
			foreach ( $verb_third_person_suffixes as $ending => $suffix ) {
				// Check if noun ends with known suffix.
				if ( substr( $term, - strlen( $ending ) ) === $ending ) {
					$glossary_entries_suffixes[ substr( $term, 0, - strlen( $ending ) ) ][] = $suffix;
					$verb_third_person = true;
					break;
				}
			}
			if ( ! $verb_third_person ) {
				// Form the third-person singular for most verbs.
				$glossary_entries_suffixes[ $term ][] = 's';
			}

			// Form the past simple tense and past participle of most verbs.
			if ( 'e' === substr( $term, -1 ) ) {
				$glossary_entries_suffixes[ substr( $term, 0, -1 ) ][] = 'ed';
			} else {
				$glossary_entries_suffixes[ $term ][] = 'd';
			}

			// Form the present participle and gerund of verbs.
			$verb_present = false;
			foreach ( $verb_present_suffixes as $ending => $suffix ) {
				// Check if noun ends with known suffix.
				if ( substr( $term, - strlen( $ending ) ) === $ending ) {
					$glossary_entries_suffixes[ substr( $term, 0, - strlen( $ending ) ) ][] = $suffix;
					$verb_present = true;
					break;
				}
			}

			if ( ! $verb_present ) {
				// Form the third-person singular for most verbs.
				$glossary_entries_suffixes[ $term ][] = 'ing';
			}

			// Form noun from verb by adding a suffix.
			foreach ( $verb_to_noun_suffixes as $ending => $suffix ) {
				// Check if noun ends with known suffix.
				if ( substr( $term, - strlen( $ending ) ) === $ending ) {
					$glossary_entries_suffixes[ $term ][] = $suffix;
					break;
				}
			}
		}
	}

	// Sort by length in descending order.
	uksort(
		$glossary_entries_suffixes,
		function( $a, $b ) {
			return mb_strlen( $b ) <=> mb_strlen( $a );
		}
	);

	return $glossary_entries_suffixes;
}

/**
 * Add markup to a translation original to identify the glossary terms.
 *
 * @param GP_Translation $translation            A GP Translation object.
 * @param GP_Glossary    $glossary               A GP Glossary object.
 *
 * @return obj The marked up translation entry.
 */
function map_glossary_entries_to_translation_originals( $translation, $glossary ) {
	static $terms_search, $glossary_entries_reference, $glossary_entries, $cached_glossary;
	if ( isset( $terms_search ) && isset( $cached_glossary ) && $cached_glossary === $glossary->id ) {
		if ( ! $terms_search ) {
			return $translation;
		}
	} else {
		// Build our glossary search.
		$glossary_entries = $glossary->get_entries();
		$cached_glossary = $glossary->id;
		if ( empty( $glossary_entries ) ) {
			$terms_search = false;
			return $translation;
		}

		$glossary_entries_suffixes = gp_glossary_add_suffixes( $glossary_entries );

		$glossary_entries_reference = array();
		foreach ( $glossary_entries as $id => $value ) {
			$term = strtolower( $value->term );
			if ( ! isset( $glossary_entries_reference[ $term ] ) ) {
				$glossary_entries_reference[ $term ] = array( $id );
				continue;
			}
			$glossary_entries_reference[ $term ][] = $id;
		}

		$terms_search = '\b(';
		foreach ( $glossary_entries_suffixes as $term => $suffixes ) {
			$terms_search .= preg_quote( $term, '/' );

			if ( ! empty( $suffixes ) ) {
				$terms_search .= '(?:' . implode( '|', $suffixes ) . ')?';
			}

			$terms_search .= '|';

			$referenced_term = $term;
			if ( ! isset( $glossary_entries_reference[ $referenced_term ] ) ) {
				foreach ( $suffixes as $suffix ) {
					if ( isset( $glossary_entries_reference[ $term . $suffix ] ) ) {
						$referenced_term = $term . $suffix;
					}
				}
				if ( ! isset( $glossary_entries_reference[ $referenced_term ] ) ) {
					// This should not happen but we don't want to access a non existing item below.
					continue;
				}
			}

			$referenced_term = $glossary_entries_reference[ $referenced_term ];
			// Add the suffixed terms to the lookup table.
			foreach ( $suffixes as $suffix ) {
				if ( isset( $glossary_entries_reference[ $term . $suffix ] ) ) {
					$glossary_entries_reference[ $term . $suffix ] = array_values( array_unique( array_merge( $glossary_entries_reference[ $term . $suffix ], $referenced_term ) ) );
				} else {
					$glossary_entries_reference[ $term . $suffix ] = $referenced_term;
				}
			}
		}

		// Remove the trailing |.
		$terms_search = substr( $terms_search, 0, -1 );
		$terms_search .= ')\b';
	}

	// Split the singular string on glossary terms boundaries.
	$singular_split = preg_split( '/' . $terms_search . '/i', $translation->singular, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

	// Loop through each chunk of the split to find glossary terms.
	if ( is_array( $singular_split ) ) {
		$singular_combined = '';

		foreach ( $singular_split as $chunk ) {
			// Create an escaped version for use later on.
			$escaped_chunk = esc_translation( $chunk );

			// Create a lower case version to compare with the glossary terms.
			$lower_chunk = strtolower( $chunk );

			// Search the glossary terms for a matching entry.
			if ( isset( $glossary_entries_reference[ $lower_chunk ] ) ) {
				$glossary_data = array();

				// Add glossary data for each matching entry.
				foreach ( $glossary_entries_reference[ $lower_chunk ] as $glossary_entry_id ) {
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
	} else {
		$translation->singular_glossary_markup = esc_translation( $translation->singular );
	}

	// Add glossary terms to the plural if we have one.
	if ( $translation->plural ) {
		// Split the plural string on glossary terms boundaries.
		$plural_split = @preg_split( '/' . $terms_search . '/i', $translation->plural, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

		// Loop through each chunk of the split to find glossary terms.
		if ( is_array( $plural_split ) ) {
			$plural_combined = '';

			foreach ( $plural_split as $chunk ) {
				// Create an escaped version for use later on.
				$escaped_chunk = esc_translation( $chunk );

				// Create a lower case version to compare with the glossary terms.
				$lower_chunk = strtolower( $chunk );

				// Search the glossary terms for a matching entry.
				if ( isset( $glossary_entries_reference[ $lower_chunk ] ) ) {
					$glossary_data = array();

					// Add glossary data for each matching entry.
					foreach ( $glossary_entries_reference[ $lower_chunk ] as $glossary_entry_id ) {
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
		} else {
			$translation->plural_glossary_markup = esc_translation( $translation->plural );
		}
	}

	return $translation;
}

function textareas( $entry, $permissions, $index = 0 ) {
	list( $can_edit, $can_approve ) = $permissions;
	?>
	<div class="textareas">
		<?php
		if ( isset( $entry->warnings[ $index ] ) ) :
			$referenceable = $entry->warnings[ $index ];

			foreach ( $referenceable as $key => $value ) :
			?>
				<div class="warning">
					<strong><?php _e( 'Warning:', 'glotpress' ); ?></strong> <?php echo esc_html( $value ); ?>

					<?php if ( $can_approve ) : ?>
						<a href="#" class="discard-warning" data-nonce="<?php echo esc_attr( wp_create_nonce( 'discard-warning_' . $index . $key ) ); ?>" data-key="<?php echo esc_attr( $key ); ?>" data-index="<?php echo esc_attr( $index ); ?>"><?php _e( 'Discard', 'glotpress' ); ?></a>
					<?php endif; ?>
				</div>
		<?php
			endforeach;

		endif;
		?>
		<blockquote class="translation"><?php echo prepare_original( esc_translation( gp_array_get( $entry->translations, $index ) ) ); ?></blockquote>
		<textarea class="foreign-text" name="translation[<?php echo esc_attr( $entry->original_id ); ?>][]" id="translation_<?php echo esc_attr( $entry->original_id ); ?>_<?php echo esc_attr( $index ); ?>" <?php echo disabled( ! $can_edit ); ?>><?php echo gp_prepare_translation_textarea( esc_translation( gp_array_get( $entry->translations, $index ) ) ); ?></textarea>

		<div>
			<?php
			if ( $can_edit ) {
				gp_entry_actions();
			} elseif ( is_user_logged_in() ) {
				_e( 'You are not allowed to edit this translation.', 'glotpress' );
			} else {
				printf(
					/* translators: %s: URL. */
					__( 'You <a href="%s">have to log in</a> to edit this translation.', 'glotpress' ),
					esc_url( wp_login_url( gp_url_current() ) )
				);
			}
			?>
		</div>
	</div>
	<?php
}

function display_status( $status ) {
	$status_labels = array(
		'current'  => _x( 'current', 'Single Status', 'glotpress' ),
		'waiting'  => _x( 'waiting', 'Single Status', 'glotpress' ),
		'fuzzy'    => _x( 'fuzzy', 'Single Status', 'glotpress' ),
		'old'      => _x( 'old', 'Single Status', 'glotpress' ),
		'rejected' => _x( 'rejected', 'Single Status', 'glotpress' ),
	);
	if ( isset( $status_labels[ $status ] ) ) {
		$status = $status_labels[ $status ];
	}
	$status = preg_replace( '/^[+-]/', '', $status );
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
		foreach ( $entry->references as $reference ) :
			list( $file, $line ) = array_pad( explode( ':', $reference ), 2, 0 );
			$source_url          = $project->source_url( $file, $line );
			if ( $source_url ) :
				?>
				<li>
					<a target="_blank" tabindex="-1" href="<?php echo esc_url( $source_url ); ?>">
						<?php echo esc_html( $file . ':' . $line ); ?>
					</a>
				</li>
				<?php
			else :
				echo '<li>' . esc_html( "$file:$line" ) . '</li>';
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
<form id="bulk-actions-toolbar-<?php echo esc_attr( $location ); ?>" class="bulk-actions" action="<?php echo esc_attr( $bulk_action ); ?>" method="post">
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
	$labels = array(
		'hidden' => _x( 'hidden', 'Priority', 'glotpress' ),
		'low'    => _x( 'low', 'Priority', 'glotpress' ),
		'normal' => _x( 'normal', 'Priority', 'glotpress' ),
		'high'   => _x( 'high', 'Priority', 'glotpress' ),
	);

	foreach ( GP::$original->get_static( 'priorities' ) as $value => $label ) {
		if ( isset( $labels[ $label ] ) ) {
			$label = $labels[ $label ];
		}

		echo "\t<option value='" . esc_attr( $value ) . "' " . selected( 'normal', $value, false ) . '>' . esc_html( $label ) . "</option>\n";
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
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $nonce;
	?>
</form>
<?php
}
