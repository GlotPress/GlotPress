<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Export extends GP_Translation_Set_Script {
	
	function action_on_translation_set( $translation_set ) {
		echo $translation_set->export_as_po() . "\n";
	}
	
}
$gp_script_export = new GP_Script_Export;
$gp_script_export->run();