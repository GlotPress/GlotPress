<?php
/**
 * Locales: Locale information for use in GlotPress.
 *
 * @package GlotPress
 * @since 1.0.0
 */

if ( ! class_exists( 'GP_Locale' ) ) :

	/**
	 * Core locale class used to define the properties of a locale.
	 *
	 * @since 1.0.0
	 */
	class GP_Locale {
		/**
		 * The english name of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $english_name;

		/**
		 * The native name of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $native_name;

		/**
		 * The text direction of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $text_direction = 'ltr';

		/**
		 * The ISO 639-1 code of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $lang_code_iso_639_1 = null;

		/**
		 * The ISO 639-2 code of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $lang_code_iso_639_2 = null;

		/**
		 * The ISO 639-3 code of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $lang_code_iso_639_3 = null;

		/**
		 * The ISO country code of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $country_code;

		/**
		 * The wordpress.org code of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $wp_locale;

		/**
		 * The internal slug of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $slug;

		/**
		 * The number of plural forms of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var int
		 */
		public $nplurals = 2;

		/**
		 * The plural expressions of the locale.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $plural_expression = 'n != 1';

		/*
		 * TODO: days, months, decimals, quotes.
		 */

		/**
		 * Index function of the pluralizing to use.
		 *
		 * @since 1.0.0
		 *
		 * @var function
		 */
		private $_index_for_number;

		/**
		 * The class constructor.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args A list of properties and values to set for the object.
		 */
		public function __construct( $args = array() ) {
			foreach ( $args as $key => $value ) {
				$this->$key = $value;
			}
		}

		/**
		 * Set the state of the object to a new set of properties.
		 *
		 * @param array $state A list of properties and values to set for the object.
		 *
		 * @return GP_Locale The new object with the desired state.
		 */
		public static function __set_state( $state ) {
			return new GP_Locale( $state );
		}

		/**
		 * Make deprecated properties checkable for backwards compatibility.
		 *
		 * @param string $name Property to check if set.
		 *
		 * @return bool Whether the property is set.
		 */
		public function __isset( $name ) {
			if ( 'rtl' === $name ) {
				return isset( $this->text_direction );
			}
		}

		/**
		 * Make deprecated properties readable for backwards compatibility.
		 *
		 * @param string $name Property to get.
		 *
		 * @return mixed Property.
		 */
		public function __get( $name ) {
			if ( 'rtl' === $name ) {
				return ( 'rtl' === $this->text_direction );
			}
		}

		/**
		 * Join the english and natives names with a '/' and pass it through the translation engine.
		 *
		 * @return string The combined and translated string.
		 */
		public function combined_name() {
			/* translators: combined name for locales: 1: name in English, 2: native name */
			return sprintf( _x( '%1$s/%2$s', 'locales' ), $this->english_name, $this->native_name );
		}

		/**
		 * Returns an array of pluralized numbers.
		 *
		 * @param string $index      Property to get.
		 * @param int    $how_many   Property to get.
		 * @param int    $test_up_to Property to get.
		 *
		 * @return array Array of numbers.
		 */
		public function numbers_for_index( $index, $how_many = 3, $test_up_to = 1000 ) {
			$numbers = array();

			for ( $number = 0; $number < $test_up_to; ++$number ) {
				if ( $this->index_for_number( $number ) === $index ) {
					$numbers[] = $number;

					if ( count( $numbers ) >= $how_many ) {
						break;
					}
				}
			}

			return $numbers;
		}

		/**
		 * Returns an pluralized format for a number.
		 *
		 * @param string $number Number to pluralize.
		 *
		 * @return string Pluralized number.
		 */
		public function index_for_number( $number ) {
			if ( ! isset( $this->_index_for_number ) ) {
				$gettext = new Gettext_Translations;
				$expression = $gettext->parenthesize_plural_exression( $this->plural_expression );
				$this->_index_for_number = $gettext->make_plural_form_function( $this->nplurals, $expression );
			}

			$f = $this->_index_for_number;

			return $f( $number );
		}
	}

endif;

