<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Import_Originals extends GP_CLI {

	public $short_options = 'p:f:o:';
	public $long_options = array(
		'disable-propagating',
		'disable-matching',
	);

	public $usage = "-p <project-path> -f <file> [-o <format>] [--disable-propagating] [--disable-matching]";

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

		$disable_propagating = isset( $this->options['disable-propagating'] );
		$disable_matching = isset( $this->options['disable-matching'] );

		if ( $disable_propagating ) {
			add_filter( 'enable_propagate_translations_across_projects', '__return_false' );
		}
		if ( $disable_matching ) {
			add_filter( 'enable_add_translations_from_other_projects', '__return_false' );
		}

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted ) = GP::$original->import_for_project( $project, $translations );

		if ( $disable_matching ) {
			remove_filter( 'enable_add_translations_from_other_projects', '__return_false' );
		}
		if ( $disable_propagating ) {
			remove_filter( 'enable_propagate_translations_across_projects', '__return_false' );
		}

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
