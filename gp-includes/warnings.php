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
	
	function check_entry( $entry, $locale ) {
		$problems = array();
		foreach( $entry->translations as $translation_index => $translation ) {
			$problems[$translation_index] = array();
			foreach( $this->callbacks as $callback_id => $callback ) {
				$singular_test = call_user_func( $callback, $entry->singular, $translation, $locale );
				if ( true !== $singular_test ) {
					$problems[$translation_index][$callback_id] = $singular_test;
				}
				if ( $entry->is_plural ) {
					$plural_test = call_user_func( $callback, $entry->plural, $translation, $locale );
					if ( true !== $plural_test ) {
						$problems[$translation_index][$callback_id] = $plural_test;
					}
				}
			}
		}
		return $problems;
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
		$len_src = gp_strlen( $original );
		$len_trans = gp_strlen( $translation );
		if ( !( $this->length_lower_bound*$len_src < $len_trans && $len_trans < $this->length_upper_bound*$len_src ) &&
				( !gp_in( '_abbreviation', $original ) && !gp_in( '_initial', $original ) ) ) {
			return 'Lenghts of source and translation differ too much.';
		}
		return true;
	}
	
	function warning_tags( $original, $translation, $locale ) {
		$tag_pattern = "(<.*>)";
		$tag_re = "/$tag_pattern/Us";
		$original_parts = preg_split($tag_re, $original, -1, PREG_SPLIT_DELIM_CAPTURE);
		$translation_parts = preg_split($tag_re, $translation, -1, PREG_SPLIT_DELIM_CAPTURE);
		if ( count( $original_parts) > count( $translation_parts ) ) {
			return 'Missing tags from translation.';
		}
		if ( count( $original_parts) < count( $translation_parts ) ) {
			return 'Too many tags in translation.';
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
		$placeholders_re = apply_filters( 'warning_placeholders_re', '%[a-z]*|%\d+\$(?:s|d)' );

		$original_counts = $this->_placeholders_counts( $original, $placeholders_re );
		$translation_counts = $this->_placeholders_counts( $translation, $placeholders_re );
		$all_placeholders = array_unique( array_merge( array_keys( $original_counts ), array_keys( $translation_counts ) ) );
		foreach( $all_placeholders as $placeholder ) {
			$original_count = gp_array_get( $original_counts, $placeholder, 0 );
			$translation_count = gp_array_get( $translation_counts, $placeholder, 0 );
			if ( $original_count > $translation_count ) {
				return 'Missing '.$placeholder.' placeholder in translaion.';
			}
			if ( $original_count < $translation_count ) {
				return 'Extra '.$placeholder.' placeholder in translaion.';
			}
		}
		return true;
	}
	
	function _placeholders_counts( $string, $re ) {
		$counts = array();
		preg_match_all( "/$re/", $string, $matches );
		foreach( $matches[0] as $match ) {
			$counts[$match]++;
		}
		return $counts;
	}
	
	function add_all( &$translation_warnings ) {
		$warnigs = array_filter( get_class_methods( get_class( $this ) ), create_function( '$f', 'return gp_startswith($f, "warning_");' ) );
		foreach( $warnigs as $warning ) {
			$translation_warnings->add( str_replace( 'warning_', '', $warning ), array( &$this, $warning ) );
		}
	}
}