<?php
/**
 * Inline Translation
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 5.0.0
 */

/**
 * Class to record the strings that were used to render the page and pass it to the Inline Translator.
 *
 * @since 5.0.0
 */
class GP_Inline_Translation {
	// This is a regex that we output, therefore the backslashes are doubled.
	const PLACEHOLDER_REGEX     = '%([0-9]\\\\*\\$)?';
	const PLACEHOLDER_MAXLENGTH = 200;

	/**
	 * The strings used on the page.
	 *
	 * @var        array
	 */
	private $strings_used = array();

	/**
	 * The translations that were actually displayed.
	 *
	 * @var        array
	 */
	private $translations = array();

	/**
	 * The translations that contained placeholders that were actually displayed.
	 *
	 * @var        array
	 */
	private $placeholders_used = array();

	/**
	 * The text domains used on the page.
	 *
	 * @var        array
	 */
	private $text_domains = array();

	/**
	 * Ignored translation translations.
	 *
	 * @var        array
	 */
	private $ignore_translation = array( 'Loading&#8230;' => true );

	/**
	 * Holds the singleton.
	 *
	 * @var        array
	 */
	private static $instance = array();

	/**
	 * Init the singleton.
	 *
	 * @return     GP_Inline_Translation  The singleton.
	 */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructs a new instance.
	 */
	public function __construct() {
		add_action( 'gettext', array( $this, 'translate' ), 10, 4 );
		add_action( 'gettext_with_context', array( $this, 'translate_with_context' ), 10, 5 );
		add_action( 'ngettext', array( $this, 'ntranslate' ), 10, 5 );
		add_action( 'ngettext_with_context', array( $this, 'ntranslate_with_context' ), 10, 6 );
		add_action( 'wp_footer', array( $this, 'load_translator' ), 1000 );
		add_action( 'gp_footer', array( $this, 'load_translator' ), 1000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'admin_footer', array( $this, 'load_admin_translator' ), 1000 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_inform_translator', array( $this, 'load_ajax_translator' ), 1000 );
		add_action( 'wp_ajax_inform_admin_translator', array( $this, 'load_ajax_admin_translator' ), 1000 );
	}

	/**
	 * Enqueue the scripts and styles for the translator.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'inline-translation-loader', gp_plugin_url( 'assets/css/inline-translation-loader.css', __FILE__ ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'inline-translation', gp_plugin_url( 'assets/css/inline-translation.css', __FILE__ ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script( 'inline-translation', gp_plugin_url( 'assets/js/inline-translation.min.js' ), array( 'jquery' ), false, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
		wp_enqueue_script( 'inline-translation-loader', gp_plugin_url( 'assets/js/inline-translation-loader.js', __FILE__ ), array( 'inline-translation' ), false, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
	}

	/**
	 * This returns true for text that consists just of placeholders or placeholders + one letter,
	 * for example '%sy': time in years abbreviation
	 * as it leads to lots of translatable text which just matches the regex
	 *
	 * @param  string $text The string to check.
	 * @return boolean      True if it contains just placeholders.
	 */
	private function contains_just_placeholders( $text ) {
		$placeholderless_text = trim( preg_replace( '#' . self::PLACEHOLDER_REGEX . '[sd]#', '', $text ) );
		return strlen( $text ) !== strlen( $placeholderless_text ) && strlen( $placeholderless_text ) <= 1;
	}

	/**
	 * This returns true for text that contains placeholders
	 *
	 * @param  string $text The string to check.
	 * @return boolean      True if it contains placeholders.
	 */
	private function contains_placeholder( $text ) {
		return (bool) preg_match( '#' . self::PLACEHOLDER_REGEX . '[sd]#', $text );
	}
	/**
	 * Whether the string was already checked.
	 *
	 * @param      string $key    The key to check.
	 *
	 * @return     bool  True when it was already checked.
	 */
	private function already_checked( $key ) {
		return isset( $this->placeholders_used[ $key ] ) ||
			isset( $this->strings_used[ $key ] );
	}

	/**
	 * Convert placeholders to regex.
	 *
	 * @param      string $text                 The text to convert.
	 * @param      string $string_placeholder   The string placeholder regex.
	 * @param      string $numeric_placeholder  The numeric placeholder regex.
	 *
	 * @return     string  A regex that will match the placeholders.
	 */
	private function convert_placeholders_to_regex( $text, $string_placeholder = null, $numeric_placeholder = null ) {
		if ( is_null( $string_placeholder ) ) {
			$string_placeholder = '(.{0,' . self::PLACEHOLDER_MAXLENGTH . '}?)';
		}
		if ( is_null( $numeric_placeholder ) ) {
			$numeric_placeholder = '([0-9]{0,15}?)';
		}

		$text = html_entity_decode( $text );
		$text = preg_quote( $text, '/' );
		$text = preg_replace( '#' . self::PLACEHOLDER_REGEX . 's#', $string_placeholder, $text );
		$text = preg_replace( '#' . self::PLACEHOLDER_REGEX . 'd#', $numeric_placeholder, $text );
		$text = str_replace( '%%', '%', $text );
		return $text;
	}

	/**
	 * Gets the hash key.
	 *
	 * @param      string $original  The original.
	 * @param      string $context   The context.
	 *
	 * @return     string  The hash key.
	 */
	private function get_hash_key( $original, $context = null ) {
		if ( ! empty( $context ) && 'default' !== $context ) {
			$context .= "\u0004";
		} else {
			$context = '';
		}

		return $context . html_entity_decode( $original );
	}

	/**
	 * Adds a context.
	 *
	 * This is when a string was already used without context but a new string comes along with context.
	 *
	 * @param      string $key        The key.
	 * @param      string $context    The context.
	 * @param      bool   $new_entry  Whether it's a new entry.
	 */
	private function add_context( $key, $context = null, $new_entry = false ) {
		if ( ! $context ) {
			return;
		}

		if ( isset( $this->strings_used[ $key ] ) ) {
			if ( ! isset( $this->strings_used[ $key ][1] ) ) {
				$this->strings_used[ $key ][1] = array();

				if ( ! $new_entry ) {
					// The first entry had an empty context, so add it now.
					$this->strings_used[ $key ][1][] = '';
				}
			}

			if ( ! in_array( $context, $this->strings_used[ $key ][1], true ) ) {
				$this->strings_used[ $key ][1][] = $context;
			}
		} elseif ( isset( $this->placeholders_used[ $key ] ) ) {
			if ( ! isset( $this->placeholders_used[ $key ][2] ) ) {
				$this->placeholders_used[ $key ][2] = array();

				if ( ! $new_entry ) {
					// The first entry had an empty context, so add it now.
					$this->placeholders_used[ $key ][2][] = '';
				}
			}

			if ( ! in_array( $context, $this->placeholders_used[ $key ][2], true ) ) {
				$this->placeholders_used[ $key ][2][] = $context;
			}
		}
	}

	/**
	 * Filter for the singular or plural form of a string.
	 *
	 * @param string $translation Translated text.
	 * @param string $single      The text to be used if the number is singular.
	 * @param string $plural      The text to be used if the number is plural.
	 * @param int    $number      The number to compare against to use either the singular or plural form.
	 * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
	 */
	public function ntranslate( $translation, $single, $plural, $number, $domain ) {
		return $this->translate_with_context( $translation, array( $single, $plural ), null, $domain );
	}

	/**
	 * Filter for the singular or plural form of a string with gettext context.
	 *
	 * @param string $translation Translated text.
	 * @param string $single      The text to be used if the number is singular.
	 * @param string $plural      The text to be used if the number is plural.
	 * @param int    $number      The number to compare against to use either the singular or plural form.
	 * @param string $context     Context information for the translators.
	 * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
	 */
	public function ntranslate_with_context( $translation, $single, $plural, $number, $context, $domain ) {
		return $this->translate_with_context( $translation, $single, array( $single, $plural ), $domain );
	}

	/**
	 * Filter for the text with its translation.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Text to translate.
	 * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
	 */
	public function translate( $translation, $text = null, $domain = null ) {
		return $this->translate_with_context( $translation, $text, null, $domain );
	}

	/**
	 * Filter for the text with its translation based on context information.
	 *
	 * @param string $translation Translated text.
	 * @param string $original        Text to translate.
	 * @param string $context     Context information for the translators.
	 * @param string $text_domain      Text domain. Unique identifier for retrieving translated strings.
	 */
	public function translate_with_context( $translation, $original = null, $context = null, $text_domain = null ) {
		if ( ! isset( $this->text_domains[ $text_domain ] ) ) {
			$this->text_domains[ $text_domain ] = $text_domain;
		}

		if ( ! $original ) {
			$original = $translation;
		}

		$original_as_string = $original;
		if ( is_array( $original_as_string ) ) {
			$original_as_string = implode( ' ', $original_as_string );
		}

		if ( isset( $this->ignore_translation[ $original_as_string ] ) ) {
			return $translation;
		}

		if ( $this->contains_just_placeholders( $original_as_string ) ) {
			$this->ignore_translation[ $original_as_string ] = true;
			return $translation;
		}
		$key = $this->get_hash_key( $translation, $context );

		if ( $this->already_checked( $key ) ) {
			$this->add_context( $key, $context );
		} else {
			if ( $this->contains_placeholder( $translation ) ) {
				$string_placeholder = null;

				$this->placeholders_used[ $key ] = array(
					$original,
					$this->convert_placeholders_to_regex( $translation, $string_placeholder ),
				);

			} else {
				$this->strings_used[ $key ] = array(
					$original,
				);
			}
			$this->translations[ $text_domain . "\u0004" . $key ] = array(
				$original,
			);

			$this->add_context( $key, $context, true );
		}

		return $translation;
	}

	/**
	 * Gets the translations from GlotPress.
	 *
	 * This will also create originals with translations just in time where applicable.
	 *
	 * @param      GP_Locale $gp_locale  The gp locale.
	 *
	 * @return     array     The translations.
	 */
	public function get_translations( GP_Locale $gp_locale ) {
		$projects           = array();
		$full_project_paths = array();
		foreach ( $this->text_domains as $domain ) {
			$project_paths = array();
			if ( 'default' === $domain ) {
				$project_paths[] = 'local-wordpress/local-wordpress-development';
				$project_paths[] = 'local-wordpress/local-wordpress-administration';
			} else {
				$project_paths[] = 'local-plugins/' . $domain;
				$project_paths[] = 'local-themes/' . $domain;
			}
			foreach ( $project_paths as $project_path ) {
				$project = GP::$project->by_path( $project_path );
				if ( $project ) {
					$full_project_paths[ $project->id ] = gp_url_project( $project );
					$projects[ $domain ][]              = $project;
				}
			}
		}

		$locale_slug          = $gp_locale->slug;
		$translation_set_slug = 'default';

		$translation_sets = array();
		foreach ( $projects as $domain => $_projects ) {
			foreach ( $_projects as $project ) {
				$translation_sets[ $domain ][ $project->id ] = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
			}
		}

		foreach ( $this->translations as $key => $original ) {
			if ( is_array( $original ) ) {
				$entry = (object) array(
					'singular' => $original[0],
					'plural'   => $original[1],
				);
			} else {
				$entry = (object) array(
					'singular' => $original,
				);
			}
			$keys = explode( "\u0004", $key );
			if ( 3 === count( $keys ) ) {
				list( $entry->domain, $entry->context, $translation ) = $keys;
				$hash = $entry->context . "\u0004" . $entry->singular;
			} else {
				list( $entry->domain, $translation ) = $keys;
				$hash                                = $entry->singular;
			}
			if ( ! isset( $projects[ $entry->domain ] ) ) {
				continue;
			}

			$project         = $projects[ $entry->domain ];
			$original_record = false;
			foreach ( $projects[ $entry->domain ] as $project ) {
				$original_record = GP::$original->by_project_id_and_entry( $project->id, $entry );
				if ( $original_record ) {
					break;
				}
			}

			if ( ! $original_record ) {
				if ( count( $projects[ $entry->domain ] ) === 1 ) {
					// JIT Original Creation.
					$original_record = GP::$original->create(
						array(
							'project_id' => $projects[ $entry->domain ][0]->id,
							'context'    => $entry->context,
							'singular'   => $entry->singular,
							'plural'     => $entry->plural,
						)
					);

					// TODO: add translation.
				} else {
					$translations['originals_not_found'][] = $original;
					continue;
				}
			}

			$translation_set                  = $translation_sets[ $entry->domain ][ $project->id ];
			$query_result                     = new stdClass();
			$query_result->original_id        = $original_record->id;
			$query_result->original           = $original;
			$query_result->domain             = $domain;
			$query_result->project            = $full_project_paths[ $project->id ];
			$query_result->translation_set_id = $translation_set->id;
			$query_result->original_comment   = $original_record->comment;

			$query_result->translations = GP::$translation->find_many( "original_id = '{$query_result->original_id}' AND translation_set_id = '{$translation_set->id}' AND ( status = 'waiting' OR status = 'fuzzy' OR status = 'current' )" );

			foreach ( $query_result->translations as $key => $current_translation ) {
				$query_result->translations[ $key ]                   = GP::$translation->prepare_fields_for_save( $current_translation );
				$query_result->translations[ $key ]['translation_id'] = $current_translation->id;
			}
			if ( is_string( $hash ) ) {
				$translations[ $hash ] = $query_result;
			}
		}

		return $translations;
	}

	/**
	 * Loads the ajax translator in the admin.
	 *
	 * @return     bool  Whethe the tranlator code was printed.
	 */
	public function load_ajax_admin_translator() {
		return $this->load_ajax_translator( get_user_locale() );
	}

	/**
	 * Loads the ajax translator.
	 *
	 * @param      string $locale_code  The locale code.
	 *
	 * @return     bool  Whethe the tranlator code was printed.
	 */
	public function load_ajax_translator( $locale_code = null ) {
		if ( 'en' === $locale_code ) {
			return false;
		}

		echo '<script type="text','/javascript">';
		echo 'var newGpInlineTranslationData = ';
		echo wp_json_encode(
			array(
				'stringsUsedOnPage'      => $this->strings_used,
				'placeholdersUsedOnPage' => $this->placeholders_used,
			),
			JSON_PRETTY_PRINT
		);
		echo ';';
		echo '</script>';
		return true;
	}

	/**
	 * Loads the translator in the admin.
	 *
	 * @return     bool  Whethe the tranlator code was printed.
	 */
	public function load_admin_translator() {
		return $this->load_translator( get_user_locale() );
	}

	/**
	 * Loads the translator.
	 *
	 * @param      string $locale_code  The locale code.
	 *
	 * @return     bool  Whethe the tranlator code was printed.
	 */
	public function load_translator( $locale_code = null ) {
		if ( ! $locale_code ) {
			$locale_code = get_locale();
		}

		if ( 'en' === $locale_code ) {
			return false;
		}

		echo '<script type="text','/javascript">';
		echo 'gpInlineTranslationData = ', wp_json_encode( $this->get_inline_translation_object( $locale_code ), JSON_PRETTY_PRINT ), ';';
		echo '</script>';

		?><div id="translator-launcher" class="translator">
			<a href="" title="<?php esc_attr_e( 'Inline Translation' ); ?>">
				<span class="dashicons dashicons-admin-site-alt3">
				</span>
				<div class="text disabled">
					<div class="enable">
						Enable Inline Translation
					</div>
					<div class="disable">
						Disable Inline Translation
					</div>
				</div>
			</a>
		</div>
		<?php
		return true;
	}

	/**
	 * Gets the inline translation object.
	 *
	 * @param      string $locale_code  The locale code.
	 *
	 * @return     array  The inline translation object.
	 */
	private function get_inline_translation_object( $locale_code ) {
		if ( ! $locale_code ) {
			$locale_code = get_locale();
		}
		$gp_locale = GP_Locales::by_field( 'wp_locale', $locale_code );

		if ( empty( $gp_locale ) ) {
			return;
		}

		$plural_forms = 'nplurals=2; plural=(n != 1)';

		if ( property_exists( $gp_locale, 'nplurals' ) || property_exists( $gp_locale, 'plural_expression' ) ) {
			$plural_forms = 'nplurals=' . $gp_locale->nplurals . '; plural=' . $gp_locale->plural_expression;
		}

		return array(
			'baseUrl'                => gp_url( '/' ),
			'translations'           => $this->get_translations( $gp_locale ),
			'stringsUsedOnPage'      => $this->strings_used,
			'placeholdersUsedOnPage' => $this->placeholders_used,
			'localeCode'             => $gp_locale->slug,
			'languageName'           => html_entity_decode( $gp_locale->english_name ),
			'pluralForms'            => $plural_forms,
			'glotPress'              => array(
				'url'     => gp_url( '/' ),
				'project' => implode( ',', array_keys( $this->text_domains ) ),
			),
		);
	}
}
