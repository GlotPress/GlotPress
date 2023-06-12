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
	const PLACEHOLDER_MAXLENGTH = 500;

	/**
	 * The strings used on the page.
	 *
	 * @var        array
	 */
	private $strings_used = array();

	/**
	 * Assigns an id to each string.
	 *
	 * @var        array
	 */
	private $string_index = array();

	/**
	 * The text domains used on the page.
	 *
	 * @var        array
	 */
	private $text_domains = array();

	/**
	 * The full project paths for projects.
	 *
	 * @var        array
	 */
	private $full_project_paths = array();

	/**
	 * Ignored translation translations.
	 *
	 * @var        array
	 */
	private $ignore_translation = array(
		'number_format_thousands_sep' => true, // exact comparison in WP_Locale::init().
		'number_format_decimal_point' => true, // exact comparison in WP_Locale::init().
	);

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
		require_once GP_PATH . 'gp-templates/helper-functions.php';

		return self::$instance;
	}

	/**
	 * Determines if local is active.
	 *
	 * @return     bool  True if active, False otherwise.
	 */
	public static function is_active() {
		static $is_active;
		if ( ! isset( $is_active ) ) {
			if ( '1' === gp_post( 'gp_inline_translation_enabled' ) && ! gp_post( 'gp_enable_inline_translation' ) ) {
				// Deactivate inline translation even before the option is saved.
				$is_active = false;
			} elseif ( '0' === gp_post( 'gp_inline_translation_enabled' ) && gp_post( 'gp_enable_inline_translation' ) ) {
				// Activate inline translation even before the option is saved.
				$is_active = true;
			} else {
				$is_active = get_option( 'gp_enable_inline_translation' );
			}
		}
		return $is_active;
	}

	/**
	 * Determines if fallback strings are active.
	 *
	 * @return     bool  True if active, False otherwise.
	 */
	public static function is_fallback_string_list_active() {
		return get_option( 'gp_enable_fallback_string_list' );
	}

	/**
	 * Constructs a new instance.
	 */
	public function __construct() {
		if ( ! self::is_active() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		global $wp;

		add_action( 'gettext', array( $this, 'translate' ), 10, 4 );
		add_action( 'gettext_with_context', array( $this, 'translate_with_context' ), 10, 5 );
		add_action( 'ngettext', array( $this, 'ntranslate' ), 10, 5 );
		add_action( 'ngettext_with_context', array( $this, 'ntranslate_with_context' ), 10, 6 );
		add_action( 'load_script_translations', array( $this, 'load_script_translations' ), 10, 4 );
		add_action( 'wp_footer	', array( $this, 'load_translator' ), 1000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'rest_pre_echo_response', array( $this, 'rest_pre_echo_response' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'load_admin_translator' ), 1000 );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'load_admin_translator' ), 1000 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_inform_translator', array( $this, 'load_ajax_translator' ), 1000 );
		add_action( 'wp_ajax_inform_admin_translator', array( $this, 'load_ajax_admin_translator' ), 1000 );
	}

	/**
	 * Enqueue the scripts and styles for the translator.
	 */
	public function enqueue_scripts() {
		$css_extension = SCRIPT_DEBUG || GP_SCRIPT_DEBUG ? '.css' : '.min.css';
		$js_extension = SCRIPT_DEBUG || GP_SCRIPT_DEBUG ? '.js' : '.min.js';

		wp_enqueue_style( 'inline-translation-loader', gp_plugin_url( 'assets/css/inline-translation-loader' . $css_extension, __FILE__ ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'inline-translation', gp_plugin_url( 'assets/css/inline-translation' . $css_extension, __FILE__ ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script( 'inline-translation', gp_plugin_url( 'assets/js/inline-translation' . $js_extension ), array( 'jquery', 'jquery-ui-tooltip' ), false, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
		wp_enqueue_script( 'inline-translation-loader', gp_plugin_url( 'assets/js/inline-translation-loader' . $js_extension, __FILE__ ), array( 'inline-translation' ), false, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
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
		return $this->translate_with_context( $translation, array( $single, $plural ), $context, $domain );
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
		if ( defined('REST_REQUEST') ) {
			$projects = $this->get_projects( array( $text_domain ) );
			$entry    = (object) array(
				'singular' => $original,
				'context' => $context,
			);
			$original_record = $this->get_original_by_entry( $projects[$text_domain], $entry );
			$id = 'o' . $original_record->id;
		} else {
			$key = $text_domain . '|' . $context . '|' . $original_as_string;
			if ( ! isset( $this->string_index[ $key ] ) ) {
				$id = count( $this->strings_used );
				$this->string_index[ $key ] = $id;
				$this->strings_used[] = array(
					'original' => $original,
					'context' => $context,
					'text_domain' => $text_domain,
					'translation' => $translation,
				);
			} else {
				$id = $this->string_index[ $key ];
			}
		}

		return $this->add_utf8_markers( $translation, $id );
	}

	function add_utf8_markers( $translation, $id ) {
		$invisible_separator = "\xE2\x80\x8B";
		$utf8_tag_id = '';
		foreach ( str_split( strval( $id ) ) as $char ) {
			$hex = ord( $char ) + 128;
			$utf8_tag_id .= "\xF3\xA0";
			if ( $hex > 191 ) {
				$utf8_tag_id .= "\x81" . chr( $hex - 64 );
			} else {
				$utf8_tag_id .= "\x80" . chr( $hex );
			}
		}
		if ( $utf8_tag_id ) {
			$utf8_tag_id .=  "\u{e007f}"; // UTF-8 Tag End.
		}

		return $invisible_separator . $utf8_tag_id . $translation . $invisible_separator;
	}

	/**
	 * Loads script translations.
	 * @param string $translations JSON-encoded translation data.
	 * @param string $file         Path to the translation file that was loaded.
	 * @param string $handle       Name of the script to register a translation domain to.
	 * @param string $domain       The text domain.
	 */
	function load_script_translations( $translations, $file, $handle, $domain ) {
		$jed = json_decode( $translations, true );
		foreach ( $jed['locale_data']['messages'] as $original => $translations ) {
			if ( '' === $original ) {
				continue;
			}
			$context = '';
			if ( false !== strpos( $original, '\u0004') ) {
				$with_context = explode( '\u0004', $original );
				$key = $domain . '|' . $with_context[0] . '|' . $with_context[1];
				$context = $with_context[0];
			} else {
				$key = $domain . '||' . $original;
			}
			if ( ! isset( $this->string_index[ $key ] ) ) {
				$id = count( $this->strings_used );
				$this->string_index[ $key ] = $id;
				$this->strings_used[] = array(
					'original' => $original,
					'context' => $context,
					'text_domain' => $domain,
					'translation' => $translations[0], // TODO: add plural
				);
			} else {
				$id = $this->string_index[ $key ];
			}
			foreach( $translations as $k => $translation ) {
				$jed['locale_data']['messages'][$original][$k] = $this->add_utf8_markers( $translation, $id );
			}
		}
		return json_encode( $jed );
	}

	/**
	 * Map the text domains to projects.
	 *
	 * @param      array $text_domains  The text domains.
	 *
	 * @return array List of potential projects keyed by text domain.
	 */
	private function get_projects( $text_domains ) {
		static $projects = array();
		foreach ( $text_domains as $text_domain ) {
			if ( isset( $projects[$text_domain] ) ) {
				continue;
			}
			$projects[ $text_domain ] = array();

			$project_paths = GP_Local::CORE_PROJECTS;
			if ( 'default' !== $text_domain ) {
				$project_paths = array(
					'wp-plugins/' . $text_domain,
					'wp-themes/' . $text_domain,
				);
			}
			foreach ( $project_paths as $project_path ) {
				$project = GP::$project->by_path( apply_filters( 'gp_local_project_path', $project_path ) );
				if ( $project ) {
					$projects[ $text_domain ][] = $project;
				}
			}
		}
		return array_intersect_key( $projects, array_flip( $text_domains ) );
	}

	/**
	 * Map the text domains to projects.
	 *
	 * @param      array $text_domains  The text domains.
	 * @param      string  $locale_slug           The locale slug.
	 * @param      string  $translation_set_slug  The translation set slug.
	 *
	 * @return array List of translation sets keyed by text domain.
	 */
	private function get_translation_sets( $projects, $locale_slug, $translation_set_slug ) {
		static $translation_sets = array();
		$return = array();
		foreach ( $projects as $text_domain => $_projects ) {
			if ( ! isset( $translation_sets[$text_domain] ) ) {
				$translation_sets[$text_domain] = array();
			}
			if ( ! isset( $return[$text_domain] ) ) {
				$return[$text_domain] = array();
			}
			foreach ( $_projects as $project ) {
				if ( ! isset( $translation_sets[$text_domain][$project->id] ) ) {
					$translation_sets[ $text_domain ][ $project->id ] = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
				}

				$return[ $text_domain ][ $project->id ] = $translation_sets[ $text_domain ][ $project->id ];
			}
		}
		return $return;
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
		$projects             = $this->get_projects( $this->text_domains );
		$locale_slug          = $gp_locale->slug;
		$translation_set_slug = 'default';
		$translation_sets     = $this->get_translation_sets( $projects, $locale_slug, $translation_set_slug );

		$translations = array();
		foreach ( $this->strings_used as $entry ) {
			$_projects = array();
			if ( isset( $projects[ $entry['text_domain'] ] )) {
				$_projects = $projects[ $entry['text_domain'] ];
			}
			$translation_set = null;
			if ( isset( $translation_sets[ $entry['text_domain'] ] )) {
				$translation_set = $translation_sets[ $entry['text_domain'] ];
			}
			$query_result = $this->get_translation(
				$_projects,
				$translation_set,
				$entry['original'],
				$entry['context'],
				$entry['text_domain'],
				$entry['translation']
			);

			$translations[] = $query_result;
		}

		return $translations;
	}

	/**
	 * Gets the original.
	 *
	 * @param      array        $projects          The potential projects.
	 * @param      object       $entry       The original entry.
	 *
	 * @return     object  The original record.
	 */
	public function get_original_by_entry( $projects, $entry, $create = true ) {
		static $cache = array();
		$key = $entry->singular . '|' . $entry->context . '|' . $entry->domain;
		if ( isset( $cache[ $key ])) {
			return $cache[ $key ];
		}

		$total_projects = 0;
		foreach ( $projects as $project ) {
			$total_projects += 1;
			$first_project = $project;
			$original_record = GP::$original->by_project_id_and_entry( $project->id, $entry, '+active' );
			$cache[ $key ] = $original_record;
			if ( $original_record ) {
				return $original_record;
			}
		}

		foreach ( $projects as $project ) {
			$original_record = GP::$original->by_project_id_and_entry( $project->id, $entry, '-obsolete' );
			if ( $original_record ) {
				$original_record->status = '+active';
				$original_record->save();
				$cache[ $key ] = $original_record;
				return $original_record;
			}
		}

		if ( $create && $total_projects === 1 ) {
			// JIT Original Creation. Can only do this when there is no ambiguity about the project.
			$original_record = GP::$original->create(
				array(
					'project_id' => $first_project->id,
					'context'    => $entry->context,
					'singular'   => $entry->singular,
					'plural'     => $entry->plural,
					'status'     => '+active',
				)
			);

			$cache[ $key ] = $original_record;
			return $original_record;
		}

		return $entry;
	}

	/**
	 * Gets the translation.
	 *
	 * @param      array        $projects          The potential projects.
	 * @param      array        $translation_sets  The potential translation sets.
	 * @param      string|array $original          The original.
	 * @param      string       $context           The context.
	 * @param      string       $text_domain       The text domain.
	 * @param      string       $translation       The translation.
	 *
	 * @return     bool|stdClass  The translation.
	 */
	private function get_translation( $projects, $translation_sets, $original, $context, $text_domain, $translation ) {
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
			$entry->domain = $text_domain;
		if ( $context ) {
			$entry->context = $context;
		}
		$entry->translation = $translation;
		if ( empty( $projects ) ) {
			return $entry;
		}
		$original_record = $this->get_original_by_entry( $projects, $entry );
		if ( ! isset( $original_record->id ) ) {
			return $entry;
		}

		$project = false;
		foreach ( $projects as $_project ) {
			if ( $original_record->project_id === $_project->id ) {
				$project = $_project;
				break;
			}
		}

		$query_result                     = new stdClass();
		$query_result->original_id        = $original_record->id;
		$query_result->domain             = $text_domain;
		$query_result->singular           = $original_record->singular;
		$query_result->plural             = $original_record->plural;
		$query_result->context            = $original_record->context;
		$query_result->project            = $project->path;
		$query_result->translation        = $translation;
		$query_result->translation_set_id = $translation_sets[ $project->id ]->id;
		$query_result->original_comment   = $original_record->comment;

		$query_result->translations = GP::$translation->find_many( "original_id = '{$query_result->original_id}' AND translation_set_id = '{$query_result->translation_set_id}' AND ( status = 'waiting' OR status = 'fuzzy' OR status = 'current' )" );

		foreach ( $query_result->translations as $key => $current_translation ) {
			$query_result->translations[ $key ] = GP::$translation->prepare_fields_for_save( $current_translation );

			$query_result->translations[ $key ]['translation_id'] = $current_translation->id;
		}

		if ( empty( $query_result->translations ) && $translation !== $entry->singular && isset( $translation_sets[ $project->id ] ) && is_object( $translation_sets[ $project->id ] ) ) {
			$key = 0;

			$translation_record = GP::$translation->create(
				array(
					'original_id'        => $original_record->id,
					'translation_set_id' => $translation_sets[ $project->id ]->id,
					'translation_0'      => $translation,
					'status'             => 'current',
				)
			);

			$query_result->translations[ $key ] = GP::$translation->prepare_fields_for_save( $translation_record );

			$query_result->translations[ $key ]['translation_id'] = $translation_record->id;
		}

		return $query_result;
	}

	public function get_current_gp_locale() {
		static $cache = array();
		$locale_code = get_user_locale();

		if ( 'en' === $locale_code ) {
			return false;
		}

		if ( ! isset( $cache[$locale_code ] ) ) {
			$cache[$locale_code ] = GP_Locales::by_field( 'wp_locale', $locale_code );
		}

		return $cache[$locale_code ];
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
				'translations'      => $this->strings_used,
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
		global $wp_customize;
		if ( isset( $wp_customize ) && ! doing_action( 'customize_controls_print_footer_scripts' ) ) {
			return false;
		}

		if ( ! $locale_code ) {
			$locale_code = get_locale();
		}

		if ( 'en' === $locale_code ) {
			return false;
		}
		$translation_stats = $this->get_inline_translation_stats( $locale_code );

		echo '<script type="text','/javascript">';
		echo 'gpInlineTranslationData = ', wp_json_encode( $this->get_inline_translation_object( $locale_code ), JSON_PRETTY_PRINT ), ';';
		echo '</script>';

		if ( self::is_fallback_string_list_active() ) {
			?>
			<div id="gp-inline-translation-list">
				<div id="gp-translation-list-wrapper">
					<input id="gp-inline-search" type="text" placeholder="<?php esc_attr_e( 'Search string', 'GlotPress' ); ?>"/>
					<ul>
					<?php

					foreach ( $this->get_translations( GP_Locales::by_field( 'wp_locale', $locale_code ) ) as $translation ) {
						echo '<li>';

						echo '<data class="translatable"';
						echo ' data-singular="' . esc_attr( $translation->singular ) . '"';
						echo ' data-plural="' . esc_attr( $translation->plural ) . '"';
						echo ' data-context="' . esc_attr( $translation->context ) . '"';
						echo ' data-domain="' . esc_attr( $translation->domain ) . '"';
						echo ' data-original-id="' . esc_attr( $translation->original_id ) . '"';
						echo ' data-project="' . esc_attr( $translation->project ) . '"';
						if ( ! empty( $translation->translations ) ) {
							$t = array_filter(
								array(
									$translation->translations[0]['translation_0'],
									$translation->translations[0]['translation_1'],
									$translation->translations[0]['translation_2'],
									$translation->translations[0]['translation_3'],
									$translation->translations[0]['translation_4'],
								)
							);
							echo ' data-translation="' . esc_attr( wp_json_encode( $t ) ) . '"';
						}
						echo '>';
						$index = strtolower( $translation->singular );
						if ( empty( $translation->translations ) ) {
							echo esc_html( $translation->singular );
						} else {
							echo esc_html( $translation->translations[0]['translation_0'] );
							$index .= ' ' . strtolower( $translation->translations[0]['translation_0'] );
						}
						echo '</data>';
						if ( $translation->context ) {
							echo ' <span class="context">' . esc_html( $translation->context ) . '</span>';
							echo ' <span class="index" style="display: none">' . esc_html( $index ) . '</span>';
						}

						echo '</li>';
					}
					?>
					</ul>
				</div>
			</div>
			<?php
		}
		?>
		<div>
			<div id="translator-pop-out">
				<ul>
					<li>
						<label class="switch">
							<input id="inline-translation-switch" type="checkbox">
							<span class="gp-inline-slider"></span>
						</label>
						<span><?php _e( 'Inline Translation Status', 'glotpress' ); ?></span>
					</li>
					<?php if ( self::is_fallback_string_list_active() ) : ?>
					<li>
						<label>
							<input id="inline-jump-next-switch" type="checkbox">
						</label>
						<span><?php _e( 'Jump to next on save', 'glotpress' ); ?></span>
					</li>
					<li>
						<a id="gp-show-translation-list" href="#"><?php _e( 'View list of strings', 'glotpress' ); ?></a>
					</li>
					<?php endif; ?>
					<li class="inline-stats">
						<strong><?php _e( 'Stats:', 'glotpress' ); ?></strong>
						<div>
							<span class="stats-label" title="<?php esc_attr_e( 'Current', 'glotpress' ); ?>">
								<div class="box stats-current"></div>
								<?php echo esc_html( $translation_stats['current'] ); ?>
							</span>
							<span class="stats-label" title="<?php esc_attr_e( 'Waiting', 'glotpress' ); ?>">
								<div class="box stats-waiting"></div>
								<?php echo esc_html( $translation_stats['waiting'] ); ?>
							</span>
							<span class="stats-label" title="<?php esc_attr_e( 'Untranslated', 'glotpress' ); ?>">
								<div class="box stats-untranslated"></div>
								<?php echo esc_html( $translation_stats['untranslated'] ); ?>
							</span>
						</div>
					</li>
				</ul>
			</div>
			<div id="translator-launcher" class="translator">
				<span class="dashicons dashicons-admin-site-alt3">
				</span>
			</div>
		</div>
		<?php
		return true;
	}

	/**
	 * Gets the status stats for the inline translation.
	 *
	 * @param      string $gp_locale  The locale.
	 *
	 * @return     array  The stats of the translation statuses.
	 */
	private function get_inline_translation_stats( $gp_locale ) {
		$translations = $this->get_translations( GP_Locales::by_field( 'wp_locale', $gp_locale ) );
		$stats        = array(
			'current'      => 0,
			'waiting'      => 0,
			'untranslated' => 0,
		);
		foreach ( $translations as $translation ) {
			if ( empty( $translation->translations ) ) {
				$stats['untranslated']++;
			} elseif ( 'current' === $translation->translations[0]['status'] ) {
				$stats['current']++;
			} elseif ( 'waiting' === $translation->translations[0]['status'] ) {
				$stats['waiting']++;
			} else {
				$stats['untranslated']++;
			}
		}
		return $stats;
	}

	public function rest_pre_echo_response( $passthru ) {
		$this->get_translations( $this->get_current_gp_locale() );
		return $passthru;
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
		$project_paths = array();
		foreach ( $this->get_projects( $this->text_domains ) as $text_domain => $projects ) {
			$project_paths[ $text_domain ] = array();
			foreach ( $projects as $project ) {
				$project_paths[ $text_domain ][] = $project->path;
			}
		}

		$data = array(
			'translations'           => $this->get_translations( $gp_locale ),
			'localeCode'             => $gp_locale->slug,
			'languageName'           => html_entity_decode( $gp_locale->english_name ),
			'currentUserId'          => get_current_user_id(),
			'pluralForms'            => $plural_forms,
			'glotPress'              => array(
				'url'                  => gp_url( '/' ),
				'restUrl'              => esc_url_raw( rest_url( 'glotpress/v1' ) ),
				'nonce'                => wp_create_nonce( 'wp_rest' ),
				'projects'             => $project_paths,
				'translation_set_slug' => 'default',
			),
		);

		if ( get_user_option( 'gp_openai_key' ) || get_option( 'gp_openai_key' ) ) {
			if ( get_user_option( 'gp_openai_key' ) ) {
				$data['glotPress']['openai_key'] = get_user_option( 'gp_openai_key' );
			} else {
				$data['glotPress']['openai_key'] = get_option( 'gp_openai_key' );
			}

			$prompt        = '';
			$custom_prompt = get_option( 'gp_chatgpt_custom_prompt' );
			if ( $custom_prompt ) {
				$prompt .= rtrim( $custom_prompt, '. ' ) . '. ';
			}
			$custom_prompt = get_user_option( 'gp_chatgpt_custom_prompt' );
			if ( $custom_prompt ) {
				$prompt .= rtrim( $custom_prompt, '. ' ) . '. ';
			}
			$data['glotPress']['openai_prompt'] = $prompt;
		}
		return $data;
	}
}
