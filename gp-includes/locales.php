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

		$es = new GP_Locale();
		$es->english_name = 'Spanish';
		$es->native_name = 'Español';
		$es->lang_code_iso_639_1 = 'es';
		$es->country_code = 'es';
		$es->wp_locale = 'es_ES';
		$es->slug = 'es';
	
	
		$bg = new GP_Locale();
		$bg->english_name = 'Bulgarian';
		$bg->native_name = 'Български';
		$bg->lang_code_iso_639_1 = 'bg';
		$bg->country_code = 'bg';
		$bg->wp_locale = 'bg_BG';
		$bg->slug = 'bg';
	
		foreach(get_defined_vars() as $value) {
			if ( isset( $value->english_name ) )
				$this->locales[$value->slug] = $value;
		}
	
	}
	
	function exists( $slug ) {
		return isset( $this->locales[$slug] );
	}
	
	function by_slug( $slug ) {
		return $this->locales[$slug];
	}
}
?>