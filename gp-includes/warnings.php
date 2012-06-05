<?php
/**
 * Handles translation warnings
 */

class GP_Translation_Warnings {
	var $callbacks = array();

	function add( $id, $callback ) {
		$this->callbacks[$id] = $callback;
	}

	function remove( $id ) {
		unset( $this->callbacks[$id] );
	}

	function has( $id ) {
		return isset( $this->callbacks[$id] );
	}

	function check( $singular, $plural, $translations, $locale ) {
		$problems = array();
		foreach( $translations as $translation_index => $translation ) {
			if ( !$translation ) continue;
			$skip = array( 'singular' => false, 'plural' => false );
			if ( !is_null( $plural ) ) {
				$numbers_for_index = $locale->numbers_for_index( $translation_index );
				if ( $numbers_for_index == array(1) ) {
					$skip['plural'] = true;
				}
				if ( !in_array( 1, $numbers_for_index ) ) {
					$skip['singular'] = true;
				}
				if ( $locale->nplurals == 1 ) {
					$skip['singular'] = true;
				}
			}
			foreach( $this->callbacks as $callback_id => $callback ) {
				if ( !$skip['singular']) {
					$singular_test = call_user_func( $callback, $singular, $translation, $locale );
					if ( true !== $singular_test ) {
						$problems[$translation_index][$callback_id] = $singular_test;
					}
				}
				if ( !is_null( $plural ) && !$skip['plural'] ) {
					$plural_test = call_user_func( $callback, $plural, $translation, $locale );
					if ( true !== $plural_test ) {
						$problems[$translation_index][$callback_id] = $plural_test;
					}
				}
			}
		}
		return empty($problems)? null : $problems;
	}
}

class GP_Builtin_Translation_Warnings {

	var $length_lower_bound = 0.2;
	var $length_upper_bound = 5.0;
	var $length_exclude_languages = array('ja', 'zh', 'zh-hk', 'zh-cn', 'zh-sg', 'zh-tw');

	function warning_length( $original, $translation, $locale ) {
		if ( in_array( $locale->slug, $this->length_exclude_languages ) ) {
			return true;
		}
		if ( gp_startswith( $original, 'number_format_' ) ) {
			return true;
		}
		$len_src = gp_strlen( $original );
		$len_trans = gp_strlen( $translation );
		if ( !( $this->length_lower_bound*$len_src < $len_trans && $len_trans < $this->length_upper_bound*$len_src ) &&
				( !gp_in( '_abbreviation', $original ) && !gp_in( '_initial', $original ) ) ) {
			return __('Lengths of source and translation differ too much.');
		}
		return true;
	}

	function warning_tags( $original, $translation, $locale ) {
		$tag_pattern = "(<[^>]*>)";
		$tag_re = "/$tag_pattern/Us";
		$original_parts = preg_split($tag_re, $original, -1, PREG_SPLIT_DELIM_CAPTURE);
		$translation_parts = preg_split($tag_re, $translation, -1, PREG_SPLIT_DELIM_CAPTURE);
		if ( count( $original_parts) > count( $translation_parts ) ) {
			return __('Missing tags from translation.');
		}
		if ( count( $original_parts) < count( $translation_parts ) ) {
			return __('Too many tags in translation.');
		}
		foreach( gp_array_zip( $original_parts, $translation_parts ) as $tags ) {
			list( $original_tag, $translation_tag ) = $tags;
			$expected_error_msg = "Expected $original_tag, got $translation_tag.";
			$original_is_tag = preg_match( "/^$tag_pattern$/", $original_tag );
			$translation_is_tag = preg_match( "/^$tag_pattern$/", $translation_tag );
			// translations should never need a quote in their title attribute
			if ( $original_is_tag && $translation_is_tag && $original_tag != $translation_tag ) {
				// we allow translations to have a different title tag
				$title_re_single = '\s*title=\'[^\']+\'\s*';
				$title_re = '%'.$title_re_single.'|'.str_replace( "'", '"', $title_re_single ).'%';
				$original_tag = preg_replace( $title_re, '', $original_tag );
				$translation_tag = preg_replace( $title_re, '', $translation_tag );
				if ( $original_tag != $translation_tag ) {
					return $expected_error_msg;
				}
			}
		}
		return true;
	}

	function warning_placeholders( $original, $translation, $locale ) {
		$placeholders_re = apply_filters( 'warning_placeholders_re', '%[a-z]*|%[A-Z]+|%\d+\$(?:s|d)' );

		$original_counts = $this->_placeholders_counts( $original, $placeholders_re );
		$translation_counts = $this->_placeholders_counts( $translation, $placeholders_re );
		$all_placeholders = array_unique( array_merge( array_keys( $original_counts ), array_keys( $translation_counts ) ) );
		foreach( $all_placeholders as $placeholder ) {
			$original_count = gp_array_get( $original_counts, $placeholder, 0 );
			$translation_count = gp_array_get( $translation_counts, $placeholder, 0 );
			if ( $original_count > $translation_count ) {
				return sprintf(__('Missing %s placeholder in translation.'), $placeholder);
			}
			if ( $original_count < $translation_count ) {
				return sprintf(__('Extra %s placeholder in translation.'), $placeholder);
			}
		}
		return true;
	}

	function _placeholders_counts( $string, $re ) {
		$counts = array();
		preg_match_all( "/$re/", $string, $matches );
		foreach( $matches[0] as $match ) {
			$counts[$match] = gp_array_get( $counts, $match, 0) + 1;
		}
		return $counts;
	}

	function warning_both_begin_end_on_newlines( $original, $translation, $locale ) {
		if ( gp_endswith( $original, "\n" ) xor gp_endswith( $translation, "\n" ) ) {
			return __('Original and translation should both end on newline.');
		}
		if ( gp_startswith( $original, "\n" ) xor gp_startswith( $translation, "\n" ) ) {
			return __('Original and translation should both begin on newline.');
		}
		return true;
	}

	/**
	 * Adds all methods starting with warning_ to $translation_warnings
	 */
	function add_all( &$translation_warnings ) {
		$warnigs = array_filter( get_class_methods( get_class( $this ) ), create_function( '$f', 'return gp_startswith($f, "warning_");' ) );
		foreach( $warnigs as $warning ) {
			$translation_warnings->add( str_replace( 'warning_', '', $warning ), array( &$this, $warning ) );
		}
	}
}
