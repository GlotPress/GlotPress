<?php
/**
 * BP_Options allows storage of options for BackPress
 * in the GlotPress/bbPress database
 */

class BP_Options
{
	function prefix() {
		return 'bp_glotpress_';
	}
	
	function get($option) {
		switch ($option) {
			case 'application_id':
				return 'glotpress';
				break;
			case 'application_uri':
				return gp_url();
				break;
			case 'cron_uri':
				return '';
				break;
			case 'cron_check':
				return '';
				break;
			case 'charset':
				return 'UTF-8';
				break;
			case 'wp_http_version':
				return 'GlotPress/' . gp_get_option('version');
				break;
			case 'hash_function_name':
				return 'gp_hash';
				break;
			default:
				return gp_get_option(BP_Options::prefix() . $option);
				break;
		}
	}
	
	function add($option, $value) {
		return BP_Options::update($option, $value);
	}
	
	function update($option, $value) {
		return bb_update_option(BP_Options::prefix() . $option, $value);
	}
	
	function delete($option) {
		return bb_delete_option(BP_Options::prefix() . $option);
	}
} // END class BP_Options
