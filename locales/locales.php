<?php
if ( ! class_exists( 'GP_Locale' ) ) :

class GP_Locale {
	public $english_name;
	public $native_name;
	public $text_direction = 'ltr';
	public $lang_code_iso_639_1 = null;
	public $lang_code_iso_639_2 = null;
	public $lang_code_iso_639_3 = null;
	public $country_code;
	public $wp_locale;
	public $slug;
	public $nplurals = 2;
	public $plural_expression = 'n != 1';
	public $cldr_code;
	public $cldr_nplurals = null;
	public $cldr_plural_expressions;
	public $google_code = null;
	public $preferred_sans_serif_font_family = null;
	public $facebook_locale = null;
	public $variant_root = null;
	public $variants = null;

	// TODO: days, months, decimals, quotes

	private $_index_for_number;

	public function __construct( $args = array() ) {
		$this->cldr_plural_expressions = array(
			'zero' => '',
			'one' => '',
			'two' => '',
			'few' => '',
			'many' => '',
			'other' => '',
		);

		foreach( $args as $key => $value ) {
			$this->$key = $value;
		}
	}

	public static function __set_state( $state ) {
		return new GP_Locale( $state );
	}

	/**
	 * Make deprecated properties checkable for backwards compatibility.
	 *
	 * @param string $name Property to check if set.
	 * @return bool Whether the property is set.
	 */
	public function __isset( $name ) {
		if ( 'rtl' == $name ) {
			return isset( $this->text_direction );
		}
	}

	/**
	 * Make deprecated properties readable for backwards compatibility.
	 *
	 * @param string $name Property to get.
	 * @return mixed Property.
	 */
	public function __get( $name ) {
		if ( 'rtl' == $name ) {
			return ( 'rtl' === $this->text_direction );
		}
	}

	public function combined_name() {
		/* translators: combined name for locales: 1: name in English, 2: native name */
		return sprintf( _x( '%1$s/%2$s', 'locales' ), $this->english_name, $this->native_name );
	}

	public function numbers_for_index( $index, $how_many = 3, $test_up_to = 1000, $type = 'gettext' ) {
		$numbers = array();

		if ( 'cldr' === $type ) {
			$i = 0;

			foreach ( $this->cldr_plural_expressions as $key => $value ) {
				if ( '' !== $value ) {
					if ( $i === $index ) {
						$example = substr( $value, strpos( $value, '@' ) );

						$integer_start = strpos( $example, '@integer' );
						$decimal_start = strpos( $example, '@decimal' );
						
						if( false !== $integer_start ) {
							if ( false !== $decimal_start ) {
								if ( $decimal_start > $integer_start ) {
									$example = substr( $example, 0, $decimal_start );
								} else {
									$exmaple = substr( $example, $integer_start );
								}
							}
							$example = str_replace( '@integer', '', $example );
							$example = str_replace( '@decimal', '', $example );
							
							$example = str_replace( ', …', '', $example );
						
							$temp_numbers = explode( ',', $example );
							
							foreach ( $temp_numbers as $num ) {
								$range = $this->expand_range( $num );
								
								foreach ( $range as $value ) {
									$numbers[] = (int)$value;
								}
							}
						} elseif ( false !== $decimal_start ) {
							if ( false !== $integer_start ) {
								if ( $decimal_start > $integer_start ) {
									$example = substr( $example, 0, $integer_start );
								} else {
									$exmaple = substr( $example, $decimal_start );
								}
							}
							
							$example = str_replace( '@integer', '', $example );
							$example = str_replace( '@decimal', '', $example );
							
							$example = str_replace( ', …', '', $example );
						
							$temp_numbers = explode( ',', $example );
							
							foreach ( $temp_numbers as $num ) {
								$range = $this->expand_range( $num );
								
								foreach ( $range as $value ) {
									$numbers[] = (int)$value;
								}
							}
						} else {
							$numbers[] = 0;
						}
					}

					$i++;
				}
			}
		} else {
			for( $number = 0; $number < $test_up_to; ++$number ) {
				if ( $this->index_for_number( $number ) == $index ) {
					$numbers[] = $number;

					if ( count( $numbers ) >= $how_many ) {
						break;
					}
				}
			}
		}
		
		return $numbers;
	}

	private function expand_range( $range ) {
		if ( false !== strpos( $range, '~' ) ) {
			$range = str_replace( '~', ',', $range );

			list( $start, $end ) = explode( ',', $range );

			$new_range = array();
			
			for ( $i = $start; $i <= $end; $i++ ) {
				$new_range[] = $i;
			}
			
			return $new_range;
		}
		
		return array( $range );
	}
	
	public function index_for_number( $number ) {
		if ( ! isset( $this->_index_for_number ) ) {
			$gettext = new Gettext_Translations;
			$expression = $gettext->parenthesize_plural_exression( $this->plural_expression );
			$this->_index_for_number = $gettext->make_plural_form_function( $this->nplurals, $expression );
		}

		$f = $this->_index_for_number;

		return $f( $number );
	}

	public function get_nplurals( $type = 'gettext' ) {
		if ( 'cldr' === $type ) {
			return $this->cldr_nplurals;
		} else {
			return $this->nplurals;
		}
	}
	
	public function get_plural_example( $type, $index ) {
		if ( 'cldr' === $type ) {
			$i = 0;

			foreach ( $this->cldr_plural_expressions as $key => $value ) {
				if ( '' !== $value ) {
					if ( $i === $index ) {
						$example = substr( $value, strpos( $value, '@' ) );
						$example = str_replace( '@integer', '', $example );
						$example = str_replace( '@decimal', ' or ', $example );
						$example = trim( $example );
						
						if( gp_startswith( $example, 'or ' ) ) {
							$example = substr( $example, 3 );
						}
						
						$example = str_replace( ', …', '', $example );
						
						return $example;
					}

					$i++;
				}
			}
		} else {
			return implode( ', ', $this->numbers_for_index( $index ) );
		}
	}
}

endif;

if ( ! class_exists( 'GP_Locales' ) ) :

class GP_Locales {

	public $locales = array();

