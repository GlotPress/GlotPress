<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Branch_Project extends GP_CLI {

	var $short_options = 's:d:';

	var $usage = "-s <source project-path> -d <desination project-path>";

	function run() {
		if ( ! isset( $this->options['s'] ) || !isset( $this->options['d'] )  ) {
			$this->usage();
		}
		$source_project = GP::$project->by_path( $this->options['s'] );
		if ( ! $source_project ){
			$this->error( __( 'Source project not found!', 'glotpress' ) );
		}

		$destination_project = GP::$project->by_path( $this->options['d'] );
		if ( ! $destination_project ){
			$this->error( __( 'Destination project not found!', 'glotpress' ) );
		}

		$destination_project->duplicate_project_contents_from( $source_project );

	}
}

$gp_script_import_originals = new GP_Script_Branch_Project;
$gp_script_import_originals->run();
