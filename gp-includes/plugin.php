<?php

/**
 * GlotPress plugin base class. It is supposed to be inherited.
 */
class GP_Plugin {

	/**
	 * @var $id unique id of the plugin. It will be used as a plugin slug in the plugin repository
	 */
	var $id;
	
	/**
	 * If you override the constructor, always call the parent one.
	 */
	function __construct() {
		$this->_object_type = 'plugin_'.$this->id;
	}
	
	
	/**
	 * Retrieve an option, specific to your plugin.
	 * 
	 * You don't have to prefix the key or to tie its name to your plugin.
	 * 	
	 * @param string $key
	 * @return mixed the value of the option, or null if it wasn't found
	 */
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
	
	/**
	 * Update an option, specific to your plugin.
	 * 
	 * You don't have to prefix the key or to tie its name to your plugin.
	 * 
	 * @param string $key
	 * @param mixed $value Can be anything serializable
	 * @return bool
	 */
	function update_option( $key, $value ) {
		return gp_update_meta( 0, $key, $value, $this->_object_type, true );
	}
	
	/**
	 * Adds a method in this class as an action with the same name.
	 * 
	 * For example $this->add_action( 'init', ... ) will add $this->init() as an init action
	 * 
	 * @param string $action_name The name of the action and the method.
	 * @param array $args Two keys are supported:
	 * 	- priority => priority of the action. Positive integer. Lower priority means earlier execution. Default is 10.
	 *  - args => how many arguments the action accepts. Default is 1.
	 * 
	 * @see add_action()
	 */
	function add_action( $action_name, $args = array() ) {
		return $this->add_filter( $action_name, $args );
	}

	/**
	 * Adds a method in this class as a filter with the same name.
	 * 
	 * For example $this->add_filter( 'the_content', ... ) will add $this->the_content() as a the_content filter
	 * 
	 * @param string $filter_name The name of the filter and the method.
	 * @param array $args Two keys are supported:
	 * 	- priority => priority of the filter. Positive integer. Lower priority means earlier execution. Default is 10.
	 *  - args => how many arguments the filter accepts. Default is 1.
	 * 
	 * @see add_action()
	 */
	function add_filter( $filter_name, $args = array() ) {
		return $this->_call_wp_plugin_api_function( 'add_filter', $filter_name, $args );
	}

	function _call_wp_plugin_api_function( $wp_function, $tag, $args = array() ) {
		$args['tag'] = $tag;
		$defaults = array( 'priority' => 10, 'args' => 1 );
		$args = array_merge( $defaults, $args );
		return $wp_function( $args['tag'], array( &$this, $args['tag'] ), $args['priority'], $args['args'] );
	}
	
	function remove_action( $action_name, $args = array() ) {
		return $this->remove_filter( $action_name, $args );
	}
	
	function remove_filter( $filter_name, $args = array() ) {
		return $this->_call_wp_plugin_api_function( 'remove_filter', $filter_name, $args );
	}
}