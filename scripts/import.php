<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Import extends GP_Translation_Set_Script {

	public $long_options = array(
		'disable-propagating',
	);

	function __construct() {
		$this->short_options .= 'f:';
		$this->usage = '-f <po-file> ' . $this->usage . ' [--disable-propagating]';
		parent::__construct();
	}

	function action_on_translation_set( $translation_set ) {
		$po = new PO();
		$po->import_from_file( $this->options['f'] );

		$disable_propagating = isset( $this->options['disable-propagating'] );
		if ( $disable_propagating ) {
			add_filter( 'enable_propagate_translations_across_projects', '__return_false' );
		}

		$added = $translation_set->import( $po );

		if ( $disable_propagating ) {
			remove_filter( 'enable_propagate_translations_across_projects', '__return_false' );
		}

		printf( _n( "%s translation was added\n", "%s translations were added\n", $added, 'glotpress' ), $added );
	}

}
$gp_script_import = new GP_Script_Import;
$gp_script_import->run();