	public function __construct() {
		$aa = new GP_Locale();
		$aa->english_name                         = 'Afar';
		$aa->native_name                          = 'Afaraf';
		$aa->text_direction                       = 'ltr';
		$aa->lang_code_iso_639_1                  = 'aa';
		$aa->lang_code_iso_639_2                  = 'aar';
		$aa->slug                                 = 'aa';
		$aa->nplurals                             = '2';
		$aa->plural_expression                    = 'n != 1';

		$ae = new GP_Locale();
		$ae->english_name                         = 'Avestan';
		$ae->native_name                          = 'Avesta';
		$ae->text_direction                       = 'ltr';
		$ae->lang_code_iso_639_1                  = 'ae';
		$ae->lang_code_iso_639_2                  = 'ave';
		$ae->slug                                 = 'ae';
		$ae->nplurals                             = '2';
		$ae->plural_expression                    = 'n != 1';

		$af = new GP_Locale();
		$af->english_name                         = 'Afrikaans';
		$af->native_name                          = 'Afrikaans';
		$af->text_direction                       = 'ltr';
		$af->lang_code_iso_639_1                  = 'af';
		$af->lang_code_iso_639_2                  = 'afr';
		$af->country_code                         = 'za';
		$af->wp_locale                            = 'af';
		$af->slug                                 = 'af';
		$af->nplurals                             = '2';
		$af->plural_expression                    = 'n != 1';
		$af->cldr_code                            = 'af';
		$af->cldr_nplurals                        = '2';
		$af->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$af->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$af->google_code                          = 'af';
		$af->facebook_locale                      = 'af_ZA';

		$ak = new GP_Locale();
		$ak->english_name                         = 'Akan';
		$ak->native_name                          = 'Akan';
		$ak->text_direction                       = 'ltr';
		$ak->lang_code_iso_639_1                  = 'ak';
		$ak->lang_code_iso_639_2                  = 'aka';
		$ak->wp_locale                            = 'ak';
		$ak->slug                                 = 'ak';
		$ak->nplurals                             = '2';
		$ak->plural_expression                    = 'n != 1';
		$ak->cldr_code                            = 'ak';
		$ak->cldr_nplurals                        = '2';
		$ak->cldr_plural_expressions['one']       = 'n = 0..1 @integer 0, 1 @decimal 0.0, 1.0, 0.00, 1.00, 0.000, 1.000, 0.0000, 1.0000';
		$ak->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ak->facebook_locale                      = 'ak_GH';

		$am = new GP_Locale();
		$am->english_name                         = 'Amharic';
		$am->native_name                          = 'አማርኛ';
		$am->text_direction                       = 'ltr';
		$am->lang_code_iso_639_1                  = 'am';
		$am->lang_code_iso_639_2                  = 'amh';
		$am->country_code                         = 'et';
		$am->wp_locale                            = 'am';
		$am->slug                                 = 'am';
		$am->nplurals                             = '2';
		$am->plural_expression                    = 'n != 1';
		$am->cldr_code                            = 'am';
		$am->cldr_nplurals                        = '2';
		$am->cldr_plural_expressions['one']       = 'i = 0 or n = 1 @integer 0, 1 @decimal 0.0~1.0, 0.00~0.04';
		$am->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 1.1~2.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$am->facebook_locale                      = 'am_ET';

		$an = new GP_Locale();
		$an->english_name                         = 'Aragonese';
		$an->native_name                          = 'Aragonés';
		$an->text_direction                       = 'ltr';
		$an->lang_code_iso_639_1                  = 'an';
		$an->lang_code_iso_639_2                  = 'arg';
		$an->country_code                         = 'es';
		$an->slug                                 = 'an';
		$an->nplurals                             = '2';
		$an->plural_expression                    = 'n != 1';

		$ar = new GP_Locale();
		$ar->english_name                         = 'Arabic';
		$ar->native_name                          = 'العربية';
		$ar->text_direction                       = 'rtl';
		$ar->lang_code_iso_639_1                  = 'ar';
		$ar->lang_code_iso_639_2                  = 'ara';
		$ar->wp_locale                            = 'ar';
		$ar->slug                                 = 'ar';
		$ar->nplurals                             = '6';
		$ar->plural_expression                    = 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5';
		$ar->cldr_code                            = 'ar';
		$ar->cldr_nplurals                        = '6';
		$ar->cldr_plural_expressions['zero']      = 'n = 0 @integer 0 @decimal 0.0, 0.00, 0.000, 0.0000';
		$ar->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ar->cldr_plural_expressions['two']       = 'n = 2 @integer 2 @decimal 2.0, 2.00, 2.000, 2.0000';
		$ar->cldr_plural_expressions['few']       = 'n % 100 = 3..10 @integer 3~10, 103~110, 1003, … @decimal 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 103.0, 1003.0, …';
		$ar->cldr_plural_expressions['many']      = 'n % 100 = 11..99 @integer 11~26, 111, 1011, … @decimal 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 111.0, 1011.0, …';
		$ar->cldr_plural_expressions['other']     = ' @integer 100~102, 200~202, 300~302, 400~402, 500~502, 600, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.1, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ar->google_code                          = 'ar';
		$ar->preferred_sans_serif_font_family     = 'Tahoma';
		$ar->facebook_locale                      = 'ar_AR';

		$arq = new GP_Locale();
		$arq->english_name                        = 'Algerian Arabic';
		$arq->native_name                         = 'الدارجة الجزايرية';
		$arq->text_direction                      = 'rtl';
		$arq->lang_code_iso_639_1                 = 'ar';
		$arq->lang_code_iso_639_3                 = 'arq';
		$arq->country_code                        = 'dz';
		$arq->wp_locale                           = 'arq';
		$arq->slug                                = 'arq';
		$arq->nplurals                            = '6';
		$arq->plural_expression                   = 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5';
		$arq->cldr_code                           = 'ar';
		$arq->cldr_nplurals                       = '6';
		$arq->cldr_plural_expressions['zero']     = 'n = 0 @integer 0 @decimal 0.0, 0.00, 0.000, 0.0000';
		$arq->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$arq->cldr_plural_expressions['two']      = 'n = 2 @integer 2 @decimal 2.0, 2.00, 2.000, 2.0000';
		$arq->cldr_plural_expressions['few']      = 'n % 100 = 3..10 @integer 3~10, 103~110, 1003, … @decimal 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 103.0, 1003.0, …';
		$arq->cldr_plural_expressions['many']     = 'n % 100 = 11..99 @integer 11~26, 111, 1011, … @decimal 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 111.0, 1011.0, …';
		$arq->cldr_plural_expressions['other']    = ' @integer 100~102, 200~202, 300~302, 400~402, 500~502, 600, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.1, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$arq->variant_root                        = 'ar';
		$ar->variants['arq']                    = $ar->english_name;

		$ary = new GP_Locale();
		$ary->english_name                        = 'Moroccan Arabic';
		$ary->native_name                         = 'العربية المغربية';
		$ary->text_direction                      = 'rtl';
		$ary->lang_code_iso_639_1                 = 'ar';
		$ary->lang_code_iso_639_3                 = 'ary';
		$ary->country_code                        = 'ma';
		$ary->wp_locale                           = 'ary';
		$ary->slug                                = 'ary';
		$ary->nplurals                            = '6';
		$ary->plural_expression                   = 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5';
		$ary->cldr_code                           = 'ar';
		$ary->cldr_nplurals                       = '6';
		$ary->cldr_plural_expressions['zero']     = 'n = 0 @integer 0 @decimal 0.0, 0.00, 0.000, 0.0000';
		$ary->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ary->cldr_plural_expressions['two']      = 'n = 2 @integer 2 @decimal 2.0, 2.00, 2.000, 2.0000';
		$ary->cldr_plural_expressions['few']      = 'n % 100 = 3..10 @integer 3~10, 103~110, 1003, … @decimal 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 103.0, 1003.0, …';
		$ary->cldr_plural_expressions['many']     = 'n % 100 = 11..99 @integer 11~26, 111, 1011, … @decimal 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 111.0, 1011.0, …';
		$ary->cldr_plural_expressions['other']    = ' @integer 100~102, 200~202, 300~302, 400~402, 500~502, 600, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.1, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ary->variant_root                        = 'ar';
		$ar->variants['ary']                    = $ar->english_name;

		$as = new GP_Locale();
		$as->english_name                         = 'Assamese';
		$as->native_name                          = 'অসমীয়া';
		$as->text_direction                       = 'ltr';
		$as->lang_code_iso_639_1                  = 'as';
		$as->lang_code_iso_639_2                  = 'asm';
		$as->lang_code_iso_639_3                  = 'asm';
		$as->country_code                         = 'in';
		$as->wp_locale                            = 'as';
		$as->slug                                 = 'as';
		$as->nplurals                             = '2';
		$as->plural_expression                    = 'n != 1';
		$as->cldr_code                            = 'as';
		$as->cldr_nplurals                        = '2';
		$as->cldr_plural_expressions['one']       = 'i = 0 or n = 1 @integer 0, 1 @decimal 0.0~1.0, 0.00~0.04';
		$as->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 1.1~2.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$as->facebook_locale                      = 'as_IN';

		$ast = new GP_Locale();
		$ast->english_name                        = 'Asturian';
		$ast->native_name                         = 'Asturianu';
		$ast->text_direction                      = 'ltr';
		$ast->lang_code_iso_639_2                 = 'ast';
		$ast->lang_code_iso_639_3                 = 'ast';
		$ast->country_code                        = 'es';
		$ast->wp_locale                           = 'ast';
		$ast->slug                                = 'ast';
		$ast->nplurals                            = '2';
		$ast->plural_expression                   = 'n != 1';
		$ast->cldr_code                           = 'ast';
		$ast->cldr_nplurals                       = '2';
		$ast->cldr_plural_expressions['one']      = 'i = 1 and v = 0 @integer 1';
		$ast->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$av = new GP_Locale();
		$av->english_name                         = 'Avaric';
		$av->native_name                          = 'авар мацӀ';
		$av->text_direction                       = 'ltr';
		$av->lang_code_iso_639_1                  = 'av';
		$av->lang_code_iso_639_2                  = 'ava';
		$av->slug                                 = 'av';
		$av->nplurals                             = '2';
		$av->plural_expression                    = 'n != 1';

		$ay = new GP_Locale();
		$ay->english_name                         = 'Aymara';
		$ay->native_name                          = 'aymar aru';
		$ay->text_direction                       = 'ltr';
		$ay->lang_code_iso_639_1                  = 'ay';
		$ay->lang_code_iso_639_2                  = 'aym';
		$ay->slug                                 = 'ay';
		$ay->nplurals                             = '1';
		$ay->facebook_locale                      = 'ay_BO';

		$az = new GP_Locale();
		$az->english_name                         = 'Azerbaijani';
		$az->native_name                          = 'Azərbaycan dili';
		$az->text_direction                       = 'ltr';
		$az->lang_code_iso_639_1                  = 'az';
		$az->lang_code_iso_639_2                  = 'aze';
		$az->country_code                         = 'az';
		$az->wp_locale                            = 'az';
		$az->slug                                 = 'az';
		$az->nplurals                             = '2';
		$az->plural_expression                    = 'n != 1';
		$az->cldr_code                            = 'az';
		$az->cldr_nplurals                        = '2';
		$az->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$az->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$az->google_code                          = 'az';
		$az->facebook_locale                      = 'az_AZ';

		$azb = new GP_Locale();
		$azb->english_name                        = 'South Azerbaijani';
		$azb->native_name                         = 'گؤنئی آذربایجان';
		$azb->text_direction                      = 'rtl';
		$azb->lang_code_iso_639_1                 = 'az';
		$azb->lang_code_iso_639_3                 = 'azb';
		$azb->country_code                        = 'ir';
		$azb->wp_locale                           = 'azb';
		$azb->slug                                = 'azb';
		$azb->nplurals                            = '2';
		$azb->plural_expression                   = 'n != 1';
		$azb->cldr_code                           = 'az';
		$azb->cldr_nplurals                       = '2';
		$azb->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$azb->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$azb->variant_root                        = 'az';
		$az->variants['azb']                    = $az->english_name;

		$az_tr = new GP_Locale();
		$az_tr->english_name                      = 'Azerbaijani (Turkey)';
		$az_tr->native_name                       = 'Azərbaycan Türkcəsi';
		$az_tr->text_direction                    = 'ltr';
		$az_tr->lang_code_iso_639_1               = 'az';
		$az_tr->lang_code_iso_639_2               = 'aze';
		$az_tr->country_code                      = 'tr';
		$az_tr->wp_locale                         = 'az_TR';
		$az_tr->slug                              = 'az-tr';
		$az_tr->nplurals                          = '2';
		$az_tr->plural_expression                 = 'n != 1';
		$az_tr->cldr_code                         = 'az';
		$az_tr->cldr_nplurals                     = '2';
		$az_tr->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$az_tr->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$az_tr->variant_root                      = 'az';
		$az->variants['az_tr']                    = $az->english_name;

		$ba = new GP_Locale();
		$ba->english_name                         = 'Bashkir';
		$ba->native_name                          = 'башҡорт теле';
		$ba->text_direction                       = 'ltr';
		$ba->lang_code_iso_639_1                  = 'ba';
		$ba->lang_code_iso_639_2                  = 'bak';
		$ba->wp_locale                            = 'ba';
		$ba->slug                                 = 'ba';
		$ba->nplurals                             = '2';
		$ba->plural_expression                    = 'n != 1';

		$bal = new GP_Locale();
		$bal->english_name                        = 'Catalan (Balear)';
		$bal->native_name                         = 'Català (Balear)';
		$bal->text_direction                      = 'ltr';
		$bal->lang_code_iso_639_2                 = 'bal';
		$bal->country_code                        = 'es';
		$bal->wp_locale                           = 'bal';
		$bal->slug                                = 'bal';
		$bal->nplurals                            = '2';
		$bal->plural_expression                   = 'n != 1';

		$bcc = new GP_Locale();
		$bcc->english_name                        = 'Balochi Southern';
		$bcc->native_name                         = 'بلوچی مکرانی';
		$bcc->text_direction                      = 'rtl';
		$bcc->lang_code_iso_639_3                 = 'bcc';
		$bcc->country_code                        = 'pk';
		$bcc->wp_locale                           = 'bcc';
		$bcc->slug                                = 'bcc';
		$bcc->nplurals                            = '1';

		$bel = new GP_Locale();
		$bel->english_name                        = 'Belarusian';
		$bel->native_name                         = 'Беларуская мова';
		$bel->text_direction                      = 'ltr';
		$bel->lang_code_iso_639_1                 = 'be';
		$bel->lang_code_iso_639_2                 = 'bel';
		$bel->country_code                        = 'by';
		$bel->wp_locale                           = 'bel';
		$bel->slug                                = 'bel';
		$bel->nplurals                            = '3';
		$bel->plural_expression                   = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';
		$bel->cldr_code                           = 'be';
		$bel->cldr_nplurals                       = '4';
		$bel->cldr_plural_expressions['one']      = 'n % 10 = 1 and n % 100 != 11 @integer 1, 21, 31, 41, 51, 61, 71, 81, 101, 1001, … @decimal 1.0, 21.0, 31.0, 41.0, 51.0, 61.0, 71.0, 81.0, 101.0, 1001.0, …';
		$bel->cldr_plural_expressions['few']      = 'n % 10 = 2..4 and n % 100 != 12..14 @integer 2~4, 22~24, 32~34, 42~44, 52~54, 62, 102, 1002, … @decimal 2.0, 3.0, 4.0, 22.0, 23.0, 24.0, 32.0, 33.0, 102.0, 1002.0, …';
		$bel->cldr_plural_expressions['many']     = 'n % 10 = 0 or n % 10 = 5..9 or n % 100 = 11..14 @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$bel->cldr_plural_expressions['other']    = '   @decimal 0.1~0.9, 1.1~1.7, 10.1, 100.1, 1000.1, …';
		$bel->google_code                         = 'be';
		$bel->facebook_locale                     = 'be_BY';

		$bg = new GP_Locale();
		$bg->english_name                         = 'Bulgarian';
		$bg->native_name                          = 'Български';
		$bg->text_direction                       = 'ltr';
		$bg->lang_code_iso_639_1                  = 'bg';
		$bg->lang_code_iso_639_2                  = 'bul';
		$bg->country_code                         = 'bg';
		$bg->wp_locale                            = 'bg_BG';
		$bg->slug                                 = 'bg';
		$bg->nplurals                             = '2';
		$bg->plural_expression                    = 'n != 1';
		$bg->cldr_code                            = 'bg';
		$bg->cldr_nplurals                        = '2';
		$bg->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$bg->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$bg->google_code                          = 'bg';
		$bg->facebook_locale                      = 'bg_BG';

		$bh = new GP_Locale();
		$bh->english_name                         = 'Bihari';
		$bh->native_name                          = 'भोजपुरी';
		$bh->text_direction                       = 'ltr';
		$bh->lang_code_iso_639_1                  = 'bh';
		$bh->lang_code_iso_639_2                  = 'bih';
		$bh->slug                                 = 'bh';
		$bh->nplurals                             = '2';
		$bh->plural_expression                    = 'n != 1';
		$bh->cldr_code                            = 'bh';
		$bh->cldr_nplurals                        = '2';
		$bh->cldr_plural_expressions['one']       = 'n = 0..1 @integer 0, 1 @decimal 0.0, 1.0, 0.00, 1.00, 0.000, 1.000, 0.0000, 1.0000';
		$bh->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$bi = new GP_Locale();
		$bi->english_name                         = 'Bislama';
		$bi->native_name                          = 'Bislama';
		$bi->text_direction                       = 'ltr';
		$bi->lang_code_iso_639_1                  = 'bi';
		$bi->lang_code_iso_639_2                  = 'bis';
		$bi->country_code                         = 'vu';
		$bi->slug                                 = 'bi';
		$bi->nplurals                             = '2';
		$bi->plural_expression                    = 'n != 1';

		$bm = new GP_Locale();
		$bm->english_name                         = 'Bambara';
		$bm->native_name                          = 'Bamanankan';
		$bm->text_direction                       = 'ltr';
		$bm->lang_code_iso_639_1                  = 'bm';
		$bm->lang_code_iso_639_2                  = 'bam';
		$bm->slug                                 = 'bm';
		$bm->nplurals                             = '2';
		$bm->plural_expression                    = 'n != 1';
		$bm->cldr_code                            = 'bm';
		$bm->cldr_nplurals                        = '1';
		$bm->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$bn = new GP_Locale();
		$bn->english_name                         = 'Bengali';
		$bn->native_name                          = 'বাংলা';
		$bn->text_direction                       = 'ltr';
		$bn->lang_code_iso_639_1                  = 'bn';
		$bn->country_code                         = 'bn';
		$bn->wp_locale                            = 'bn_BD';
		$bn->slug                                 = 'bn';
		$bn->nplurals                             = '2';
		$bn->plural_expression                    = 'n != 1';
		$bn->cldr_code                            = 'bn';
		$bn->cldr_nplurals                        = '2';
		$bn->cldr_plural_expressions['one']       = 'i = 0 or n = 1 @integer 0, 1 @decimal 0.0~1.0, 0.00~0.04';
		$bn->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 1.1~2.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$bn->google_code                          = 'bn';
		$bn->facebook_locale                      = 'bn_IN';

		$bo = new GP_Locale();
		$bo->english_name                         = 'Tibetan';
		$bo->native_name                          = 'བོད་ཡིག';
		$bo->text_direction                       = 'ltr';
		$bo->lang_code_iso_639_1                  = 'bo';
		$bo->lang_code_iso_639_2                  = 'tib';
		$bo->wp_locale                            = 'bo';
		$bo->slug                                 = 'bo';
		$bo->nplurals                             = '1';
		$bo->cldr_code                            = 'bo';
		$bo->cldr_nplurals                        = '1';
		$bo->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$br = new GP_Locale();
		$br->english_name                         = 'Breton';
		$br->native_name                          = 'Brezhoneg';
		$br->text_direction                       = 'ltr';
		$br->lang_code_iso_639_1                  = 'br';
		$br->lang_code_iso_639_2                  = 'bre';
		$br->lang_code_iso_639_3                  = 'bre';
		$br->country_code                         = 'fr';
		$br->wp_locale                            = 'bre';
		$br->slug                                 = 'br';
		$br->nplurals                             = '2';
		$br->plural_expression                    = '(n > 1)';
		$br->cldr_code                            = 'br';
		$br->cldr_nplurals                        = '5';
		$br->cldr_plural_expressions['one']       = 'n % 10 = 1 and n % 100 != 11,71,91 @integer 1, 21, 31, 41, 51, 61, 81, 101, 1001, … @decimal 1.0, 21.0, 31.0, 41.0, 51.0, 61.0, 81.0, 101.0, 1001.0, …';
		$br->cldr_plural_expressions['two']       = 'n % 10 = 2 and n % 100 != 12,72,92 @integer 2, 22, 32, 42, 52, 62, 82, 102, 1002, … @decimal 2.0, 22.0, 32.0, 42.0, 52.0, 62.0, 82.0, 102.0, 1002.0, …';
		$br->cldr_plural_expressions['few']       = 'n % 10 = 3..4,9 and n % 100 != 10..19,70..79,90..99 @integer 3, 4, 9, 23, 24, 29, 33, 34, 39, 43, 44, 49, 103, 1003, … @decimal 3.0, 4.0, 9.0, 23.0, 24.0, 29.0, 33.0, 34.0, 103.0, 1003.0, …';
		$br->cldr_plural_expressions['many']      = 'n != 0 and n % 1000000 = 0 @integer 1000000, … @decimal 1000000.0, 1000000.00, 1000000.000, …';
		$br->cldr_plural_expressions['other']     = ' @integer 0, 5~8, 10~20, 100, 1000, 10000, 100000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, …';
		$br->facebook_locale                      = 'br_FR';

		$bs = new GP_Locale();
		$bs->english_name                         = 'Bosnian';
		$bs->native_name                          = 'Bosanski';
		$bs->text_direction                       = 'ltr';
		$bs->lang_code_iso_639_1                  = 'bs';
		$bs->lang_code_iso_639_2                  = 'bos';
		$bs->country_code                         = 'ba';
		$bs->wp_locale                            = 'bs_BA';
		$bs->slug                                 = 'bs';
		$bs->nplurals                             = '3';
		$bs->plural_expression                    = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';
		$bs->cldr_code                            = 'bs';
		$bs->cldr_nplurals                        = '3';
		$bs->cldr_plural_expressions['one']       = 'v = 0 and i % 10 = 1 and i % 100 != 11 or f % 10 = 1 and f % 100 != 11 @integer 1, 21, 31, 41, 51, 61, 71, 81, 101, 1001, … @decimal 0.1, 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 10.1, 100.1, 1000.1, …';
		$bs->cldr_plural_expressions['few']       = 'v = 0 and i % 10 = 2..4 and i % 100 != 12..14 or f % 10 = 2..4 and f % 100 != 12..14 @integer 2~4, 22~24, 32~34, 42~44, 52~54, 62, 102, 1002, … @decimal 0.2~0.4, 1.2~1.4, 2.2~2.4, 3.2~3.4, 4.2~4.4, 5.2, 10.2, 100.2, 1000.2, …';
		$bs->cldr_plural_expressions['other']     = ' @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0, 0.5~1.0, 1.5~2.0, 2.5~2.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$bs->google_code                          = 'bs';
		$bs->facebook_locale                      = 'bs_BA';

		$ca = new GP_Locale();
		$ca->english_name                         = 'Catalan';
		$ca->native_name                          = 'Català';
		$ca->text_direction                       = 'ltr';
		$ca->lang_code_iso_639_1                  = 'ca';
		$ca->lang_code_iso_639_2                  = 'cat';
		$ca->wp_locale                            = 'ca';
		$ca->slug                                 = 'ca';
		$ca->nplurals                             = '2';
		$ca->plural_expression                    = 'n != 1';
		$ca->cldr_code                            = 'ca';
		$ca->cldr_nplurals                        = '2';
		$ca->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$ca->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ca->google_code                          = 'ca';
		$ca->facebook_locale                      = 'ca_ES';

		$ce = new GP_Locale();
		$ce->english_name                         = 'Chechen';
		$ce->native_name                          = 'Нохчийн мотт';
		$ce->text_direction                       = 'ltr';
		$ce->lang_code_iso_639_1                  = 'ce';
		$ce->lang_code_iso_639_2                  = 'che';
		$ce->slug                                 = 'ce';
		$ce->nplurals                             = '2';
		$ce->plural_expression                    = 'n != 1';
		$ce->cldr_code                            = 'ce';
		$ce->cldr_nplurals                        = '2';
		$ce->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ce->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$ceb = new GP_Locale();
		$ceb->english_name                        = 'Cebuano';
		$ceb->native_name                         = 'Cebuano';
		$ceb->text_direction                      = 'ltr';
		$ceb->lang_code_iso_639_2                 = 'ceb';
		$ceb->lang_code_iso_639_3                 = 'ceb';
		$ceb->country_code                        = 'ph';
		$ceb->wp_locale                           = 'ceb';
		$ceb->slug                                = 'ceb';
		$ceb->nplurals                            = '2';
		$ceb->plural_expression                   = 'n != 1';
		$ceb->facebook_locale                     = 'cx_PH';

		$ch = new GP_Locale();
		$ch->english_name                         = 'Chamorro';
		$ch->native_name                          = 'Chamoru';
		$ch->text_direction                       = 'ltr';
		$ch->lang_code_iso_639_1                  = 'ch';
		$ch->lang_code_iso_639_2                  = 'cha';
		$ch->slug                                 = 'ch';
		$ch->nplurals                             = '2';
		$ch->plural_expression                    = 'n != 1';

		$ckb = new GP_Locale();
		$ckb->english_name                        = 'Kurdish (Sorani)';
		$ckb->native_name                         = 'كوردی‎';
		$ckb->text_direction                      = 'rtl';
		$ckb->lang_code_iso_639_1                 = 'ku';
		$ckb->lang_code_iso_639_3                 = 'ckb';
		$ckb->country_code                        = 'iq';
		$ckb->wp_locale                           = 'ckb';
		$ckb->slug                                = 'ckb';
		$ckb->nplurals                            = '2';
		$ckb->plural_expression                   = 'n != 1';
		$ckb->cldr_code                           = 'ku';
		$ckb->cldr_nplurals                       = '2';
		$ckb->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ckb->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ckb->facebook_locale                     = 'cb_IQ';

		$co = new GP_Locale();
		$co->english_name                         = 'Corsican';
		$co->native_name                          = 'Corsu';
		$co->text_direction                       = 'ltr';
		$co->lang_code_iso_639_1                  = 'co';
		$co->lang_code_iso_639_2                  = 'cos';
		$co->country_code                         = 'it';
		$co->wp_locale                            = 'co';
		$co->slug                                 = 'co';
		$co->nplurals                             = '2';
		$co->plural_expression                    = 'n != 1';

		$cr = new GP_Locale();
		$cr->english_name                         = 'Cree';
		$cr->native_name                          = 'ᓀᐦᐃᔭᐍᐏᐣ';
		$cr->text_direction                       = 'ltr';
		$cr->lang_code_iso_639_1                  = 'cr';
		$cr->lang_code_iso_639_2                  = 'cre';
		$cr->country_code                         = 'ca';
		$cr->slug                                 = 'cr';
		$cr->nplurals                             = '2';
		$cr->plural_expression                    = 'n != 1';

		$cs = new GP_Locale();
		$cs->english_name                         = 'Czech';
		$cs->native_name                          = 'Čeština';
		$cs->text_direction                       = 'ltr';
		$cs->lang_code_iso_639_1                  = 'cs';
		$cs->lang_code_iso_639_2                  = 'ces';
		$cs->country_code                         = 'cz';
		$cs->wp_locale                            = 'cs_CZ';
		$cs->slug                                 = 'cs';
		$cs->nplurals                             = '3';
		$cs->plural_expression                    = '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2';
		$cs->cldr_code                            = 'cs';
		$cs->cldr_nplurals                        = '4';
		$cs->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$cs->cldr_plural_expressions['few']       = 'i = 2..4 and v = 0 @integer 2~4';
		$cs->cldr_plural_expressions['many']      = 'v != 0   @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$cs->cldr_plural_expressions['other']     = ' @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, …';
		$cs->google_code                          = 'cs';
		$cs->facebook_locale                      = 'cs_CZ';

		$csb = new GP_Locale();
		$csb->english_name                        = 'Kashubian';
		$csb->native_name                         = 'Kaszëbsczi';
		$csb->text_direction                      = 'ltr';
		$csb->lang_code_iso_639_2                 = 'csb';
		$csb->slug                                = 'csb';
		$csb->nplurals                            = '3';
		$csb->plural_expression                   = 'n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2';

		$cu = new GP_Locale();
		$cu->english_name                         = 'Church Slavic';
		$cu->native_name                          = 'ѩзыкъ словѣньскъ';
		$cu->text_direction                       = 'ltr';
		$cu->lang_code_iso_639_1                  = 'cu';
		$cu->lang_code_iso_639_2                  = 'chu';
		$cu->slug                                 = 'cu';
		$cu->nplurals                             = '2';
		$cu->plural_expression                    = 'n != 1';

		$cv = new GP_Locale();
		$cv->english_name                         = 'Chuvash';
		$cv->native_name                          = 'чӑваш чӗлхи';
		$cv->text_direction                       = 'ltr';
		$cv->lang_code_iso_639_1                  = 'cv';
		$cv->lang_code_iso_639_2                  = 'chv';
		$cv->country_code                         = 'ru';
		$cv->slug                                 = 'cv';
		$cv->nplurals                             = '2';
		$cv->plural_expression                    = 'n != 1';

		$cy = new GP_Locale();
		$cy->english_name                         = 'Welsh';
		$cy->native_name                          = 'Cymraeg';
		$cy->text_direction                       = 'ltr';
		$cy->lang_code_iso_639_1                  = 'cy';
		$cy->lang_code_iso_639_2                  = 'cym';
		$cy->country_code                         = 'gb';
		$cy->wp_locale                            = 'cy';
		$cy->slug                                 = 'cy';
		$cy->nplurals                             = '4';
		$cy->plural_expression                    = '(n==1) ? 0 : (n==2) ? 1 : (n != 8 && n != 11) ? 2 : 3';
		$cy->cldr_code                            = 'cy';
		$cy->cldr_nplurals                        = '6';
		$cy->cldr_plural_expressions['zero']      = 'n = 0 @integer 0 @decimal 0.0, 0.00, 0.000, 0.0000';
		$cy->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$cy->cldr_plural_expressions['two']       = 'n = 2 @integer 2 @decimal 2.0, 2.00, 2.000, 2.0000';
		$cy->cldr_plural_expressions['few']       = 'n = 3 @integer 3 @decimal 3.0, 3.00, 3.000, 3.0000';
		$cy->cldr_plural_expressions['many']      = 'n = 6 @integer 6 @decimal 6.0, 6.00, 6.000, 6.0000';
		$cy->cldr_plural_expressions['other']     = ' @integer 4, 5, 7~20, 100, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$cy->google_code                          = 'cy';
		$cy->facebook_locale                      = 'cy_GB';

		$da = new GP_Locale();
		$da->english_name                         = 'Danish';
		$da->native_name                          = 'Dansk';
		$da->text_direction                       = 'ltr';
		$da->lang_code_iso_639_1                  = 'da';
		$da->lang_code_iso_639_2                  = 'dan';
		$da->country_code                         = 'dk';
		$da->wp_locale                            = 'da_DK';
		$da->slug                                 = 'da';
		$da->nplurals                             = '2';
		$da->plural_expression                    = 'n != 1';
		$da->cldr_code                            = 'da';
		$da->cldr_nplurals                        = '2';
		$da->cldr_plural_expressions['one']       = 'n = 1 or t != 0 and i = 0,1 @integer 1 @decimal 0.1~1.6';
		$da->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0, 2.0~3.4, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$da->google_code                          = 'da';
		$da->facebook_locale                      = 'da_DK';

		$de = new GP_Locale();
		$de->english_name                         = 'German';
		$de->native_name                          = 'Deutsch';
		$de->text_direction                       = 'ltr';
		$de->lang_code_iso_639_1                  = 'de';
		$de->country_code                         = 'de';
		$de->wp_locale                            = 'de_DE';
		$de->slug                                 = 'de';
		$de->nplurals                             = '2';
		$de->plural_expression                    = 'n != 1';
		$de->cldr_code                            = 'de';
		$de->cldr_nplurals                        = '2';
		$de->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$de->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$de->google_code                          = 'de';
		$de->facebook_locale                      = 'de_DE';

		$de_ch = new GP_Locale();
		$de_ch->english_name                      = 'German (Switzerland)';
		$de_ch->native_name                       = 'Deutsch (Schweiz)';
		$de_ch->text_direction                    = 'ltr';
		$de_ch->lang_code_iso_639_1               = 'de';
		$de_ch->country_code                      = 'ch';
		$de_ch->wp_locale                         = 'de_CH';
		$de_ch->slug                              = 'de-ch';
		$de_ch->nplurals                          = '2';
		$de_ch->plural_expression                 = 'n != 1';
		$de_ch->cldr_code                         = 'de';
		$de_ch->cldr_nplurals                     = '2';
		$de_ch->cldr_plural_expressions['one']    = 'i = 1 and v = 0 @integer 1';
		$de_ch->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$de_ch->google_code                       = 'de';
		$de_ch->variant_root                      = 'de';
		$de->variants['de_ch']                    = $de->english_name;

		$dv = new GP_Locale();
		$dv->english_name                         = 'Dhivehi';
		$dv->native_name                          = 'ދިވެހި';
		$dv->text_direction                       = 'rtl';
		$dv->lang_code_iso_639_1                  = 'dv';
		$dv->lang_code_iso_639_2                  = 'div';
		$dv->country_code                         = 'mv';
		$dv->wp_locale                            = 'dv';
		$dv->slug                                 = 'dv';
		$dv->nplurals                             = '2';
		$dv->plural_expression                    = 'n != 1';
		$dv->cldr_code                            = 'dv';
		$dv->cldr_nplurals                        = '2';
		$dv->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$dv->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$dzo = new GP_Locale();
		$dzo->english_name                        = 'Dzongkha';
		$dzo->native_name                         = 'རྫོང་ཁ';
		$dzo->text_direction                      = 'ltr';
		$dzo->lang_code_iso_639_1                 = 'dz';
		$dzo->lang_code_iso_639_2                 = 'dzo';
		$dzo->country_code                        = 'bt';
		$dzo->wp_locale                           = 'dzo';
		$dzo->slug                                = 'dzo';
		$dzo->nplurals                            = '1';
		$dzo->cldr_code                           = 'dz';
		$dzo->cldr_nplurals                       = '1';
		$dzo->cldr_plural_expressions['other']    = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$ewe = new GP_Locale();
		$ewe->english_name = 'Ewe';
		$ewe->native_name = 'Eʋegbe';
		$ewe->lang_code_iso_639_1 = 'ee';
		$ewe->lang_code_iso_639_2 = 'ewe';
		$ewe->lang_code_iso_639_3 = 'ewe';
		$ewe->country_code = 'gh';
		$ewe->wp_locale = 'ewe';
		$ewe->slug = 'ee';
		$ewe->nplurals                             = '2';
		$ewe->plural_expression                    = 'n != 1';
		$ewe->cldr_code = 'ee';
		$ewe->cldr_nplurals = '2';
		$ewe->cldr_plural_expressions['one'] = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ewe->cldr_plural_expressions['other'] = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$el = new GP_Locale();
		$el->english_name                         = 'Greek';
		$el->native_name                          = 'Ελληνικά';
		$el->text_direction                       = 'ltr';
		$el->lang_code_iso_639_1                  = 'el';
		$el->lang_code_iso_639_2                  = 'ell';
		$el->country_code                         = 'gr';
		$el->wp_locale                            = 'el';
		$el->slug                                 = 'el';
		$el->nplurals                             = '2';
		$el->plural_expression                    = 'n != 1';
		$el->cldr_code                            = 'el';
		$el->cldr_nplurals                        = '2';
		$el->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$el->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$el->google_code                          = 'el';
		$el->facebook_locale                      = 'el_GR';

		$el_po = new GP_Locale();
		$el_po->english_name                      = 'Greek (Polytonic)';
		$el_po->native_name                       = 'Greek (Polytonic)';
		$el_po->text_direction                    = 'ltr';
		$el_po->country_code                      = 'gr';
		$el_po->slug                              = 'el-po';
		$el_po->nplurals                          = '2';
		$el_po->plural_expression                 = 'n != 1';
		$el_po->variant_root                      = 'el';
		$el->variants['el_po']                    = $el->english_name;

		$art_xemoji = new GP_Locale();
		$art_xemoji->english_name                 = 'Emoji';
		$art_xemoji->native_name                  = '🌏🌍🌎 (Emoji)';
		$art_xemoji->text_direction               = 'ltr';
		$art_xemoji->lang_code_iso_639_2          = 'art';
		$art_xemoji->wp_locale                    = 'art_xemoji';
		$art_xemoji->slug                         = 'art-xemoji';
		$art_xemoji->nplurals                     = '1';

		$en = new GP_Locale();
		$en->english_name                         = 'English (Unites States)';
		$en->native_name                          = 'English (United States)';
		$en->text_direction                       = 'ltr';
		$en->lang_code_iso_639_1                  = 'en';
		$en->country_code                         = 'us';
		$en->wp_locale                            = 'en_US';
		$en->slug                                 = 'en';
		$en->nplurals                             = '2';
		$en->plural_expression                    = 'n != 1';
		$en->cldr_code                            = 'en';
		$en->cldr_nplurals                        = '2';
		$en->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$en->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$en->google_code                          = 'en';
		$en->facebook_locale                      = 'en_US';

		$en_au = new GP_Locale();
		$en_au->english_name                      = 'English (Australia)';
		$en_au->native_name                       = 'English (Australia)';
		$en_au->text_direction                    = 'ltr';
		$en_au->lang_code_iso_639_1               = 'en';
		$en_au->lang_code_iso_639_2               = 'eng';
		$en_au->lang_code_iso_639_3               = 'eng';
		$en_au->country_code                      = 'au';
		$en_au->wp_locale                         = 'en_AU';
		$en_au->slug                              = 'en-au';
		$en_au->nplurals                          = '2';
		$en_au->plural_expression                 = 'n != 1';
		$en_au->cldr_code                         = 'en';
		$en_au->cldr_nplurals                     = '2';
		$en_au->cldr_plural_expressions['one']    = 'i = 1 and v = 0 @integer 1';
		$en_au->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$en_au->google_code                       = 'en';
		$en_au->variant_root                      = 'en';
		$en->variants['en_au']                    = $en->english_name;

		$en_ca = new GP_Locale();
		$en_ca->english_name                      = 'English (Canada)';
		$en_ca->native_name                       = 'English (Canada)';
		$en_ca->text_direction                    = 'ltr';
		$en_ca->lang_code_iso_639_1               = 'en';
		$en_ca->lang_code_iso_639_2               = 'eng';
		$en_ca->lang_code_iso_639_3               = 'eng';
		$en_ca->country_code                      = 'ca';
		$en_ca->wp_locale                         = 'en_CA';
		$en_ca->slug                              = 'en-ca';
		$en_ca->nplurals                          = '2';
		$en_ca->plural_expression                 = 'n != 1';
		$en_ca->cldr_code                         = 'en';
		$en_ca->cldr_nplurals                     = '2';
		$en_ca->cldr_plural_expressions['one']    = 'i = 1 and v = 0 @integer 1';
		$en_ca->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$en_ca->google_code                       = 'en';
		$en_ca->variant_root                      = 'en';
		$en->variants['en_ca']                    = $en->english_name;

		$en_gb = new GP_Locale();
		$en_gb->english_name                      = 'English (UK)';
		$en_gb->native_name                       = 'English (UK)';
		$en_gb->text_direction                    = 'ltr';
		$en_gb->lang_code_iso_639_1               = 'en';
		$en_gb->lang_code_iso_639_2               = 'eng';
		$en_gb->lang_code_iso_639_3               = 'eng';
		$en_gb->country_code                      = 'gb';
		$en_gb->wp_locale                         = 'en_GB';
		$en_gb->slug                              = 'en-gb';
		$en_gb->nplurals                          = '2';
		$en_gb->plural_expression                 = 'n != 1';
		$en_gb->cldr_code                         = 'en';
		$en_gb->cldr_nplurals                     = '2';
		$en_gb->cldr_plural_expressions['one']    = 'i = 1 and v = 0 @integer 1';
		$en_gb->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$en_gb->google_code                       = 'en';
		$en_gb->facebook_locale                   = 'en_GB';
		$en_gb->variant_root                      = 'en';
		$en->variants['en_gb']                    = $en->english_name;

		$en_nz = new GP_Locale();
		$en_nz->english_name                      = 'English (New Zealand)';
		$en_nz->native_name                       = 'English (New Zealand)';
		$en_nz->text_direction                    = 'ltr';
		$en_nz->lang_code_iso_639_1               = 'en';
		$en_nz->lang_code_iso_639_2               = 'eng';
		$en_nz->lang_code_iso_639_3               = 'eng';
		$en_nz->country_code                      = 'nz';
		$en_nz->wp_locale                         = 'en_NZ';
		$en_nz->slug                              = 'en-nz';
		$en_nz->nplurals                          = '2';
		$en_nz->plural_expression                 = 'n != 1';
		$en_nz->cldr_code                         = 'en';
		$en_nz->cldr_nplurals                     = '2';
		$en_nz->cldr_plural_expressions['one']    = 'i = 1 and v = 0 @integer 1';
		$en_nz->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$en_nz->google_code                       = 'en';
		$en_nz->variant_root                      = 'en';
		$en->variants['en_nz']                    = $en->english_name;

		$en_za = new GP_Locale();
		$en_za->english_name                      = 'English (South Africa)';
		$en_za->native_name                       = 'English (South Africa)';
		$en_za->text_direction                    = 'ltr';
		$en_za->lang_code_iso_639_1               = 'en';
		$en_za->lang_code_iso_639_2               = 'eng';
		$en_za->lang_code_iso_639_3               = 'eng';
		$en_za->country_code                      = 'za';
		$en_za->wp_locale                         = 'en_ZA';
		$en_za->slug                              = 'en-za';
		$en_za->nplurals                          = '2';
		$en_za->plural_expression                 = 'n != 1';
		$en_za->cldr_code                         = 'en';
		$en_za->cldr_nplurals                     = '2';
		$en_za->cldr_plural_expressions['one']    = 'i = 1 and v = 0 @integer 1';
		$en_za->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$en_za->google_code                       = 'en';
		$en_za->variant_root                      = 'en';
		$en->variants['en_za']                    = $en->english_name;

		$eo = new GP_Locale();
		$eo->english_name                         = 'Esperanto';
		$eo->native_name                          = 'Esperanto';
		$eo->text_direction                       = 'ltr';
		$eo->lang_code_iso_639_1                  = 'eo';
		$eo->lang_code_iso_639_2                  = 'epo';
		$eo->wp_locale                            = 'eo';
		$eo->slug                                 = 'eo';
		$eo->nplurals                             = '2';
		$eo->plural_expression                    = 'n != 1';
		$eo->cldr_code                            = 'eo';
		$eo->cldr_nplurals                        = '2';
		$eo->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$eo->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$eo->google_code                          = 'eo';
		$eo->facebook_locale                      = 'eo_EO';

		$es = new GP_Locale();
		$es->english_name                         = 'Spanish (Spain)';
		$es->native_name                          = 'Español';
		$es->text_direction                       = 'ltr';
		$es->lang_code_iso_639_1                  = 'es';
		$es->lang_code_iso_639_2 = 'spa';
		$es->lang_code_iso_639_3 = 'spa';
		$es->country_code                         = 'es';
		$es->wp_locale                            = 'es_ES';
		$es->slug                                 = 'es';
		$es->nplurals                             = '2';
		$es->plural_expression                    = 'n != 1';
		$es->cldr_code                            = 'es';
		$es->cldr_nplurals                        = '2';
		$es->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es->google_code                          = 'es';
		$es->facebook_locale                      = 'es_ES';

		$es_ar = new GP_Locale();
		$es_ar->english_name                      = 'Spanish (Argentina)';
		$es_ar->native_name                       = 'Español de Argentina';
		$es_ar->text_direction                    = 'ltr';
		$es_ar->lang_code_iso_639_1               = 'es';
		$es_ar->lang_code_iso_639_2               = 'spa';
		$es_ar->lang_code_iso_639_3 = 'spa';
		$es_ar->country_code                      = 'ar';
		$es_ar->wp_locale                         = 'es_AR';
		$es_ar->slug                              = 'es-ar';
		$es_ar->nplurals                          = '2';
		$es_ar->plural_expression                 = 'n != 1';
		$es_ar->cldr_code                         = 'es';
		$es_ar->cldr_nplurals                     = '2';
		$es_ar->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es_ar->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es_ar->google_code                       = 'es';
		$es_ar->facebook_locale                   = 'es_LA';
		$es_ar->variant_root                      = 'es';
		$es->variants['es_ar']                    = $es->english_name;

		$es_cl = new GP_Locale();
		$es_cl->english_name                      = 'Spanish (Chile)';
		$es_cl->native_name                       = 'Español de Chile';
		$es_cl->text_direction                    = 'ltr';
		$es_cl->lang_code_iso_639_1               = 'es';
		$es_cl->lang_code_iso_639_2               = 'spa';
		$es_cl->lang_code_iso_639_3 = 'spa';
		$es_cl->country_code                      = 'cl';
		$es_cl->wp_locale                         = 'es_CL';
		$es_cl->slug                              = 'es-cl';
		$es_cl->nplurals                          = '2';
		$es_cl->plural_expression                 = 'n != 1';
		$es_cl->cldr_code                         = 'es';
		$es_cl->cldr_nplurals                     = '2';
		$es_cl->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es_cl->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es_cl->google_code                       = 'es';
		$es_cl->facebook_locale                   = 'es_CL';
		$es_cl->variant_root                      = 'es';
		$es->variants['es_cl']                    = $es->english_name;

		$es_co = new GP_Locale();
		$es_co->english_name                      = 'Spanish (Colombia)';
		$es_co->native_name                       = 'Español de Colombia';
		$es_co->text_direction                    = 'ltr';
		$es_co->lang_code_iso_639_1               = 'es';
		$es_co->lang_code_iso_639_2               = 'spa';
		$es_co->lang_code_iso_639_3 = 'spa';
		$es_co->country_code                      = 'co';
		$es_co->wp_locale                         = 'es_CO';
		$es_co->slug                              = 'es-co';
		$es_co->nplurals                          = '2';
		$es_co->plural_expression                 = 'n != 1';
		$es_co->cldr_code                         = 'es';
		$es_co->cldr_nplurals                     = '2';
		$es_co->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es_co->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es_co->google_code                       = 'es';
		$es_co->facebook_locale                   = 'es_CO';
		$es_co->variant_root                      = 'es';
		$es->variants['es_co']                    = $es->english_name;

		$es_cr = new GP_Locale();
		$es_cr->english_name                      = 'Spanish (Costa Rica)';
		$es_cr->native_name                       = 'Español de Costa Rica';
		$es_cr->text_direction                    = 'ltr';
		$es_cr->lang_code_iso_639_1               = 'es';
		$es_cr->lang_code_iso_639_2               = 'spa';
		$es_cr->lang_code_iso_639_3               = 'spa';
		$es_cr->country_code                      = 'cr';
		$es_cr->wp_locale                         = 'es_CR';
		$es_cr->slug                              = 'es-cr';
		$es_cr->nplurals                          = '2';
		$es_cr->plural_expression                 = 'n != 1';
		$es_cr->cldr_code                         = 'es';
		$es_cr->cldr_nplurals                     = '2';
		$es_cr->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es_cr->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es_cr->variant_root                      = 'es';
		$es->variants['es_cr']                    = $es->english_name;

		$es_gt = new GP_Locale();
		$es_gt->english_name                      = 'Spanish (Guatemala)';
		$es_gt->native_name                       = 'Español de Guatemala';
		$es_gt->text_direction                    = 'ltr';
		$es_gt->lang_code_iso_639_1               = 'es';
		$es_gt->lang_code_iso_639_2               = 'spa';
		$es_gt->lang_code_iso_639_3               = 'spa';
		$es_gt->country_code                      = 'gt';
		$es_gt->wp_locale                         = 'es_GT';
		$es_gt->slug                              = 'es-gt';
		$es_gt->nplurals                          = '2';
		$es_gt->plural_expression                 = 'n != 1';
		$es_gt->cldr_code                         = 'es';
		$es_gt->cldr_nplurals                     = '2';
		$es_gt->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es_gt->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es_gt->google_code                       = 'es';
		$es_gt->facebook_locale                   = 'es_LA';
		$es_gt->variant_root                      = 'es';
		$es->variants['es_gt']                    = $es->english_name;

		$es_mx = new GP_Locale();
		$es_mx->english_name                      = 'Spanish (Mexico)';
		$es_mx->native_name                       = 'Español de México';
		$es_mx->text_direction                    = 'ltr';
		$es_mx->lang_code_iso_639_1               = 'es';
		$es_mx->lang_code_iso_639_2               = 'spa';
		$es_mx->lang_code_iso_639_3               = 'spa';
		$es_mx->country_code                      = 'mx';
		$es_mx->wp_locale                         = 'es_MX';
		$es_mx->slug                              = 'es-mx';
		$es_mx->nplurals                          = '2';
		$es_mx->plural_expression                 = 'n != 1';
		$es_mx->cldr_code                         = 'es';
		$es_mx->cldr_nplurals                     = '2';
		$es_mx->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es_mx->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es_mx->google_code                       = 'es';
		$es_mx->facebook_locale                   = 'es_MX';
		$es_mx->variant_root                      = 'es';
		$es->variants['es_mx']                    = $es->english_name;

		$es_pe = new GP_Locale();
		$es_pe->english_name                      = 'Spanish (Peru)';
		$es_pe->native_name                       = 'Español de Perú';
		$es_pe->text_direction                    = 'ltr';
		$es_pe->lang_code_iso_639_1               = 'es';
		$es_pe->lang_code_iso_639_2               = 'spa';
		$es_pe->lang_code_iso_639_3               = 'spa';
		$es_pe->country_code                      = 'pe';
		$es_pe->wp_locale                         = 'es_PE';
		$es_pe->slug                              = 'es-pe';
		$es_pe->nplurals                          = '2';
		$es_pe->plural_expression                 = 'n != 1';
		$es_pe->cldr_code                         = 'es';
		$es_pe->cldr_nplurals                     = '2';
		$es_pe->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es_pe->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es_pe->google_code                       = 'es';
		$es_pe->facebook_locale                   = 'es_LA';
		$es_pe->variant_root                      = 'es';
		$es->variants['es_pe']                    = $es->english_name;

		$es_pr = new GP_Locale();
		$es_pr->english_name                      = 'Spanish (Puerto Rico)';
		$es_pr->native_name                       = 'Español de Puerto Rico';
		$es_pr->text_direction                    = 'ltr';
		$es_pr->lang_code_iso_639_1               = 'es';
		$es_pr->lang_code_iso_639_2               = 'spa';
		$es_pr->lang_code_iso_639_3               = 'spa';
		$es_pr->country_code                      = 'pr';
		$es_pr->wp_locale                         = 'es_PR';
		$es_pr->slug                              = 'es-pr';
		$es_pr->nplurals                          = '2';
		$es_pr->plural_expression                 = 'n != 1';
		$es_pr->cldr_code                         = 'es';
		$es_pr->cldr_nplurals                     = '2';
		$es_pr->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es_pr->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es_pr->google_code                       = 'es';
		$es_pr->facebook_locale                   = 'es_LA';
		$es_pr->variant_root                      = 'es';
		$es->variants['es_pr']                    = $es->english_name;

		$es_us = new GP_Locale();
		$es_us->english_name = 'Spanish (US)';
		$es_us->native_name = 'Español de los Estados Unidos';
		$es_us->lang_code_iso_639_1 = 'es';
		$es_us->lang_code_iso_639_2 = 'spa';
		$es_us->lang_code_iso_639_3 = 'spa';
		$es_us->country_code = 'us';
		$es_us->slug = 'es-us';

		$es_ve = new GP_Locale();
		$es_ve->english_name                      = 'Spanish (Venezuela)';
		$es_ve->native_name                       = 'Español de Venezuela';
		$es_ve->text_direction                    = 'ltr';
		$es_ve->lang_code_iso_639_1               = 'es';
		$es_ve->lang_code_iso_639_2               = 'spa';
		$es_ve->lang_code_iso_639_3               = 'spa';
		$es_ve->country_code                      = 've';
		$es_ve->wp_locale                         = 'es_VE';
		$es_ve->slug                              = 'es-ve';
		$es_ve->nplurals                          = '2';
		$es_ve->plural_expression                 = 'n != 1';
		$es_ve->cldr_code                         = 'es';
		$es_ve->cldr_nplurals                     = '2';
		$es_ve->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$es_ve->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$es_ve->google_code                       = 'es';
		$es_ve->facebook_locale                   = 'es_VE';
		$es_ve->variant_root                      = 'es';
		$es->variants['es_ve']                    = $es->english_name;

		$et = new GP_Locale();
		$et->english_name                         = 'Estonian';
		$et->native_name                          = 'Eesti';
		$et->text_direction                       = 'ltr';
		$et->lang_code_iso_639_1                  = 'et';
		$et->lang_code_iso_639_2                  = 'est';
		$et->country_code                         = 'ee';
		$et->wp_locale                            = 'et';
		$et->slug                                 = 'et';
		$et->nplurals                             = '2';
		$et->plural_expression                    = 'n != 1';
		$et->cldr_code                            = 'et';
		$et->cldr_nplurals                        = '2';
		$et->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$et->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$et->google_code                          = 'et';
		$et->facebook_locale                      = 'et_EE';

		$eu = new GP_Locale();
		$eu->english_name                         = 'Basque';
		$eu->native_name                          = 'Euskara';
		$eu->text_direction                       = 'ltr';
		$eu->lang_code_iso_639_1                  = 'eu';
		$eu->lang_code_iso_639_2                  = 'eus';
		$eu->country_code                         = 'es';
		$eu->wp_locale                            = 'eu';
		$eu->slug                                 = 'eu';
		$eu->nplurals                             = '2';
		$eu->plural_expression                    = 'n != 1';
		$eu->cldr_code                            = 'eu';
		$eu->cldr_nplurals                        = '2';
		$eu->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$eu->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$eu->google_code                          = 'eu';
		$eu->facebook_locale                      = 'eu_ES';

		$fa = new GP_Locale();
		$fa->english_name                         = 'Persian';
		$fa->native_name                          = 'فارسی';
		$fa->text_direction                       = 'rtl';
		$fa->lang_code_iso_639_1                  = 'fa';
		$fa->lang_code_iso_639_2                  = 'fas';
		$fa->wp_locale                            = 'fa_IR';
		$fa->slug                                 = 'fa';
		$fa->nplurals                             = '1';
		$fa->cldr_code                            = 'fa';
		$fa->cldr_nplurals                        = '2';
		$fa->cldr_plural_expressions['one']       = 'i = 0 or n = 1 @integer 0, 1 @decimal 0.0~1.0, 0.00~0.04';
		$fa->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 1.1~2.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$fa->google_code                          = 'fa';
		$fa->facebook_locale                      = 'fa_IR';

		$fa_af = new GP_Locale();
		$fa_af->english_name                      = 'Persian (Afghanistan)';
		$fa_af->native_name                       = '(فارسی (افغانستان';
		$fa_af->text_direction                    = 'rtl';
		$fa_af->lang_code_iso_639_1               = 'fa';
		$fa_af->lang_code_iso_639_2               = 'fas';
		$fa_af->wp_locale                         = 'fa_AF';
		$fa_af->slug                              = 'fa-af';
		$fa_af->nplurals                          = '1';
		$fa_af->cldr_code                         = 'fa';
		$fa_af->cldr_nplurals                     = '2';
		$fa_af->cldr_plural_expressions['one']    = 'i = 0 or n = 1 @integer 0, 1 @decimal 0.0~1.0, 0.00~0.04';
		$fa_af->cldr_plural_expressions['other']  = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 1.1~2.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$fa_af->google_code                       = 'fa';
		$fa_af->variant_root                      = 'fa';
		$fa->variants['fa_af']                    = $fa->english_name;

		$fuc = new GP_Locale();
		$fuc->english_name                        = 'Fulah';
		$fuc->native_name                         = 'Pulaar';
		$fuc->text_direction                      = 'ltr';
		$fuc->lang_code_iso_639_1                 = 'ff';
		$fuc->lang_code_iso_639_2                 = 'fuc';
		$fuc->country_code                        = 'sn';
		$fuc->wp_locale                           = 'fuc';
		$fuc->slug                                = 'fuc';
		$fuc->nplurals                            = '2';
		$fuc->plural_expression                   = 'n!=1';
		$fuc->cldr_code                           = 'ff';
		$fuc->cldr_nplurals                       = '2';
		$fuc->cldr_plural_expressions['one']      = 'i = 0,1 @integer 0, 1 @decimal 0.0~1.5';
		$fuc->cldr_plural_expressions['other']    = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 2.0~3.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$fi = new GP_Locale();
		$fi->english_name                         = 'Finnish';
		$fi->native_name                          = 'Suomi';
		$fi->text_direction                       = 'ltr';
		$fi->lang_code_iso_639_1                  = 'fi';
		$fi->lang_code_iso_639_2                  = 'fin';
		$fi->country_code                         = 'fi';
		$fi->wp_locale                            = 'fi';
		$fi->slug                                 = 'fi';
		$fi->nplurals                             = '2';
		$fi->plural_expression                    = 'n != 1';
		$fi->cldr_code                            = 'fi';
		$fi->cldr_nplurals                        = '2';
		$fi->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$fi->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$fi->google_code                          = 'fi';
		$fi->facebook_locale                      = 'fi_FI';

		$fj = new GP_Locale();
		$fj->english_name                         = 'Fijian';
		$fj->native_name                          = 'Vosa Vakaviti';
		$fj->text_direction                       = 'ltr';
		$fj->lang_code_iso_639_1                  = 'fj';
		$fj->lang_code_iso_639_2                  = 'fij';
		$fj->country_code                         = 'fj';
		$fj->slug                                 = 'fj';
		$fj->nplurals                             = '2';
		$fj->plural_expression                    = 'n != 1';

		$fo = new GP_Locale();
		$fo->english_name                         = 'Faroese';
		$fo->native_name                          = 'Føroyskt';
		$fo->text_direction                       = 'ltr';
		$fo->lang_code_iso_639_1                  = 'fo';
		$fo->lang_code_iso_639_2                  = 'fao';
		$fo->country_code                         = 'fo';
		$fo->wp_locale                            = 'fo';
		$fo->slug                                 = 'fo';
		$fo->nplurals                             = '2';
		$fo->plural_expression                    = 'n != 1';
		$fo->cldr_code                            = 'fo';
		$fo->cldr_nplurals                        = '2';
		$fo->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$fo->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$fo->facebook_locale                      = 'fo_FO';

		$fr = new GP_Locale();
		$fr->english_name                         = 'French (France)';
		$fr->native_name                          = 'Français';
		$fr->text_direction                       = 'ltr';
		$fr->lang_code_iso_639_1                  = 'fr';
		$fr->country_code                         = 'fr';
		$fr->wp_locale                            = 'fr_FR';
		$fr->slug                                 = 'fr';
		$fr->nplurals                             = '2';
		$fr->plural_expression                    = 'n > 1';
		$fr->cldr_code                            = 'fr';
		$fr->cldr_nplurals                        = '2';
		$fr->cldr_plural_expressions['one']       = 'i = 0,1 @integer 0, 1 @decimal 0.0~1.5';
		$fr->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 2.0~3.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$fr->google_code                          = 'fr';
		$fr->facebook_locale                      = 'fr_FR';

		$fr_be = new GP_Locale();
		$fr_be->english_name                      = 'French (Belgium)';
		$fr_be->native_name                       = 'Français de Belgique';
		$fr_be->text_direction                    = 'ltr';
		$fr_be->lang_code_iso_639_1               = 'fr';
		$fr_be->lang_code_iso_639_2               = 'fra';
		$fr_be->country_code                      = 'be';
		$fr_be->wp_locale                         = 'fr_BE';
		$fr_be->slug                              = 'fr-be';
		$fr_be->nplurals                          = '2';
		$fr_be->plural_expression                 = 'n != 1';
		$fr_be->cldr_code                         = 'fr';
		$fr_be->cldr_nplurals                     = '2';
		$fr_be->cldr_plural_expressions['one']    = 'i = 0,1 @integer 0, 1 @decimal 0.0~1.5';
		$fr_be->cldr_plural_expressions['other']  = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 2.0~3.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$fr_be->variant_root                      = 'fr';
		$fr->variants['fr_be']                    = $fr->english_name;

		$fr_ca = new GP_Locale();
		$fr_ca->english_name                      = 'French (Canada)';
		$fr_ca->native_name                       = 'Français du Canada';
		$fr_ca->text_direction                    = 'ltr';
		$fr_ca->lang_code_iso_639_1               = 'fr';
		$fr_ca->lang_code_iso_639_2               = 'fra';
		$fr_ca->country_code                      = 'ca';
		$fr_ca->wp_locale                         = 'fr_CA';
		$fr_ca->slug                              = 'fr-ca';
		$fr_ca->nplurals                          = '2';
		$fr_ca->plural_expression                 = 'n != 1';
		$fr_ca->cldr_code                         = 'fr';
		$fr_ca->cldr_nplurals                     = '2';
		$fr_ca->cldr_plural_expressions['one']    = 'i = 0,1 @integer 0, 1 @decimal 0.0~1.5';
		$fr_ca->cldr_plural_expressions['other']  = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 2.0~3.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$fr_ca->facebook_locale                   = 'fr_CA';
		$fr_ca->variant_root                      = 'fr';
		$fr->variants['fr_ca']                    = $fr->english_name;

		$fr_ch = new GP_Locale();
		$fr_ch->english_name                      = 'French (Switzerland)';
		$fr_ch->native_name                       = 'Français de Suisse';
		$fr_ch->text_direction                    = 'ltr';
		$fr_ch->lang_code_iso_639_1               = 'fr';
		$fr_ch->lang_code_iso_639_2               = 'fra';
		$fr_ch->country_code                      = 'ch';
		$fr_ch->slug                              = 'fr-ch';
		$fr_ch->nplurals                          = '2';
		$fr_ch->plural_expression                 = 'n != 1';
		$fr_ch->cldr_code                         = 'fr';
		$fr_ch->cldr_nplurals                     = '2';
		$fr_ch->cldr_plural_expressions['one']    = 'i = 0,1 @integer 0, 1 @decimal 0.0~1.5';
		$fr_ch->cldr_plural_expressions['other']  = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 2.0~3.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$fr_ch->variant_root                      = 'fr';
		$fr->variants['fr_ch']                    = $fr->english_name;

		$frp = new GP_Locale();
		$frp->english_name                        = 'Arpitan';
		$frp->native_name                         = 'Arpitan';
		$frp->text_direction                      = 'ltr';
		$frp->lang_code_iso_639_3                 = 'frp';
		$frp->country_code                        = 'fr';
		$frp->wp_locale                           = 'frp';
		$frp->slug                                = 'frp';
		$frp->nplurals                            = '2';
		$frp->plural_expression                   = 'n > 1';

		$fur = new GP_Locale();
		$fur->english_name                        = 'Friulian';
		$fur->native_name                         = 'Friulian';
		$fur->text_direction                      = 'ltr';
		$fur->lang_code_iso_639_2                 = 'fur';
		$fur->lang_code_iso_639_3                 = 'fur';
		$fur->country_code                        = 'it';
		$fur->wp_locale                           = 'fur';
		$fur->slug                                = 'fur';
		$fur->nplurals                            = '2';
		$fur->plural_expression                   = 'n != 1';
		$fur->cldr_code                           = 'fur';
		$fur->cldr_nplurals                       = '2';
		$fur->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$fur->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$fy = new GP_Locale();
		$fy->english_name                         = 'Frisian';
		$fy->native_name                          = 'Frysk';
		$fy->text_direction                       = 'ltr';
		$fy->lang_code_iso_639_1                  = 'fy';
		$fy->lang_code_iso_639_2                  = 'fry';
		$fy->country_code                         = 'nl';
		$fy->wp_locale                            = 'fy';
		$fy->slug                                 = 'fy';
		$fy->nplurals                             = '2';
		$fy->plural_expression                    = 'n != 1';
		$fy->cldr_code                            = 'fy';
		$fy->cldr_nplurals                        = '2';
		$fy->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$fy->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$fy->facebook_locale                      = 'fy_NL';

		$ga = new GP_Locale();
		$ga->english_name                         = 'Irish';
		$ga->native_name                          = 'Gaelige';
		$ga->text_direction                       = 'ltr';
		$ga->lang_code_iso_639_1                  = 'ga';
		$ga->lang_code_iso_639_2                  = 'gle';
		$ga->country_code                         = 'ie';
		$ga->wp_locale                            = 'ga';
		$ga->slug                                 = 'ga';
		$ga->nplurals                             = '5';
		$ga->plural_expression                    = 'n==1 ? 0 : n==2 ? 1 : n<7 ? 2 : n<11 ? 3 : 4';
		$ga->cldr_code                            = 'ga';
		$ga->cldr_nplurals                        = '5';
		$ga->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ga->cldr_plural_expressions['two']       = 'n = 2 @integer 2 @decimal 2.0, 2.00, 2.000, 2.0000';
		$ga->cldr_plural_expressions['few']       = 'n = 3..6 @integer 3~6 @decimal 3.0, 4.0, 5.0, 6.0, 3.00, 4.00, 5.00, 6.00, 3.000, 4.000, 5.000, 6.000, 3.0000, 4.0000, 5.0000, 6.0000';
		$ga->cldr_plural_expressions['many']      = 'n = 7..10 @integer 7~10 @decimal 7.0, 8.0, 9.0, 10.0, 7.00, 8.00, 9.00, 10.00, 7.000, 8.000, 9.000, 10.000, 7.0000, 8.0000, 9.0000, 10.0000';
		$ga->cldr_plural_expressions['other']     = ' @integer 0, 11~25, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.1, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ga->google_code                          = 'ga';
		$ga->facebook_locale                      = 'ga_IE';

		$gd = new GP_Locale();
		$gd->english_name                         = 'Scottish Gaelic';
		$gd->native_name                          = 'Gàidhlig';
		$gd->text_direction                       = 'ltr';
		$gd->lang_code_iso_639_1                  = 'gd';
		$gd->lang_code_iso_639_2                  = 'gla';
		$gd->lang_code_iso_639_3                  = 'gla';
		$gd->country_code                         = 'gb';
		$gd->wp_locale                            = 'gd';
		$gd->slug                                 = 'gd';
		$gd->nplurals                             = '4';
		$gd->plural_expression                    = '(n==1 || n==11) ? 0 : (n==2 || n==12) ? 1 : (n > 2 && n < 20) ? 2 : 3';
		$gd->cldr_code                            = 'gd';
		$gd->cldr_nplurals                        = '4';
		$gd->cldr_plural_expressions['one']       = 'n = 1,11 @integer 1, 11 @decimal 1.0, 11.0, 1.00, 11.00, 1.000, 11.000, 1.0000';
		$gd->cldr_plural_expressions['two']       = 'n = 2,12 @integer 2, 12 @decimal 2.0, 12.0, 2.00, 12.00, 2.000, 12.000, 2.0000';
		$gd->cldr_plural_expressions['few']       = 'n = 3..10,13..19 @integer 3~10, 13~19 @decimal 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 19.0, 3.00';
		$gd->cldr_plural_expressions['other']     = ' @integer 0, 20~34, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.1, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$gd->google_code                          = 'gd';

		$gl = new GP_Locale();
		$gl->english_name                         = 'Galician';
		$gl->native_name                          = 'Galego';
		$gl->text_direction                       = 'ltr';
		$gl->lang_code_iso_639_1                  = 'gl';
		$gl->lang_code_iso_639_2                  = 'glg';
		$gl->country_code                         = 'es';
		$gl->wp_locale                            = 'gl_ES';
		$gl->slug                                 = 'gl';
		$gl->nplurals                             = '2';
		$gl->plural_expression                    = 'n != 1';
		$gl->cldr_code                            = 'gl';
		$gl->cldr_nplurals                        = '2';
		$gl->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$gl->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$gl->google_code                          = 'gl';
		$gl->facebook_locale                      = 'gl_ES';

		$gn = new GP_Locale();
		$gn->english_name                         = 'Guaraní';
		$gn->native_name                          = 'Avañe\'ẽ';
		$gn->text_direction                       = 'ltr';
		$gn->lang_code_iso_639_1                  = 'gn';
		$gn->lang_code_iso_639_2                  = 'grn';
		$gn->wp_locale                            = 'gn';
		$gn->slug                                 = 'gn';
		$gn->nplurals                             = '2';
		$gn->plural_expression                    = 'n != 1';

		$gsw = new GP_Locale();
		$gsw->english_name                        = 'Swiss German';
		$gsw->native_name                         = 'Schwyzerdütsch';
		$gsw->text_direction                      = 'ltr';
		$gsw->lang_code_iso_639_2                 = 'gsw';
		$gsw->lang_code_iso_639_3                 = 'gsw';
		$gsw->country_code                        = 'ch';
		$gsw->wp_locale                           = 'gsw';
		$gsw->slug                                = 'gsw';
		$gsw->nplurals                            = '2';
		$gsw->plural_expression                   = 'n != 1';
		$gsw->cldr_code                           = 'gsw';
		$gsw->cldr_nplurals                       = '2';
		$gsw->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$gsw->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$gsw->variant_root                        = 'de';
		$de->variants['gsw']                    = $de->english_name;

		$gu = new GP_Locale();
		$gu->english_name                         = 'Gujarati';
		$gu->native_name                          = 'ગુજરાતી';
		$gu->text_direction                       = 'ltr';
		$gu->lang_code_iso_639_1                  = 'gu';
		$gu->lang_code_iso_639_2                  = 'guj';
		$gu->wp_locale                            = 'gu';
		$gu->slug                                 = 'gu';
		$gu->nplurals                             = '2';
		$gu->plural_expression                    = 'n != 1';
		$gu->cldr_code                            = 'gu';
		$gu->cldr_nplurals                        = '2';
		$gu->cldr_plural_expressions['one']       = 'i = 0 or n = 1 @integer 0, 1 @decimal 0.0~1.0, 0.00~0.04';
		$gu->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 1.1~2.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$gu->google_code                          = 'gu';
		$gu->facebook_locale                      = 'gu_IN';

		$ha = new GP_Locale();
		$ha->english_name                         = 'Hausa (Arabic)';
		$ha->native_name                          = 'هَوُسَ';
		$ha->text_direction                       = 'rtl';
		$ha->lang_code_iso_639_1                  = 'ha';
		$ha->lang_code_iso_639_2                  = 'hau';
		$ha->slug                                 = 'ha';
		$ha->nplurals                             = '2';
		$ha->plural_expression                    = 'n != 1';
		$ha->cldr_code                            = 'ha';
		$ha->cldr_nplurals                        = '2';
		$ha->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ha->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ha->google_code                          = 'ha';

		$hat = new GP_Locale();
		$hat->english_name                        = 'Haitian Creole';
		$hat->native_name                         = 'Kreyol ayisyen';
		$hat->text_direction                      = 'ltr';
		$hat->lang_code_iso_639_1                 = 'ht';
		$hat->lang_code_iso_639_2                 = 'hat';
		$hat->lang_code_iso_639_3                 = 'hat';
		$hat->country_code                        = 'ht';
		$hat->wp_locale                           = 'hat';
		$hat->slug                                = 'hat';
		$hat->nplurals                            = '2';
		$hat->plural_expression                   = 'n != 1';

		$hau = new GP_Locale();
		$hau->english_name                        = 'Hausa';
		$hau->native_name                         = 'Harshen Hausa';
		$hau->text_direction                      = 'ltr';
		$hau->lang_code_iso_639_1                 = 'ha';
		$hau->lang_code_iso_639_2                 = 'hau';
		$hau->lang_code_iso_639_3                 = 'hau';
		$hau->country_code                        = 'ng';
		$hau->wp_locale                           = 'hau';
		$hau->slug                                = 'hau';
		$hau->nplurals                            = '2';
		$hau->plural_expression                   = 'n != 1';
		$hau->cldr_code                           = 'ha';
		$hau->cldr_nplurals                       = '2';
		$hau->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$hau->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$hau->google_code                         = 'ha';
		$hau->facebook_locale                     = 'ha_NG';

		$haw = new GP_Locale();
		$haw->english_name                        = 'Hawaiian';
		$haw->native_name                         = 'Ōlelo Hawaiʻi';
		$haw->text_direction                      = 'ltr';
		$haw->lang_code_iso_639_2                 = 'haw';
		$haw->country_code                        = 'us';
		$haw->wp_locale                           = 'haw_US';
		$haw->slug                                = 'haw';
		$haw->nplurals                            = '2';
		$haw->plural_expression                   = 'n != 1';
		$haw->cldr_code                           = 'haw';
		$haw->cldr_nplurals                       = '2';
		$haw->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$haw->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$haz = new GP_Locale();
		$haz->english_name                        = 'Hazaragi';
		$haz->native_name                         = 'هزاره گی';
		$haz->text_direction                      = 'rtl';
		$haz->lang_code_iso_639_3                 = 'haz';
		$haz->country_code                        = 'af';
		$haz->wp_locale                           = 'haz';
		$haz->slug                                = 'haz';
		$haz->nplurals                            = '2';
		$haz->plural_expression                   = 'n != 1';

		$he = new GP_Locale();
		$he->english_name                         = 'Hebrew';
		$he->native_name                          = 'עִבְרִית';
		$he->text_direction                       = 'rtl';
		$he->lang_code_iso_639_1                  = 'he';
		$he->country_code                         = 'il';
		$he->wp_locale                            = 'he_IL';
		$he->slug                                 = 'he';
		$he->nplurals                             = '2';
		$he->plural_expression                    = 'n != 1';
		$he->cldr_code                            = 'he';
		$he->cldr_nplurals                        = '4';
		$he->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$he->cldr_plural_expressions['two']       = 'i = 2 and v = 0 @integer 2';
		$he->cldr_plural_expressions['many']      = 'v = 0 and n != 0..10 and n % 10 = 0 @integer 20, 30, 40, 50, 60, 70, 80, 90, 100, 1000, 10000, 100000, 1000000, …';
		$he->cldr_plural_expressions['other']     = ' @integer 0, 3~17, 101, 1001, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$he->google_code                          = 'iw';
		$he->facebook_locale                      = 'he_IL';

		$hi = new GP_Locale();
		$hi->english_name                         = 'Hindi';
		$hi->native_name                          = 'हिन्दी';
		$hi->text_direction                       = 'ltr';
		$hi->lang_code_iso_639_1                  = 'hi';
		$hi->lang_code_iso_639_2                  = 'hin';
		$hi->country_code                         = 'in';
		$hi->wp_locale                            = 'hi_IN';
		$hi->slug                                 = 'hi';
		$hi->nplurals                             = '2';
		$hi->plural_expression                    = 'n != 1';
		$hi->cldr_code                            = 'hi';
		$hi->cldr_nplurals                        = '2';
		$hi->cldr_plural_expressions['one']       = 'i = 0 or n = 1 @integer 0, 1 @decimal 0.0~1.0, 0.00~0.04';
		$hi->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 1.1~2.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$hi->google_code                          = 'hi';
		$hi->facebook_locale                      = 'hi_IN';

		$hr = new GP_Locale();
		$hr->english_name                         = 'Croatian';
		$hr->native_name                          = 'Hrvatski';
		$hr->text_direction                       = 'ltr';
		$hr->lang_code_iso_639_1                  = 'hr';
		$hr->lang_code_iso_639_2                  = 'hrv';
		$hr->country_code                         = 'hr';
		$hr->wp_locale                            = 'hr';
		$hr->slug                                 = 'hr';
		$hr->nplurals                             = '3';
		$hr->plural_expression                    = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';
		$hr->cldr_code                            = 'hr';
		$hr->cldr_nplurals                        = '3';
		$hr->cldr_plural_expressions['one']       = 'v = 0 and i % 10 = 1 and i % 100 != 11 or f % 10 = 1 and f % 100 != 11 @integer 1, 21, 31, 41, 51, 61, 71, 81, 101, 1001, … @decimal 0.1, 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 10.1, 100.1, 1000.1, …';
		$hr->cldr_plural_expressions['few']       = 'v = 0 and i % 10 = 2..4 and i % 100 != 12..14 or f % 10 = 2..4 and f % 100 != 12..14 @integer 2~4, 22~24, 32~34, 42~44, 52~54, 62, 102, 1002, … @decimal 0.2~0.4, 1.2~1.4, 2.2~2.4, 3.2~3.4, 4.2~4.4, 5.2, 10.2, 100.2, 1000.2, …';
		$hr->cldr_plural_expressions['other']     = ' @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0, 0.5~1.0, 1.5~2.0, 2.5~2.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$hr->google_code                          = 'hr';
		$hr->facebook_locale                      = 'hr_HR';

		$hu = new GP_Locale();
		$hu->english_name                         = 'Hungarian';
		$hu->native_name                          = 'Magyar';
		$hu->text_direction                       = 'ltr';
		$hu->lang_code_iso_639_1                  = 'hu';
		$hu->lang_code_iso_639_2                  = 'hun';
		$hu->country_code                         = 'hu';
		$hu->wp_locale                            = 'hu_HU';
		$hu->slug                                 = 'hu';
		$hu->nplurals                             = '2';
		$hu->plural_expression                    = 'n != 1';
		$hu->cldr_code                            = 'hu';
		$hu->cldr_nplurals                        = '2';
		$hu->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$hu->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$hu->google_code                          = 'hu';
		$hu->facebook_locale                      = 'hu_HU';

		$hy = new GP_Locale();
		$hy->english_name                         = 'Armenian';
		$hy->native_name                          = 'Հայերեն';
		$hy->text_direction                       = 'ltr';
		$hy->lang_code_iso_639_1                  = 'hy';
		$hy->lang_code_iso_639_2                  = 'hye';
		$hy->country_code                         = 'am';
		$hy->wp_locale                            = 'hy';
		$hy->slug                                 = 'hy';
		$hy->nplurals                             = '2';
		$hy->plural_expression                    = 'n != 1';
		$hy->cldr_code                            = 'hy';
		$hy->cldr_nplurals                        = '2';
		$hy->cldr_plural_expressions['one']       = 'i = 0,1 @integer 0, 1 @decimal 0.0~1.5';
		$hy->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 2.0~3.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$hy->google_code                          = 'hy';
		$hy->facebook_locale                      = 'hy_AM';

		$ia = new GP_Locale();
		$ia->english_name                         = 'Interlingua';
		$ia->native_name                          = 'Interlingua';
		$ia->text_direction                       = 'ltr';
		$ia->lang_code_iso_639_1                  = 'ia';
		$ia->lang_code_iso_639_2                  = 'ina';
		$ia->slug                                 = 'ia';
		$ia->nplurals                             = '2';
		$ia->plural_expression                    = 'n != 1';

		$id = new GP_Locale();
		$id->english_name                         = 'Indonesian';
		$id->native_name                          = 'Bahasa Indonesia';
		$id->text_direction                       = 'ltr';
		$id->lang_code_iso_639_1                  = 'id';
		$id->lang_code_iso_639_2                  = 'ind';
		$id->country_code                         = 'id';
		$id->wp_locale                            = 'id_ID';
		$id->slug                                 = 'id';
		$id->nplurals                             = '2';
		$id->plural_expression                    = 'n > 1';
		$id->cldr_code                            = 'id';
		$id->cldr_nplurals                        = '1';
		$id->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$id->google_code                          = 'id';
		$id->facebook_locale                      = 'id_ID';

		$ido = new GP_Locale();
		$ido->english_name                        = 'Ido';
		$ido->native_name                         = 'Ido';
		$ido->text_direction                      = 'ltr';
		$ido->lang_code_iso_639_1                 = 'io';
		$ido->lang_code_iso_639_2                 = 'ido';
		$ido->lang_code_iso_639_3                 = 'ido';
		$ido->wp_locale                           = 'ido';
		$ido->slug                                = 'ido';
		$ido->nplurals                            = '2';
		$ido->plural_expression                   = 'n != 1';

		$ike = new GP_Locale();
		$ike->english_name                        = 'Inuktitut';
		$ike->native_name                         = 'ᐃᓄᒃᑎᑐᑦ';
		$ike->text_direction                      = 'ltr';
		$ike->lang_code_iso_639_1                 = 'iu';
		$ike->lang_code_iso_639_2                 = 'iku';
		$ike->country_code                        = 'ca';
		$ike->slug                                = 'ike';
		$ike->nplurals                            = '2';
		$ike->plural_expression                   = 'n != 1';
		$ike->cldr_code                           = 'iu';
		$ike->cldr_nplurals                       = '3';
		$ike->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ike->cldr_plural_expressions['two']      = 'n = 2 @integer 2 @decimal 2.0, 2.00, 2.000, 2.0000';
		$ike->cldr_plural_expressions['other']    = ' @integer 0, 3~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$ilo = new GP_Locale();
		$ilo->english_name                        = 'Iloko';
		$ilo->native_name                         = 'Pagsasao nga Iloko';
		$ilo->text_direction                      = 'ltr';
		$ilo->lang_code_iso_639_2                 = 'ilo';
		$ilo->country_code                        = 'ph';
		$ilo->slug                                = 'ilo';
		$ilo->nplurals                            = '2';
		$ilo->plural_expression                   = 'n != 1';

		$is = new GP_Locale();
		$is->english_name                         = 'Icelandic';
		$is->native_name                          = 'Íslenska';
		$is->text_direction                       = 'ltr';
		$is->lang_code_iso_639_1                  = 'is';
		$is->lang_code_iso_639_2                  = 'isl';
		$is->country_code                         = 'is';
		$is->wp_locale                            = 'is_IS';
		$is->slug                                 = 'is';
		$is->nplurals                             = '2';
		$is->plural_expression                    = '(n % 100 != 1 && n % 100 != 21 && n % 100 != 31 && n % 100 != 41 && n % 100 != 51 && n % 100 != 61 && n % 100 != 71 && n % 100 != 81 && n % 100 != 91)';
		$is->cldr_code                            = 'is';
		$is->cldr_nplurals                        = '2';
		$is->cldr_plural_expressions['one']       = 't = 0 and i % 10 = 1 and i % 100 != 11 or t != 0 @integer 1, 21, 31, 41, 51, 61, 71, 81, 101, 1001, … @decimal 0.1~1.6, 10.1, 100.1, 1000.1, …';
		$is->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$is->google_code                          = 'is';
		$is->facebook_locale                      = 'is_IS';

		$it = new GP_Locale();
		$it->english_name                         = 'Italian';
		$it->native_name                          = 'Italiano';
		$it->text_direction                       = 'ltr';
		$it->lang_code_iso_639_1                  = 'it';
		$it->lang_code_iso_639_2                  = 'ita';
		$it->country_code                         = 'it';
		$it->wp_locale                            = 'it_IT';
		$it->slug                                 = 'it';
		$it->nplurals                             = '2';
		$it->plural_expression                    = 'n != 1';
		$it->cldr_code                            = 'it';
		$it->cldr_nplurals                        = '2';
		$it->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$it->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$it->google_code                          = 'it';
		$it->facebook_locale                      = 'it_IT';

		$ja = new GP_Locale();
		$ja->english_name                         = 'Japanese';
		$ja->native_name                          = '日本語';
		$ja->text_direction                       = 'ltr';
		$ja->lang_code_iso_639_1                  = 'ja';
		$ja->country_code                         = 'jp';
		$ja->wp_locale                            = 'ja';
		$ja->slug                                 = 'ja';
		$ja->nplurals                             = '1';
		$ja->cldr_code                            = 'ja';
		$ja->cldr_nplurals                        = '1';
		$ja->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ja->google_code                          = 'ja';
		$ja->facebook_locale                      = 'ja_JP';

		$jv = new GP_Locale();
		$jv->english_name                         = 'Javanese';
		$jv->native_name                          = 'Basa Jawa';
		$jv->text_direction                       = 'ltr';
		$jv->lang_code_iso_639_1                  = 'jv';
		$jv->lang_code_iso_639_2                  = 'jav';
		$jv->country_code                         = 'id';
		$jv->wp_locale                            = 'jv_ID';
		$jv->slug                                 = 'jv';
		$jv->nplurals                             = '2';
		$jv->plural_expression                    = 'n != 1';
		$jv->cldr_code                            = 'jv';
		$jv->cldr_nplurals                        = '1';
		$jv->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$jv->google_code                          = 'jw';
		$jv->facebook_locale                      = 'jv_ID';

		$ka = new GP_Locale();
		$ka->english_name                         = 'Georgian';
		$ka->native_name                          = 'ქართული';
		$ka->text_direction                       = 'ltr';
		$ka->lang_code_iso_639_1                  = 'ka';
		$ka->lang_code_iso_639_2                  = 'kat';
		$ka->country_code                         = 'ge';
		$ka->wp_locale                            = 'ka_GE';
		$ka->slug                                 = 'ka';
		$ka->nplurals                             = '1';
		$ka->cldr_code                            = 'ka';
		$ka->cldr_nplurals                        = '2';
		$ka->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ka->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ka->google_code                          = 'ka';
		$ka->facebook_locale                      = 'ka_GE';

		$kab = new GP_Locale();
		$kab->english_name                        = 'Kabyle';
		$kab->native_name                         = 'Taqbaylit';
		$kab->text_direction                      = 'ltr';
		$kab->lang_code_iso_639_2                 = 'kab';
		$kab->lang_code_iso_639_3                 = 'kab';
		$kab->country_code                        = 'dz';
		$kab->wp_locale                           = 'kab';
		$kab->slug                                = 'kab';
		$kab->nplurals                            = '2';
		$kab->plural_expression                   = '(n > 1)';
		$kab->cldr_code                           = 'kab';
		$kab->cldr_nplurals                       = '2';
		$kab->cldr_plural_expressions['one']      = 'i = 0,1 @integer 0, 1 @decimal 0.0~1.5';
		$kab->cldr_plural_expressions['other']    = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 2.0~3.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$kal = new GP_Locale();
		$kal->english_name                        = 'Greenlandic';
		$kal->native_name                         = 'Kalaallisut';
		$kal->text_direction                      = 'ltr';
		$kal->lang_code_iso_639_1                 = 'kl';
		$kal->lang_code_iso_639_2                 = 'kal';
		$kal->lang_code_iso_639_3                 = 'kal';
		$kal->country_code                        = 'gl';
		$kal->wp_locale                           = 'kal';
		$kal->slug                                = 'kal';
		$kal->nplurals                            = '2';
		$kal->plural_expression                   = 'n != 1';
		$kal->cldr_code                           = 'kl';
		$kal->cldr_nplurals                       = '2';
		$kal->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$kal->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$kin = new GP_Locale();
		$kin->english_name                        = 'Kinyarwanda';
		$kin->native_name                         = 'Ikinyarwanda';
		$kin->text_direction                      = 'ltr';
		$kin->lang_code_iso_639_1                 = 'rw';
		$kin->lang_code_iso_639_2                 = 'kin';
		$kin->lang_code_iso_639_3                 = 'kin';
		$kin->country_code                        = 'rw';
		$kin->wp_locale                           = 'kin';
		$kin->slug                                = 'kin';
		$kin->nplurals                            = '2';
		$kin->plural_expression                   = 'n != 1';
		$kin->facebook_locale                     = 'rw_RW';

		$kk = new GP_Locale();
		$kk->english_name                         = 'Kazakh';
		$kk->native_name                          = 'Қазақ тілі';
		$kk->text_direction                       = 'ltr';
		$kk->lang_code_iso_639_1                  = 'kk';
		$kk->lang_code_iso_639_2                  = 'kaz';
		$kk->country_code                         = 'kz';
		$kk->wp_locale                            = 'kk';
		$kk->slug                                 = 'kk';
		$kk->nplurals                             = '2';
		$kk->plural_expression                    = 'n != 1';
		$kk->cldr_code                            = 'kk';
		$kk->cldr_nplurals                        = '2';
		$kk->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$kk->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$kk->google_code                          = 'kk';
		$kk->facebook_locale                      = 'kk_KZ';

		$km = new GP_Locale();
		$km->english_name                         = 'Khmer';
		$km->native_name                          = 'ភាសាខ្មែរ';
		$km->text_direction                       = 'ltr';
		$km->lang_code_iso_639_1                  = 'km';
		$km->lang_code_iso_639_2                  = 'khm';
		$km->country_code                         = 'kh';
		$km->wp_locale                            = 'km';
		$km->slug                                 = 'km';
		$km->nplurals                             = '1';
		$km->cldr_code                            = 'km';
		$km->cldr_nplurals                        = '1';
		$km->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$km->google_code                          = 'km';
		$km->facebook_locale                      = 'km_KH';

		$kmr = new GP_Locale();
		$kmr->english_name                        = 'Kurdish (Kurmanji)';
		$kmr->native_name                         = 'Kurdî';
		$kmr->text_direction                      = 'ltr';
		$kmr->lang_code_iso_639_1                 = 'ku';
		$kmr->lang_code_iso_639_3                 = 'kmr';
		$kmr->country_code                        = 'tr';
		$kmr->slug                                = 'kmr';
		$kmr->nplurals                            = '2';
		$kmr->plural_expression                   = 'n != 1';
		$kmr->cldr_code                           = 'ku';
		$kmr->cldr_nplurals                       = '2';
		$kmr->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$kmr->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$kmr->facebook_locale                     = 'ku_TR';

		$kn = new GP_Locale();
		$kn->english_name                         = 'Kannada';
		$kn->native_name                          = 'ಕನ್ನಡ';
		$kn->text_direction                       = 'ltr';
		$kn->lang_code_iso_639_1                  = 'kn';
		$kn->lang_code_iso_639_2                  = 'kan';
		$kn->country_code                         = 'in';
		$kn->wp_locale                            = 'kn';
		$kn->slug                                 = 'kn';
		$kn->nplurals                             = '2';
		$kn->plural_expression                    = 'n != 1';
		$kn->cldr_code                            = 'kn';
		$kn->cldr_nplurals                        = '2';
		$kn->cldr_plural_expressions['one']       = 'i = 0 or n = 1 @integer 0, 1 @decimal 0.0~1.0, 0.00~0.04';
		$kn->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 1.1~2.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$kn->google_code                          = 'kn';
		$kn->facebook_locale                      = 'kn_IN';

		$ko = new GP_Locale();
		$ko->english_name                         = 'Korean';
		$ko->native_name                          = '한국어';
		$ko->text_direction                       = 'ltr';
		$ko->lang_code_iso_639_1                  = 'ko';
		$ko->lang_code_iso_639_2                  = 'kor';
		$ko->country_code                         = 'kr';
		$ko->wp_locale                            = 'ko_KR';
		$ko->slug                                 = 'ko';
		$ko->nplurals                             = '1';
		$ko->cldr_code                            = 'ko';
		$ko->cldr_nplurals                        = '1';
		$ko->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ko->google_code                          = 'ko';
		$ko->facebook_locale                      = 'ko_KR';

		$ks = new GP_Locale();
		$ks->english_name                         = 'Kashmiri';
		$ks->native_name                          = 'कश्मीरी';
		$ks->text_direction                       = 'ltr';
		$ks->lang_code_iso_639_1                  = 'ks';
		$ks->lang_code_iso_639_2                  = 'kas';
		$ks->slug                                 = 'ks';
		$ks->nplurals                             = '2';
		$ks->plural_expression                    = 'n != 1';
		$ks->cldr_code                            = 'ks';
		$ks->cldr_nplurals                        = '2';
		$ks->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ks->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$kir = new GP_Locale();
		$kir->english_name                        = 'Kyrgyz';
		$kir->native_name                         = 'Кыргызча';
		$kir->text_direction                      = 'ltr';
		$kir->lang_code_iso_639_1                 = 'ky';
		$kir->lang_code_iso_639_2                 = 'kir';
		$kir->lang_code_iso_639_3                 = 'kir';
		$kir->country_code                        = 'kg';
		$kir->wp_locale                           = 'kir';
		$kir->slug                                = 'kir';
		$kir->nplurals                            = '1';
		$kir->cldr_code                           = 'ky';
		$kir->cldr_nplurals                       = '2';
		$kir->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$kir->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$kir->google_code                         = 'ky';

		$la = new GP_Locale();
		$la->english_name                         = 'Latin';
		$la->native_name                          = 'Latine';
		$la->text_direction                       = 'ltr';
		$la->lang_code_iso_639_1                  = 'la';
		$la->lang_code_iso_639_2                  = 'lat';
		$la->slug                                 = 'la';
		$la->nplurals                             = '2';
		$la->plural_expression                    = 'n != 1';
		$la->google_code                          = 'la';
		$la->facebook_locale                      = 'la_VA';

		$lb = new GP_Locale();
		$lb->english_name                         = 'Luxembourgish';
		$lb->native_name                          = 'Lëtzebuergesch';
		$lb->text_direction                       = 'ltr';
		$lb->lang_code_iso_639_1                  = 'lb';
		$lb->country_code                         = 'lu';
		$lb->wp_locale                            = 'lb_LU';
		$lb->slug                                 = 'lb';
		$lb->nplurals                             = '2';
		$lb->plural_expression                    = 'n != 1';
		$lb->cldr_code                            = 'lb';
		$lb->cldr_nplurals                        = '2';
		$lb->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$lb->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$li = new GP_Locale();
		$li->english_name                         = 'Limburgish';
		$li->native_name                          = 'Limburgs';
		$li->text_direction                       = 'ltr';
		$li->lang_code_iso_639_1                  = 'li';
		$li->lang_code_iso_639_2                  = 'lim';
		$li->lang_code_iso_639_3                  = 'lim';
		$li->country_code                         = 'nl';
		$li->wp_locale                            = 'li';
		$li->slug                                 = 'li';
		$li->nplurals                             = '2';
		$li->plural_expression                    = 'n != 1';
		$li->facebook_locale                      = 'li_NL';

		$lin = new GP_Locale();
		$lin->english_name                        = 'Lingala';
		$lin->native_name                         = 'Ngala';
		$lin->text_direction                      = 'ltr';
		$lin->lang_code_iso_639_1                 = 'ln';
		$lin->lang_code_iso_639_2                 = 'lin';
		$lin->country_code                        = 'cd';
		$lin->wp_locale                           = 'lin';
		$lin->slug                                = 'lin';
		$lin->nplurals                            = '2';
		$lin->plural_expression                   = 'n>1';
		$lin->cldr_code                           = 'ln';
		$lin->cldr_nplurals                       = '2';
		$lin->cldr_plural_expressions['one']      = 'n = 0..1 @integer 0, 1 @decimal 0.0, 1.0, 0.00, 1.00, 0.000, 1.000, 0.0000, 1.0000';
		$lin->cldr_plural_expressions['other']    = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$lin->facebook_locale                     = 'ln_CD';

		$lmo = new GP_Locale();
		$lmo->english_name = 'Lombard';
		$lmo->native_name = 'Lombardo';
		$lmo->lang_code_iso_639_3 = 'lmo';
		$lmo->country_code = 'it';
		$lmo->wp_locale = 'lmo';
		$lmo->slug = 'lmo';

		$lo = new GP_Locale();
		$lo->english_name                         = 'Lao';
		$lo->native_name                          = 'ພາສາລາວ';
		$lo->text_direction                       = 'ltr';
		$lo->lang_code_iso_639_1                  = 'lo';
		$lo->lang_code_iso_639_2                  = 'lao';
		$lo->country_code                         = 'LA';
		$lo->wp_locale                            = 'lo';
		$lo->slug                                 = 'lo';
		$lo->nplurals                             = '1';
		$lo->cldr_code                            = 'lo';
		$lo->cldr_nplurals                        = '1';
		$lo->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$lo->google_code                          = 'lo';
		$lo->facebook_locale                      = 'lo_LA';

		$lt = new GP_Locale();
		$lt->english_name                         = 'Lithuanian';
		$lt->native_name                          = 'Lietuvių kalba';
		$lt->text_direction                       = 'ltr';
		$lt->lang_code_iso_639_1                  = 'lt';
		$lt->lang_code_iso_639_2                  = 'lit';
		$lt->country_code                         = 'lt';
		$lt->wp_locale                            = 'lt_LT';
		$lt->slug                                 = 'lt';
		$lt->nplurals                             = '3';
		$lt->plural_expression                    = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2)';
		$lt->cldr_code                            = 'lt';
		$lt->cldr_nplurals                        = '4';
		$lt->cldr_plural_expressions['one']       = 'n % 10 = 1 and n % 100 != 11..19 @integer 1, 21, 31, 41, 51, 61, 71, 81, 101, 1001, … @decimal 1.0, 21.0, 31.0, 41.0, 51.0, 61.0, 71.0, 81.0, 101.0, 1001.0, …';
		$lt->cldr_plural_expressions['few']       = 'n % 10 = 2..9 and n % 100 != 11..19 @integer 2~9, 22~29, 102, 1002, … @decimal 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 22.0, 102.0, 1002.0, …';
		$lt->cldr_plural_expressions['many']      = 'f != 0   @decimal 0.1~0.9, 1.1~1.7, 10.1, 100.1, 1000.1, …';
		$lt->cldr_plural_expressions['other']     = ' @integer 0, 10~20, 30, 40, 50, 60, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$lug = new GP_Locale();
		$lug->english_name = 'Luganda';
		$lug->native_name = 'Oluganda';
		$lug->lang_code_iso_639_1 = 'lg';
		$lug->lang_code_iso_639_2 = 'lug';
		$lug->lang_code_iso_639_3 = 'lug';
		$lug->slug = 'lug';

