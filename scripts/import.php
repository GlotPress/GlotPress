<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Import extends GP_Translation_Set_Script {
	
	function __construct() {
		$this->short_options .= 'f:';
		$this->usage = '-f <po-file> '.$this->usage;
		parent::__construct();
	}
	
	function action_on_translation_set( $translation_set ) {
		$po = new PO();
		$po->import_from_file( $this->options['f'] );
		$added = $translation_set->import( $po );
		printf( _n( "%s translation were added\n", "%s translations were added\n", $added ), $added );
	}
	
}
$gp_script_import = new GP_Script_Import;
$gp_script_import->run();
