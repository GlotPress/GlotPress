<?php
/**
 * BP_Options allows storage of options for BackPress
 * in the GlotPress/bbPress database
 */

class BP_Options
{
	public static function prefix() {
		return 'bp_glotpress_';
	}

	public static function get( $option ) {
		switch ( $option ) {
			case 'application_id':
				return 'glotpress';
			case 'application_uri':
				return gp_url();
			case 'cron_uri':
				return '';
			case 'cron_check':
				return '';
			case 'charset':
				return 'UTF-8';
			case 'wp_http_version':
				return 'GlotPress/' . gp_get_option('version');
			case 'hash_function_name':
				return 'gp_hash';
			default:
				return gp_get_option( BP_Options::prefix() . $option );
		}
	}

	public static function add( $option, $value ) {
		return BP_Options::update( $option, $value );
	}

	public static function update($option, $value) {
		return bb_update_option( BP_Options::prefix() . $option, $value );
	}

	public static function delete($option) {
		return bb_delete_option( BP_Options::prefix() . $option );
	}

} // END class BP_Options
