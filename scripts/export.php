<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Export extends GP_CLI {
	
	var $short_options = 'p:l:t:';
	
	var $usage = "-p <project-path> -l <locale> [-t <translation-set-slug>]";
	
	function run() {
		if ( !isset( $this->options['l'] ) || !isset( $this->options['p'] ) ) {
			$this->usage();
		}
		$project = GP::$project->by_path( $this->options['p'] );
		if ( !$project ) $this->error( 'Project not found!' );
		
		$locale = GP_Locales::by_slug( $this->options['l'] );
		if ( !$locale ) $this->error( 'Locale not found!' );
		
		$this->options['t'] = gp_array_get( $this->options, 't', 'default' );
		
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $this->options['t'], $locale->slug );
		if ( !$translation_set ) $this->error( 'Translation set not found!' );

		echo $translation_set->export_as_po() . "\n";
	}
}
$gp_script_export = new GP_Script_Export;
$gp_script_export->run();