		$lv = new GP_Locale();
		$lv->english_name                         = 'Latvian';
		$lv->native_name                          = 'Latviešu valoda';
		$lv->text_direction                       = 'ltr';
		$lv->lang_code_iso_639_1                  = 'lv';
		$lv->lang_code_iso_639_2                  = 'lav';
		$lv->country_code                         = 'lv';
		$lv->wp_locale                            = 'lv';
		$lv->slug                                 = 'lv';
		$lv->nplurals                             = '3';
		$lv->plural_expression                    = '(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2)';
		$lv->cldr_code                            = 'lv';
		$lv->cldr_nplurals                        = '3';
		$lv->cldr_plural_expressions['zero']      = 'n % 10 = 0 or n % 100 = 11..19 or v = 2 and f % 100 = 11..19 @integer 0, 10~20, 30, 40, 50, 60, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$lv->cldr_plural_expressions['one']       = 'n % 10 = 1 and n % 100 != 11 or v = 2 and f % 10 = 1 and f % 100 != 11 or v != 2 and f % 10 = 1 @integer 1, 21, 31, 41, 51, 61, 71, 81, 101, 1001, … @decimal 0.1, 1.0, 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 10.1, 100.1, 1000.1, …';
		$lv->cldr_plural_expressions['other']     = ' @integer 2~9, 22~29, 102, 1002, … @decimal 0.2~0.9, 1.2~1.9, 10.2, 100.2, 1000.2, …';
		$lv->google_code                          = 'lv';
		$lv->facebook_locale                      = 'lv_LV';

