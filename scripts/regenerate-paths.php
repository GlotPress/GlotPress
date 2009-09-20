<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Regenerate extends GP_CLI {
	
	function run() {
		GP::$project->regenerate_paths();
	}
}
$gp_script_regenerate = new GP_Script_Regenerate;
$gp_script_regenerate->run();