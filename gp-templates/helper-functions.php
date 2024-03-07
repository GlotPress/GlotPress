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
	// Wrap placeholders with 'notranslate placeholder'.
	$text = preg_replace( '/(%(?:(?:\d+\$)?(?:\d+)?)?[bcdefglosuxEFGX%@])/', '<span class="notranslate placeholder">\\1</span>', $text );

	// Highlight two or more spaces between words.
	$text = preg_replace( '/(?!^)  +(?!$)/', '<span class="invisible-spaces">$0</span>', $text );
	// Highlight leading and trailing spaces in single lines.
	$text = preg_replace( '/^ +| +$/', '<span class="invisible-spaces">$0</span>', $text );
	// Highlight leading spaces in multi lines.
	$text = preg_replace( "/\n( +)/", "\n<span class=\"invisible-spaces\">$1</span>", $text );
	// Highlight trailing spaces in multi lines.
	$text = preg_replace( "/( +)\n/", "<span class=\"invisible-spaces\">$1</span>\n", $text );

	$text = str_replace( array( "\r", "\n" ), "<span class='invisibles' title='" . esc_attr__( 'New line', 'glotpress' ) . "'>&crarr;</span>\n", $text );
	$text = str_replace( "\t", "<span class='invisibles' title='" . esc_attr__( 'Tab character', 'glotpress' ) . "'>&rarr;</span>\t", $text );

	// Put the glossaries back!
	$text = preg_replace_callback(
		'!(<span GLOSSARY=(\d+)>)!',
		function( $m ) use ( $glossary_entries ) {
			return $glossary_entries[ $m[2] ];
		},
		$text
	);

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

	$suffixes = array(

		/**
		 * Array structure of suffix matcing cases:
		 *   - 'preceded'  String with value or regex pattern of what precedes the ending of the word.
		 *   - 'endings'   Array of endings to match. Key: String or regex pattern of original ending. Value: String of changed ending, can include placeholder (single %s or multiple %1$s) to represent the original ending.
		 *   - 'add'       String to add after the changed ending.
		 *
		 * Example array(
		 *   'preceded' => '[aeiou]', // Preceded by any vowel.
		 *   'endings'  => array(
		 *     'ss'     => '%s',          // Ending with '-ss'.              Add 'es'. Kiss and ki[ss]-es, pass and pa[ss]-es.
		 *     '[sxz]'  => '%s',          // Ending with '-s', '-x' or '-z'. Add 'es'. Bias and bia[s]-es, fix and fi[x]-es, quiz and qui[z]-es.
		 *     'sh'     => '%s',          // Ending with '-sh'.              Add 'es'. Dish and di[sh]-es.
		 *     'ch'     => '%s',          // Ending with '-ch'.              Add 'es'. Coach and coa[ch]-es.
		 *     '[rt]'   => '%1$s%1$sing', // Ending with '-r' or '-t'.       Doubles the consonant, add 'ing' and add 'es'. Prefer and prefe[rring]-es Visit and visi[tting]-es (non-existing matches to show the possibilies).
		 *   ),
		 *   'add'      => 'es', // Add 'es'.
		 * )
		 */

		/**
		 * Plurals of singular nouns.
		 * https://www.thefreedictionary.com/Forming-Plurals.htm.
		 * https://preply.com/en/blog/simple-rules-for-the-formation-of-plural-nouns-in-english/.
		 */
		'noun'         => array(

			// Ending in a sibilant. Suffix: '-es'.
			array(
				'preceded' => '[a-z]', // Preceded by any letter.
				'endings'  => array(
					'ss' => '%s', // Add 'es'. Kiss and kiss-es.
					'z'  => '%s', // Add 'es'. Waltz and waltz-es.
					'x'  => '%s', // Add 'es'. Box and box-es.
					'sh' => '%s', // Add 'es'. Dish and dish-es.
					'ch' => '%s', // Add 'es'. Coach and coach-es.
				),
				'add'      => 'es', // Add 'es'.
			),

			// Ending with '-y' preceded by vowel. Suffix: '-s'.
			array(
				'preceded' => '[aeiou]', // Preceded by any vowel.
				'endings'  => array(
					'y' => '%s', // Add 's'. Delay and delay-s, key and key-s, toy and toy-s, guy and guy-s.
				),
				'add'      => 's',       // Add 's'.
			),

			// Ending with '-o' and '-y' preceded by consonant. Suffix: '-es'.
			array(
				'preceded' => '[b-df-hj-np-tv-xz]', // Preceded by any consonant.
				'endings'  => array(
					'y' => 'i',  // Change to 'i-es'. Lady and ladi-es.
					'o' => '%s', // Add 'es'.         Hero and hero-es, tomato and tomato-es.
				),
				'add'      => 'es',                 // Add 'es'.
			),

			// Ending with '-f', '-fe' or '-s'. Suffix: '-es'.
			array(
				'preceded' => '[a-z]', // Preceded by any letter.
				'endings'  => array(
					'fe' => 'v',  // Change to 'v-es'. Wife and wiv-es.
					'f'  => 'v',  // Change to 'v-es'. Leaf and leav-es, wolf and wolv-es.
					's'  => '%s', // Add 'es'.         Bus and bus-es, lens and len-ses.
				),
				'add'      => 'es', // Add 'es'.
			),

			// Fallback suffix for most nouns not ended with '-s'. Suffix: '-s'.
			array(
				'preceded' => '[a-z]', // Preceded by any letter.
				'endings'  => array(
					'\w(?<!z|x|sh|ch|s|y|fe)' => '%s', // Add 's'. None of the above except 'f' because of words like 'Chief' which plural is '-s'.
				),
				'add'      => 's', // Add 's'.
			),

		),

		/**
		 * Verb tenses.
		 */
		'verb'         => array(

			/**
			 * Third-person singular for verbs.
			 */

			// Ending in a sibilant. Suffix: '-es'.
			array(
				'preceded' => '[a-z]', // Preceded by any letter.
				'endings'  => array(
					's'  => '%s', // Add 'es'. Pass and pass-es, bias and bias-es, focus and focus-es.
					'z'  => '%s', // Add 'es'. Quiz and quiz-es.
					'x'  => '%s', // Add 'es'. Fix and fix-es.
					'sh' => '%s', // Add 'es'. Push and push-es.
					'ch' => '%s', // Add 'es'. Watch and watch-es.
				),
				'add'      => 'es', // Add 'es'.
			),

			// Ending with '-y' preceded by vowel. Suffix: '-s'.
			array(
				'preceded' => '[aeiou]', // Preceded by any vowel.
				'endings'  => array(
					'y' => '%s', // Add 's'. Play and play-s.
				),
				'add'      => 's',       // Add 's'.
			),

			// Ending with '-o' and '-y' preceded by consonant. Suffix: '-es'.
			array(
				'preceded' => '[b-df-hj-np-tv-xz]', // Preceded by any consonant.
				'endings'  => array(
					'y' => 'i',  // Change to 'i-es'. Try and tri-es.
					'o' => '%s', // Add 'es'.         Go and go-es, do and do-es.
				),
				'add'      => 'es',                 // Add 'es'.
			),

			// Fallback suffix for most verbs. Suffix: '-s'.
			array(
				'preceded' => '[a-z]', // Preceded by any letter.
				'endings'  => array(
					'\w(?<!z|x|sh|ch|s|y|o)' => '%s', // Add 's'. None of the above. Format and format-s, make and make-s, pull and pull-s.
				),
				'add'      => 's',  // Add 's'.
			),

			/**
			 * Past simple tense and past participle of verbs.
			 */

			// Not ending with '-e', and ending with '-e'. Suffix '-ed'.
			array(
				'preceded' => '[a-z]', // Preceded by any letter.
				'endings'  => array(
					// Not ending with '-e'.
					'\w(?<!e)' => '%s', // Add 'ed'.       Fix and fix-ed, push and push-ed.
					// Ending with '-e'.
					'e'        => '',   // Change to 'ed'. Contribute and contribut-ed, delete and delet-ed.
				),
				'add'      => 'ed', // Add 'ed'.
			),

			// Ending with consonant preceded by vowel, independently of the stressed syllable. Suffix: '-ed'.
			array(
				'preceded' => '[aeiou]', // Preceded by any vowel.
				'endings'  => array(
					'[b-df-hj-np-tv-xz]' => '%s', // Add 'ed'. Visit and visit-ed, develop and develop-ed.
				),
				'add'      => 'ed', // Add 'ed'.
			),

			// Ending with consonant preceded by vowel, independently of the stressed syllable. Suffix: Repeat ending consonant and '-ed'.
			array(
				'preceded' => '[aeiou]', // Preceded by any vowel.
				'endings'  => array(
					'[b-df-hj-np-tv-xz]' => '%1$s%1$s', // Double ending consonant and add 'ed'. Commit and committ-ed, prefer and preferr-ed, travel and travell-ed.
				),
				'add'      => 'ed', // Add 'ed'.
			),

			/**
			 * Present participle and gerund of verbs.
			 */

			// Not ending with '-e', ending with '-ee', '-ye' or '-oe', and ending with '-e'. Suffix '-ing'.
			array(
				'preceded' => '[a-z]', // Preceded by any letter.
				'endings'  => array(
					// Not ending with '-e'.
					'\w(?<!e)' => '%s', // Add 'ing'.        Fix and fix-ing, push and push-ing.
					// Ending with '-ee', '-ye' or '-oe'.
					'ee'       => '%s', // Add 'ing'.        Agree and agree-ing, see and see-ing.
					'ye'       => '%s', // Add 'ing'.        Dye and dye-ing.
					'oe'       => '%s', // Add 'ing'.        Tiptoe and tiptoe-ing.
					// Ending with single '-e'.
					'e'        => '',   // Change to 'ing'. Contribute and contribut-ing, delete and delet-ing, care and car-ing.
				),
				'add'      => 'ing', // Add 'ing'.
			),

			// Ending with consonant preceded by vowel, independently of the stressed syllable. Suffix: '-ing'.
			array(
				'preceded' => '[aeiou]', // Preceded by any vowel.
				'endings'  => array(
					'[b-df-hj-np-tv-xz]' => '%s', // Add 'ing'. Visit and visit-ing, develop and develop-ing.
				),
				'add'      => 'ing', // Add 'ing'.
			),

			// Ending with consonant preceded by vowel, independently of the stressed syllable. Suffix: Repeat ending consonant and '-ing'.
			array(
				'preceded' => '[aeiou]', // Preceded by any vowel.
				'endings'  => array(
					'[b-df-hj-np-tv-xz]' => '%1$s%1$s', // Double ending consonant and add 'ing'. Commit and committ-ing, prefer and preferr-ing, travelling and travell-ing.
				),
				'add'      => 'ing', // Add 'ing'.
			),

			/**
			 * Nouns formed by Verbs.
			 * https://www.thefreedictionary.com/Commonly-Confused-Suffixes-tion-vs-sion.htm.
			 */

			// Verbs that form nouns ending with suffix '-tion'.
			array(
				'preceded' => '[a-z]', // Preceded by any letter.
				'endings'  => array(
					// General.
					'ate'    => 'a',     // Change to 'a-tion'.     Abbreviate and abbrevia-tion.
					'ize'    => 'iza',   // Change to 'iza-tion'.   Authorize and authoriza-tion.
					'ify'    => 'ifica', // Change to 'ifica-tion'. Specify and specifica-tion.
					'efy'    => 'efac',  // Change to 'efaca-tion'. Liquefy and liquefac-tion.
					'aim'    => 'ama',   // Change to 'ama-tion'.   Exclaim and exclama-tion.
					'pt'     => 'p',     // Change to 'p-tion'.     Encrypt and encryp-tion.
					'scribe' => 'scrip', // Change to 'scrip-tion'. Subscribe and subscrip-tion.
					'ceive'  => 'cep',   // Change to 'cep-tion'.   Perceive and percep-tion.
					'sume'   => 'sump',  // Change to 'sump-tion'.  Resume and resump-tion.
					'ct'     => 'c',     // Change to 'c-tion'.     Correct and correc-tion.
					'ete'    => 'e',     // Change to 'e-tion'.     Delete and dele-tion.
					'it'     => 'i',     // Change to 'i-tion'.     Edit and edi-tion.
					'ite'    => 'i',     // Change to 'i-tion'.     Ignite and igni-tion.
					'ute'    => 'u',     // Change to 'u-tion'.     Contribute and contribu-tion.
					'olve'   => 'olu',   // Change to 'olu-tion'.   Resolve and resolu-tion.
					'ose'    => 'osi',   // Change to 'osi-tion'.   Compose and composi-tion.
					// After 'n' cases.
					'tain'   => 'ten',   // Change to 'ten-tion'.   Abstain and absten-tion.
					'vene'   => 'ven',   // Change to 'ven-tion'.   Contravene and contraven-tion.
					'vent'   => 'ven',   // Change to 'ven-tion'.   Prevent and preven-tion.
					// After 'r' cases.
					'rt'     => 'r',     // Change to 'r-tion'.     Insert and inser-tion.
				),
				'add'      => 'tion', // Add 'tion'.
			),

			// Verbs that form nouns ending with suffix '-sion'.
			array(
				'preceded' => '[a-z]', // Preceded by any letter.
				'endings'  => array(
					// General.
					'ade'  => 'a',   // Change to 'a-sion'.   Invade and inva-sion.
					'cede' => 'ces', // Change to 'ces-sion'. Precede and preces-sion.
					'ide'  => 'i',   // Change to 'i-sion'.   Decide and deci-sion.
					'ode'  => 'o',   // Change to 'o-sion'.   Explode and explo-sion.
					'ude'  => 'u',   // Change to 'u-sion'.   Exclude and exclu-sion.
					'ise'  => 'i',   // Change to 'i-sion'.   Supervise and supervi-sion.
					'use'  => 'u',   // Change to 'u-sion'.   Confuse and confu-sion.
					'pel'  => 'pul', // Change to 'pul-sion'. Expel and expul-sion.
					'mit'  => 'mis', // Change to 'mis-sion'. Submit and submis-sion.
					'ss'   => 's',   // Change to 's-sion'.   Compress and compres-sion.
					// After 'n' cases.
					'end'  => 'en',  // Change to 'en-sion'.  Extend and exten-sion.
					// After 'r' cases.
					'vert' => 'ver', // Change to 'ver-sion'. Convert and conver-sion.
					'erse' => 'er',  // Change to 'er-sion'.  Disperse and disper-sion.
					'ur'   => 'ur',  // Change to 'ur-sion'.  Recur and recur-sion.
					'erge' => 'er',  // Change to 'er-sion'.  Emerge and emer-sion.
				),
				'add'      => 'sion', // Add 'sion'.
			),

		),

		// Plurals of adverbs.
		'adjective'    => array(),

		// Plurals of adverbs.
		'adverb'       => array(),

		// Plurals of interjections.
		'interjection' => array(),

		// Plurals of conjunctions.
		'conjunction'  => array(),

		// Plurals of prepositions.
		'preposition'  => array(),

		// Plurals of pronouns.
		'pronoun'      => array(),

		// Plurals of expressions.
		'expression'   => array(),

		// Plurals of abbreviations.
		'abbeviation'  => array(),

	);

	/**
	 * Filter the list of Suffixes to match glossary terms for each Part of Speech.
	 *
	 * @since 4.0.0
	 */
	$suffixes = apply_filters( 'gp_glossary_match_suffixes', $suffixes );

	$glossary_entries_suffixes = array();

	// Create array of glossary terms, longest first.
	foreach ( $glossary_entries as $value ) {
		$term = strtolower( $value->term );
		$type = $value->part_of_speech;

		// Check if is multiple word term.
		if ( preg_match( '/\s/', $term ) ) {

			// Don't add suffix to terms with multiple words.
			$glossary_entries_suffixes[ $term ] = array();
			continue;
		}

		// Filter out suffixes with empty values.
		$suffixes = array_filter( $suffixes, fn( $value ) => ! empty( $value ) );

		// Add suffixes for part_of_speech with rules.
		if ( ! empty( $suffixes[ $type ] ) ) {
			// Loop through rules.
			foreach ( $suffixes[ $type ] as $rule ) {

				// Loop through rule endings.
				foreach ( $rule['endings'] as $ending_pattern => $new_ending ) {

					// Check if noun ends with known suffix.
					if ( preg_match( '/' . $rule['preceded'] . $ending_pattern . '\b/i', $term, $match ) ) {

						// Set ending.
						$old_ending = preg_replace( '/^' . $rule['preceded'] . '/', '', $match[0] );

						// Format endings using %s or %1$s placeholders to allow using the ending itself in the changing string.
						// Eg.: '%1$s%1$s' to match 'Commi[t]' -> Commi[tt][ing]
						$new_ending = sprintf(
							$new_ending,
							$old_ending
						);

						$change_ending = $old_ending === $new_ending ? false : true;

						// Build suffix with changes and additions.
						$suffix = ( ! $change_ending ? '' : $new_ending ) . $rule['add'];

						// Set key term.
						$key = $change_ending ? substr( $term, 0, -strlen( $old_ending ) ) : $term;

						// Check if key term is set.
						if ( ! isset( $glossary_entries_suffixes[ $key ] ) ) {
							// Add the key term with empty array.
							$glossary_entries_suffixes[ $key ] = array();
						}

						// If the ending changes, also add the ending.
						if ( $change_ending ) {

							// Check if ending already exist in array of suffixes.
							if ( ! in_array( $old_ending, $glossary_entries_suffixes[ $key ], true ) ) {
								// Add the ending to the suffixes.
								$glossary_entries_suffixes[ $key ][] = $old_ending;
							}
						}

						// Check if suffix already exist in array of suffixes.
						if ( ! in_array( $suffix, $glossary_entries_suffixes[ $key ], true ) ) {
							// Add suffix.
							$glossary_entries_suffixes[ $key ][] = $suffix;
						}

						break;
					}
				}
			}
		} else {
			// Add match for part_of_speech without any suffix rules.
			$glossary_entries_suffixes[ $term ] = array();
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
		$cached_glossary  = $glossary->id;
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

		$regex_group = array();
		foreach ( $glossary_entries_suffixes as $term => $suffixes ) {
			$regex_suffix = $suffixes ? '(?:' . implode( '|', $suffixes ) . ')?' : '';

			if ( ! isset( $regex_group[ $regex_suffix ] ) ) {
				$regex_group[ $regex_suffix ] = array();
			}

			$regex_group[ $regex_suffix ][] = preg_quote( $term, '/' );

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
		// Make the regex more deterministic.
		ksort( $regex_group );

		// Build the regular expression.
		$placeholders_search = '%(?:(?:\d+\$)?(?:\d+)?)?[bcdefglosuxEFGX%@]';
		$terms_search        = '(?:(\b|' . $placeholders_search . ')(';
		foreach ( $regex_group as $suffix => $terms ) {
			$terms_search .= '(?:' . implode( '|', $terms ) . ')' . $suffix . '|';
		}

		// Remove the trailing |.
		$terms_search  = rtrim( $terms_search, '|' );
		$terms_search .= ')\b)|(' . $placeholders_search . ')';
	}
	// Split the singular string on glossary terms boundaries.
	$singular_split = preg_split( '/' . $terms_search . '/i', $translation->singular, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
	// Loop through each chunk of the split to find glossary terms.
	if ( is_array( $singular_split ) ) {
		$singular_combined = '';
		should_skip_chunk( '' ); // Reset the state machine.

		foreach ( $singular_split as $chunk ) {
			// Create an escaped version for use later on.
			$escaped_chunk = esc_translation( $chunk );

			if ( should_skip_chunk( $chunk ) ) {
				$singular_combined .= $escaped_chunk;
				continue;
			}

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
			should_skip_chunk( '' ); // Reset the state machine.

			foreach ( $plural_split as $chunk ) {
				// Create an escaped version for use later on.
				$escaped_chunk = esc_translation( $chunk );

				if ( should_skip_chunk( $chunk ) ) {
					$plural_combined .= $escaped_chunk;
					continue;
				}

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
		// Don't show the translation in the translation textarea if the translation status is changesrequested but the
		// changesrequested is not enabled, because in this situation we consider the changesrequested as rejected translations.
		if ( 'changesrequested' == $entry->translation_status && ! apply_filters( 'gp_enable_changesrequested_status', false ) ) { // todo: delete when we merge the gp-translation-helpers in GlotPress
			$entry->translations = array();
		}
		?>
		<blockquote class="translation"><?php echo prepare_original( esc_translation( gp_array_get( $entry->translations, $index ) ) ); ?></blockquote>
		<textarea class="foreign-text" name="translation[<?php echo esc_attr( $entry->original_id ); ?>][]" id="translation_<?php echo esc_attr( $entry->original_id ); ?>_<?php echo esc_attr( $entry->id ); ?>_<?php echo esc_attr( $index ); ?>" <?php echo disabled( ! $can_edit ); ?>><?php echo gp_prepare_translation_textarea( esc_translation( gp_array_get( $entry->translations, $index ) ) ); ?></textarea>

		<div>
			<?php
			if ( $can_edit ) {
				echo '<div class="counts"></div>';
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
	if ( ! $status ) {
		$status = '';
	}

	$status_labels = array(
		'current'          => _x( 'current', 'Single Status', 'glotpress' ),
		'waiting'          => _x( 'waiting', 'Single Status', 'glotpress' ),
		'fuzzy'            => _x( 'fuzzy', 'Single Status', 'glotpress' ),
		'old'              => _x( 'old', 'Single Status', 'glotpress' ),
		'rejected'         => _x( 'rejected', 'Single Status', 'glotpress' ),
		'changesrequested' => _x( 'changes requested', 'Single Status', 'glotpress' ),
	);
	// If a changesrequested status exists in the database but they are no longer enabled, they will show as rejected.
	if ( ! apply_filters( 'gp_enable_changesrequested_status', false ) ) {// todo: delete when we merge the gp-translation-helpers in GlotPress
		$status_labels['changesrequested'] = _x( 'rejected', 'Single Status', 'glotpress' );
	}
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

/**
 * Determine if the current chunk should be skipped.
 *
 * @since 4.0.0
 *
 * @param string $chunk The current chunk.
 *
 * @return bool
 */
function should_skip_chunk( string $chunk ) {
	static $state = false;
	if ( '' === $chunk ) {
		$state = false;
	}

	if ( ! $state ) {
		if ( '<' === substr( $chunk, -1 ) || '</' === substr( $chunk, -2 ) ) {
			$state = 'tag_open';
		} elseif ( preg_match( '/<[^>]+$/', $chunk, $m ) ) {
			$state = 'inside_tag';
			$chunk = $m[0];
		}
	}

	if ( ! $state ) {
		return false;
	}

	if ( 'tag_open' === $state ) {
		$state = 'inside_tag';

		if ( preg_match( '/\s/', $chunk ) ) {
			// Our chunk is just the HTML tag name, so skip.
			return true;
		}
	}

	if ( 'inside_attr' === $state || 'inside_allowed_attr' === $state ) {
		$p = strpos( $chunk, '"' );
		if ( false === $p ) {
				// Still inside the attribute.
				if ( 'inside_allowed_attr' === $state ) {
				return false;
			}
			return true;
		}

		$state = 'inside_tag';
		// Back in the tag but maybe an attribute will be opened again, so let's check the rest.
		$chunk = substr( $chunk, $p + 1 );
	}

	if ( 'inside_tag' === $state ) {
		if ( preg_match( '/\b([a-z]+)\s*=\s*"[^"]*$/', strtolower( $chunk ), $m ) ) {
			// The chunk ends with an open-ended attribute.

			$state = 'inside_attr';
			if ( 'alt' === $m[1] || 'title' === $m[1] ) {
				$state = 'inside_allowed_attr';
			}
			return true;
		}

		$p = strpos( $chunk, '>' );
		while ( false !== $p ) {
			// The tag ended, let's see if a new one starts again inside the current chunk.
			$chunk = substr( $chunk, $p + 1 );
			if ( false === strpos( $chunk, '<' ) ) {
				// No more html start, so we're outside of a tag.
				$state = false;
				return true;
			}
		}

		return true;
	}

	if ( 'inside_allowed_attr' === $state ) {
		return false;
	}

	return true;
}
