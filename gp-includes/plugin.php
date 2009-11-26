<?php

class GP_Plugin {
	
	var $id;
	
	function __construct() {
		$this->_object_type = 'plugin_'.$this->id;
	}
		
	function get_option( $key ) {
		global $gpdb;
		// TODO: add caching (see gp_get_option_from_db())
		$row = $gpdb->get_row( $gpdb->prepare( "SELECT `meta_value` FROM `$gpdb->meta` WHERE `object_type` = %s AND `meta_key` = %s", $this->_object_type, $key ) );
		if ( is_object( $row ) ) {
			$r = maybe_unserialize( $row->meta_value );
		} else {
			$r = null;
		}
		return $r;
	}
	
	function update_option( $key, $value ) {
		return gp_update_meta( 0, $key, $value, $this->_object_type, true );
	}
	
	function add_action( $tag, $priority = 10, $accepted_args = 1 ) {
		add_action( $tag, array( &$this, $tag ), $priority, $accepted_args );
	}
}