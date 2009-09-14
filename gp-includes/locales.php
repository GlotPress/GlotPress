<?php
class GP_Locale {
	var $english_name;
	var $native_name;
	var $text_direction = 'ltr';
	var $lang_code_iso_639_1;
	var $country_code;
	var $wp_locale;
	var $slug;
	// TODO: days, months, decimals, quotes
	
	function combined_name() {
		/* translators: combined name for locales: 1: name in English, 2: native name */
		return sprintf( _x( '%1$s/%2$s', 'locales' ), $this->english_name, $this->native_name );
	}
}

class GP_Locales {
	
	var $locales = array();
	
	function GP_Locales() {
		$en = new GP_Locale();
		$en->english_name = 'English';
		$en->native_name = 'English';
		$en->lang_code_iso_639_1 = 'en';
		$en->country_code = 'us';
		$en->wp_locale = 'en_US';
		$en->slug = 'en';

		$bg = new GP_Locale();
		$bg->english_name = 'Bulgarian';
		$bg->native_name = 'Български';
		$bg->lang_code_iso_639_1 = 'bg';
		$bg->country_code = 'bg';
		$bg->wp_locale = 'bg_BG';
		$bg->slug = 'bg';

		$es = new GP_Locale();
		$es->english_name = 'Spanish';
		$es->native_name = 'Español';
		$es->lang_code_iso_639_1 = 'es';
		$es->country_code = 'es';
		$es->wp_locale = 'es_ES';
		$es->slug = 'es';

		$de = new GP_Locale();
		$de->english_name = 'German';
		$de->native_name = 'Deutsch';
		$de->lang_code_iso_639_1 = 'de';
		$de->country_code = 'de';
		$de->wp_locale = 'de_DE';
		$de->slug = 'de';
		
		$fr = new GP_Locale();
		$fr->english_name = 'French';
		$fr->native_name = 'Français';
		$fr->lang_code_iso_639_1 = 'fr';
		$fr->country_code = 'fr';
		$fr->wp_locale = 'fr_FR';
		$fr->slug = 'fr';

		$zh = new GP_Locale();
		$zh->english_name = 'Chinese';
		$zh->native_name = '汉语/漢語';
		$zh->lang_code_iso_639_1 = 'zh';
		$zh->country_code = 'cn';
		$zh->wp_locale = 'zh_CN';
		$zh->slug = 'zh';
		
		$pt = new GP_Locale();
		$pt->english_name = 'Portuguese';
		$pt->native_name = 'Português';
		$pt->lang_code_iso_639_1 = 'pt';
		$pt->country_code = 'pt';
		$pt->wp_locale = 'pt_PT';
		$pt->slug = 'pt';

		$he = new GP_Locale();
		$he->english_name = 'Hebrew';
		$he->native_name = 'עִבְרִית';
		$he->lang_code_iso_639_1 = 'he';
		$he->country_code = 'il';
		$he->wp_locale = 'he_IL';
		$he->slug = 'he';
		$he->rtl = true;

		$ja = new GP_Locale();
		$ja->english_name = 'Japanese';
		$ja->native_name = '日本語';
		$ja->lang_code_iso_639_1 = 'ja';
		$ja->country_code = 'jp';
		$ja->wp_locale = 'ja';
		$ja->slug = 'ja';
		$ja->nplurals = 1;
		$ja->plural_expression = '0';

		
		foreach( get_defined_vars() as $value ) {
			if ( isset( $value->english_name ) ) {
				if ( !isset( $value->nplurals ) ) {
					$value->nplurals = 2;
					$value->plural_expression = 'n != 1';
				}
				$this->locales[$value->slug] = $value;
			}
		}
	
	}
	
	function &instance() {
		if ( !isset( $GLOBALS['gp_locales'] ) )
			$GLOBALS['gp_locales'] = &new GP_Locales();
		return $GLOBALS['gp_locales'];
	}
	
	function locales() {
		$instance = &GP_Locales::instance();
		return $instance->locales;
	}
	
	function exists( $slug ) {
		$instance = &GP_Locales::instance();
		return isset( $instance->locales[$slug] );
	}
	
	function by_slug( $slug ) {
		$instance = &GP_Locales::instance();
		return $instance->locales[$slug];
	}
}
?>