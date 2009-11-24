<?php
/**
 * Handles translation warnings
 */

class GP_Translation_Warnings {
	var $callbacks = array();

	function add( $id, $callback, $bucket = 'warning') {
		$this->callbacks[$bucket][$id] = $callback;
	}
	
	function remove( $id, $bucket = 'warning') {
		unset( $this->callbacks[$bucket][$id] );
	}
	
	function has( $id, $bucket = 'warning' ) {
		return isset( $this->callbacks[$bucket][$id] );
	}
	
	function test( $entry, $bucket = 'warning' ) {
		$problems = array();
		foreach( $this->callbacks[$bucket] as $id => $callback ) {
			$single_test = call_user_func( $callback, $entry );
			if ( is_array( $single_test ) && $single_test[0] === false ) {
				$problems[] = array( $id, $single_test[1] );
			}
		}
		return $problems;
	}
}