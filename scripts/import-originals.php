<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Import_Originals extends GP_CLI {

	var $short_options = 'p:f:o:';

	var $usage = "-p <project-path> -f <file> [-o <format>]";

	function run() {
		if ( !isset( $this->options['p'] ) ) {
			$this->usage();
		}
		$project = GP::$project->by_path( $this->options['p'] );
		if ( !$project ) $this->error( __('Project not found!') );

		$format = gp_array_get( GP::$formats, isset( $this->options['o'] )? $this->options['o'] : 'po', null );
		if ( !$format ) $this->error( __('No such format.') );;

		$translations = $format->read_originals_from_file( $this->options['f'], $project );
		if ( !$translations ) {
			$this->error( __("Couldn't load translations from file!") );
		}

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted ) = GP::$original->import_for_project( $project, $translations );
		printf(
			__( '%1$s new strings added, %2$s updated, %3$s fuzzied, and %4$s obsoleted.' ),
			$originals_added,
			$originals_existing,
			$originals_fuzzied,
			$originals_obsoleted
		);
		echo "\n";
	}
}

$gp_script_import_originals = new GP_Script_Import_Originals;
$gp_script_import_originals->run();