		$me = new GP_Locale();
		$me->english_name                         = 'Montenegrin';
		$me->native_name                          = 'Crnogorski jezik';
		$me->text_direction                       = 'ltr';
		$me->lang_code_iso_639_1                  = 'me';
		$me->country_code                         = 'me';
		$me->wp_locale                            = 'me_ME';
		$me->slug                                 = 'me';
		$me->nplurals                             = '3';
		$me->plural_expression                    = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

		$mfe = new GP_Locale();
		$mfe->english_name = 'Mauritian Creole';
		$mfe->native_name = 'Kreol Morisien';
		$mfe->lang_code_iso_639_3 = 'mfe';
		$mfe->country_code = 'mu';
		$mfe->wp_locale = 'mfe';
		$mfe->slug = 'mfe';
		$mfe->nplurals = 1;
		$mfe->plural_expression = '0';

		$mg = new GP_Locale();
		$mg->english_name                         = 'Malagasy';
		$mg->native_name                          = 'Malagasy';
		$mg->text_direction                       = 'ltr';
		$mg->lang_code_iso_639_1                  = 'mg';
		$mg->lang_code_iso_639_2                  = 'mlg';
		$mg->country_code                         = 'mg';
		$mg->wp_locale                            = 'mg_MG';
		$mg->slug                                 = 'mg';
		$mg->nplurals                             = '2';
		$mg->plural_expression                    = 'n != 1';
		$mg->cldr_code                            = 'mg';
		$mg->cldr_nplurals                        = '2';
		$mg->cldr_plural_expressions['one']       = 'n = 0..1 @integer 0, 1 @decimal 0.0, 1.0, 0.00, 1.00, 0.000, 1.000, 0.0000, 1.0000';
		$mg->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$mg->google_code                          = 'mg';
		$mg->facebook_locale                      = 'mg_MG';

