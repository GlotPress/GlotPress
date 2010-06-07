<?php

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