if ( ! class_exists( 'GP_Locales' ) ) :

	/**
	 * Locales class used to define the locales collection.
	 *
	 * @since 1.0.0
	 */
	class GP_Locales {

		/**
		 * The array of current locales.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		public $locales = array();

		/**
		 * The class constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->_populate_locales();
		}

		/**
		 * Returns a global instance of the class.
		 *
		 * @return GP_Locales The global instance of the GP_Locales class.
		 */
		public static function &instance() {
			if ( ! isset( $GLOBALS['gp_locales'] ) ) {
				$GLOBALS['gp_locales'] = new GP_Locales;
			}

			return $GLOBALS['gp_locales'];
		}

		/**
		 * Returns the list of locales.
		 *
		 * @return array The list of current locales.
		 */
		public static function locales() {
			$instance = GP_Locales::instance();

			return $instance->locales;
		}

		/**
		 * Checks to see if a given locale exists.
		 *
		 * @param string $slug The slug to check.
		 *
		 * @return bool True if the locale exists, false otherwise.
		 */
		public static function exists( $slug ) {
			$instance = GP_Locales::instance();

			return isset( $instance->locales[ $slug ] );
		}

		/**
		 * Gets a locale by the slug.
		 *
		 * @param string $slug The slug to search for.
		 *
		 * @return GP_Locale|null Returns the GP_Locale object if it exists, otherwise returns null.
		 */
		public static function by_slug( $slug ) {
			$instance = GP_Locales::instance();

			return isset( $instance->locales[ $slug ] ) ? $instance->locales[ $slug ] : null;
		}

		/**
		 * Gets a locale by a GP_Locale field.
		 *
		 * @param string $field_name  The field to search.
		 * @param string $field_value The value to search for.
		 *
		 * @return GP_Locale|false Returns the GP_Locale object if it exists, otherwise returns false.
		 */
		public static function by_field( $field_name, $field_value ) {
			$instance = GP_Locales::instance();
			$result   = false;

			foreach ( $instance->locales() as $locale ) {
				if ( isset( $locale->$field_name ) && $locale->$field_name === $field_value ) {
					$result = $locale;
					break;
				}
			}

			return $result;
		}

		/**
		 * Populate the class locales array.
		 */
		private function _populate_locales() {
			$this->locales['aa'] = new GP_Locale();
			$this->locales['aa']->english_name = 'Afar';
			$this->locales['aa']->native_name = 'Afaraf';
			$this->locales['aa']->lang_code_iso_639_1 = 'aa';
			$this->locales['aa']->lang_code_iso_639_2 = 'aar';
			$this->locales['aa']->slug = 'aa';

			$this->locales['ae'] = new GP_Locale();
			$this->locales['ae']->english_name = 'Avestan';
			$this->locales['ae']->native_name = 'Avesta';
			$this->locales['ae']->lang_code_iso_639_1 = 'ae';
			$this->locales['ae']->lang_code_iso_639_2 = 'ave';
			$this->locales['ae']->slug = 'ae';

			$this->locales['af'] = new GP_Locale();
			$this->locales['af']->english_name = 'Afrikaans';
			$this->locales['af']->native_name = 'Afrikaans';
			$this->locales['af']->lang_code_iso_639_1 = 'af';
			$this->locales['af']->lang_code_iso_639_2 = 'afr';
			$this->locales['af']->country_code = 'za';
			$this->locales['af']->wp_locale = 'af';
			$this->locales['af']->slug = 'af';

			$this->locales['ak'] = new GP_Locale();
			$this->locales['ak']->english_name = 'Akan';
			$this->locales['ak']->native_name = 'Akan';
			$this->locales['ak']->lang_code_iso_639_1 = 'ak';
			$this->locales['ak']->lang_code_iso_639_2 = 'aka';
			$this->locales['ak']->wp_locale = 'ak';
			$this->locales['ak']->slug = 'ak';

			$this->locales['am'] = new GP_Locale();
			$this->locales['am']->english_name = 'Amharic';
			$this->locales['am']->native_name = 'አማርኛ';
			$this->locales['am']->lang_code_iso_639_1 = 'am';
			$this->locales['am']->lang_code_iso_639_2 = 'amh';
			$this->locales['am']->country_code = 'et';
			$this->locales['am']->wp_locale = 'am';
			$this->locales['am']->slug = 'am';

			$this->locales['an'] = new GP_Locale();
			$this->locales['an']->english_name = 'Aragonese';
			$this->locales['an']->native_name = 'Aragonés';
			$this->locales['an']->lang_code_iso_639_1 = 'an';
			$this->locales['an']->lang_code_iso_639_2 = 'arg';
			$this->locales['an']->country_code = 'es';
			$this->locales['an']->slug = 'an';

			$this->locales['ar'] = new GP_Locale();
			$this->locales['ar']->english_name = 'Arabic';
			$this->locales['ar']->native_name = 'العربية';
			$this->locales['ar']->lang_code_iso_639_1 = 'ar';
			$this->locales['ar']->lang_code_iso_639_2 = 'ara';
			$this->locales['ar']->wp_locale = 'ar';
			$this->locales['ar']->slug = 'ar';
			$this->locales['ar']->nplurals = 6;
			$this->locales['ar']->plural_expression = 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5';
			$this->locales['ar']->text_direction = 'rtl';

			$this->locales['arq'] = new GP_Locale();
			$this->locales['arq']->english_name = 'Algerian Arabic';
			$this->locales['arq']->native_name = 'الدارجة الجزايرية';
			$this->locales['arq']->lang_code_iso_639_1 = 'ar';
			$this->locales['arq']->lang_code_iso_639_3 = 'arq';
			$this->locales['arq']->country_code = 'dz';
			$this->locales['arq']->wp_locale = 'arq';
			$this->locales['arq']->slug = 'arq';
			$this->locales['arq']->nplurals = 6;
			$this->locales['arq']->plural_expression = 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5';
			$this->locales['arq']->text_direction = 'rtl';

			$this->locales['ary'] = new GP_Locale();
			$this->locales['ary']->english_name = 'Moroccan Arabic';
			$this->locales['ary']->native_name = 'العربية المغربية';
			$this->locales['ary']->lang_code_iso_639_1 = 'ar';
			$this->locales['ary']->lang_code_iso_639_3 = 'ary';
			$this->locales['ary']->country_code = 'ma';
			$this->locales['ary']->wp_locale = 'ary';
			$this->locales['ary']->slug = 'ary';
			$this->locales['ary']->nplurals = 6;
			$this->locales['ary']->plural_expression = 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5';
			$this->locales['ary']->text_direction = 'rtl';

			$this->locales['as'] = new GP_Locale();
			$this->locales['as']->english_name = 'Assamese';
			$this->locales['as']->native_name = 'অসমীয়া';
			$this->locales['as']->lang_code_iso_639_1 = 'as';
			$this->locales['as']->lang_code_iso_639_2 = 'asm';
			$this->locales['as']->lang_code_iso_639_3 = 'asm';
			$this->locales['as']->country_code = 'in';
			$this->locales['as']->wp_locale = 'as';
			$this->locales['as']->slug = 'as';

			$this->locales['ast'] = new GP_Locale();
			$this->locales['ast']->english_name = 'Asturian';
			$this->locales['ast']->native_name = 'Asturianu';
			$this->locales['ast']->lang_code_iso_639_2 = 'ast';
			$this->locales['ast']->lang_code_iso_639_3 = 'ast';
			$this->locales['ast']->country_code = 'es';
			$this->locales['ast']->wp_locale = 'ast';
			$this->locales['ast']->slug = 'ast';

			$this->locales['av'] = new GP_Locale();
			$this->locales['av']->english_name = 'Avaric';
			$this->locales['av']->native_name = 'авар мацӀ';
			$this->locales['av']->lang_code_iso_639_1 = 'av';
			$this->locales['av']->lang_code_iso_639_2 = 'ava';
			$this->locales['av']->slug = 'av';

			$this->locales['ay'] = new GP_Locale();
			$this->locales['ay']->english_name = 'Aymara';
			$this->locales['ay']->native_name = 'aymar aru';
			$this->locales['ay']->lang_code_iso_639_1 = 'ay';
			$this->locales['ay']->lang_code_iso_639_2 = 'aym';
			$this->locales['ay']->slug = 'ay';
			$this->locales['ay']->nplurals = 1;
			$this->locales['ay']->plural_expression = '0';

			$this->locales['az'] = new GP_Locale();
			$this->locales['az']->english_name = 'Azerbaijani';
			$this->locales['az']->native_name = 'Azərbaycan dili';
			$this->locales['az']->lang_code_iso_639_1 = 'az';
			$this->locales['az']->lang_code_iso_639_2 = 'aze';
			$this->locales['az']->country_code = 'az';
			$this->locales['az']->wp_locale = 'az';
			$this->locales['az']->slug = 'az';

			$this->locales['azb'] = new GP_Locale();
			$this->locales['azb']->english_name = 'South Azerbaijani';
			$this->locales['azb']->native_name = 'گؤنئی آذربایجان';
			$this->locales['azb']->lang_code_iso_639_1 = 'az';
			$this->locales['azb']->lang_code_iso_639_3 = 'azb';
			$this->locales['azb']->country_code = 'ir';
			$this->locales['azb']->wp_locale = 'azb';
			$this->locales['azb']->slug = 'azb';
			$this->locales['azb']->text_direction = 'rtl';

			$this->locales['az-tr'] = new GP_Locale();
			$this->locales['az-tr']->english_name = 'Azerbaijani (Turkey)';
			$this->locales['az-tr']->native_name = 'Azərbaycan Türkcəsi';
			$this->locales['az-tr']->lang_code_iso_639_1 = 'az';
			$this->locales['az-tr']->lang_code_iso_639_2 = 'aze';
			$this->locales['az-tr']->country_code = 'tr';
			$this->locales['az-tr']->wp_locale = 'az_TR';
			$this->locales['az-tr']->slug = 'az-tr';

			$this->locales['ba'] = new GP_Locale();
			$this->locales['ba']->english_name = 'Bashkir';
			$this->locales['ba']->native_name = 'башҡорт теле';
			$this->locales['ba']->lang_code_iso_639_1 = 'ba';
			$this->locales['ba']->lang_code_iso_639_2 = 'bak';
			$this->locales['ba']->wp_locale = 'ba';
			$this->locales['ba']->slug = 'ba';

			$this->locales['bal'] = new GP_Locale();
			$this->locales['bal']->english_name = 'Catalan (Balear)';
			$this->locales['bal']->native_name = 'Català (Balear)';
			$this->locales['bal']->lang_code_iso_639_2 = 'bal';
			$this->locales['bal']->country_code = 'es';
			$this->locales['bal']->wp_locale = 'bal';
			$this->locales['bal']->slug = 'bal';

			$this->locales['bcc'] = new GP_Locale();
			$this->locales['bcc']->english_name = 'Balochi Southern';
			$this->locales['bcc']->native_name = 'بلوچی مکرانی';
			$this->locales['bcc']->lang_code_iso_639_3 = 'bcc';
			$this->locales['bcc']->country_code = 'pk';
			$this->locales['bcc']->wp_locale = 'bcc';
			$this->locales['bcc']->slug = 'bcc';
			$this->locales['bcc']->nplurals = 1;
			$this->locales['bcc']->plural_expression = '0';
			$this->locales['bcc']->text_direction = 'rtl';

			$this->locales['bel'] = new GP_Locale();
			$this->locales['bel']->english_name = 'Belarusian';
			$this->locales['bel']->native_name = 'Беларуская мова';
			$this->locales['bel']->lang_code_iso_639_1 = 'be';
			$this->locales['bel']->lang_code_iso_639_2 = 'bel';
			$this->locales['bel']->country_code = 'by';
			$this->locales['bel']->wp_locale = 'bel';
			$this->locales['bel']->slug = 'bel';
			$this->locales['bel']->nplurals = 3;
			$this->locales['bel']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['bg'] = new GP_Locale();
			$this->locales['bg']->english_name = 'Bulgarian';
			$this->locales['bg']->native_name = 'Български';
			$this->locales['bg']->lang_code_iso_639_1 = 'bg';
			$this->locales['bg']->lang_code_iso_639_2 = 'bul';
			$this->locales['bg']->country_code = 'bg';
			$this->locales['bg']->wp_locale = 'bg_BG';
			$this->locales['bg']->slug = 'bg';

			$this->locales['bh'] = new GP_Locale();
			$this->locales['bh']->english_name = 'Bihari';
			$this->locales['bh']->native_name = 'भोजपुरी';
			$this->locales['bh']->lang_code_iso_639_1 = 'bh';
			$this->locales['bh']->lang_code_iso_639_2 = 'bih';
			$this->locales['bh']->slug = 'bh';

			$this->locales['bi'] = new GP_Locale();
			$this->locales['bi']->english_name = 'Bislama';
			$this->locales['bi']->native_name = 'Bislama';
			$this->locales['bi']->lang_code_iso_639_1 = 'bi';
			$this->locales['bi']->lang_code_iso_639_2 = 'bis';
			$this->locales['bi']->country_code = 'vu';
			$this->locales['bi']->slug = 'bi';

			$this->locales['bm'] = new GP_Locale();
			$this->locales['bm']->english_name = 'Bambara';
			$this->locales['bm']->native_name = 'Bamanankan';
			$this->locales['bm']->lang_code_iso_639_1 = 'bm';
			$this->locales['bm']->lang_code_iso_639_2 = 'bam';
			$this->locales['bm']->slug = 'bm';

			$this->locales['bn'] = new GP_Locale();
			$this->locales['bn']->english_name = 'Bengali';
			$this->locales['bn']->native_name = 'বাংলা';
			$this->locales['bn']->lang_code_iso_639_1 = 'bn';
			$this->locales['bn']->country_code = 'bn';
			$this->locales['bn']->wp_locale = 'bn_BD';
			$this->locales['bn']->slug = 'bn';

			$this->locales['bo'] = new GP_Locale();
			$this->locales['bo']->english_name = 'Tibetan';
			$this->locales['bo']->native_name = 'བོད་སྐད';
			$this->locales['bo']->lang_code_iso_639_1 = 'bo';
			$this->locales['bo']->lang_code_iso_639_2 = 'tib';
			$this->locales['bo']->wp_locale = 'bo';
			$this->locales['bo']->slug = 'bo';
			$this->locales['bo']->nplurals = 1;
			$this->locales['bo']->plural_expression = '0';

			$this->locales['br'] = new GP_Locale();
			$this->locales['br']->english_name = 'Breton';
			$this->locales['br']->native_name = 'Brezhoneg';
			$this->locales['br']->lang_code_iso_639_1 = 'br';
			$this->locales['br']->lang_code_iso_639_2 = 'bre';
			$this->locales['br']->lang_code_iso_639_3 = 'bre';
			$this->locales['br']->country_code = 'fr';
			$this->locales['br']->wp_locale = 'bre';
			$this->locales['br']->slug = 'br';
			$this->locales['br']->nplurals = 2;
			$this->locales['br']->plural_expression = '(n > 1)';

			$this->locales['bs'] = new GP_Locale();
			$this->locales['bs']->english_name = 'Bosnian';
			$this->locales['bs']->native_name = 'Bosanski';
			$this->locales['bs']->lang_code_iso_639_1 = 'bs';
			$this->locales['bs']->lang_code_iso_639_2 = 'bos';
			$this->locales['bs']->country_code = 'ba';
			$this->locales['bs']->wp_locale = 'bs_BA';
			$this->locales['bs']->slug = 'bs';
			$this->locales['bs']->nplurals = 3;
			$this->locales['bs']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['ca'] = new GP_Locale();
			$this->locales['ca']->english_name = 'Catalan';
			$this->locales['ca']->native_name = 'Català';
			$this->locales['ca']->lang_code_iso_639_1 = 'ca';
			$this->locales['ca']->lang_code_iso_639_2 = 'cat';
			$this->locales['ca']->wp_locale = 'ca';
			$this->locales['ca']->slug = 'ca';

			$this->locales['ce'] = new GP_Locale();
			$this->locales['ce']->english_name = 'Chechen';
			$this->locales['ce']->native_name = 'Нохчийн мотт';
			$this->locales['ce']->lang_code_iso_639_1 = 'ce';
			$this->locales['ce']->lang_code_iso_639_2 = 'che';
			$this->locales['ce']->slug = 'ce';

			$this->locales['ceb'] = new GP_Locale();
			$this->locales['ceb']->english_name = 'Cebuano';
			$this->locales['ceb']->native_name = 'Cebuano';
			$this->locales['ceb']->lang_code_iso_639_2 = 'ceb';
			$this->locales['ceb']->lang_code_iso_639_3 = 'ceb';
			$this->locales['ceb']->country_code = 'ph';
			$this->locales['ceb']->wp_locale = 'ceb';
			$this->locales['ceb']->slug = 'ceb';

			$this->locales['ch'] = new GP_Locale();
			$this->locales['ch']->english_name = 'Chamorro';
			$this->locales['ch']->native_name = 'Chamoru';
			$this->locales['ch']->lang_code_iso_639_1 = 'ch';
			$this->locales['ch']->lang_code_iso_639_2 = 'cha';
			$this->locales['ch']->slug = 'ch';

			$this->locales['ckb'] = new GP_Locale();
			$this->locales['ckb']->english_name = 'Kurdish (Sorani)';
			$this->locales['ckb']->native_name = 'كوردی‎';
			$this->locales['ckb']->lang_code_iso_639_1 = 'ku';
			$this->locales['ckb']->lang_code_iso_639_3 = 'ckb';
			$this->locales['ckb']->country_code = 'iq';
			$this->locales['ckb']->wp_locale = 'ckb';
			$this->locales['ckb']->slug = 'ckb';
			$this->locales['ckb']->text_direction = 'rtl';

			$this->locales['co'] = new GP_Locale();
			$this->locales['co']->english_name = 'Corsican';
			$this->locales['co']->native_name = 'Corsu';
			$this->locales['co']->lang_code_iso_639_1 = 'co';
			$this->locales['co']->lang_code_iso_639_2 = 'cos';
			$this->locales['co']->country_code = 'it';
			$this->locales['co']->wp_locale = 'co';
			$this->locales['co']->slug = 'co';

			$this->locales['cr'] = new GP_Locale();
			$this->locales['cr']->english_name = 'Cree';
			$this->locales['cr']->native_name = 'ᓀᐦᐃᔭᐍᐏᐣ';
			$this->locales['cr']->lang_code_iso_639_1 = 'cr';
			$this->locales['cr']->lang_code_iso_639_2 = 'cre';
			$this->locales['cr']->country_code = 'ca';
			$this->locales['cr']->slug = 'cr';

			$this->locales['cs'] = new GP_Locale();
			$this->locales['cs']->english_name = 'Czech';
			$this->locales['cs']->native_name = 'Čeština‎';
			$this->locales['cs']->lang_code_iso_639_1 = 'cs';
			$this->locales['cs']->lang_code_iso_639_2 = 'ces';
			$this->locales['cs']->country_code = 'cz';
			$this->locales['cs']->wp_locale = 'cs_CZ';
			$this->locales['cs']->slug = 'cs';
			$this->locales['cs']->nplurals = 3;
			$this->locales['cs']->plural_expression = '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2';

			$this->locales['csb'] = new GP_Locale();
			$this->locales['csb']->english_name = 'Kashubian';
			$this->locales['csb']->native_name = 'Kaszëbsczi';
			$this->locales['csb']->lang_code_iso_639_2 = 'csb';
			$this->locales['csb']->slug = 'csb';
			$this->locales['csb']->nplurals = 3;
			$this->locales['csb']->plural_expression = 'n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2';

			$this->locales['cu'] = new GP_Locale();
			$this->locales['cu']->english_name = 'Church Slavic';
			$this->locales['cu']->native_name = 'ѩзыкъ словѣньскъ';
			$this->locales['cu']->lang_code_iso_639_1 = 'cu';
			$this->locales['cu']->lang_code_iso_639_2 = 'chu';
			$this->locales['cu']->slug = 'cu';

			$this->locales['cv'] = new GP_Locale();
			$this->locales['cv']->english_name = 'Chuvash';
			$this->locales['cv']->native_name = 'чӑваш чӗлхи';
			$this->locales['cv']->lang_code_iso_639_1 = 'cv';
			$this->locales['cv']->lang_code_iso_639_2 = 'chv';
			$this->locales['cv']->country_code = 'ru';
			$this->locales['cv']->slug = 'cv';

			$this->locales['cy'] = new GP_Locale();
			$this->locales['cy']->english_name = 'Welsh';
			$this->locales['cy']->native_name = 'Cymraeg';
			$this->locales['cy']->lang_code_iso_639_1 = 'cy';
			$this->locales['cy']->lang_code_iso_639_2 = 'cym';
			$this->locales['cy']->country_code = 'gb';
			$this->locales['cy']->wp_locale = 'cy';
			$this->locales['cy']->slug = 'cy';
			$this->locales['cy']->nplurals = 4;
			$this->locales['cy']->plural_expression = '(n==1) ? 0 : (n==2) ? 1 : (n != 8 && n != 11) ? 2 : 3';

			$this->locales['da'] = new GP_Locale();
			$this->locales['da']->english_name = 'Danish';
			$this->locales['da']->native_name = 'Dansk';
			$this->locales['da']->lang_code_iso_639_1 = 'da';
			$this->locales['da']->lang_code_iso_639_2 = 'dan';
			$this->locales['da']->country_code = 'dk';
			$this->locales['da']->wp_locale = 'da_DK';
			$this->locales['da']->slug = 'da';

			$this->locales['de'] = new GP_Locale();
			$this->locales['de']->english_name = 'German';
			$this->locales['de']->native_name = 'Deutsch';
			$this->locales['de']->lang_code_iso_639_1 = 'de';
			$this->locales['de']->country_code = 'de';
			$this->locales['de']->wp_locale = 'de_DE';
			$this->locales['de']->slug = 'de';

			$this->locales['de-ch'] = new GP_Locale();
			$this->locales['de-ch']->english_name = 'German (Switzerland)';
			$this->locales['de-ch']->native_name = 'Deutsch (Schweiz)';
			$this->locales['de-ch']->lang_code_iso_639_1 = 'de';
			$this->locales['de-ch']->country_code = 'ch';
			$this->locales['de-ch']->wp_locale = 'de_CH';
			$this->locales['de-ch']->slug = 'de-ch';

			$this->locales['dv'] = new GP_Locale();
			$this->locales['dv']->english_name = 'Dhivehi';
			$this->locales['dv']->native_name = 'ދިވެހި';
			$this->locales['dv']->lang_code_iso_639_1 = 'dv';
			$this->locales['dv']->lang_code_iso_639_2 = 'div';
			$this->locales['dv']->country_code = 'mv';
			$this->locales['dv']->wp_locale = 'dv';
			$this->locales['dv']->slug = 'dv';
			$this->locales['dv']->text_direction = 'rtl';

			$this->locales['dzo'] = new GP_Locale();
			$this->locales['dzo']->english_name = 'Dzongkha';
			$this->locales['dzo']->native_name = 'རྫོང་ཁ';
			$this->locales['dzo']->lang_code_iso_639_1 = 'dz';
			$this->locales['dzo']->lang_code_iso_639_2 = 'dzo';
			$this->locales['dzo']->country_code = 'bt';
			$this->locales['dzo']->wp_locale = 'dzo';
			$this->locales['dzo']->slug = 'dzo';
			$this->locales['dzo']->nplurals = 1;
			$this->locales['dzo']->plural_expression = '0';

			$this->locales['ee'] = new GP_Locale();
			$this->locales['ee']->english_name = 'Ewe';
			$this->locales['ee']->native_name = 'Eʋegbe';
			$this->locales['ee']->lang_code_iso_639_1 = 'ee';
			$this->locales['ee']->lang_code_iso_639_2 = 'ewe';
			$this->locales['ee']->slug = 'ee';

			$this->locales['el-po'] = new GP_Locale();
			$this->locales['el-po']->english_name = 'Greek (Polytonic)';
			$this->locales['el-po']->native_name = 'Greek (Polytonic)'; // TODO.
			$this->locales['el-po']->country_code = 'gr';
			$this->locales['el-po']->slug = 'el-po';

			$this->locales['el'] = new GP_Locale();
			$this->locales['el']->english_name = 'Greek';
			$this->locales['el']->native_name = 'Ελληνικά';
			$this->locales['el']->lang_code_iso_639_1 = 'el';
			$this->locales['el']->lang_code_iso_639_2 = 'ell';
			$this->locales['el']->country_code = 'gr';
			$this->locales['el']->wp_locale = 'el';
			$this->locales['el']->slug = 'el';

			$this->locales['art-xemoji'] = new GP_Locale();
			$this->locales['art-xemoji']->english_name = 'Emoji';
			$this->locales['art-xemoji']->native_name = "\xf0\x9f\x8c\x8f\xf0\x9f\x8c\x8d\xf0\x9f\x8c\x8e (Emoji)";
			$this->locales['art-xemoji']->lang_code_iso_639_2 = 'art';
			$this->locales['art-xemoji']->wp_locale = 'art_xemoji';
			$this->locales['art-xemoji']->slug = 'art-xemoji';
			$this->locales['art-xemoji']->nplurals = 1;
			$this->locales['art-xemoji']->plural_expression = '0';

			$this->locales['en'] = new GP_Locale();
			$this->locales['en']->english_name = 'English';
			$this->locales['en']->native_name = 'English';
			$this->locales['en']->lang_code_iso_639_1 = 'en';
			$this->locales['en']->country_code = 'us';
			$this->locales['en']->wp_locale = 'en_US';
			$this->locales['en']->slug = 'en';

			$this->locales['en-au'] = new GP_Locale();
			$this->locales['en-au']->english_name = 'English (Australia)';
			$this->locales['en-au']->native_name = 'English (Australia)';
			$this->locales['en-au']->lang_code_iso_639_1 = 'en';
			$this->locales['en-au']->lang_code_iso_639_2 = 'eng';
			$this->locales['en-au']->lang_code_iso_639_3 = 'eng';
			$this->locales['en-au']->country_code = 'au';
			$this->locales['en-au']->wp_locale = 'en_AU';
			$this->locales['en-au']->slug = 'en-au';

			$this->locales['en-ca'] = new GP_Locale();
			$this->locales['en-ca']->english_name = 'English (Canada)';
			$this->locales['en-ca']->native_name = 'English (Canada)';
			$this->locales['en-ca']->lang_code_iso_639_1 = 'en';
			$this->locales['en-ca']->lang_code_iso_639_2 = 'eng';
			$this->locales['en-ca']->lang_code_iso_639_3 = 'eng';
			$this->locales['en-ca']->country_code = 'ca';
			$this->locales['en-ca']->wp_locale = 'en_CA';
			$this->locales['en-ca']->slug = 'en-ca';

			$this->locales['en-gb'] = new GP_Locale();
			$this->locales['en-gb']->english_name = 'English (UK)';
			$this->locales['en-gb']->native_name = 'English (UK)';
			$this->locales['en-gb']->lang_code_iso_639_1 = 'en';
			$this->locales['en-gb']->lang_code_iso_639_2 = 'eng';
			$this->locales['en-gb']->lang_code_iso_639_3 = 'eng';
			$this->locales['en-gb']->country_code = 'gb';
			$this->locales['en-gb']->wp_locale = 'en_GB';
			$this->locales['en-gb']->slug = 'en-gb';

			$this->locales['en-nz'] = new GP_Locale();
			$this->locales['en-nz']->english_name = 'English (New Zealand)';
			$this->locales['en-nz']->native_name = 'English (New Zealand)';
			$this->locales['en-nz']->lang_code_iso_639_1 = 'en';
			$this->locales['en-nz']->lang_code_iso_639_2 = 'eng';
			$this->locales['en-nz']->lang_code_iso_639_3 = 'eng';
			$this->locales['en-nz']->country_code = 'nz';
			$this->locales['en-nz']->wp_locale = 'en_NZ';
			$this->locales['en-nz']->slug = 'en-nz';

			$this->locales['en-za'] = new GP_Locale();
			$this->locales['en-za']->english_name = 'English (South Africa)';
			$this->locales['en-za']->native_name = 'English (South Africa)';
			$this->locales['en-za']->lang_code_iso_639_1 = 'en';
			$this->locales['en-za']->lang_code_iso_639_2 = 'eng';
			$this->locales['en-za']->lang_code_iso_639_3 = 'eng';
			$this->locales['en-za']->country_code = 'za';
			$this->locales['en-za']->wp_locale = 'en_ZA';
			$this->locales['en-za']->slug = 'en-za';

			$this->locales['eo'] = new GP_Locale();
			$this->locales['eo']->english_name = 'Esperanto';
			$this->locales['eo']->native_name = 'Esperanto';
			$this->locales['eo']->lang_code_iso_639_1 = 'eo';
			$this->locales['eo']->lang_code_iso_639_2 = 'epo';
			$this->locales['eo']->wp_locale = 'eo';
			$this->locales['eo']->slug = 'eo';

			$this->locales['es'] = new GP_Locale();
			$this->locales['es']->english_name = 'Spanish (Spain)';
			$this->locales['es']->native_name = 'Español';
			$this->locales['es']->lang_code_iso_639_1 = 'es';
			$this->locales['es']->country_code = 'es';
			$this->locales['es']->wp_locale = 'es_ES';
			$this->locales['es']->slug = 'es';

			$this->locales['es-ar'] = new GP_Locale();
			$this->locales['es-ar']->english_name = 'Spanish (Argentina)';
			$this->locales['es-ar']->native_name = 'Español de Argentina';
			$this->locales['es-ar']->lang_code_iso_639_1 = 'es';
			$this->locales['es-ar']->lang_code_iso_639_2 = 'spa';
			$this->locales['es-ar']->country_code = 'ar';
			$this->locales['es-ar']->wp_locale = 'es_AR';
			$this->locales['es-ar']->slug = 'es-ar';

			$this->locales['es-cl'] = new GP_Locale();
			$this->locales['es-cl']->english_name = 'Spanish (Chile)';
			$this->locales['es-cl']->native_name = 'Español de Chile';
			$this->locales['es-cl']->lang_code_iso_639_1 = 'es';
			$this->locales['es-cl']->lang_code_iso_639_2 = 'spa';
			$this->locales['es-cl']->country_code = 'cl';
			$this->locales['es-cl']->wp_locale = 'es_CL';
			$this->locales['es-cl']->slug = 'es-cl';

			$this->locales['es-co'] = new GP_Locale();
			$this->locales['es-co']->english_name = 'Spanish (Colombia)';
			$this->locales['es-co']->native_name = 'Español de Colombia';
			$this->locales['es-co']->lang_code_iso_639_1 = 'es';
			$this->locales['es-co']->lang_code_iso_639_2 = 'spa';
			$this->locales['es-co']->country_code = 'co';
			$this->locales['es-co']->wp_locale = 'es_CO';
			$this->locales['es-co']->slug = 'es-co';

			$this->locales['es-gt'] = new GP_Locale();
			$this->locales['es-gt']->english_name = 'Spanish (Guatemala)';
			$this->locales['es-gt']->native_name = 'Español de Guatemala';
			$this->locales['es-gt']->lang_code_iso_639_1 = 'es';
			$this->locales['es-gt']->lang_code_iso_639_2 = 'spa';
			$this->locales['es-gt']->country_code = 'gt';
			$this->locales['es-gt']->wp_locale = 'es_GT';
			$this->locales['es-gt']->slug = 'es-gt';

			$this->locales['es-mx'] = new GP_Locale();
			$this->locales['es-mx']->english_name = 'Spanish (Mexico)';
			$this->locales['es-mx']->native_name = 'Español de México';
			$this->locales['es-mx']->lang_code_iso_639_1 = 'es';
			$this->locales['es-mx']->lang_code_iso_639_2 = 'spa';
			$this->locales['es-mx']->country_code = 'mx';
			$this->locales['es-mx']->wp_locale = 'es_MX';
			$this->locales['es-mx']->slug = 'es-mx';

			$this->locales['es-pe'] = new GP_Locale();
			$this->locales['es-pe']->english_name = 'Spanish (Peru)';
			$this->locales['es-pe']->native_name = 'Español de Perú';
			$this->locales['es-pe']->lang_code_iso_639_1 = 'es';
			$this->locales['es-pe']->lang_code_iso_639_2 = 'spa';
			$this->locales['es-pe']->country_code = 'pe';
			$this->locales['es-pe']->wp_locale = 'es_PE';
			$this->locales['es-pe']->slug = 'es-pe';

			$this->locales['es-pr'] = new GP_Locale();
			$this->locales['es-pr']->english_name = 'Spanish (Puerto Rico)';
			$this->locales['es-pr']->native_name = 'Español de Puerto Rico';
			$this->locales['es-pr']->lang_code_iso_639_1 = 'es';
			$this->locales['es-pr']->lang_code_iso_639_2 = 'spa';
			$this->locales['es-pr']->country_code = 'pr';
			$this->locales['es-pr']->wp_locale = 'es_PR';
			$this->locales['es-pr']->slug = 'es-pr';

			$this->locales['es-ve'] = new GP_Locale();
			$this->locales['es-ve']->english_name = 'Spanish (Venezuela)';
			$this->locales['es-ve']->native_name = 'Español de Venezuela';
			$this->locales['es-ve']->lang_code_iso_639_1 = 'es';
			$this->locales['es-ve']->lang_code_iso_639_2 = 'spa';
			$this->locales['es-ve']->country_code = 've';
			$this->locales['es-ve']->wp_locale = 'es_VE';
			$this->locales['es-ve']->slug = 'es-ve';

			$this->locales['et'] = new GP_Locale();
			$this->locales['et']->english_name = 'Estonian';
			$this->locales['et']->native_name = 'Eesti';
			$this->locales['et']->lang_code_iso_639_1 = 'et';
			$this->locales['et']->lang_code_iso_639_2 = 'est';
			$this->locales['et']->country_code = 'ee';
			$this->locales['et']->wp_locale = 'et';
			$this->locales['et']->slug = 'et';

			$this->locales['eu'] = new GP_Locale();
			$this->locales['eu']->english_name = 'Basque';
			$this->locales['eu']->native_name = 'Euskara';
			$this->locales['eu']->lang_code_iso_639_1 = 'eu';
			$this->locales['eu']->lang_code_iso_639_2 = 'eus';
			$this->locales['eu']->country_code = 'es';
			$this->locales['eu']->wp_locale = 'eu';
			$this->locales['eu']->slug = 'eu';

			$this->locales['fa'] = new GP_Locale();
			$this->locales['fa']->english_name = 'Persian';
			$this->locales['fa']->native_name = 'فارسی';
			$this->locales['fa']->lang_code_iso_639_1 = 'fa';
			$this->locales['fa']->lang_code_iso_639_2 = 'fas';
			$this->locales['fa']->wp_locale = 'fa_IR';
			$this->locales['fa']->slug = 'fa';
			$this->locales['fa']->nplurals = 1;
			$this->locales['fa']->plural_expression = '0';
			$this->locales['fa']->text_direction = 'rtl';

			$this->locales['fa-af'] = new GP_Locale();
			$this->locales['fa-af']->english_name = 'Persian (Afghanistan)';
			$this->locales['fa-af']->native_name = '(فارسی (افغانستان';
			$this->locales['fa-af']->lang_code_iso_639_1 = 'fa';
			$this->locales['fa-af']->lang_code_iso_639_2 = 'fas';
			$this->locales['fa-af']->wp_locale = 'fa_AF';
			$this->locales['fa-af']->slug = 'fa-af';
			$this->locales['fa-af']->nplurals = 1;
			$this->locales['fa-af']->plural_expression = '0';
			$this->locales['fa-af']->text_direction = 'rtl';

			$this->locales['fuc'] = new GP_Locale();
			$this->locales['fuc']->english_name = 'Fulah';
			$this->locales['fuc']->native_name = 'Pulaar';
			$this->locales['fuc']->lang_code_iso_639_1 = 'ff';
			$this->locales['fuc']->lang_code_iso_639_2 = 'fuc';
			$this->locales['fuc']->country_code = 'sn';
			$this->locales['fuc']->wp_locale = 'fuc';
			$this->locales['fuc']->slug = 'fuc';
			$this->locales['fuc']->plural_expression = 'n!=1';

			$this->locales['fi'] = new GP_Locale();
			$this->locales['fi']->english_name = 'Finnish';
			$this->locales['fi']->native_name = 'Suomi';
			$this->locales['fi']->lang_code_iso_639_1 = 'fi';
			$this->locales['fi']->lang_code_iso_639_2 = 'fin';
			$this->locales['fi']->country_code = 'fi';
			$this->locales['fi']->wp_locale = 'fi';
			$this->locales['fi']->slug = 'fi';

			$this->locales['fj'] = new GP_Locale();
			$this->locales['fj']->english_name = 'Fijian';
			$this->locales['fj']->native_name = 'Vosa Vakaviti';
			$this->locales['fj']->lang_code_iso_639_1 = 'fj';
			$this->locales['fj']->lang_code_iso_639_2 = 'fij';
			$this->locales['fj']->country_code = 'fj';
			$this->locales['fj']->slug = 'fj';

			$this->locales['fo'] = new GP_Locale();
			$this->locales['fo']->english_name = 'Faroese';
			$this->locales['fo']->native_name = 'Føroyskt';
			$this->locales['fo']->lang_code_iso_639_1 = 'fo';
			$this->locales['fo']->lang_code_iso_639_2 = 'fao';
			$this->locales['fo']->country_code = 'fo';
			$this->locales['fo']->wp_locale = 'fo';
			$this->locales['fo']->slug = 'fo';

			$this->locales['fr'] = new GP_Locale();
			$this->locales['fr']->english_name = 'French (France)';
			$this->locales['fr']->native_name = 'Français';
			$this->locales['fr']->lang_code_iso_639_1 = 'fr';
			$this->locales['fr']->country_code = 'fr';
			$this->locales['fr']->wp_locale = 'fr_FR';
			$this->locales['fr']->slug = 'fr';
			$this->locales['fr']->nplurals = 2;
			$this->locales['fr']->plural_expression = 'n > 1';

			$this->locales['fr-be'] = new GP_Locale();
			$this->locales['fr-be']->english_name = 'French (Belgium)';
			$this->locales['fr-be']->native_name = 'Français de Belgique';
			$this->locales['fr-be']->lang_code_iso_639_1 = 'fr';
			$this->locales['fr-be']->lang_code_iso_639_2 = 'fra';
			$this->locales['fr-be']->country_code = 'be';
			$this->locales['fr-be']->wp_locale = 'fr_BE';
			$this->locales['fr-be']->slug = 'fr-be';

			$this->locales['fr-ca'] = new GP_Locale();
			$this->locales['fr-ca']->english_name = 'French (Canada)';
			$this->locales['fr-ca']->native_name = 'Français du Canada';
			$this->locales['fr-ca']->lang_code_iso_639_1 = 'fr';
			$this->locales['fr-ca']->lang_code_iso_639_2 = 'fra';
			$this->locales['fr-ca']->country_code = 'ca';
			$this->locales['fr-ca']->wp_locale = 'fr_CA';
			$this->locales['fr-ca']->slug = 'fr-ca';

			$this->locales['fr-ch'] = new GP_Locale();
			$this->locales['fr-ch']->english_name = 'French (Switzerland)';
			$this->locales['fr-ch']->native_name = 'Français de Suisse';
			$this->locales['fr-ch']->lang_code_iso_639_1 = 'fr';
			$this->locales['fr-ch']->lang_code_iso_639_2 = 'fra';
			$this->locales['fr-ch']->country_code = 'ch';
			$this->locales['fr-ch']->slug = 'fr-ch';

			$this->locales['frp'] = new GP_Locale();
			$this->locales['frp']->english_name = 'Arpitan';
			$this->locales['frp']->native_name = 'Arpitan';
			$this->locales['frp']->lang_code_iso_639_3 = 'frp';
			$this->locales['frp']->country_code = 'fr';
			$this->locales['frp']->wp_locale = 'frp';
			$this->locales['frp']->slug = 'frp';
			$this->locales['frp']->nplurals = 2;
			$this->locales['frp']->plural_expression = 'n > 1';

			$this->locales['fur'] = new GP_Locale();
			$this->locales['fur']->english_name = 'Friulian';
			$this->locales['fur']->native_name = 'Friulian';
			$this->locales['fur']->lang_code_iso_639_2 = 'fur';
			$this->locales['fur']->lang_code_iso_639_3 = 'fur';
			$this->locales['fur']->country_code = 'it';
			$this->locales['fur']->wp_locale = 'fur';
			$this->locales['fur']->slug = 'fur';

			$this->locales['fy'] = new GP_Locale();
			$this->locales['fy']->english_name = 'Frisian';
			$this->locales['fy']->native_name = 'Frysk';
			$this->locales['fy']->lang_code_iso_639_1 = 'fy';
			$this->locales['fy']->lang_code_iso_639_2 = 'fry';
			$this->locales['fy']->country_code = 'nl';
			$this->locales['fy']->wp_locale = 'fy';
			$this->locales['fy']->slug = 'fy';

			$this->locales['ga'] = new GP_Locale();
			$this->locales['ga']->english_name = 'Irish';
			$this->locales['ga']->native_name = 'Gaelige';
			$this->locales['ga']->lang_code_iso_639_1 = 'ga';
			$this->locales['ga']->lang_code_iso_639_2 = 'gle';
			$this->locales['ga']->country_code = 'ie';
			$this->locales['ga']->slug = 'ga';
			$this->locales['ga']->wp_locale = 'ga';
			$this->locales['ga']->nplurals = 5;
			$this->locales['ga']->plural_expression = 'n==1 ? 0 : n==2 ? 1 : n<7 ? 2 : n<11 ? 3 : 4';

			$this->locales['gd'] = new GP_Locale();
			$this->locales['gd']->english_name = 'Scottish Gaelic';
			$this->locales['gd']->native_name = 'Gàidhlig';
			$this->locales['gd']->lang_code_iso_639_1 = 'gd';
			$this->locales['gd']->lang_code_iso_639_2 = 'gla';
			$this->locales['gd']->lang_code_iso_639_3 = 'gla';
			$this->locales['gd']->country_code = 'gb';
			$this->locales['gd']->wp_locale = 'gd';
			$this->locales['gd']->slug = 'gd';
			$this->locales['gd']->nplurals = 4;
			$this->locales['gd']->plural_expression = '(n==1 || n==11) ? 0 : (n==2 || n==12) ? 1 : (n > 2 && n < 20) ? 2 : 3';

			$this->locales['gl'] = new GP_Locale();
			$this->locales['gl']->english_name = 'Galician';
			$this->locales['gl']->native_name = 'Galego';
			$this->locales['gl']->lang_code_iso_639_1 = 'gl';
			$this->locales['gl']->lang_code_iso_639_2 = 'glg';
			$this->locales['gl']->country_code = 'es';
			$this->locales['gl']->wp_locale = 'gl_ES';
			$this->locales['gl']->slug = 'gl';

			$this->locales['gn'] = new GP_Locale();
			$this->locales['gn']->english_name = 'Guaraní';
			$this->locales['gn']->native_name = 'Avañe\'ẽ';
			$this->locales['gn']->lang_code_iso_639_1 = 'gn';
			$this->locales['gn']->lang_code_iso_639_2 = 'grn';
			$this->locales['gn']->wp_locale = 'gn';
			$this->locales['gn']->slug = 'gn';

			$this->locales['gsw'] = new GP_Locale();
			$this->locales['gsw']->english_name = 'Swiss German';
			$this->locales['gsw']->native_name = 'Schwyzerdütsch';
			$this->locales['gsw']->lang_code_iso_639_2 = 'gsw';
			$this->locales['gsw']->lang_code_iso_639_3 = 'gsw';
			$this->locales['gsw']->country_code = 'ch';
			$this->locales['gsw']->wp_locale = 'gsw';
			$this->locales['gsw']->slug = 'gsw';

			$this->locales['gu'] = new GP_Locale();
			$this->locales['gu']->english_name = 'Gujarati';
			$this->locales['gu']->native_name = 'ગુજરાતી';
			$this->locales['gu']->lang_code_iso_639_1 = 'gu';
			$this->locales['gu']->lang_code_iso_639_2 = 'guj';
			$this->locales['gu']->wp_locale = 'gu';
			$this->locales['gu']->slug = 'gu';

			$this->locales['ha'] = new GP_Locale();
			$this->locales['ha']->english_name = 'Hausa (Arabic)';
			$this->locales['ha']->native_name = 'هَوُسَ';
			$this->locales['ha']->lang_code_iso_639_1 = 'ha';
			$this->locales['ha']->lang_code_iso_639_2 = 'hau';
			$this->locales['ha']->slug = 'ha';
			$this->locales['ha']->text_direction = 'rtl';

			$this->locales['hat'] = new GP_Locale();
			$this->locales['hat']->english_name = 'Haitian Creole';
			$this->locales['hat']->native_name = 'Kreyol ayisyen';
			$this->locales['hat']->lang_code_iso_639_1 = 'ht';
			$this->locales['hat']->lang_code_iso_639_2 = 'hat';
			$this->locales['hat']->lang_code_iso_639_3 = 'hat';
			$this->locales['hat']->country_code = 'ht';
			$this->locales['hat']->wp_locale = 'hat';
			$this->locales['hat']->slug = 'hat';

			$this->locales['hau'] = new GP_Locale();
			$this->locales['hau']->english_name = 'Hausa';
			$this->locales['hau']->native_name = 'Harshen Hausa';
			$this->locales['hau']->lang_code_iso_639_1 = 'ha';
			$this->locales['hau']->lang_code_iso_639_2 = 'hau';
			$this->locales['hau']->lang_code_iso_639_3 = 'hau';
			$this->locales['hau']->country_code = 'ng';
			$this->locales['hau']->wp_locale = 'hau';
			$this->locales['hau']->slug = 'hau';

			$this->locales['haw'] = new GP_Locale();
			$this->locales['haw']->english_name = 'Hawaiian';
			$this->locales['haw']->native_name = 'Ōlelo Hawaiʻi';
			$this->locales['haw']->lang_code_iso_639_2 = 'haw';
			$this->locales['haw']->country_code = 'us';
			$this->locales['haw']->wp_locale = 'haw_US';
			$this->locales['haw']->slug = 'haw';

			$this->locales['haz'] = new GP_Locale();
			$this->locales['haz']->english_name = 'Hazaragi';
			$this->locales['haz']->native_name = 'هزاره گی';
			$this->locales['haz']->lang_code_iso_639_3 = 'haz';
			$this->locales['haz']->country_code = 'af';
			$this->locales['haz']->wp_locale = 'haz';
			$this->locales['haz']->slug = 'haz';
			$this->locales['haz']->text_direction = 'rtl';

			$this->locales['he'] = new GP_Locale();
			$this->locales['he']->english_name = 'Hebrew';
			$this->locales['he']->native_name = 'עִבְרִית';
			$this->locales['he']->lang_code_iso_639_1 = 'he';
			$this->locales['he']->country_code = 'il';
			$this->locales['he']->wp_locale = 'he_IL';
			$this->locales['he']->slug = 'he';
			$this->locales['he']->text_direction = 'rtl';

			$this->locales['hi'] = new GP_Locale();
			$this->locales['hi']->english_name = 'Hindi';
			$this->locales['hi']->native_name = 'हिन्दी';
			$this->locales['hi']->lang_code_iso_639_1 = 'hi';
			$this->locales['hi']->lang_code_iso_639_2 = 'hin';
			$this->locales['hi']->country_code = 'in';
			$this->locales['hi']->wp_locale = 'hi_IN';
			$this->locales['hi']->slug = 'hi';

			$this->locales['hr'] = new GP_Locale();
			$this->locales['hr']->english_name = 'Croatian';
			$this->locales['hr']->native_name = 'Hrvatski';
			$this->locales['hr']->lang_code_iso_639_1 = 'hr';
			$this->locales['hr']->lang_code_iso_639_2 = 'hrv';
			$this->locales['hr']->country_code = 'hr';
			$this->locales['hr']->wp_locale = 'hr';
			$this->locales['hr']->slug = 'hr';
			$this->locales['hr']->nplurals = 3;
			$this->locales['hr']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['hu'] = new GP_Locale();
			$this->locales['hu']->english_name = 'Hungarian';
			$this->locales['hu']->native_name = 'Magyar';
			$this->locales['hu']->lang_code_iso_639_1 = 'hu';
			$this->locales['hu']->lang_code_iso_639_2 = 'hun';
			$this->locales['hu']->country_code = 'hu';
			$this->locales['hu']->wp_locale = 'hu_HU';
			$this->locales['hu']->slug = 'hu';

			$this->locales['hy'] = new GP_Locale();
			$this->locales['hy']->english_name = 'Armenian';
			$this->locales['hy']->native_name = 'Հայերեն';
			$this->locales['hy']->lang_code_iso_639_1 = 'hy';
			$this->locales['hy']->lang_code_iso_639_2 = 'hye';
			$this->locales['hy']->country_code = 'am';
			$this->locales['hy']->wp_locale = 'hy';
			$this->locales['hy']->slug = 'hy';

			$this->locales['ia'] = new GP_Locale();
			$this->locales['ia']->english_name = 'Interlingua';
			$this->locales['ia']->native_name = 'Interlingua';
			$this->locales['ia']->lang_code_iso_639_1 = 'ia';
			$this->locales['ia']->lang_code_iso_639_2 = 'ina';
			$this->locales['ia']->slug = 'ia';

			$this->locales['id'] = new GP_Locale();
			$this->locales['id']->english_name = 'Indonesian';
			$this->locales['id']->native_name = 'Bahasa Indonesia';
			$this->locales['id']->lang_code_iso_639_1 = 'id';
			$this->locales['id']->lang_code_iso_639_2 = 'ind';
			$this->locales['id']->country_code = 'id';
			$this->locales['id']->wp_locale = 'id_ID';
			$this->locales['id']->slug = 'id';
			$this->locales['id']->nplurals = 2;
			$this->locales['id']->plural_expression = 'n > 1';

			$this->locales['ido'] = new GP_Locale();
			$this->locales['ido']->english_name = 'Ido';
			$this->locales['ido']->native_name = 'Ido';
			$this->locales['ido']->lang_code_iso_639_1 = 'io';
			$this->locales['ido']->lang_code_iso_639_2 = 'ido';
			$this->locales['ido']->lang_code_iso_639_3 = 'ido';
			$this->locales['ido']->wp_locale = 'ido';
			$this->locales['ido']->slug = 'ido';

			$this->locales['ike'] = new GP_Locale();
			$this->locales['ike']->english_name = 'Inuktitut';
			$this->locales['ike']->native_name = 'ᐃᓄᒃᑎᑐᑦ';
			$this->locales['ike']->lang_code_iso_639_1 = 'iu';
			$this->locales['ike']->lang_code_iso_639_2 = 'iku';
			$this->locales['ike']->country_code = 'ca';
			$this->locales['ike']->slug = 'ike';

			$this->locales['ilo'] = new GP_Locale();
			$this->locales['ilo']->english_name = 'Iloko';
			$this->locales['ilo']->native_name = 'Pagsasao nga Iloko';
			$this->locales['ilo']->lang_code_iso_639_2 = 'ilo';
			$this->locales['ilo']->country_code = 'ph';
			$this->locales['ilo']->slug = 'ilo';

			$this->locales['is'] = new GP_Locale();
			$this->locales['is']->english_name = 'Icelandic';
			$this->locales['is']->native_name = 'Íslenska';
			$this->locales['is']->lang_code_iso_639_1 = 'is';
			$this->locales['is']->lang_code_iso_639_2 = 'isl';
			$this->locales['is']->country_code = 'is';
			$this->locales['is']->slug = 'is';
			$this->locales['is']->wp_locale = 'is_IS';
			$this->locales['is']->nplurals = 2;
			$this->locales['is']->plural_expression = '(n % 100 != 1 && n % 100 != 21 && n % 100 != 31 && n % 100 != 41 && n % 100 != 51 && n % 100 != 61 && n % 100 != 71 && n % 100 != 81 && n % 100 != 91)';

			$this->locales['it'] = new GP_Locale();
			$this->locales['it']->english_name = 'Italian';
			$this->locales['it']->native_name = 'Italiano';
			$this->locales['it']->lang_code_iso_639_1 = 'it';
			$this->locales['it']->lang_code_iso_639_2 = 'ita';
			$this->locales['it']->country_code = 'it';
			$this->locales['it']->wp_locale = 'it_IT';
			$this->locales['it']->slug = 'it';

			$this->locales['ja'] = new GP_Locale();
			$this->locales['ja']->english_name = 'Japanese';
			$this->locales['ja']->native_name = '日本語';
			$this->locales['ja']->lang_code_iso_639_1 = 'ja';
			$this->locales['ja']->country_code = 'jp';
			$this->locales['ja']->wp_locale = 'ja';
			$this->locales['ja']->slug = 'ja';
			$this->locales['ja']->nplurals = 1;
			$this->locales['ja']->plural_expression = '0';

			$this->locales['jv'] = new GP_Locale();
			$this->locales['jv']->english_name = 'Javanese';
			$this->locales['jv']->native_name = 'Basa Jawa';
			$this->locales['jv']->lang_code_iso_639_1 = 'jv';
			$this->locales['jv']->lang_code_iso_639_2 = 'jav';
			$this->locales['jv']->country_code = 'id';
			$this->locales['jv']->wp_locale = 'jv_ID';
			$this->locales['jv']->slug = 'jv';

			$this->locales['ka'] = new GP_Locale();
			$this->locales['ka']->english_name = 'Georgian';
			$this->locales['ka']->native_name = 'ქართული';
			$this->locales['ka']->lang_code_iso_639_1 = 'ka';
			$this->locales['ka']->lang_code_iso_639_2 = 'kat';
			$this->locales['ka']->country_code = 'ge';
			$this->locales['ka']->wp_locale = 'ka_GE';
			$this->locales['ka']->slug = 'ka';
			$this->locales['ka']->nplurals = 1;
			$this->locales['ka']->plural_expression = '0';

			$this->locales['kab'] = new GP_Locale();
			$this->locales['kab']->english_name = 'Kabyle';
			$this->locales['kab']->native_name = 'Taqbaylit';
			$this->locales['kab']->lang_code_iso_639_2 = 'kab';
			$this->locales['kab']->lang_code_iso_639_3 = 'kab';
			$this->locales['kab']->country_code = 'dz';
			$this->locales['kab']->wp_locale = 'kab';
			$this->locales['kab']->slug = 'kab';
			$this->locales['kab']->nplurals = 2;
			$this->locales['kab']->plural_expression = '(n > 1)';

			$this->locales['kal'] = new GP_Locale();
			$this->locales['kal']->english_name = 'Greenlandic';
			$this->locales['kal']->native_name = 'Kalaallisut';
			$this->locales['kal']->lang_code_iso_639_1 = 'kl';
			$this->locales['kal']->lang_code_iso_639_2 = 'kal';
			$this->locales['kal']->lang_code_iso_639_3 = 'kal';
			$this->locales['kal']->country_code = 'gl';
			$this->locales['kal']->wp_locale = 'kal';
			$this->locales['kal']->slug = 'kal';

			$this->locales['kin'] = new GP_Locale();
			$this->locales['kin']->english_name = 'Kinyarwanda';
			$this->locales['kin']->native_name = 'Ikinyarwanda';
			$this->locales['kin']->lang_code_iso_639_1 = 'rw';
			$this->locales['kin']->lang_code_iso_639_2 = 'kin';
			$this->locales['kin']->lang_code_iso_639_3 = 'kin';
			$this->locales['kin']->wp_locale = 'kin';
			$this->locales['kin']->country_code = 'rw';
			$this->locales['kin']->slug = 'kin';

			$this->locales['kir'] = new GP_Locale();
			$this->locales['kir']->english_name = 'Kyrgyz';
			$this->locales['kir']->native_name = 'Кыргызча';
			$this->locales['kir']->lang_code_iso_639_1 = 'ky';
			$this->locales['kir']->lang_code_iso_639_2 = 'kir';
			$this->locales['kir']->lang_code_iso_639_3 = 'kir';
			$this->locales['kir']->country_code = 'kg';
			$this->locales['kir']->wp_locale = 'kir';
			$this->locales['kir']->slug = 'kir';
			$this->locales['kir']->nplurals = 1;
			$this->locales['kir']->plural_expression = '0';

			$this->locales['kk'] = new GP_Locale();
			$this->locales['kk']->english_name = 'Kazakh';
			$this->locales['kk']->native_name = 'Қазақ тілі';
			$this->locales['kk']->lang_code_iso_639_1 = 'kk';
			$this->locales['kk']->lang_code_iso_639_2 = 'kaz';
			$this->locales['kk']->country_code = 'kz';
			$this->locales['kk']->wp_locale = 'kk';
			$this->locales['kk']->slug = 'kk';

			$this->locales['km'] = new GP_Locale();
			$this->locales['km']->english_name = 'Khmer';
			$this->locales['km']->native_name = 'ភាសាខ្មែរ';
			$this->locales['km']->lang_code_iso_639_1 = 'km';
			$this->locales['km']->lang_code_iso_639_2 = 'khm';
			$this->locales['km']->country_code = 'kh';
			$this->locales['km']->wp_locale = 'km';
			$this->locales['km']->slug = 'km';
			$this->locales['km']->nplurals = 1;
			$this->locales['km']->plural_expression = '0';

			$this->locales['kmr'] = new GP_Locale();
			$this->locales['kmr']->english_name = 'Kurdish (Kurmanji)';
			$this->locales['kmr']->native_name = 'Kurdî';
			$this->locales['kmr']->lang_code_iso_639_1 = 'ku';
			$this->locales['kmr']->lang_code_iso_639_3 = 'kmr';
			$this->locales['kmr']->country_code = 'tr';
			$this->locales['kmr']->slug = 'kmr';

			$this->locales['kn'] = new GP_Locale();
			$this->locales['kn']->english_name = 'Kannada';
			$this->locales['kn']->native_name = 'ಕನ್ನಡ';
			$this->locales['kn']->lang_code_iso_639_1 = 'kn';
			$this->locales['kn']->lang_code_iso_639_2 = 'kan';
			$this->locales['kn']->country_code = 'in';
			$this->locales['kn']->wp_locale = 'kn';
			$this->locales['kn']->slug = 'kn';

			$this->locales['ko'] = new GP_Locale();
			$this->locales['ko']->english_name = 'Korean';
			$this->locales['ko']->native_name = '한국어';
			$this->locales['ko']->lang_code_iso_639_1 = 'ko';
			$this->locales['ko']->lang_code_iso_639_2 = 'kor';
			$this->locales['ko']->country_code = 'kr';
			$this->locales['ko']->wp_locale = 'ko_KR';
			$this->locales['ko']->slug = 'ko';
			$this->locales['ko']->nplurals = 1;
			$this->locales['ko']->plural_expression = '0';

			$this->locales['ks'] = new GP_Locale();
			$this->locales['ks']->english_name = 'Kashmiri';
			$this->locales['ks']->native_name = 'कश्मीरी';
			$this->locales['ks']->lang_code_iso_639_1 = 'ks';
			$this->locales['ks']->lang_code_iso_639_2 = 'kas';
			$this->locales['ks']->slug = 'ks';

			$this->locales['la'] = new GP_Locale();
			$this->locales['la']->english_name = 'Latin';
			$this->locales['la']->native_name = 'Latine';
			$this->locales['la']->lang_code_iso_639_1 = 'la';
			$this->locales['la']->lang_code_iso_639_2 = 'lat';
			$this->locales['la']->slug = 'la';

			$this->locales['lb'] = new GP_Locale();
			$this->locales['lb']->english_name = 'Luxembourgish';
			$this->locales['lb']->native_name = 'Lëtzebuergesch';
			$this->locales['lb']->lang_code_iso_639_1 = 'lb';
			$this->locales['lb']->country_code = 'lu';
			$this->locales['lb']->wp_locale = 'lb_LU';
			$this->locales['lb']->slug = 'lb';

			$this->locales['li'] = new GP_Locale();
			$this->locales['li']->english_name = 'Limburgish';
			$this->locales['li']->native_name = 'Limburgs';
			$this->locales['li']->lang_code_iso_639_1 = 'li';
			$this->locales['li']->lang_code_iso_639_2 = 'lim';
			$this->locales['li']->lang_code_iso_639_3 = 'lim';
			$this->locales['li']->country_code = 'nl';
			$this->locales['li']->wp_locale = 'li';
			$this->locales['li']->slug = 'li';

			$this->locales['lin'] = new GP_Locale();
			$this->locales['lin']->english_name = 'Lingala';
			$this->locales['lin']->native_name = 'Ngala';
			$this->locales['lin']->lang_code_iso_639_1 = 'ln';
			$this->locales['lin']->lang_code_iso_639_2 = 'lin';
			$this->locales['lin']->country_code = 'cd';
			$this->locales['lin']->wp_locale = 'lin';
			$this->locales['lin']->slug = 'lin';
			$this->locales['lin']->nplurals = 2;
			$this->locales['lin']->plural_expression = 'n>1';

			$this->locales['lo'] = new GP_Locale();
			$this->locales['lo']->english_name = 'Lao';
			$this->locales['lo']->native_name = 'ພາສາລາວ';
			$this->locales['lo']->lang_code_iso_639_1 = 'lo';
			$this->locales['lo']->lang_code_iso_639_2 = 'lao';
			$this->locales['lo']->country_code = 'LA';
			$this->locales['lo']->wp_locale = 'lo';
			$this->locales['lo']->slug = 'lo';
			$this->locales['lo']->nplurals = 1;
			$this->locales['lo']->plural_expression = '0';

			$this->locales['lt'] = new GP_Locale();
			$this->locales['lt']->english_name = 'Lithuanian';
			$this->locales['lt']->native_name = 'Lietuvių kalba';
			$this->locales['lt']->lang_code_iso_639_1 = 'lt';
			$this->locales['lt']->lang_code_iso_639_2 = 'lit';
			$this->locales['lt']->country_code = 'lt';
			$this->locales['lt']->wp_locale = 'lt_LT';
			$this->locales['lt']->slug = 'lt';
			$this->locales['lt']->nplurals = 3;
			$this->locales['lt']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['lv'] = new GP_Locale();
			$this->locales['lv']->english_name = 'Latvian';
			$this->locales['lv']->native_name = 'Latviešu valoda';
			$this->locales['lv']->lang_code_iso_639_1 = 'lv';
			$this->locales['lv']->lang_code_iso_639_2 = 'lav';
			$this->locales['lv']->country_code = 'lv';
			$this->locales['lv']->wp_locale = 'lv';
			$this->locales['lv']->slug = 'lv';
			$this->locales['lv']->nplurals = 3;
			$this->locales['lv']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2)';

			$this->locales['me'] = new GP_Locale();
			$this->locales['me']->english_name = 'Montenegrin';
			$this->locales['me']->native_name = 'Crnogorski jezik';
			$this->locales['me']->lang_code_iso_639_1 = 'me';
			$this->locales['me']->country_code = 'me';
			$this->locales['me']->wp_locale = 'me_ME';
			$this->locales['me']->slug = 'me';
			$this->locales['me']->nplurals = 3;
			$this->locales['me']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['mg'] = new GP_Locale();
			$this->locales['mg']->english_name = 'Malagasy';
			$this->locales['mg']->native_name = 'Malagasy';
			$this->locales['mg']->lang_code_iso_639_1 = 'mg';
			$this->locales['mg']->lang_code_iso_639_2 = 'mlg';
			$this->locales['mg']->country_code = 'mg';
			$this->locales['mg']->wp_locale = 'mg_MG';
			$this->locales['mg']->slug = 'mg';

			$this->locales['mhr'] = new GP_Locale();
			$this->locales['mhr']->english_name = 'Mari (Meadow)';
			$this->locales['mhr']->native_name = 'Олык марий';
			$this->locales['mhr']->lang_code_iso_639_3 = 'mhr';
			$this->locales['mhr']->country_code = 'ru';
			$this->locales['mhr']->slug = 'mhr';

			$this->locales['mk'] = new GP_Locale();
			$this->locales['mk']->english_name = 'Macedonian';
			$this->locales['mk']->native_name = 'Македонски јазик';
			$this->locales['mk']->lang_code_iso_639_1 = 'mk';
			$this->locales['mk']->lang_code_iso_639_2 = 'mkd';
			$this->locales['mk']->country_code = 'mk';
			$this->locales['mk']->wp_locale = 'mk_MK';
			$this->locales['mk']->slug = 'mk';
			$this->locales['mk']->nplurals = 2;
			$this->locales['mk']->plural_expression = 'n==1 || n%10==1 ? 0 : 1';

			$this->locales['ml'] = new GP_Locale();
			$this->locales['ml']->english_name = 'Malayalam';
			$this->locales['ml']->native_name = 'മലയാളം';
			$this->locales['ml']->lang_code_iso_639_1 = 'ml';
			$this->locales['ml']->lang_code_iso_639_2 = 'mal';
			$this->locales['ml']->country_code = 'in';
			$this->locales['ml']->wp_locale = 'ml_IN';
			$this->locales['ml']->slug = 'ml';

			$this->locales['mn'] = new GP_Locale();
			$this->locales['mn']->english_name = 'Mongolian';
			$this->locales['mn']->native_name = 'Монгол';
			$this->locales['mn']->lang_code_iso_639_1 = 'mn';
			$this->locales['mn']->lang_code_iso_639_2 = 'mon';
			$this->locales['mn']->country_code = 'mn';
			$this->locales['mn']->wp_locale = 'mn';
			$this->locales['mn']->slug = 'mn';

			$this->locales['mr'] = new GP_Locale();
			$this->locales['mr']->english_name = 'Marathi';
			$this->locales['mr']->native_name = 'मराठी';
			$this->locales['mr']->lang_code_iso_639_1 = 'mr';
			$this->locales['mr']->lang_code_iso_639_2 = 'mar';
			$this->locales['mr']->wp_locale = 'mr';
			$this->locales['mr']->slug = 'mr';

			$this->locales['mri'] = new GP_Locale();
			$this->locales['mri']->english_name = 'Maori';
			$this->locales['mri']->native_name = 'Te Reo Māori';
			$this->locales['mri']->lang_code_iso_639_1 = 'mi';
			$this->locales['mri']->lang_code_iso_639_3 = 'mri';
			$this->locales['mri']->country_code = 'nz';
			$this->locales['mri']->slug = 'mri';
			$this->locales['mri']->wp_locale = 'mri';
			$this->locales['mri']->nplurals = 2;
			$this->locales['mri']->plural_expression = '(n > 1)';

			$this->locales['mrj'] = new GP_Locale();
			$this->locales['mrj']->english_name = 'Mari (Hill)';
			$this->locales['mrj']->native_name = 'Кырык мары';
			$this->locales['mrj']->lang_code_iso_639_3 = 'mrj';
			$this->locales['mrj']->country_code = 'ru';
			$this->locales['mrj']->slug = 'mrj';

			$this->locales['ms'] = new GP_Locale();
			$this->locales['ms']->english_name = 'Malay';
			$this->locales['ms']->native_name = 'Bahasa Melayu';
			$this->locales['ms']->lang_code_iso_639_1 = 'ms';
			$this->locales['ms']->lang_code_iso_639_2 = 'msa';
			$this->locales['ms']->wp_locale = 'ms_MY';
			$this->locales['ms']->slug = 'ms';
			$this->locales['ms']->nplurals = 1;
			$this->locales['ms']->plural_expression = '0';

			$this->locales['mwl'] = new GP_Locale();
			$this->locales['mwl']->english_name = 'Mirandese';
			$this->locales['mwl']->native_name = 'Mirandés';
			$this->locales['mwl']->lang_code_iso_639_2 = 'mwl';
			$this->locales['mwl']->slug = 'mwl';

			$this->locales['mya'] = new GP_Locale();
			$this->locales['mya']->english_name = 'Myanmar (Burmese)';
			$this->locales['mya']->native_name = 'ဗမာစာ';
			$this->locales['mya']->lang_code_iso_639_1 = 'my';
			$this->locales['mya']->lang_code_iso_639_2 = 'mya';
			$this->locales['mya']->country_code = 'mm';
			$this->locales['mya']->wp_locale = 'my_MM';
			$this->locales['mya']->slug = 'mya';

			$this->locales['ne'] = new GP_Locale();
			$this->locales['ne']->english_name = 'Nepali';
			$this->locales['ne']->native_name = 'नेपाली';
			$this->locales['ne']->lang_code_iso_639_1 = 'ne';
			$this->locales['ne']->lang_code_iso_639_2 = 'nep';
			$this->locales['ne']->country_code = 'np';
			$this->locales['ne']->wp_locale = 'ne_NP';
			$this->locales['ne']->slug = 'ne';

			$this->locales['nb'] = new GP_Locale();
			$this->locales['nb']->english_name = 'Norwegian (Bokmål)';
			$this->locales['nb']->native_name = 'Norsk bokmål';
			$this->locales['nb']->lang_code_iso_639_1 = 'nb';
			$this->locales['nb']->lang_code_iso_639_2 = 'nob';
			$this->locales['nb']->country_code = 'no';
			$this->locales['nb']->wp_locale = 'nb_NO';
			$this->locales['nb']->slug = 'nb';

			$this->locales['nl'] = new GP_Locale();
			$this->locales['nl']->english_name = 'Dutch';
			$this->locales['nl']->native_name = 'Nederlands';
			$this->locales['nl']->lang_code_iso_639_1 = 'nl';
			$this->locales['nl']->lang_code_iso_639_2 = 'nld';
			$this->locales['nl']->country_code = 'nl';
			$this->locales['nl']->wp_locale = 'nl_NL';
			$this->locales['nl']->slug = 'nl';

			$this->locales['nl-be'] = new GP_Locale();
			$this->locales['nl-be']->english_name = 'Dutch (Belgium)';
			$this->locales['nl-be']->native_name = 'Nederlands (België)';
			$this->locales['nl-be']->lang_code_iso_639_1 = 'nl';
			$this->locales['nl-be']->lang_code_iso_639_2 = 'nld';
			$this->locales['nl-be']->country_code = 'be';
			$this->locales['nl-be']->wp_locale = 'nl_BE';
			$this->locales['nl-be']->slug = 'nl-be';

			$this->locales['nn'] = new GP_Locale();
			$this->locales['nn']->english_name = 'Norwegian (Nynorsk)';
			$this->locales['nn']->native_name = 'Norsk nynorsk';
			$this->locales['nn']->lang_code_iso_639_1 = 'nn';
			$this->locales['nn']->lang_code_iso_639_2 = 'nno';
			$this->locales['nn']->country_code = 'no';
			$this->locales['nn']->wp_locale = 'nn_NO';
			$this->locales['nn']->slug = 'nn';

			$this->locales['no'] = new GP_Locale();
			$this->locales['no']->english_name = 'Norwegian';
			$this->locales['no']->native_name = 'Norsk';
			$this->locales['no']->lang_code_iso_639_1 = 'no';
			$this->locales['no']->lang_code_iso_639_2 = 'nor';
			$this->locales['no']->country_code = 'no';
			$this->locales['no']->slug = 'no';

			$this->locales['oci'] = new GP_Locale();
			$this->locales['oci']->english_name = 'Occitan';
			$this->locales['oci']->native_name = 'Occitan';
			$this->locales['oci']->lang_code_iso_639_1 = 'oc';
			$this->locales['oci']->lang_code_iso_639_2 = 'oci';
			$this->locales['oci']->country_code = 'fr';
			$this->locales['oci']->wp_locale = 'oci';
			$this->locales['oci']->slug = 'oci';
			$this->locales['oci']->nplurals = 2;
			$this->locales['oci']->plural_expression = '(n > 1)';

			$this->locales['orm'] = new GP_Locale();
			$this->locales['orm']->english_name = 'Oromo';
			$this->locales['orm']->native_name = 'Afaan Oromo';
			$this->locales['orm']->lang_code_iso_639_1 = 'om';
			$this->locales['orm']->lang_code_iso_639_2 = 'orm';
			$this->locales['orm']->lang_code_iso_639_3 = 'orm';
			$this->locales['orm']->slug = 'orm';
			$this->locales['orm']->plural_expression = '(n > 1)';

			$this->locales['ory'] = new GP_Locale();
			$this->locales['ory']->english_name = 'Oriya';
			$this->locales['ory']->native_name = 'ଓଡ଼ିଆ';
			$this->locales['ory']->lang_code_iso_639_1 = 'or';
			$this->locales['ory']->lang_code_iso_639_2 = 'ory';
			$this->locales['ory']->country_code = 'in';
			$this->locales['ory']->wp_locale = 'ory';
			$this->locales['ory']->slug = 'ory';

			$this->locales['os'] = new GP_Locale();
			$this->locales['os']->english_name = 'Ossetic';
			$this->locales['os']->native_name = 'Ирон';
			$this->locales['os']->lang_code_iso_639_1 = 'os';
			$this->locales['os']->lang_code_iso_639_2 = 'oss';
			$this->locales['os']->wp_locale = 'os';
			$this->locales['os']->slug = 'os';

			$this->locales['pa'] = new GP_Locale();
			$this->locales['pa']->english_name = 'Punjabi';
			$this->locales['pa']->native_name = 'ਪੰਜਾਬੀ';
			$this->locales['pa']->lang_code_iso_639_1 = 'pa';
			$this->locales['pa']->lang_code_iso_639_2 = 'pan';
			$this->locales['pa']->country_code = 'in';
			$this->locales['pa']->wp_locale = 'pa_IN';
			$this->locales['pa']->slug = 'pa';

			$this->locales['pl'] = new GP_Locale();
			$this->locales['pl']->english_name = 'Polish';
			$this->locales['pl']->native_name = 'Polski';
			$this->locales['pl']->lang_code_iso_639_1 = 'pl';
			$this->locales['pl']->lang_code_iso_639_2 = 'pol';
			$this->locales['pl']->country_code = 'pl';
			$this->locales['pl']->wp_locale = 'pl_PL';
			$this->locales['pl']->slug = 'pl';
			$this->locales['pl']->nplurals = 3;
			$this->locales['pl']->plural_expression = '(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['pt'] = new GP_Locale();
			$this->locales['pt']->english_name = 'Portuguese (Portugal)';
			$this->locales['pt']->native_name = 'Português';
			$this->locales['pt']->lang_code_iso_639_1 = 'pt';
			$this->locales['pt']->country_code = 'pt';
			$this->locales['pt']->wp_locale = 'pt_PT';
			$this->locales['pt']->slug = 'pt';

			$this->locales['pt-br'] = new GP_Locale();
			$this->locales['pt-br']->english_name = 'Portuguese (Brazil)';
			$this->locales['pt-br']->native_name = 'Português do Brasil';
			$this->locales['pt-br']->lang_code_iso_639_1 = 'pt';
			$this->locales['pt-br']->lang_code_iso_639_2 = 'por';
			$this->locales['pt-br']->country_code = 'br';
			$this->locales['pt-br']->wp_locale = 'pt_BR';
			$this->locales['pt-br']->slug = 'pt-br';
			$this->locales['pt-br']->nplurals = 2;
			$this->locales['pt-br']->plural_expression = '(n > 1)';

			$this->locales['ps'] = new GP_Locale();
			$this->locales['ps']->english_name = 'Pashto';
			$this->locales['ps']->native_name = 'پښتو';
			$this->locales['ps']->lang_code_iso_639_1 = 'ps';
			$this->locales['ps']->lang_code_iso_639_2 = 'pus';
			$this->locales['ps']->country_code = 'af';
			$this->locales['ps']->wp_locale = 'ps';
			$this->locales['ps']->slug = 'ps';
			$this->locales['ps']->text_direction = 'rtl';

			$this->locales['rhg'] = new GP_Locale();
			$this->locales['rhg']->english_name = 'Rohingya';
			$this->locales['rhg']->native_name = 'Ruáinga';
			$this->locales['rhg']->lang_code_iso_639_3 = 'rhg';
			$this->locales['rhg']->country_code = 'mm';
			$this->locales['rhg']->wp_locale = 'rhg';
			$this->locales['rhg']->slug = 'rhg';
			$this->locales['rhg']->nplurals = 1;
			$this->locales['rhg']->plural_expression = '0';

			$this->locales['ro'] = new GP_Locale();
			$this->locales['ro']->english_name = 'Romanian';
			$this->locales['ro']->native_name = 'Română';
			$this->locales['ro']->lang_code_iso_639_1 = 'ro';
			$this->locales['ro']->lang_code_iso_639_2 = 'ron';
			$this->locales['ro']->country_code = 'ro';
			$this->locales['ro']->wp_locale = 'ro_RO';
			$this->locales['ro']->slug = 'ro';
			$this->locales['ro']->nplurals = 3;
			$this->locales['ro']->plural_expression = '(n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2)';

			$this->locales['roh'] = new GP_Locale();
			$this->locales['roh']->english_name = 'Romansh Vallader';
			$this->locales['roh']->native_name = 'Rumantsch Vallader';
			$this->locales['roh']->lang_code_iso_639_2 = 'rm';
			$this->locales['roh']->lang_code_iso_639_3 = 'roh';
			$this->locales['roh']->country_code = 'ch';
			$this->locales['roh']->wp_locale = 'roh';
			$this->locales['roh']->slug = 'roh';

			$this->locales['ru'] = new GP_Locale();
			$this->locales['ru']->english_name = 'Russian';
			$this->locales['ru']->native_name = 'Русский';
			$this->locales['ru']->lang_code_iso_639_1 = 'ru';
			$this->locales['ru']->lang_code_iso_639_2 = 'rus';
			$this->locales['ru']->country_code = 'ru';
			$this->locales['ru']->wp_locale = 'ru_RU';
			$this->locales['ru']->slug = 'ru';
			$this->locales['ru']->nplurals = 3;
			$this->locales['ru']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['rue'] = new GP_Locale();
			$this->locales['rue']->english_name = 'Rusyn';
			$this->locales['rue']->native_name = 'Русиньскый';
			$this->locales['rue']->lang_code_iso_639_3 = 'rue';
			$this->locales['rue']->wp_locale = 'rue';
			$this->locales['rue']->slug = 'rue';
			$this->locales['rue']->nplurals = 3;
			$this->locales['rue']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['rup'] = new GP_Locale();
			$this->locales['rup']->english_name = 'Aromanian';
			$this->locales['rup']->native_name = 'Armãneashce';
			$this->locales['rup']->lang_code_iso_639_2 = 'rup';
			$this->locales['rup']->lang_code_iso_639_3 = 'rup';
			$this->locales['rup']->country_code = 'mk';
			$this->locales['rup']->wp_locale = 'rup_MK';
			$this->locales['rup']->slug = 'rup';

			$this->locales['sah'] = new GP_Locale();
			$this->locales['sah']->english_name = 'Sakha';
			$this->locales['sah']->native_name = 'Сахалыы';
			$this->locales['sah']->lang_code_iso_639_2 = 'sah';
			$this->locales['sah']->lang_code_iso_639_3 = 'sah';
			$this->locales['sah']->country_code = 'ru';
			$this->locales['sah']->wp_locale = 'sah';
			$this->locales['sah']->slug = 'sah';

			$this->locales['sa-in'] = new GP_Locale();
			$this->locales['sa-in']->english_name = 'Sanskrit';
			$this->locales['sa-in']->native_name = 'भारतम्';
			$this->locales['sa-in']->lang_code_iso_639_1 = 'sa';
			$this->locales['sa-in']->lang_code_iso_639_2 = 'san';
			$this->locales['sa-in']->lang_code_iso_639_3 = 'san';
			$this->locales['sa-in']->country_code = 'in';
			$this->locales['sa-in']->wp_locale = 'sa_IN';
			$this->locales['sa-in']->slug = 'sa-in';

			$this->locales['si'] = new GP_Locale();
			$this->locales['si']->english_name = 'Sinhala';
			$this->locales['si']->native_name = 'සිංහල';
			$this->locales['si']->lang_code_iso_639_1 = 'si';
			$this->locales['si']->lang_code_iso_639_2 = 'sin';
			$this->locales['si']->country_code = 'lk';
			$this->locales['si']->wp_locale = 'si_LK';
			$this->locales['si']->slug = 'si';

			$this->locales['sk'] = new GP_Locale();
			$this->locales['sk']->english_name = 'Slovak';
			$this->locales['sk']->native_name = 'Slovenčina';
			$this->locales['sk']->lang_code_iso_639_1 = 'sk';
			$this->locales['sk']->lang_code_iso_639_2 = 'slk';
			$this->locales['sk']->country_code = 'sk';
			$this->locales['sk']->slug = 'sk';
			$this->locales['sk']->wp_locale = 'sk_SK';
			$this->locales['sk']->nplurals = 3;
			$this->locales['sk']->plural_expression = '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2';

			$this->locales['sl'] = new GP_Locale();
			$this->locales['sl']->english_name = 'Slovenian';
			$this->locales['sl']->native_name = 'Slovenščina';
			$this->locales['sl']->lang_code_iso_639_1 = 'sl';
			$this->locales['sl']->lang_code_iso_639_2 = 'slv';
			$this->locales['sl']->country_code = 'si';
			$this->locales['sl']->wp_locale = 'sl_SI';
			$this->locales['sl']->slug = 'sl';
			$this->locales['sl']->nplurals = 4;
			$this->locales['sl']->plural_expression = '(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3)';

			$this->locales['snd'] = new GP_Locale();
			$this->locales['snd']->english_name = 'Sindhi';
			$this->locales['snd']->native_name = 'سنڌي';
			$this->locales['snd']->lang_code_iso_639_1 = 'sd';
			$this->locales['snd']->lang_code_iso_639_2 = 'sd';
			$this->locales['snd']->lang_code_iso_639_3 = 'snd';
			$this->locales['snd']->country_code = 'pk';
			$this->locales['snd']->wp_locale = 'snd';
			$this->locales['snd']->slug = 'snd';
			$this->locales['snd']->text_direction = 'rtl';

			$this->locales['so'] = new GP_Locale();
			$this->locales['so']->english_name = 'Somali';
			$this->locales['so']->native_name = 'Afsoomaali';
			$this->locales['so']->lang_code_iso_639_1 = 'so';
			$this->locales['so']->lang_code_iso_639_2 = 'som';
			$this->locales['so']->lang_code_iso_639_3 = 'som';
			$this->locales['so']->country_code = 'so';
			$this->locales['so']->wp_locale = 'so_SO';
			$this->locales['so']->slug = 'so';

			$this->locales['sq'] = new GP_Locale();
			$this->locales['sq']->english_name = 'Albanian';
			$this->locales['sq']->native_name = 'Shqip';
			$this->locales['sq']->lang_code_iso_639_1 = 'sq';
			$this->locales['sq']->lang_code_iso_639_2 = 'sqi';
			$this->locales['sq']->wp_locale = 'sq';
			$this->locales['sq']->country_code = 'al';
			$this->locales['sq']->slug = 'sq';

			$this->locales['sr'] = new GP_Locale();
			$this->locales['sr']->english_name = 'Serbian';
			$this->locales['sr']->native_name = 'Српски језик';
			$this->locales['sr']->lang_code_iso_639_1 = 'sr';
			$this->locales['sr']->lang_code_iso_639_2 = 'srp';
			$this->locales['sr']->country_code = 'rs';
			$this->locales['sr']->wp_locale = 'sr_RS';
			$this->locales['sr']->slug = 'sr';
			$this->locales['sr']->nplurals = 3;
			$this->locales['sr']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['srd'] = new GP_Locale();
			$this->locales['srd']->english_name = 'Sardinian';
			$this->locales['srd']->native_name = 'Sardu';
			$this->locales['srd']->lang_code_iso_639_1 = 'sc';
			$this->locales['srd']->lang_code_iso_639_2 = 'srd';
			$this->locales['srd']->country_code = 'it';
			$this->locales['srd']->wp_locale = 'srd';
			$this->locales['srd']->slug = 'srd';

			$this->locales['su'] = new GP_Locale();
			$this->locales['su']->english_name = 'Sundanese';
			$this->locales['su']->native_name = 'Basa Sunda';
			$this->locales['su']->lang_code_iso_639_1 = 'su';
			$this->locales['su']->lang_code_iso_639_2 = 'sun';
			$this->locales['su']->country_code = 'id';
			$this->locales['su']->wp_locale = 'su_ID';
			$this->locales['su']->slug = 'su';
			$this->locales['su']->nplurals = 1;
			$this->locales['su']->plural_expression = '0';

			$this->locales['sv'] = new GP_Locale();
			$this->locales['sv']->english_name = 'Swedish';
			$this->locales['sv']->native_name = 'Svenska';
			$this->locales['sv']->lang_code_iso_639_1 = 'sv';
			$this->locales['sv']->lang_code_iso_639_2 = 'swe';
			$this->locales['sv']->country_code = 'se';
			$this->locales['sv']->wp_locale = 'sv_SE';
			$this->locales['sv']->slug = 'sv';

			$this->locales['sw'] = new GP_Locale();
			$this->locales['sw']->english_name = 'Swahili';
			$this->locales['sw']->native_name = 'Kiswahili';
			$this->locales['sw']->lang_code_iso_639_1 = 'sw';
			$this->locales['sw']->lang_code_iso_639_2 = 'swa';
			$this->locales['sw']->wp_locale = 'sw';
			$this->locales['sw']->slug = 'sw';

			$this->locales['szl'] = new GP_Locale();
			$this->locales['szl']->english_name = 'Silesian';
			$this->locales['szl']->native_name = 'Ślōnskŏ gŏdka';
			$this->locales['szl']->lang_code_iso_639_3 = 'szl';
			$this->locales['szl']->country_code = 'pl';
			$this->locales['szl']->wp_locale = 'szl';
			$this->locales['szl']->slug = 'szl';
			$this->locales['szl']->nplurals = 3;
			$this->locales['szl']->plural_expression = '(n==1 ? 0 : n%10>=2 && n%10<=4 && n%100==20 ? 1 : 2)';

			$this->locales['ta'] = new GP_Locale();
			$this->locales['ta']->english_name = 'Tamil';
			$this->locales['ta']->native_name = 'தமிழ்';
			$this->locales['ta']->lang_code_iso_639_1 = 'ta';
			$this->locales['ta']->lang_code_iso_639_2 = 'tam';
			$this->locales['ta']->country_code = 'in';
			$this->locales['ta']->wp_locale = 'ta_IN';
			$this->locales['ta']->slug = 'ta';

			$this->locales['ta-lk'] = new GP_Locale();
			$this->locales['ta-lk']->english_name = 'Tamil (Sri Lanka)';
			$this->locales['ta-lk']->native_name = 'தமிழ்';
			$this->locales['ta-lk']->lang_code_iso_639_1 = 'ta';
			$this->locales['ta-lk']->lang_code_iso_639_2 = 'tam';
			$this->locales['ta-lk']->country_code = 'lk';
			$this->locales['ta-lk']->wp_locale = 'ta_LK';
			$this->locales['ta-lk']->slug = 'ta-lk';

			$this->locales['tah'] = new GP_Locale();
			$this->locales['tah']->english_name = 'Tahitian';
			$this->locales['tah']->native_name = 'Reo Tahiti';
			$this->locales['tah']->lang_code_iso_639_1 = 'ty';
			$this->locales['tah']->lang_code_iso_639_2 = 'tah';
			$this->locales['tah']->lang_code_iso_639_3 = 'tah';
			$this->locales['tah']->country_code = 'fr';
			$this->locales['tah']->wp_locale = 'tah';
			$this->locales['tah']->slug = 'tah';
			$this->locales['tah']->nplurals = 2;
			$this->locales['tah']->plural_expression = '(n > 1)';

			$this->locales['te'] = new GP_Locale();
			$this->locales['te']->english_name = 'Telugu';
			$this->locales['te']->native_name = 'తెలుగు';
			$this->locales['te']->lang_code_iso_639_1 = 'te';
			$this->locales['te']->lang_code_iso_639_2 = 'tel';
			$this->locales['te']->wp_locale = 'te';
			$this->locales['te']->slug = 'te';

			$this->locales['tg'] = new GP_Locale();
			$this->locales['tg']->english_name = 'Tajik';
			$this->locales['tg']->native_name = 'Тоҷикӣ';
			$this->locales['tg']->lang_code_iso_639_1 = 'tg';
			$this->locales['tg']->lang_code_iso_639_2 = 'tgk';
			$this->locales['tah']->country_code = 'tj';
			$this->locales['tg']->wp_locale = 'tg';
			$this->locales['tg']->slug = 'tg';

			$this->locales['th'] = new GP_Locale();
			$this->locales['th']->english_name = 'Thai';
			$this->locales['th']->native_name = 'ไทย';
			$this->locales['th']->lang_code_iso_639_1 = 'th';
			$this->locales['th']->lang_code_iso_639_2 = 'tha';
			$this->locales['th']->wp_locale = 'th';
			$this->locales['th']->slug = 'th';
			$this->locales['th']->nplurals = 1;
			$this->locales['th']->plural_expression = '0';

			$this->locales['tir'] = new GP_Locale();
			$this->locales['tir']->english_name = 'Tigrinya';
			$this->locales['tir']->native_name = 'ትግርኛ';
			$this->locales['tir']->lang_code_iso_639_1 = 'ti';
			$this->locales['tir']->lang_code_iso_639_2 = 'tir';
			$this->locales['tir']->country_code = 'er';
			$this->locales['tir']->wp_locale = 'tir';
			$this->locales['tir']->slug = 'tir';
			$this->locales['tir']->nplurals = 1;
			$this->locales['tir']->plural_expression = '0';

			$this->locales['tlh'] = new GP_Locale();
			$this->locales['tlh']->english_name = 'Klingon';
			$this->locales['tlh']->native_name = 'TlhIngan';
			$this->locales['tlh']->lang_code_iso_639_2 = 'tlh';
			$this->locales['tlh']->slug = 'tlh';
			$this->locales['tlh']->nplurals = 1;
			$this->locales['tlh']->plural_expression = '0';

			$this->locales['tl'] = new GP_Locale();
			$this->locales['tl']->english_name = 'Tagalog';
			$this->locales['tl']->native_name = 'Tagalog';
			$this->locales['tl']->lang_code_iso_639_1 = 'tl';
			$this->locales['tl']->lang_code_iso_639_2 = 'tgl';
			$this->locales['tl']->country_code = 'ph';
			$this->locales['tl']->wp_locale = 'tl';
			$this->locales['tl']->slug = 'tl';

			$this->locales['tr'] = new GP_Locale();
			$this->locales['tr']->english_name = 'Turkish';
			$this->locales['tr']->native_name = 'Türkçe';
			$this->locales['tr']->lang_code_iso_639_1 = 'tr';
			$this->locales['tr']->lang_code_iso_639_2 = 'tur';
			$this->locales['tr']->country_code = 'tr';
			$this->locales['tr']->wp_locale = 'tr_TR';
			$this->locales['tr']->slug = 'tr';
			$this->locales['tr']->nplurals = 2;
			$this->locales['tr']->plural_expression = '(n > 1)';

			$this->locales['tt'] = new GP_Locale();
			$this->locales['tt']->english_name = 'Tatar';
			$this->locales['tt']->native_name = 'Татар теле';
			$this->locales['tt']->lang_code_iso_639_1 = 'tt';
			$this->locales['tt']->lang_code_iso_639_2 = 'tat';
			$this->locales['tt']->country_code = 'ru';
			$this->locales['tt']->wp_locale = 'tt_RU';
			$this->locales['tt']->slug = 'tt';
			$this->locales['tt']->nplurals = 1;
			$this->locales['tt']->plural_expression = '0';

			$this->locales['tuk'] = new GP_Locale();
			$this->locales['tuk']->english_name = 'Turkmen';
			$this->locales['tuk']->native_name = 'Türkmençe';
			$this->locales['tuk']->lang_code_iso_639_1 = 'tk';
			$this->locales['tuk']->lang_code_iso_639_2 = 'tuk';
			$this->locales['tuk']->country_code = 'tm';
			$this->locales['tuk']->wp_locale = 'tuk';
			$this->locales['tuk']->slug = 'tuk';
			$this->locales['tuk']->nplurals = 2;
			$this->locales['tuk']->plural_expression = '(n > 1)';

			$this->locales['twd'] = new GP_Locale();
			$this->locales['twd']->english_name = 'Tweants';
			$this->locales['twd']->native_name = 'Twents';
			$this->locales['twd']->lang_code_iso_639_3 = 'twd';
			$this->locales['twd']->country_code = 'nl';
			$this->locales['twd']->wp_locale = 'twd';
			$this->locales['twd']->slug = 'twd';

			$this->locales['tzm'] = new GP_Locale();
			$this->locales['tzm']->english_name = 'Tamazight (Central Atlas)';
			$this->locales['tzm']->native_name = 'ⵜⴰⵎⴰⵣⵉⵖⵜ';
			$this->locales['tzm']->lang_code_iso_639_2 = 'tzm';
			$this->locales['tzm']->country_code = 'ma';
			$this->locales['tzm']->wp_locale = 'tzm';
			$this->locales['tzm']->slug = 'tzm';
			$this->locales['tzm']->nplurals = 2;
			$this->locales['tzm']->plural_expression = '(n > 1)';

			$this->locales['udm'] = new GP_Locale();
			$this->locales['udm']->english_name = 'Udmurt';
			$this->locales['udm']->native_name = 'Удмурт кыл';
			$this->locales['udm']->lang_code_iso_639_2 = 'udm';
			$this->locales['udm']->slug = 'udm';

			$this->locales['ug'] = new GP_Locale();
			$this->locales['ug']->english_name = 'Uighur';
			$this->locales['ug']->native_name = 'Uyƣurqə';
			$this->locales['ug']->lang_code_iso_639_1 = 'ug';
			$this->locales['ug']->lang_code_iso_639_2 = 'uig';
			$this->locales['ug']->country_code = 'cn';
			$this->locales['ug']->wp_locale = 'ug_CN';
			$this->locales['ug']->slug = 'ug';
			$this->locales['ug']->text_direction = 'rtl';

			$this->locales['uk'] = new GP_Locale();
			$this->locales['uk']->english_name = 'Ukrainian';
			$this->locales['uk']->native_name = 'Українська';
			$this->locales['uk']->lang_code_iso_639_1 = 'uk';
			$this->locales['uk']->lang_code_iso_639_2 = 'ukr';
			$this->locales['uk']->country_code = 'ua';
			$this->locales['uk']->wp_locale = 'uk';
			$this->locales['uk']->slug = 'uk';
			$this->locales['uk']->nplurals = 3;
			$this->locales['uk']->plural_expression = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

			$this->locales['ur'] = new GP_Locale();
			$this->locales['ur']->english_name = 'Urdu';
			$this->locales['ur']->native_name = 'اردو';
			$this->locales['ur']->lang_code_iso_639_1 = 'ur';
			$this->locales['ur']->lang_code_iso_639_2 = 'urd';
			$this->locales['ur']->country_code = 'pk';
			$this->locales['ur']->wp_locale = 'ur';
			$this->locales['ur']->slug = 'ur';
			$this->locales['ur']->text_direction = 'rtl';

			$this->locales['uz'] = new GP_Locale();
			$this->locales['uz']->english_name = 'Uzbek';
			$this->locales['uz']->native_name = 'O‘zbekcha';
			$this->locales['uz']->lang_code_iso_639_1 = 'uz';
			$this->locales['uz']->lang_code_iso_639_2 = 'uzb';
			$this->locales['uz']->country_code = 'uz';
			$this->locales['uz']->wp_locale = 'uz_UZ';
			$this->locales['uz']->slug = 'uz';
			$this->locales['uz']->nplurals = 1;
			$this->locales['uz']->plural_expression = '0';

			$this->locales['vec'] = new GP_Locale();
			$this->locales['vec']->english_name = 'Venetian';
			$this->locales['vec']->native_name = 'Vèneta';
			$this->locales['vec']->lang_code_iso_639_2 = 'roa';
			$this->locales['vec']->lang_code_iso_639_3 = 'vec';
			$this->locales['vec']->country_code = 'it';
			$this->locales['vec']->slug = 'vec';

			$this->locales['vi'] = new GP_Locale();
			$this->locales['vi']->english_name = 'Vietnamese';
			$this->locales['vi']->native_name = 'Tiếng Việt';
			$this->locales['vi']->lang_code_iso_639_1 = 'vi';
			$this->locales['vi']->lang_code_iso_639_2 = 'vie';
			$this->locales['vi']->country_code = 'vn';
			$this->locales['vi']->wp_locale = 'vi';
			$this->locales['vi']->slug = 'vi';
			$this->locales['vi']->nplurals = 1;
			$this->locales['vi']->plural_expression = '0';

			$this->locales['wa'] = new GP_Locale();
			$this->locales['wa']->english_name = 'Walloon';
			$this->locales['wa']->native_name = 'Walon';
			$this->locales['wa']->lang_code_iso_639_1 = 'wa';
			$this->locales['wa']->lang_code_iso_639_2 = 'wln';
			$this->locales['wa']->country_code = 'be';
			$this->locales['wa']->wp_locale = 'wa';
			$this->locales['wa']->slug = 'wa';

			$this->locales['xmf'] = new GP_Locale();
			$this->locales['xmf']->english_name = 'Mingrelian';
			$this->locales['xmf']->native_name = 'მარგალური ნინა';
			$this->locales['xmf']->lang_code_iso_639_3 = 'xmf';
			$this->locales['xmf']->country_code = 'ge';
			$this->locales['xmf']->wp_locale = 'xmf';
			$this->locales['xmf']->slug = 'xmf';

			$this->locales['yi'] = new GP_Locale();
			$this->locales['yi']->english_name = 'Yiddish';
			$this->locales['yi']->native_name = 'ייִדיש';
			$this->locales['yi']->lang_code_iso_639_1 = 'yi';
			$this->locales['yi']->lang_code_iso_639_2 = 'yid';
			$this->locales['yi']->slug = 'yi';
			$this->locales['yi']->text_direction = 'rtl';

			$this->locales['yor'] = new GP_Locale();
			$this->locales['yor']->english_name = 'Yoruba';
			$this->locales['yor']->native_name = 'Yorùbá';
			$this->locales['yor']->lang_code_iso_639_1 = 'yo';
			$this->locales['yor']->lang_code_iso_639_2 = 'yor';
			$this->locales['yor']->lang_code_iso_639_3 = 'yor';
			$this->locales['yor']->country_code = 'ng';
			$this->locales['yor']->wp_locale = 'yor';
			$this->locales['yor']->slug = 'yor';

			$this->locales['zh'] = new GP_Locale();
			$this->locales['zh']->english_name = 'Chinese';
			$this->locales['zh']->native_name = '中文';
			$this->locales['zh']->lang_code_iso_639_1 = 'zh';
			$this->locales['zh']->lang_code_iso_639_2 = 'zho';
			$this->locales['zh']->slug = 'zh';
			$this->locales['zh']->nplurals = 1;
			$this->locales['zh']->plural_expression = '0';

			$this->locales['zh-cn'] = new GP_Locale();
			$this->locales['zh-cn']->english_name = 'Chinese (China)';
			$this->locales['zh-cn']->native_name = '简体中文';
			$this->locales['zh-cn']->lang_code_iso_639_1 = 'zh';
			$this->locales['zh-cn']->lang_code_iso_639_2 = 'zho';
			$this->locales['zh-cn']->country_code = 'cn';
			$this->locales['zh-cn']->wp_locale = 'zh_CN';
			$this->locales['zh-cn']->slug = 'zh-cn';
			$this->locales['zh-cn']->nplurals = 1;
			$this->locales['zh-cn']->plural_expression = '0';

			$this->locales['zh-hk'] = new GP_Locale();
			$this->locales['zh-hk']->english_name = 'Chinese (Hong Kong)';
			$this->locales['zh-hk']->native_name = '香港中文版	';
			$this->locales['zh-hk']->lang_code_iso_639_1 = 'zh';
			$this->locales['zh-hk']->lang_code_iso_639_2 = 'zho';
			$this->locales['zh-hk']->country_code = 'hk';
			$this->locales['zh-hk']->wp_locale = 'zh_HK';
			$this->locales['zh-hk']->slug = 'zh-hk';
			$this->locales['zh-hk']->nplurals = 1;
			$this->locales['zh-hk']->plural_expression = '0';

			$this->locales['zh-sg'] = new GP_Locale();
			$this->locales['zh-sg']->english_name = 'Chinese (Singapore)';
			$this->locales['zh-sg']->native_name = '中文';
			$this->locales['zh-sg']->lang_code_iso_639_1 = 'zh';
			$this->locales['zh-sg']->lang_code_iso_639_2 = 'zho';
			$this->locales['zh-sg']->country_code = 'sg';
			$this->locales['zh-sg']->slug = 'zh-sg';
			$this->locales['zh-sg']->nplurals = 1;
			$this->locales['zh-sg']->plural_expression = '0';

			$this->locales['zh-tw'] = new GP_Locale();
			$this->locales['zh-tw']->english_name = 'Chinese (Taiwan)';
			$this->locales['zh-tw']->native_name = '繁體中文';
			$this->locales['zh-tw']->lang_code_iso_639_1 = 'zh';
			$this->locales['zh-tw']->lang_code_iso_639_2 = 'zho';
			$this->locales['zh-tw']->country_code = 'tw';
			$this->locales['zh-tw']->slug = 'zh-tw';
			$this->locales['zh-tw']->wp_locale = 'zh_TW';
			$this->locales['zh-tw']->nplurals = 1;
			$this->locales['zh-tw']->plural_expression = '0';
		}
	}

endif;