		$mhr = new GP_Locale();
		$mhr->english_name                        = 'Mari (Meadow)';
		$mhr->native_name                         = 'Олык марий';
		$mhr->text_direction                      = 'ltr';
		$mhr->lang_code_iso_639_3                 = 'mhr';
		$mhr->country_code                        = 'ru';
		$mhr->slug                                = 'mhr';
		$mhr->nplurals                            = '2';
		$mhr->plural_expression                   = 'n != 1';

		$mk = new GP_Locale();
		$mk->english_name                         = 'Macedonian';
		$mk->native_name                          = 'Македонски јазик';
		$mk->text_direction                       = 'ltr';
		$mk->lang_code_iso_639_1                  = 'mk';
		$mk->lang_code_iso_639_2                  = 'mkd';
		$mk->country_code                         = 'mk';
		$mk->wp_locale                            = 'mk_MK';
		$mk->slug                                 = 'mk';
		$mk->nplurals                             = '2';
		$mk->plural_expression                    = 'n==1 || n%10==1 ? 0 : 1';
		$mk->cldr_code                            = 'mk';
		$mk->cldr_nplurals                        = '2';
		$mk->cldr_plural_expressions['one']       = 'v = 0 and i % 10 = 1 or f % 10 = 1 @integer 1, 11, 21, 31, 41, 51, 61, 71, 101, 1001, … @decimal 0.1, 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 10.1, 100.1, 1000.1, …';
		$mk->cldr_plural_expressions['other']     = ' @integer 0, 2~10, 12~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0, 0.2~1.0, 1.2~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$mk->google_code                          = 'mk';
		$mk->facebook_locale                      = 'mk_MK';

