<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_WPorg2Slug extends GP_CLI {
	
	var $usage = "<wporg-locale>";
	
	function run() {
		if ( !isset( $this->args[0] ) ) {
			$this->usage();
		}
		$wporg_slug = $this->args[0];
		$slug = null;
		foreach( GP_Locales::locales() as $locale ) {
			if ( $locale->wp_locale == $wporg_slug ) {
				$slug = $locale->slug;
				break;
			}
		}
		if ( !$slug )
			$this->to_stderr("No slug match for $wporg_slug.");
		else
			echo $slug;
	}
}

$gp_script_wporg2slug = new GP_Script_WPorg2Slug;
$gp_script_wporg2slug->run();
