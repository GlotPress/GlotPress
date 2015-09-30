<?php

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	gp_cli_register();
}

function gp_cli_register() {
	require_once dirname( __FILE__ ) . '/cli/add-admin.php';
	require_once dirname( __FILE__ ) . '/cli/branch-project.php';
	require_once dirname( __FILE__ ) . '/cli/import-originals.php';
	require_once dirname( __FILE__ ) . '/cli/regenerate-paths.php';
	require_once dirname( __FILE__ ) . '/cli/remove-multiple-currents.php';
	require_once dirname( __FILE__ ) . '/cli/upgrade-set-permissions.php';
	require_once dirname( __FILE__ ) . '/cli/wipe-permissions.php';
	require_once dirname( __FILE__ ) . '/cli/wporg2slug.php';

	WP_CLI::add_command( 'glotpress add-admin', 'GP_CLI_Add_Admin' );
	WP_CLI::add_command( 'glotpress branch-project', 'GP_CLI_Branch_Project' );
	WP_CLI::add_command( 'glotpress import-originals', 'GP_CLI_Import_Originals' );
	WP_CLI::add_command( 'glotpress regenerate-paths', 'GP_CLI_Regenerate_Paths' );
	WP_CLI::add_command( 'glotpress remove-multiple-currents', 'GP_CLI_Remove_Multiple_Currents' );
	WP_CLI::add_command( 'glotpress upgrade-set-permissions', 'GP_CLI_Upgrade_Set_Permissions' );
	WP_CLI::add_command( 'glotpress wipe-permissions', 'GP_CLI_Wipe_Permissions' );
	WP_CLI::add_command( 'glotpress wporg2slug', 'GP_CLI_WPorg2Slug' );
}

class GP_CLI {

	var $short_options = '';
	var $program_name = '';
	var $usage = '';

	function __construct() {
		global $argv;
		if ( gp_array_get( $_SERVER, 'HTTP_HOST' ) ) {
			die('CLI only!');
		}
		if ( !defined( 'STDERR' ) ) {
			define( 'STDERR', fopen( 'php://stderr', 'w' ) );
		}

		$this->program_name = array_shift( $argv );
		$this->options = getopt( $this->short_options );
		$this->args = $argv;
	}

	function usage() {
		$this->error( 'php '.$this->program_name.' '.$this->usage );
	}

	function to_stderr( $text, $no_new_line = false ) {
		$text .= ($no_new_line? '' : "\n");
		fwrite( STDERR, $text );
	}

	function error( $message, $exit_code = 1 ) {
		$this->to_stderr( $message );
		exit( $exit_code );
	}
}

class GP_Translation_Set_Script extends GP_CLI {

	var $short_options = 'p:l:t:';

	var $usage = "-p <project-path> -l <locale> [-t <translation-set-slug>]";

	function run() {
		if ( !isset( $this->options['l'] ) || !isset( $this->options['p'] ) ) {
			$this->usage();
		}
		$this->project = GP::$project->by_path( $this->options['p'] );
		if ( !$this->project ) $this->error( __('Project not found!') );

		$this->locale = GP_Locales::by_slug( $this->options['l'] );
		if ( !$this->locale ) $this->error( __('Locale not found!') );

		$this->options['t'] = gp_array_get( $this->options, 't', 'default' );

		$this->translation_set = GP::$translation_set->by_project_id_slug_and_locale( $this->project->id, $this->options['t'], $this->locale->slug );
		if ( !$this->translation_set ) $this->error( __('Translation set not found!') );

		$this->action_on_translation_set( $this->translation_set );
	}

	function action_on_translation_set( $translation_set ) {
		// define this function in a subclass
	}
}