		$ml = new GP_Locale();
		$ml->english_name                         = 'Malayalam';
		$ml->native_name                          = 'മലയാളം';
		$ml->text_direction                       = 'ltr';
		$ml->lang_code_iso_639_1                  = 'ml';
		$ml->lang_code_iso_639_2                  = 'mal';
		$ml->country_code                         = 'in';
		$ml->wp_locale                            = 'ml_IN';
		$ml->slug                                 = 'ml';
		$ml->nplurals                             = '2';
		$ml->plural_expression                    = 'n != 1';
		$ml->cldr_code                            = 'ml';
		$ml->cldr_nplurals                        = '2';
		$ml->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ml->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ml->google_code                          = 'ml';
		$ml->facebook_locale                      = 'ml_IN';

		$mlt = new GP_Locale();
		$mlt->english_name = 'Maltese';
		$mlt->native_name = 'Malti';
		$mlt->lang_code_iso_639_1 = 'mt';
		$mlt->lang_code_iso_639_2 = 'mlt';
		$mlt->lang_code_iso_639_3 = 'mlt';
		$mlt->country_code = 'mt';
		$mlt->wp_locale = 'mlt';
		$mlt->slug = 'mlt';
		$mlt->nplurals = 4;
		$mlt->plural_expression = '(n==1 ? 0 : n==0 || ( n%100>1 && n%100<11) ? 1 : (n%100>10 && n%100<20 ) ? 2 : 3)';
		$mlt->google_code = 'mt';
		$mlt->facebook_locale = 'mt_MT';

		$mn = new GP_Locale();
		$mn->english_name                         = 'Mongolian';
		$mn->native_name                          = 'Монгол';
		$mn->text_direction                       = 'ltr';
		$mn->lang_code_iso_639_1                  = 'mn';
		$mn->lang_code_iso_639_2                  = 'mon';
		$mn->country_code                         = 'mn';
		$mn->wp_locale                            = 'mn';
		$mn->slug                                 = 'mn';
		$mn->nplurals                             = '2';
		$mn->plural_expression                    = 'n != 1';
		$mn->cldr_code                            = 'mn';
		$mn->cldr_nplurals                        = '2';
		$mn->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$mn->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$mn->google_code                          = 'mn';
		$mn->facebook_locale                      = 'mn_MN';

		$mr = new GP_Locale();
		$mr->english_name                         = 'Marathi';
		$mr->native_name                          = 'मराठी';
		$mr->text_direction                       = 'ltr';
		$mr->lang_code_iso_639_1                  = 'mr';
		$mr->lang_code_iso_639_2                  = 'mar';
		$mr->wp_locale                            = 'mr';
		$mr->slug                                 = 'mr';
		$mr->nplurals                             = '2';
		$mr->plural_expression                    = 'n != 1';
		$mr->cldr_code                            = 'mr';
		$mr->cldr_nplurals                        = '2';
		$mr->cldr_plural_expressions['one']       = 'i = 0 or n = 1 @integer 0, 1 @decimal 0.0~1.0, 0.00~0.04';
		$mr->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 1.1~2.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$mr->google_code                          = 'mr';
		$mr->facebook_locale                      = 'mr_IN';

		$mri = new GP_Locale();
		$mri->english_name                        = 'Maori';
		$mri->native_name                         = 'Te Reo Māori';
		$mri->text_direction                      = 'ltr';
		$mri->lang_code_iso_639_1                 = 'mi';
		$mri->lang_code_iso_639_3                 = 'mri';
		$mri->country_code                        = 'nz';
		$mri->wp_locale                           = 'mri';
		$mri->slug                                = 'mri';
		$mri->nplurals                            = '2';
		$mri->plural_expression                   = '(n > 1)';
		$mri->google_code                         = 'mi';

		$mrj = new GP_Locale();
		$mrj->english_name                        = 'Mari (Hill)';
		$mrj->native_name                         = 'Кырык мары';
		$mrj->text_direction                      = 'ltr';
		$mrj->lang_code_iso_639_3                 = 'mrj';
		$mrj->country_code                        = 'ru';
		$mrj->slug                                = 'mrj';
		$mrj->nplurals                            = '2';
		$mrj->plural_expression                   = 'n != 1';

		$ms = new GP_Locale();
		$ms->english_name                         = 'Malay';
		$ms->native_name                          = 'Bahasa Melayu';
		$ms->text_direction                       = 'ltr';
		$ms->lang_code_iso_639_1                  = 'ms';
		$ms->lang_code_iso_639_2                  = 'msa';
		$ms->wp_locale                            = 'ms_MY';
		$ms->slug                                 = 'ms';
		$ms->nplurals                             = '1';
		$ms->cldr_code                            = 'ms';
		$ms->cldr_nplurals                        = '1';
		$ms->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ms->google_code                          = 'ms';
		$ms->facebook_locale                      = 'ms_MY';

		$mwl = new GP_Locale();
		$mwl->english_name                        = 'Mirandese';
		$mwl->native_name                         = 'Mirandés';
		$mwl->text_direction                      = 'ltr';
		$mwl->lang_code_iso_639_2                 = 'mwl';
		$mwl->slug                                = 'mwl';
		$mwl->nplurals                            = '2';
		$mwl->plural_expression                   = 'n != 1';

		$mya = new GP_Locale();
		$mya->english_name                        = 'Myanmar (Burmese)';
		$mya->native_name                         = 'ဗမာစာ';
		$mya->text_direction                      = 'ltr';
		$mya->lang_code_iso_639_1                 = 'my';
		$mya->lang_code_iso_639_2                 = 'mya';
		$mya->country_code                        = 'mm';
		$mya->wp_locale                           = 'my_MM';
		$mya->slug                                = 'mya';
		$mya->nplurals                            = '2';
		$mya->plural_expression                   = 'n != 1';
		$mya->cldr_code                           = 'my';
		$mya->cldr_nplurals                       = '1';
		$mya->cldr_plural_expressions['other']    = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$mya->google_code                         = 'my';

		$ne = new GP_Locale();
		$ne->english_name                         = 'Nepali';
		$ne->native_name                          = 'नेपाली';
		$ne->text_direction                       = 'ltr';
		$ne->lang_code_iso_639_1                  = 'ne';
		$ne->lang_code_iso_639_2                  = 'nep';
		$ne->country_code                         = 'np';
		$ne->wp_locale                            = 'ne_NP';
		$ne->slug                                 = 'ne';
		$ne->nplurals                             = '2';
		$ne->plural_expression                    = 'n != 1';
		$ne->cldr_code                            = 'ne';
		$ne->cldr_nplurals                        = '2';
		$ne->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ne->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ne->google_code                          = 'ne';
		$ne->facebook_locale                      = 'ne_NP';

		$nb = new GP_Locale();
		$nb->english_name                         = 'Norwegian (Bokmål)';
		$nb->native_name                          = 'Norsk bokmål';
		$nb->text_direction                       = 'ltr';
		$nb->lang_code_iso_639_1                  = 'nb';
		$nb->lang_code_iso_639_2                  = 'nob';
		$nb->country_code                         = 'no';
		$nb->wp_locale                            = 'nb_NO';
		$nb->slug                                 = 'nb';
		$nb->nplurals                             = '2';
		$nb->plural_expression                    = 'n != 1';
		$nb->cldr_code                            = 'nb';
		$nb->cldr_nplurals                        = '2';
		$nb->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$nb->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$nb->google_code                          = 'no';
		$nb->facebook_locale                      = 'nb_NO';

		$nl = new GP_Locale();
		$nl->english_name                         = 'Dutch';
		$nl->native_name                          = 'Nederlands';
		$nl->text_direction                       = 'ltr';
		$nl->lang_code_iso_639_1                  = 'nl';
		$nl->lang_code_iso_639_2                  = 'nld';
		$nl->country_code                         = 'nl';
		$nl->wp_locale                            = 'nl_NL';
		$nl->slug                                 = 'nl';
		$nl->nplurals                             = '2';
		$nl->plural_expression                    = 'n != 1';
		$nl->cldr_code                            = 'nl';
		$nl->cldr_nplurals                        = '2';
		$nl->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$nl->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$nl->google_code                          = 'nl';
		$nl->facebook_locale                      = 'nl_NL';

		$nl_be = new GP_Locale();
		$nl_be->english_name                      = 'Dutch (Belgium)';
		$nl_be->native_name                       = 'Nederlands (België)';
		$nl_be->text_direction                    = 'ltr';
		$nl_be->lang_code_iso_639_1               = 'nl';
		$nl_be->lang_code_iso_639_2               = 'nld';
		$nl_be->country_code                      = 'be';
		$nl_be->wp_locale                         = 'nl_BE';
		$nl_be->slug                              = 'nl-be';
		$nl_be->nplurals                          = '2';
		$nl_be->plural_expression                 = 'n != 1';
		$nl_be->cldr_code                         = 'nl';
		$nl_be->cldr_nplurals                     = '2';
		$nl_be->cldr_plural_expressions['one']    = 'i = 1 and v = 0 @integer 1';
		$nl_be->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$nl_be->google_code                       = 'nl';
		$nl_be->variant_root                      = 'nl';
		$nl->variants['nl_be']                    = $nl->english_name;

		$no = new GP_Locale();
		$no->english_name                         = 'Norwegian';
		$no->native_name                          = 'Norsk';
		$no->text_direction                       = 'ltr';
		$no->lang_code_iso_639_1                  = 'no';
		$no->lang_code_iso_639_2                  = 'nor';
		$no->country_code                         = 'no';
		$no->slug                                 = 'no';
		$no->nplurals                             = '2';
		$no->plural_expression                    = 'n != 1';
		$no->cldr_code                            = 'no';
		$no->cldr_nplurals                        = '2';
		$no->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$no->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$no->google_code                          = 'no';

		$nn = new GP_Locale();
		$nn->english_name                         = 'Norwegian (Nynorsk)';
		$nn->native_name                          = 'Norsk nynorsk';
		$nn->text_direction                       = 'ltr';
		$nn->lang_code_iso_639_1                  = 'nn';
		$nn->lang_code_iso_639_2                  = 'nno';
		$nn->country_code                         = 'no';
		$nn->wp_locale                            = 'nn_NO';
		$nn->slug                                 = 'nn';
		$nn->nplurals                             = '2';
		$nn->plural_expression                    = 'n != 1';
		$nn->cldr_code                            = 'nn';
		$nn->cldr_nplurals                        = '2';
		$nn->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$nn->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$nn->google_code                          = 'no';
		$nn->facebook_locale                      = 'nn_NO';
		$nn->variant_root                         = 'no';
		$no->variants['nn']                    = $no->english_name;

		$oci = new GP_Locale();
		$oci->english_name                        = 'Occitan';
		$oci->native_name                         = 'Occitan';
		$oci->text_direction                      = 'ltr';
		$oci->lang_code_iso_639_1                 = 'oc';
		$oci->lang_code_iso_639_2                 = 'oci';
		$oci->country_code                        = 'fr';
		$oci->wp_locale                           = 'oci';
		$oci->slug                                = 'oci';
		$oci->nplurals                            = '2';
		$oci->plural_expression                   = '(n > 1)';

		$orm = new GP_Locale();
		$orm->english_name                        = 'Oromo';
		$orm->native_name                         = 'Afaan Oromo';
		$orm->text_direction                      = 'ltr';
		$orm->lang_code_iso_639_1                 = 'om';
		$orm->lang_code_iso_639_2                 = 'orm';
		$orm->lang_code_iso_639_3                 = 'orm';
		$orm->slug                                = 'orm';
		$orm->nplurals                            = '2';
		$orm->plural_expression                   = '(n > 1)';
		$orm->cldr_code                           = 'om';
		$orm->cldr_nplurals                       = '2';
		$orm->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$orm->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$ory = new GP_Locale();
		$ory->english_name                        = 'Oriya';
		$ory->native_name                         = 'ଓଡ଼ିଆ';
		$ory->text_direction                      = 'ltr';
		$ory->lang_code_iso_639_1                 = 'or';
		$ory->lang_code_iso_639_2                 = 'ory';
		$ory->country_code                        = 'in';
		$ory->wp_locale                           = 'ory';
		$ory->slug                                = 'ory';
		$ory->nplurals                            = '2';
		$ory->plural_expression                   = 'n != 1';
		$ory->cldr_code                           = 'or';
		$ory->cldr_nplurals                       = '2';
		$ory->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ory->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ory->facebook_locale                     = 'or_IN';

		$os = new GP_Locale();
		$os->english_name                         = 'Ossetic';
		$os->native_name                          = 'Ирон';
		$os->text_direction                       = 'ltr';
		$os->lang_code_iso_639_1                  = 'os';
		$os->lang_code_iso_639_2                  = 'oss';
		$os->wp_locale                            = 'os';
		$os->slug                                 = 'os';
		$os->nplurals                             = '2';
		$os->plural_expression                    = 'n != 1';
		$os->cldr_code                            = 'os';
		$os->cldr_nplurals                        = '2';
		$os->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$os->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$pa = new GP_Locale();
		$pa->english_name                         = 'Punjabi';
		$pa->native_name                          = 'ਪੰਜਾਬੀ';
		$pa->text_direction                       = 'ltr';
		$pa->lang_code_iso_639_1                  = 'pa';
		$pa->lang_code_iso_639_2                  = 'pan';
		$pa->country_code                         = 'in';
		$pa->wp_locale                            = 'pa_IN';
		$pa->slug                                 = 'pa';
		$pa->nplurals                             = '2';
		$pa->plural_expression                    = 'n != 1';
		$pa->cldr_code                            = 'pa';
		$pa->cldr_nplurals                        = '2';
		$pa->cldr_plural_expressions['one']       = 'n = 0..1 @integer 0, 1 @decimal 0.0, 1.0, 0.00, 1.00, 0.000, 1.000, 0.0000, 1.0000';
		$pa->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$pa->google_code                          = 'pa';
		$pa->facebook_locale                      = 'pa_IN';

		$pap = new GP_Locale();
		$pap->english_name = 'Papiamento';
		$pap->native_name = 'Papiamentu';
		$pap->lang_code_iso_639_2 = 'pap';
		$pap->lang_code_iso_639_3 = 'pap';
		$pap->country_code = 'cw';
		$pap->wp_locale = 'pap';
		$pap->slug = 'pap';

		$pirate = new GP_Locale();
		$pirate->english_name = 'English (Pirate)';
		$pirate->native_name = 'English (Pirate)';
		$pirate->lang_code_iso_639_2 = 'art';
		$pirate->wp_locale = 'art_xpirate';
		$pirate->slug = 'pirate';
		$pirate->google_code = 'xx-pirate';
		$pirate->facebook_locale = 'en_PI';

		$pl = new GP_Locale();
		$pl->english_name                         = 'Polish';
		$pl->native_name                          = 'Polski';
		$pl->text_direction                       = 'ltr';
		$pl->lang_code_iso_639_1                  = 'pl';
		$pl->lang_code_iso_639_2                  = 'pol';
		$pl->country_code                         = 'pl';
		$pl->wp_locale                            = 'pl_PL';
		$pl->slug                                 = 'pl';
		$pl->nplurals                             = '3';
		$pl->plural_expression                    = '(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';
		$pl->cldr_code                            = 'pl';
		$pl->cldr_nplurals                        = '4';
		$pl->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$pl->cldr_plural_expressions['few']       = 'v = 0 and i % 10 = 2..4 and i % 100 != 12..14 @integer 2~4, 22~24, 32~34, 42~44, 52~54, 62, 102, 1002, …';
		$pl->cldr_plural_expressions['many']      = 'v = 0 and i != 1 and i % 10 = 0..1 or v = 0 and i % 10 = 5..9 or v = 0 and i % 100 = 12..14 @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, …';
		$pl->cldr_plural_expressions['other']     = '   @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$pl->google_code                          = 'pl';
		$pl->facebook_locale                      = 'pl_PL';

		$pt = new GP_Locale();
		$pt->english_name                         = 'Portuguese (Portugal)';
		$pt->native_name                          = 'Português';
		$pt->text_direction                       = 'ltr';
		$pt->lang_code_iso_639_1                  = 'pt';
		$pt->country_code                         = 'pt';
		$pt->wp_locale                            = 'pt_PT';
		$pt->slug                                 = 'pt';
		$pt->nplurals                             = '2';
		$pt->plural_expression                    = 'n != 1';
		$pt->cldr_code                            = 'pt';
		$pt->cldr_nplurals                        = '2';
		$pt->cldr_plural_expressions['one']       = 'i = 0..1 @integer 0, 1 @decimal 0.0~1.5';
		$pt->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 2.0~3.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$pt->google_code                          = 'pt-PT';
		$pt->facebook_locale                      = 'pt_PT';

		$pt_br = new GP_Locale();
		$pt_br->english_name                      = 'Portuguese (Brazil)';
		$pt_br->native_name                       = 'Português do Brasil';
		$pt_br->text_direction                    = 'ltr';
		$pt_br->lang_code_iso_639_1               = 'pt';
		$pt_br->lang_code_iso_639_2               = 'por';
		$pt_br->country_code                      = 'br';
		$pt_br->wp_locale                         = 'pt_BR';
		$pt_br->slug                              = 'pt-br';
		$pt_br->nplurals                          = '2';
		$pt_br->plural_expression                 = '(n > 1)';
		$pt_br->cldr_code                         = 'pt';
		$pt_br->cldr_nplurals                     = '2';
		$pt_br->cldr_plural_expressions['one']    = 'i = 0..1 @integer 0, 1 @decimal 0.0~1.5';
		$pt_br->cldr_plural_expressions['other']  = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 2.0~3.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$pt_br->google_code                       = 'pt-BR';
		$pt_br->facebook_locale                   = 'pt_BR';
		$pt_br->variant_root                      = 'pt';
		$pt->variants['pt_br']                    = $pt->english_name;

		$ps = new GP_Locale();
		$ps->english_name                         = 'Pashto';
		$ps->native_name                          = 'پښتو';
		$ps->text_direction                       = 'rtl';
		$ps->lang_code_iso_639_1                  = 'ps';
		$ps->lang_code_iso_639_2                  = 'pus';
		$ps->country_code                         = 'af';
		$ps->wp_locale                            = 'ps';
		$ps->slug                                 = 'ps';
		$ps->nplurals                             = '2';
		$ps->plural_expression                    = 'n != 1';
		$ps->cldr_code                            = 'ps';
		$ps->cldr_nplurals                        = '2';
		$ps->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ps->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ps->facebook_locale                      = 'ps_AF';

		$rhg = new GP_Locale();
		$rhg->english_name                        = 'Rohingya';
		$rhg->native_name                         = 'Ruáinga';
		$rhg->text_direction                      = 'ltr';
		$rhg->lang_code_iso_639_3                 = 'rhg';
		$rhg->country_code                        = 'mm';
		$rhg->wp_locale                           = 'rhg';
		$rhg->slug                                = 'rhg';
		$rhg->nplurals                            = '1';

		$ro = new GP_Locale();
		$ro->english_name                         = 'Romanian';
		$ro->native_name                          = 'Română';
		$ro->text_direction                       = 'ltr';
		$ro->lang_code_iso_639_1                  = 'ro';
		$ro->lang_code_iso_639_2                  = 'ron';
		$ro->country_code                         = 'ro';
		$ro->wp_locale                            = 'ro_RO';
		$ro->slug                                 = 'ro';
		$ro->nplurals                             = '3';
		$ro->plural_expression                    = '(n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2)';
		$ro->cldr_code                            = 'ro';
		$ro->cldr_nplurals                        = '3';
		$ro->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$ro->cldr_plural_expressions['few']       = 'v != 0 or n = 0 or n != 1 and n % 100 = 1..19 @integer 0, 2~16, 101, 1001, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ro->cldr_plural_expressions['other']     = ' @integer 20~35, 100, 1000, 10000, 100000, 1000000, …';
		$ro->google_code                          = 'ro';
		$ro->facebook_locale                      = 'ro_RO';

