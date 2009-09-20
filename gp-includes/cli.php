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
		
		$this->program_name = array_shift( $argv );
		$this->options = getopt( $this->short_options );
	}
	
	function usage() {
		$this->error( $this->program_name.' '.$this->usage );
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