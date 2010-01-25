<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Import_Originals extends GP_CLI {
	
	var $short_options = 'p:f:';
	
	var $usage = "-p <project-path> -f <pot-file>";
	
	function run() {
		if ( !isset( $this->options['p'] ) ) {
			$this->usage();
		}
		$project = GP::$project->by_path( $this->options['p'] );
		if ( !$project ) $this->error( __('Project not found!') );
		
		$translations = new PO();
		$translations->import_from_file( $this->options['f'] );
		if ( !$translations ) $this->error( __('Error importing from POT file!') );
		
		GP::$original->import_for_project( $project, $translations );
	}
}

$gp_script_import_originals = new GP_Script_Import_Originals;
$gp_script_import_originals->run();