		$roh = new GP_Locale();
		$roh->english_name                        = 'Romansh';
		$roh->native_name                         = 'Rumantsch';
		$roh->text_direction                      = 'ltr';
		$roh->lang_code_iso_639_2                 = 'rm';
		$roh->lang_code_iso_639_3                 = 'roh';
		$roh->country_code                        = 'ch';
		$roh->wp_locale                           = 'roh';
		$roh->slug                                = 'roh';
		$roh->nplurals                            = '2';
		$roh->plural_expression                   = 'n != 1';
		$roh->cldr_code                           = 'rm';
		$roh->cldr_nplurals                       = '2';
		$roh->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$roh->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$ru = new GP_Locale();
		$ru->english_name                         = 'Russian';
		$ru->native_name                          = 'Русский';
		$ru->text_direction                       = 'ltr';
		$ru->lang_code_iso_639_1                  = 'ru';
		$ru->lang_code_iso_639_2                  = 'rus';
		$ru->country_code                         = 'ru';
		$ru->wp_locale                            = 'ru_RU';
		$ru->slug                                 = 'ru';
		$ru->nplurals                             = '3';
		$ru->plural_expression                    = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';
		$ru->cldr_code                            = 'ru';
		$ru->cldr_nplurals                        = '4';
		$ru->cldr_plural_expressions['one']       = 'v = 0 and i % 10 = 1 and i % 100 != 11 @integer 1, 21, 31, 41, 51, 61, 71, 81, 101, 1001, …';
		$ru->cldr_plural_expressions['few']       = 'v = 0 and i % 10 = 2..4 and i % 100 != 12..14 @integer 2~4, 22~24, 32~34, 42~44, 52~54, 62, 102, 1002, …';
		$ru->cldr_plural_expressions['many']      = 'v = 0 and i % 10 = 0 or v = 0 and i % 10 = 5..9 or v = 0 and i % 100 = 11..14 @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, …';
		$ru->cldr_plural_expressions['other']     = '   @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ru->google_code                          = 'ru';
		$ru->facebook_locale                      = 'ru_RU';

		$rue = new GP_Locale();
		$rue->english_name                        = 'Rusyn';
		$rue->native_name                         = 'Русиньскый';
		$rue->text_direction                      = 'ltr';
		$rue->lang_code_iso_639_3                 = 'rue';
		$rue->wp_locale                           = 'rue';
		$rue->slug                                = 'rue';
		$rue->nplurals                            = '3';
		$rue->plural_expression                   = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';

		$rup = new GP_Locale();
		$rup->english_name                        = 'Aromanian';
		$rup->native_name                         = 'Armãneashce';
		$rup->text_direction                      = 'ltr';
		$rup->lang_code_iso_639_2                 = 'rup';
		$rup->lang_code_iso_639_3                 = 'rup';
		$rup->country_code                        = 'mk';
		$rup->wp_locale                           = 'rup_MK';
		$rup->slug                                = 'rup';
		$rup->nplurals                            = '2';
		$rup->plural_expression                   = 'n != 1';

		$sah = new GP_Locale();
		$sah->english_name                        = 'Sakha';
		$sah->native_name                         = 'Сахалыы';
		$sah->text_direction                      = 'ltr';
		$sah->lang_code_iso_639_2                 = 'sah';
		$sah->lang_code_iso_639_3                 = 'sah';
		$sah->country_code                        = 'ru';
		$sah->wp_locale                           = 'sah';
		$sah->slug                                = 'sah';
		$sah->nplurals                            = '2';
		$sah->plural_expression                   = 'n != 1';
		$sah->cldr_code                           = 'sah';
		$sah->cldr_nplurals                       = '1';
		$sah->cldr_plural_expressions['other']    = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$sa_in = new GP_Locale();
		$sa_in->english_name                      = 'Sanskrit';
		$sa_in->native_name                       = 'भारतम्';
		$sa_in->text_direction                    = 'ltr';
		$sa_in->lang_code_iso_639_1               = 'sa';
		$sa_in->lang_code_iso_639_2               = 'san';
		$sa_in->lang_code_iso_639_3               = 'san';
		$sa_in->country_code                      = 'in';
		$sa_in->wp_locale                         = 'sa_IN';
		$sa_in->slug                              = 'sa-in';
		$sa_in->nplurals                          = '2';
		$sa_in->plural_expression                 = 'n != 1';
		$sa_in->facebook_locale                   = 'sa_IN';

		$scn = new GP_Locale();
		$scn->english_name                        = 'Sicilian';
		$scn->native_name                         = 'Sicilianu';
		$scn->text_direction                      = 'ltr';
		$scn->lang_code_iso_639_3                 = 'scn';
		$scn->country_code                        = 'it';
		$scn->wp_locale                           = 'scn';
		$scn->slug                                = 'scn';
		$scn->nplurals                            = '2';
		$scn->plural_expression                   = 'n != 1';

		$si = new GP_Locale();
		$si->english_name                         = 'Sinhala';
		$si->native_name                          = 'සිංහල';
		$si->text_direction                       = 'ltr';
		$si->lang_code_iso_639_1                  = 'si';
		$si->lang_code_iso_639_2                  = 'sin';
		$si->country_code                         = 'lk';
		$si->wp_locale                            = 'si_LK';
		$si->slug                                 = 'si';
		$si->nplurals                             = '2';
		$si->plural_expression                    = 'n != 1';
		$si->cldr_code                            = 'si';
		$si->cldr_nplurals                        = '2';
		$si->cldr_plural_expressions['one']       = 'n = 0,1 or i = 0 and f = 1 @integer 0, 1 @decimal 0.0, 0.1, 1.0, 0.00, 0.01, 1.00, 0.000, 0.001, 1.000, 0.0000, 0.0001, 1.0000';
		$si->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.2~0.9, 1.1~1.8, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$si->google_code                          = 'si';
		$si->facebook_locale                      = 'si_LK';

		$sk = new GP_Locale();
		$sk->english_name                         = 'Slovak';
		$sk->native_name                          = 'Slovenčina';
		$sk->text_direction                       = 'ltr';
		$sk->lang_code_iso_639_1                  = 'sk';
		$sk->lang_code_iso_639_2                  = 'slk';
		$sk->country_code                         = 'sk';
		$sk->wp_locale                            = 'sk_SK';
		$sk->slug                                 = 'sk';
		$sk->nplurals                             = '3';
		$sk->plural_expression                    = '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2';
		$sk->cldr_code                            = 'sk';
		$sk->cldr_nplurals                        = '4';
		$sk->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$sk->cldr_plural_expressions['few']       = 'i = 2..4 and v = 0 @integer 2~4';
		$sk->cldr_plural_expressions['many']      = 'v != 0   @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$sk->cldr_plural_expressions['other']     = ' @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, …';
		$sk->google_code                          = 'sk';
		$sk->facebook_locale                      = 'sk_SK';

		$skr = new GP_Locale();
		$skr->english_name = 'Saraiki';
		$skr->native_name = 'سرائیکی';
		$skr->lang_code_iso_639_3 = 'skr';
		$skr->country_code = 'pk';
		$skr->wp_locale = 'skr';
		$skr->slug = 'skr';
		$skr->nplurals = 2;
		$skr->plural_expression = '(n > 1)';
		$skr->text_direction = 'rtl';

		$sl = new GP_Locale();
		$sl->english_name                         = 'Slovenian';
		$sl->native_name                          = 'Slovenščina';
		$sl->text_direction                       = 'ltr';
		$sl->lang_code_iso_639_1                  = 'sl';
		$sl->lang_code_iso_639_2                  = 'slv';
		$sl->country_code                         = 'si';
		$sl->wp_locale                            = 'sl_SI';
		$sl->slug                                 = 'sl';
		$sl->nplurals                             = '4';
		$sl->plural_expression                    = '(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3)';
		$sl->cldr_code                            = 'sl';
		$sl->cldr_nplurals                        = '4';
		$sl->cldr_plural_expressions['one']       = 'v = 0 and i % 100 = 1 @integer 1, 101, 201, 301, 401, 501, 601, 701, 1001, …';
		$sl->cldr_plural_expressions['two']       = 'v = 0 and i % 100 = 2 @integer 2, 102, 202, 302, 402, 502, 602, 702, 1002, …';
		$sl->cldr_plural_expressions['few']       = 'v = 0 and i % 100 = 3..4 or v != 0 @integer 3, 4, 103, 104, 203, 204, 303, 304, 403, 404, 503, 504, 603, 604, 703, 704, 1003, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$sl->cldr_plural_expressions['other']     = ' @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, …';
		$sl->google_code                          = 'sl';
		$sl->facebook_locale                      = 'sl_SI';

		$sna = new GP_Locale();
		$sna->english_name                        = 'Shona';
		$sna->native_name                         = 'ChiShona';
		$sna->text_direction                      = 'ltr';
		$sna->lang_code_iso_639_1                 = 'sn';
		$sna->lang_code_iso_639_3                 = 'sna';
		$sna->country_code                        = 'zw';
		$sna->wp_locale                           = 'sna';
		$sna->slug                                = 'sna';
		$sna->nplurals                            = '2';
		$sna->plural_expression                   = 'n != 1';
		$sna->cldr_code                           = 'sn';
		$sna->cldr_nplurals                       = '2';
		$sna->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$sna->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$snd = new GP_Locale();
		$snd->english_name                        = 'Sindhi';
		$snd->native_name                         = 'سنڌي';
		$snd->text_direction                      = 'rtl';
		$snd->lang_code_iso_639_1                 = 'sd';
		$snd->lang_code_iso_639_2                 = 'sd';
		$snd->lang_code_iso_639_3                 = 'snd';
		$snd->country_code                        = 'pk';
		$snd->wp_locale                           = 'snd';
		$snd->slug                                = 'snd';
		$snd->nplurals                            = '2';
		$snd->plural_expression                   = 'n != 1';

		$so = new GP_Locale();
		$so->english_name                         = 'Somali';
		$so->native_name                          = 'Afsoomaali';
		$so->text_direction                       = 'ltr';
		$so->lang_code_iso_639_1                  = 'so';
		$so->lang_code_iso_639_2                  = 'som';
		$so->lang_code_iso_639_3                  = 'som';
		$so->country_code                         = 'so';
		$so->wp_locale                            = 'so_SO';
		$so->slug                                 = 'so';
		$so->nplurals                             = '2';
		$so->plural_expression                    = 'n != 1';
		$so->cldr_code                            = 'so';
		$so->cldr_nplurals                        = '2';
		$so->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$so->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$so->google_code                          = 'so';
		$so->facebook_locale                      = 'so_SO';

		$sq = new GP_Locale();
		$sq->english_name                         = 'Albanian';
		$sq->native_name                          = 'Shqip';
		$sq->text_direction                       = 'ltr';
		$sq->lang_code_iso_639_1                  = 'sq';
		$sq->lang_code_iso_639_2                  = 'sqi';
		$sq->country_code                         = 'al';
		$sq->wp_locale                            = 'sq';
		$sq->slug                                 = 'sq';
		$sq->nplurals                             = '2';
		$sq->plural_expression                    = 'n != 1';
		$sq->cldr_code                            = 'sq';
		$sq->cldr_nplurals                        = '2';
		$sq->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$sq->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$sq->google_code                          = 'sq';
		$sq->facebook_locale                      = 'sq_AL';

		$sq_xk = new GP_Locale();
		$sq_xk->english_name                      = 'Shqip (Kosovo)';
		$sq_xk->native_name                       = 'Për Kosovën Shqip';
		$sq_xk->text_direction                    = 'ltr';
		$sq_xk->lang_code_iso_639_1               = 'sq';
		$sq_xk->country_code                      = 'xk';
		$sq_xk->wp_locale                         = 'sq_XK';
		$sq_xk->slug                              = 'sq-xk';
		$sq_xk->nplurals                          = '2';
		$sq_xk->plural_expression                 = 'n != 1';
		$sq_xk->cldr_code                         = 'sq';
		$sq_xk->cldr_nplurals                     = '2';
		$sq_xk->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$sq_xk->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$sr = new GP_Locale();
		$sr->english_name                         = 'Serbian';
		$sr->native_name                          = 'Српски језик';
		$sr->text_direction                       = 'ltr';
		$sr->lang_code_iso_639_1                  = 'sr';
		$sr->lang_code_iso_639_2                  = 'srp';
		$sr->country_code                         = 'rs';
		$sr->wp_locale                            = 'sr_RS';
		$sr->slug                                 = 'sr';
		$sr->nplurals                             = '3';
		$sr->plural_expression                    = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';
		$sr->cldr_code                            = 'sr';
		$sr->cldr_nplurals                        = '3';
		$sr->cldr_plural_expressions['one']       = 'v = 0 and i % 10 = 1 and i % 100 != 11 or f % 10 = 1 and f % 100 != 11 @integer 1, 21, 31, 41, 51, 61, 71, 81, 101, 1001, … @decimal 0.1, 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 10.1, 100.1, 1000.1, …';
		$sr->cldr_plural_expressions['few']       = 'v = 0 and i % 10 = 2..4 and i % 100 != 12..14 or f % 10 = 2..4 and f % 100 != 12..14 @integer 2~4, 22~24, 32~34, 42~44, 52~54, 62, 102, 1002, … @decimal 0.2~0.4, 1.2~1.4, 2.2~2.4, 3.2~3.4, 4.2~4.4, 5.2, 10.2, 100.2, 1000.2, …';
		$sr->cldr_plural_expressions['other']     = ' @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0, 0.5~1.0, 1.5~2.0, 2.5~2.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$sr->google_code                          = 'sr';
		$sr->facebook_locale                      = 'sr_RS';

		$srd = new GP_Locale();
		$srd->english_name                        = 'Sardinian';
		$srd->native_name                         = 'Sardu';
		$srd->text_direction                      = 'ltr';
		$srd->lang_code_iso_639_1                 = 'sc';
		$srd->lang_code_iso_639_2                 = 'srd';
		$srd->country_code                        = 'it';
		$srd->wp_locale                           = 'srd';
		$srd->slug                                = 'srd';
		$srd->nplurals                            = '2';
		$srd->plural_expression                   = 'n != 1';
		$srd->facebook_locale                     = 'sc_IT';

		$ssw = new GP_Locale();
		$ssw->english_name = 'Swati';
		$ssw->native_name = 'SiSwati';
		$ssw->lang_code_iso_639_1 = 'ss';
		$ssw->lang_code_iso_639_2 = 'ssw';
		$ssw->lang_code_iso_639_3 = 'ssw';
		$ssw->country_code = 'sz';
		$ssw->wp_locale = 'ssw';
		$ssw->slug = 'ssw';

		$su = new GP_Locale();
		$su->english_name                         = 'Sundanese';
		$su->native_name                          = 'Basa Sunda';
		$su->text_direction                       = 'ltr';
		$su->lang_code_iso_639_1                  = 'su';
		$su->lang_code_iso_639_2                  = 'sun';
		$su->country_code                         = 'id';
		$su->wp_locale                            = 'su_ID';
		$su->slug                                 = 'su';
		$su->nplurals                             = '1';
		$su->google_code                          = 'su';

		$sv = new GP_Locale();
		$sv->english_name                         = 'Swedish';
		$sv->native_name                          = 'Svenska';
		$sv->text_direction                       = 'ltr';
		$sv->lang_code_iso_639_1                  = 'sv';
		$sv->lang_code_iso_639_2                  = 'swe';
		$sv->country_code                         = 'se';
		$sv->wp_locale                            = 'sv_SE';
		$sv->slug                                 = 'sv';
		$sv->nplurals                             = '2';
		$sv->plural_expression                    = 'n != 1';
		$sv->cldr_code                            = 'sv';
		$sv->cldr_nplurals                        = '2';
		$sv->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$sv->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$sv->google_code                          = 'sv';
		$sv->facebook_locale                      = 'sv_SE';

		$sw = new GP_Locale();
		$sw->english_name                         = 'Swahili';
		$sw->native_name                          = 'Kiswahili';
		$sw->text_direction                       = 'ltr';
		$sw->lang_code_iso_639_1                  = 'sw';
		$sw->lang_code_iso_639_2                  = 'swa';
		$sw->wp_locale                            = 'sw';
		$sw->slug                                 = 'sw';
		$sw->nplurals                             = '2';
		$sw->plural_expression                    = 'n != 1';
		$sw->cldr_code                            = 'sw';
		$sw->cldr_nplurals                        = '2';
		$sw->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$sw->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$sw->google_code                          = 'sw';
		$sw->facebook_locale                      = 'sw_KE';

		$syr = new GP_Locale();
		$syr->english_name                        = 'Syriac';
		$syr->native_name                         = 'Syriac';
		$syr->text_direction                      = 'ltr';
		$syr->lang_code_iso_639_3                 = 'syr';
		$syr->country_code                        = 'iq';
		$syr->wp_locale                           = 'syr';
		$syr->slug                                = 'syr';
		$syr->nplurals                            = '2';
		$syr->plural_expression                   = 'n != 1';
		$syr->cldr_code                           = 'syr';
		$syr->cldr_nplurals                       = '2';
		$syr->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$syr->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$szl = new GP_Locale();
		$szl->english_name                        = 'Silesian';
		$szl->native_name                         = 'Ślōnskŏ gŏdka';
		$szl->text_direction                      = 'ltr';
		$szl->lang_code_iso_639_3                 = 'szl';
		$szl->country_code                        = 'pl';
		$szl->wp_locale                           = 'szl';
		$szl->slug                                = 'szl';
		$szl->nplurals                            = '3';
		$szl->plural_expression                   = '(n==1 ? 0 : n%10>=2 && n%10<=4 && n%100==20 ? 1 : 2)';
		$szl->facebook_locale                     = 'sz_PL';

		$ta = new GP_Locale();
		$ta->english_name                         = 'Tamil';
		$ta->native_name                          = 'தமிழ்';
		$ta->text_direction                       = 'ltr';
		$ta->lang_code_iso_639_1                  = 'ta';
		$ta->lang_code_iso_639_2                  = 'tam';
		$ta->country_code                         = 'in';
		$ta->wp_locale                            = 'ta_IN';
		$ta->slug                                 = 'ta';
		$ta->nplurals                             = '2';
		$ta->plural_expression                    = 'n != 1';
		$ta->cldr_code                            = 'ta';
		$ta->cldr_nplurals                        = '2';
		$ta->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ta->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ta->google_code                          = 'ta';
		$ta->facebook_locale                      = 'ta_IN';

		$ta_lk = new GP_Locale();
		$ta_lk->english_name                      = 'Tamil (Sri Lanka)';
		$ta_lk->native_name                       = 'தமிழ்';
		$ta_lk->text_direction                    = 'ltr';
		$ta_lk->lang_code_iso_639_1               = 'ta';
		$ta_lk->lang_code_iso_639_2               = 'tam';
		$ta_lk->country_code                      = 'lk';
		$ta_lk->wp_locale                         = 'ta_LK';
		$ta_lk->slug                              = 'ta-lk';
		$ta_lk->nplurals                          = '2';
		$ta_lk->plural_expression                 = 'n != 1';
		$ta_lk->cldr_code                         = 'ta';
		$ta_lk->cldr_nplurals                     = '2';
		$ta_lk->cldr_plural_expressions['one']    = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ta_lk->cldr_plural_expressions['other']  = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ta_lk->google_code                       = 'ta';
		$ta_lk->variant_root                      = 'ta';
		$ta->variants['ta_lk']                    = $ta->english_name;

		$tah = new GP_Locale();
		$tah->english_name                        = 'Tahitian';
		$tah->native_name                         = 'Reo Tahiti';
		$tah->text_direction                      = 'ltr';
		$tah->lang_code_iso_639_1                 = 'ty';
		$tah->lang_code_iso_639_2                 = 'tah';
		$tah->lang_code_iso_639_3                 = 'tah';
		$tah->country_code                        = 'tj';
		$tah->wp_locale                           = 'tah';
		$tah->slug                                = 'tah';
		$tah->nplurals                            = '2';
		$tah->plural_expression                   = '(n > 1)';

		$te = new GP_Locale();
		$te->english_name                         = 'Telugu';
		$te->native_name                          = 'తెలుగు';
		$te->text_direction                       = 'ltr';
		$te->lang_code_iso_639_1                  = 'te';
		$te->lang_code_iso_639_2                  = 'tel';
		$te->wp_locale                            = 'te';
		$te->slug                                 = 'te';
		$te->nplurals                             = '2';
		$te->plural_expression                    = 'n != 1';
		$te->cldr_code                            = 'te';
		$te->cldr_nplurals                        = '2';
		$te->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$te->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$te->google_code                          = 'te';
		$te->facebook_locale                      = 'te_IN';

		$tg = new GP_Locale();
		$tg->english_name                         = 'Tajik';
		$tg->native_name                          = 'Тоҷикӣ';
		$tg->text_direction                       = 'ltr';
		$tg->lang_code_iso_639_1                  = 'tg';
		$tg->lang_code_iso_639_2                  = 'tgk';
		$tg->wp_locale                            = 'tg';
		$tg->slug                                 = 'tg';
		$tg->nplurals                             = '2';
		$tg->plural_expression                    = 'n != 1';
		$tg->google_code                          = 'tg';
		$tg->facebook_locale                      = 'tg_TJ';

