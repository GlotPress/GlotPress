<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Export extends GP_Translation_Set_Script {

	function __construct() {
		$this->short_options .= 'o:';
		$this->usage = $this->usage.' [-o <format (default=po)>]';
		parent::__construct();
	}

	function action_on_translation_set( $translation_set ) {

		$format = gp_array_get( GP::$formats, isset( $this->options['o'] )? $this->options['o'] : 'po', null );
		if ( !$format ) $this->error( __('No such format.') );;
		
		$entries = GP::$translation->for_export( $this->project, $translation_set );
		echo $format->print_exported_file( $this->project, $this->locale, $translation_set, $entries )."\n";
	}
	
}
$gp_script_export = new GP_Script_Export;
$gp_script_export->run();