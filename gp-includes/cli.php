<?php

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	gp_cli_register();
}

function gp_cli_register() {
	require_once GP_PATH . GP_INC . 'cli/add-admin.php';
	require_once GP_PATH . GP_INC . 'cli/branch-project.php';
	require_once GP_PATH . GP_INC . 'cli/import-originals.php';
	require_once GP_PATH . GP_INC . 'cli/regenerate-paths.php';
	require_once GP_PATH . GP_INC . 'cli/remove-multiple-currents.php';
	require_once GP_PATH . GP_INC . 'cli/translation-set.php';
	require_once GP_PATH . GP_INC . 'cli/upgrade-set-permissions.php';
	require_once GP_PATH . GP_INC . 'cli/wipe-permissions.php';

	// Legacy commands.
	WP_CLI::add_command( 'glotpress add-admin', 'GP_CLI_Add_Admin' );
	WP_CLI::add_command( 'glotpress branch-project', 'GP_CLI_Branch_Project' );
	WP_CLI::add_command( 'glotpress import-originals', 'GP_CLI_Import_Originals' );
	WP_CLI::add_command( 'glotpress regenerate-paths', 'GP_CLI_Regenerate_Paths' );
	WP_CLI::add_command( 'glotpress remove-multiple-currents', 'GP_CLI_Remove_Multiple_Currents' );
	WP_CLI::add_command( 'glotpress upgrade-set-permissions', 'GP_CLI_Upgrade_Set_Permissions' );
	WP_CLI::add_command( 'glotpress wipe-permissions', 'GP_CLI_Wipe_Permissions' );

	// New style commands.
	WP_CLI::add_command( 'glotpress translation-set', 'GP_CLI_Translation_Set' );

	// CLI related filters.
	add_filter( 'gp_pre_can_set_translation_status', '__return_true' );
}

class GP_CLI {

	public $short_options = '';
	public $long_options = array();
	public $options = array();
	public $program_name = '';
	public $usage = '';
	public $args;
	public $project;
	public $locale;
	public $translation_set;

	public function __construct() {
		_deprecated_function( 'GP_CLI::__construct', '', 'WP_CLI_Command' );

		global $argv;
		if ( gp_array_get( $_SERVER, 'HTTP_HOST' ) ) {
			die('CLI only!');
		}
		if ( !defined( 'STDERR' ) ) {
			define( 'STDERR', fopen( 'php://stderr', 'w' ) );
		}

		$this->program_name = array_shift( $argv );
		$this->options = getopt( $this->short_options, $this->long_options );
		$this->args = $argv;
	}

	public function usage() {
		$this->error( 'php '.$this->program_name.' '.$this->usage );
	}

	public function to_stderr( $text, $no_new_line = false ) {
		$text .= ($no_new_line? '' : "\n");
		fwrite( STDERR, $text );
	}

	public function error( $message, $exit_code = 1 ) {
		$this->to_stderr( $message );
		exit( $exit_code );
	}
}

class GP_Translation_Set_Script extends GP_CLI {

	var $short_options = 'p:l:t:';

	var $usage = "-p <project-path> -l <locale> [-t <translation-set-slug>]";

	public function run() {
		if ( !isset( $this->options['l'] ) || !isset( $this->options['p'] ) ) {
			$this->usage();
		}
		$this->project = GP::$project->by_path( $this->options['p'] );
		if ( !$this->project ) $this->error( __( 'Project not found!', 'glotpress' ) );

		$this->locale = GP_Locales::by_slug( $this->options['l'] );
		if ( !$this->locale ) $this->error( __( 'Locale not found!', 'glotpress' ) );

		$this->options['t'] = gp_array_get( $this->options, 't', 'default' );

		$this->translation_set = GP::$translation_set->by_project_id_slug_and_locale( $this->project->id, $this->options['t'], $this->locale->slug );
		if ( !$this->translation_set ) $this->error( __( 'Translation set not found!', 'glotpress' ) );

		$this->action_on_translation_set( $this->translation_set );
	}

	public function action_on_translation_set( $translation_set ) {
		// define this function in a subclass

		do_action( 'gp_cli_action_on_translation_set', $translation_set );
	}
}