		$th = new GP_Locale();
		$th->english_name                         = 'Thai';
		$th->native_name                          = 'ไทย';
		$th->text_direction                       = 'ltr';
		$th->lang_code_iso_639_1                  = 'th';
		$th->lang_code_iso_639_2                  = 'tha';
		$th->wp_locale                            = 'th';
		$th->slug                                 = 'th';
		$th->nplurals                             = '1';
		$th->cldr_code                            = 'th';
		$th->cldr_nplurals                        = '1';
		$th->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$th->google_code                          = 'th';
		$th->facebook_locale                      = 'th_TH';

		$tir = new GP_Locale();
		$tir->english_name                        = 'Tigrinya';
		$tir->native_name                         = 'ትግርኛ';
		$tir->text_direction                      = 'ltr';
		$tir->lang_code_iso_639_1                 = 'ti';
		$tir->lang_code_iso_639_2                 = 'tir';
		$tir->country_code                        = 'er';
		$tir->wp_locale                           = 'tir';
		$tir->slug                                = 'tir';
		$tir->nplurals                            = '1';
		$tir->cldr_code                           = 'ti';
		$tir->cldr_nplurals                       = '2';
		$tir->cldr_plural_expressions['one']      = 'n = 0..1 @integer 0, 1 @decimal 0.0, 1.0, 0.00, 1.00, 0.000, 1.000, 0.0000, 1.0000';
		$tir->cldr_plural_expressions['other']    = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$tlh = new GP_Locale();
		$tlh->english_name                        = 'Klingon';
		$tlh->native_name                         = 'TlhIngan';
		$tlh->text_direction                      = 'ltr';
		$tlh->lang_code_iso_639_2                 = 'tlh';
		$tlh->slug                                = 'tlh';
		$tlh->nplurals                            = '1';
		$tlh->facebook_locale                     = 'tl_ST';

		$tl = new GP_Locale();
		$tl->english_name                         = 'Tagalog';
		$tl->native_name                          = 'Tagalog';
		$tl->text_direction                       = 'ltr';
		$tl->lang_code_iso_639_1                  = 'tl';
		$tl->lang_code_iso_639_2                  = 'tgl';
		$tl->country_code                         = 'ph';
		$tl->wp_locale                            = 'tl';
		$tl->slug                                 = 'tl';
		$tl->nplurals                             = '2';
		$tl->plural_expression                    = 'n != 1';
		$tl->cldr_code                            = 'tl';
		$tl->cldr_nplurals                        = '2';
		$tl->cldr_plural_expressions['one']       = 'v = 0 and i = 1,2,3 or v = 0 and i % 10 != 4,6,9 or v != 0 and f % 10 != 4,6,9 @integer 0~3, 5, 7, 8, 10~13, 15, 17, 18, 20, 21, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.3, 0.5, 0.7, 0.8, 1.0~1.3, 1.5, 1.7, 1.8, 2.0, 2.1, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$tl->cldr_plural_expressions['other']     = ' @integer 4, 6, 9, 14, 16, 19, 24, 26, 104, 1004, … @decimal 0.4, 0.6, 0.9, 1.4, 1.6, 1.9, 2.4, 2.6, 10.4, 100.4, 1000.4, …';
		$tl->google_code                          = 'tl';
		$tl->facebook_locale                      = 'tl_PH';

		$tr = new GP_Locale();
		$tr->english_name                         = 'Turkish';
		$tr->native_name                          = 'Türkçe';
		$tr->text_direction                       = 'ltr';
		$tr->lang_code_iso_639_1                  = 'tr';
		$tr->lang_code_iso_639_2                  = 'tur';
		$tr->country_code                         = 'tr';
		$tr->wp_locale                            = 'tr_TR';
		$tr->slug                                 = 'tr';
		$tr->nplurals                             = '2';
		$tr->plural_expression                    = '(n > 1)';
		$tr->cldr_code                            = 'tr';
		$tr->cldr_nplurals                        = '2';
		$tr->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$tr->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$tr->google_code                          = 'tr';
		$tr->facebook_locale                      = 'tr_TR';

		$tt = new GP_Locale();
		$tt->english_name                         = 'Tatar';
		$tt->native_name                          = 'Татар теле';
		$tt->text_direction                       = 'ltr';
		$tt->lang_code_iso_639_1                  = 'tt';
		$tt->lang_code_iso_639_2                  = 'tat';
		$tt->country_code                         = 'ru';
		$tt->wp_locale                            = 'tt_RU';
		$tt->slug                                 = 'tt';
		$tt->nplurals                             = '1';
		$tt->facebook_locale                      = 'tt_RU';

		$tuk = new GP_Locale();
		$tuk->english_name                        = 'Turkmen';
		$tuk->native_name                         = 'Türkmençe';
		$tuk->text_direction                      = 'ltr';
		$tuk->lang_code_iso_639_1                 = 'tk';
		$tuk->lang_code_iso_639_2                 = 'tuk';
		$tuk->country_code                        = 'tm';
		$tuk->wp_locale                           = 'tuk';
		$tuk->slug                                = 'tuk';
		$tuk->nplurals                            = '2';
		$tuk->plural_expression                   = '(n > 1)';
		$tuk->cldr_code                           = 'tk';
		$tuk->cldr_nplurals                       = '2';
		$tuk->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$tuk->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$tuk->facebook_locale                     = 'tk_TM';

		$twd = new GP_Locale();
		$twd->english_name                        = 'Tweants';
		$twd->native_name                         = 'Twents';
		$twd->text_direction                      = 'ltr';
		$twd->lang_code_iso_639_3                 = 'twd';
		$twd->country_code                        = 'nl';
		$twd->wp_locale                           = 'twd';
		$twd->slug                                = 'twd';
		$twd->nplurals                            = '2';
		$twd->plural_expression                   = 'n != 1';

		$tzm = new GP_Locale();
		$tzm->english_name                        = 'Tamazight (Central Atlas)';
		$tzm->native_name                         = 'ⵜⴰⵎⴰⵣⵉⵖⵜ';
		$tzm->text_direction                      = 'ltr';
		$tzm->lang_code_iso_639_2                 = 'tzm';
		$tzm->country_code                        = 'ma';
		$tzm->wp_locale                           = 'tzm';
		$tzm->slug                                = 'tzm';
		$tzm->nplurals                            = '2';
		$tzm->plural_expression                   = '(n > 1)';
		$tzm->cldr_code                           = 'tzm';
		$tzm->cldr_nplurals                       = '2';
		$tzm->cldr_plural_expressions['one']      = 'n = 0..1 or n = 11..99 @integer 0, 1, 11~24 @decimal 0.0, 1.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 19.0, 20.0, 21.0, 22.0, 23.0, 24.0';
		$tzm->cldr_plural_expressions['other']    = ' @integer 2~10, 100~106, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$udm = new GP_Locale();
		$udm->english_name                        = 'Udmurt';
		$udm->native_name                         = 'Удмурт кыл';
		$udm->text_direction                      = 'ltr';
		$udm->lang_code_iso_639_2                 = 'udm';
		$udm->slug                                = 'udm';
		$udm->nplurals                            = '2';
		$udm->plural_expression                   = 'n != 1';

		$ug = new GP_Locale();
		$ug->english_name                         = 'Uighur';
		$ug->native_name = 'ئۇيغۇرچە';
		$ug->text_direction                       = 'rtl';
		$ug->lang_code_iso_639_1                  = 'ug';
		$ug->lang_code_iso_639_2                  = 'uig';
		$ug->country_code                         = 'cn';
		$ug->wp_locale                            = 'ug_CN';
		$ug->slug                                 = 'ug';
		$ug->nplurals                             = '2';
		$ug->plural_expression                    = 'n != 1';
		$ug->cldr_code                            = 'ug';
		$ug->cldr_nplurals                        = '2';
		$ug->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$ug->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$uk = new GP_Locale();
		$uk->english_name                         = 'Ukrainian';
		$uk->native_name                          = 'Українська';
		$uk->text_direction                       = 'ltr';
		$uk->lang_code_iso_639_1                  = 'uk';
		$uk->lang_code_iso_639_2                  = 'ukr';
		$uk->country_code                         = 'ua';
		$uk->wp_locale                            = 'uk';
		$uk->slug                                 = 'uk';
		$uk->nplurals                             = '3';
		$uk->plural_expression                    = '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)';
		$uk->cldr_code                            = 'uk';
		$uk->cldr_nplurals                        = '4';
		$uk->cldr_plural_expressions['one']       = 'v = 0 and i % 10 = 1 and i % 100 != 11 @integer 1, 21, 31, 41, 51, 61, 71, 81, 101, 1001, …';
		$uk->cldr_plural_expressions['few']       = 'v = 0 and i % 10 = 2..4 and i % 100 != 12..14 @integer 2~4, 22~24, 32~34, 42~44, 52~54, 62, 102, 1002, …';
		$uk->cldr_plural_expressions['many']      = 'v = 0 and i % 10 = 0 or v = 0 and i % 10 = 5..9 or v = 0 and i % 100 = 11..14 @integer 0, 5~19, 100, 1000, 10000, 100000, 1000000, …';
		$uk->cldr_plural_expressions['other']     = '   @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$uk->google_code                          = 'uk';
		$uk->facebook_locale                      = 'uk_UA';

		$ur = new GP_Locale();
		$ur->english_name                         = 'Urdu';
		$ur->native_name                          = 'اردو';
		$ur->text_direction                       = 'rtl';
		$ur->lang_code_iso_639_1                  = 'ur';
		$ur->lang_code_iso_639_2                  = 'urd';
		$ur->country_code                         = 'pk';
		$ur->wp_locale                            = 'ur';
		$ur->slug                                 = 'ur';
		$ur->nplurals                             = '2';
		$ur->plural_expression                    = 'n != 1';
		$ur->cldr_code                            = 'ur';
		$ur->cldr_nplurals                        = '2';
		$ur->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$ur->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$ur->google_code                          = 'ur';
		$ur->facebook_locale                      = 'ur_PK';

		$uz = new GP_Locale();
		$uz->english_name                         = 'Uzbek';
		$uz->native_name                          = 'O‘zbekcha';
		$uz->text_direction                       = 'ltr';
		$uz->lang_code_iso_639_1                  = 'uz';
		$uz->lang_code_iso_639_2                  = 'uzb';
		$uz->country_code                         = 'uz';
		$uz->wp_locale                            = 'uz_UZ';
		$uz->slug                                 = 'uz';
		$uz->nplurals                             = '1';
		$uz->cldr_code                            = 'uz';
		$uz->cldr_nplurals                        = '2';
		$uz->cldr_plural_expressions['one']       = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$uz->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$uz->google_code                          = 'uz';
		$uz->facebook_locale                      = 'uz_UZ';

		$vec = new GP_Locale();
		$vec->english_name                        = 'Venetian';
		$vec->native_name                         = 'Vèneta';
		$vec->text_direction                      = 'ltr';
		$vec->lang_code_iso_639_2                 = 'roa';
		$vec->lang_code_iso_639_3                 = 'vec';
		$vec->country_code                        = 'it';
		$vec->slug                                = 'vec';
		$vec->nplurals                            = '2';
		$vec->plural_expression                   = 'n != 1';

		$vi = new GP_Locale();
		$vi->english_name                         = 'Vietnamese';
		$vi->native_name                          = 'Tiếng Việt';
		$vi->text_direction                       = 'ltr';
		$vi->lang_code_iso_639_1                  = 'vi';
		$vi->lang_code_iso_639_2                  = 'vie';
		$vi->country_code                         = 'vn';
		$vi->wp_locale                            = 'vi';
		$vi->slug                                 = 'vi';
		$vi->nplurals                             = '1';
		$vi->cldr_code                            = 'vi';
		$vi->cldr_nplurals                        = '1';
		$vi->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$vi->google_code                          = 'vi';
		$vi->facebook_locale                      = 'vi_VN';

		$wa = new GP_Locale();
		$wa->english_name                         = 'Walloon';
		$wa->native_name                          = 'Walon';
		$wa->text_direction                       = 'ltr';
		$wa->lang_code_iso_639_1                  = 'wa';
		$wa->lang_code_iso_639_2                  = 'wln';
		$wa->country_code                         = 'be';
		$wa->wp_locale                            = 'wa';
		$wa->slug                                 = 'wa';
		$wa->nplurals                             = '2';
		$wa->plural_expression                    = 'n != 1';
		$wa->cldr_code                            = 'wa';
		$wa->cldr_nplurals                        = '2';
		$wa->cldr_plural_expressions['one']       = 'n = 0..1 @integer 0, 1 @decimal 0.0, 1.0, 0.00, 1.00, 0.000, 1.000, 0.0000, 1.0000';
		$wa->cldr_plural_expressions['other']     = ' @integer 2~17, 100, 1000, 10000, 100000, 1000000, … @decimal 0.1~0.9, 1.1~1.7, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$xho = new GP_Locale();
		$xho->english_name                        = 'Xhosa';
		$xho->native_name                         = 'isiXhosa';
		$xho->text_direction                      = 'ltr';
		$xho->lang_code_iso_639_1                 = 'xh';
		$xho->lang_code_iso_639_2                 = 'xho';
		$xho->lang_code_iso_639_3                 = 'xho';
		$xho->country_code                        = 'za';
		$xho->wp_locale                           = 'xho';
		$xho->slug                                = 'xho';
		$xho->nplurals                            = '2';
		$xho->plural_expression                   = 'n != 1';
		$xho->cldr_code                           = 'xh';
		$xho->cldr_nplurals                       = '2';
		$xho->cldr_plural_expressions['one']      = 'n = 1 @integer 1 @decimal 1.0, 1.00, 1.000, 1.0000';
		$xho->cldr_plural_expressions['other']    = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~0.9, 1.1~1.6, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$xho->google_code                         = 'xh';
		$xho->facebook_locale                     = 'xh_ZA';

		$xmf = new GP_Locale();
		$xmf->english_name                        = 'Mingrelian';
		$xmf->native_name                         = 'მარგალური ნინა';
		$xmf->text_direction                      = 'ltr';
		$xmf->lang_code_iso_639_3                 = 'xmf';
		$xmf->country_code                        = 'ge';
		$xmf->wp_locale                           = 'xmf';
		$xmf->slug                                = 'xmf';
		$xmf->nplurals                            = '2';
		$xmf->plural_expression                   = 'n != 1';

		$yi = new GP_Locale();
		$yi->english_name                         = 'Yiddish';
		$yi->native_name                          = 'ייִדיש';
		$yi->text_direction                       = 'rtl';
		$yi->lang_code_iso_639_1                  = 'yi';
		$yi->lang_code_iso_639_2                  = 'yid';
		$yi->slug                                 = 'yi';
		$yi->nplurals                             = '2';
		$yi->plural_expression                    = 'n != 1';
		$yi->cldr_code                            = 'yi';
		$yi->cldr_nplurals                        = '2';
		$yi->cldr_plural_expressions['one']       = 'i = 1 and v = 0 @integer 1';
		$yi->cldr_plural_expressions['other']     = ' @integer 0, 2~16, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$yi->google_code                          = 'yi';

		$yor = new GP_Locale();
		$yor->english_name                        = 'Yoruba';
		$yor->native_name                         = 'Yorùbá';
		$yor->text_direction                      = 'ltr';
		$yor->lang_code_iso_639_1                 = 'yo';
		$yor->lang_code_iso_639_2                 = 'yor';
		$yor->lang_code_iso_639_3                 = 'yor';
		$yor->country_code                        = 'ng';
		$yor->wp_locale                           = 'yor';
		$yor->slug                                = 'yor';
		$yor->nplurals                            = '2';
		$yor->plural_expression                   = 'n != 1';
		$yor->cldr_code                           = 'yo';
		$yor->cldr_nplurals                       = '1';
		$yor->cldr_plural_expressions['other']    = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$yor->google_code                         = 'yo';
		$yor->facebook_locale                     = 'yo_NG';

		$zh = new GP_Locale();
		$zh->english_name                         = 'Chinese';
		$zh->native_name                          = '中文';
		$zh->text_direction                       = 'ltr';
		$zh->lang_code_iso_639_1                  = 'zh';
		$zh->lang_code_iso_639_2                  = 'zho';
		$zh->slug                                 = 'zh';
		$zh->nplurals                             = '1';
		$zh->cldr_code                            = 'zh';
		$zh->cldr_nplurals                        = '1';
		$zh->cldr_plural_expressions['other']     = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';

		$zh_cn = new GP_Locale();
		$zh_cn->english_name                      = 'Chinese (China)';
		$zh_cn->native_name                       = '简体中文';
		$zh_cn->text_direction                    = 'ltr';
		$zh_cn->lang_code_iso_639_1               = 'zh';
		$zh_cn->lang_code_iso_639_2               = 'zho';
		$zh_cn->country_code                      = 'cn';
		$zh_cn->wp_locale                         = 'zh_CN';
		$zh_cn->slug                              = 'zh-cn';
		$zh_cn->nplurals                          = '1';
		$zh_cn->cldr_code                         = 'zh';
		$zh_cn->cldr_nplurals                     = '1';
		$zh_cn->cldr_plural_expressions['other']  = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$zh_cn->google_code                       = 'zh-CN';
		$zh_cn->facebook_locale                   = 'zh_CN';
		$zh_cn->variant_root                      = 'zh';
		$zh->variants['zh_cn']                    = $zh->english_name;

		$zh_hk = new GP_Locale();
		$zh_hk->english_name                      = 'Chinese (Hong Kong)';
		$zh_hk->native_name                       = '香港中文版	';
		$zh_hk->text_direction                    = 'ltr';
		$zh_hk->lang_code_iso_639_1               = 'zh';
		$zh_hk->lang_code_iso_639_2               = 'zho';
		$zh_hk->country_code                      = 'hk';
		$zh_hk->wp_locale                         = 'zh_HK';
		$zh_hk->slug                              = 'zh-hk';
		$zh_hk->nplurals                          = '1';
		$zh_hk->cldr_code                         = 'zh';
		$zh_hk->cldr_nplurals                     = '1';
		$zh_hk->cldr_plural_expressions['other']  = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$zh_hk->facebook_locale                   = 'zh_HK';
		$zh_hk->variant_root                      = 'zh';
		$zh->variants['zh_hk']                    = $zh->english_name;

		$zh_sg = new GP_Locale();
		$zh_sg->english_name                      = 'Chinese (Singapore)';
		$zh_sg->native_name                       = '中文';
		$zh_sg->text_direction                    = 'ltr';
		$zh_sg->lang_code_iso_639_1               = 'zh';
		$zh_sg->lang_code_iso_639_2               = 'zho';
		$zh_sg->country_code                      = 'sg';
		$zh_sg->wp_locale                         = 'zh_SG';
		$zh_sg->slug                              = 'zh-sg';
		$zh_sg->nplurals                          = 1;
		$zh_sg->plural_expression                 = '0';
		$zh_sg->cldr_code                         = 'zh';
		$zh_sg->cldr_nplurals                     = '1';
		$zh_sg->cldr_plural_expressions['other']  = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$zh_sg->variant_root                      = 'zh';
		$zh->variants['zh_sg']                    = $zh->english_name;

		$zh_tw = new GP_Locale();
		$zh_tw->english_name                      = 'Chinese (Taiwan)';
		$zh_tw->native_name                       = '繁體中文';
		$zh_tw->text_direction                    = 'ltr';
		$zh_tw->lang_code_iso_639_1               = 'zh';
		$zh_tw->lang_code_iso_639_2               = 'zho';
		$zh_tw->country_code                      = 'tw';
		$zh_tw->wp_locale                         = 'zh_TW';
		$zh_tw->slug                              = 'zh-tw';
		$zh_tw->nplurals                          = '1';
		$zh_tw->cldr_code                         = 'zh';
		$zh_tw->cldr_nplurals                     = '1';
		$zh_tw->cldr_plural_expressions['other']  = ' @integer 0~15, 100, 1000, 10000, 100000, 1000000, … @decimal 0.0~1.5, 10.0, 100.0, 1000.0, 10000.0, 100000.0, 1000000.0, …';
		$zh_tw->google_code                       = 'zh-TW';
		$zh_tw->facebook_locale                   = 'zh_TW';
		$zh_tw->variant_root                      = 'zh';
		$zh->variants['zh_tw']                    = $zh->english_name;

		$zul = new GP_Locale();
		$zul->english_name = 'Zulu';
		$zul->native_name = 'isiZulu';
		$zul->lang_code_iso_639_1 = 'zu';
		$zul->lang_code_iso_639_2 = 'zul';
		$zul->lang_code_iso_639_3 = 'zul';
		$zul->country_code = 'za';
		$zul->wp_locale = 'zul';
		$zul->slug = 'zul';
		$zul->google_code = 'zu';

		foreach( get_defined_vars() as $locale ) {
			$this->locales[ $locale->slug ] = $locale;
		}
	}

	public static function &instance() {
		if ( ! isset( $GLOBALS['gp_locales'] ) )
			$GLOBALS['gp_locales'] = new GP_Locales;

		return $GLOBALS['gp_locales'];
	}

	public static function locales() {
		$instance = GP_Locales::instance();
		return $instance->locales;
	}

	public static function exists( $slug ) {
		$instance = GP_Locales::instance();
		return isset( $instance->locales[ $slug ] );
	}

	public static function by_slug( $slug ) {
		$instance = GP_Locales::instance();
		return isset( $instance->locales[ $slug ] )? $instance->locales[ $slug ] : null;
	}

	public static function by_field( $field_name, $field_value ) {
		$instance = GP_Locales::instance();
		$result   = false;

		foreach( $instance->locales() as $locale ) {
			if ( isset( $locale->$field_name ) && $locale->$field_name == $field_value ) {
				$result = $locale;
				break;
			}
		}

		return $result;
	}
}

endif;
