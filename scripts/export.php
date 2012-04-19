<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Export extends GP_Translation_Set_Script {
	
	var $filter_options = array(
		'f' => array( 'key' => 'term', 'desc' => 'search term' ),
		'q' => array( 'key' => 'status', 'desc' => 'translation string status' ),
	);

	function __construct() {
		$this->short_options .= 'o:';
		$this->usage .= ' [-o <format (default=po)>]';
		$this->add_filters_to_options_and_usage();
		parent::__construct();
	}
	
	function action_on_translation_set( $translation_set ) {

		$format = gp_array_get( GP::$formats, isset( $this->options['o'] )? $this->options['o'] : 'po', null );
		if ( !$format ) $this->error( __('No such format.') );;

		$entries = GP::$translation->for_export( $this->project, $translation_set, $this->get_filters_for_translation() );
		echo $format->print_exported_file( $this->project, $this->locale, $translation_set, $entries )."\n";
	}
	
	function add_filters_to_options_and_usage() {
		foreach( $this->filter_options as $option => $details ) {
			$this->short_options .= "$option:";
			$this->usage .= " [-$option <{$details['desc']}>]";
		}		
	}

	function get_filters_for_translation() {
		$filters = array();
		foreach( $this->filter_options as $option => $option_details ) {
			if ( isset( $this->options[$option] ) ) {
				$filters[$option_details['key']] = $this->options[$option];
			}
		}
		return $filters;
	}
}
$gp_script_export = new GP_Script_Export;
$gp_script_export->run();
