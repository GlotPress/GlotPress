<?php

/**
 * The WP Admin class.
 *
 * This controls all of the WP admin functionality for GlotPress.
 *
 * @since      0.1
 * @package    GlotPress
 * @subpackage GlotPress/wp-admin
 */
class GlotPress_WP_Admin {

	/**
	 * @var GlotPress_WP_Admin
	 */
	protected static $instance = null;
	
	/**
	 * GlotPress_WP_Admin constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->loader();
	}

	/**
	 * Make this class a singleton
	 *
	 * Use this instead of __construct()
	 *
	 * @return GlotPress_WP_Admin
	 */
	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * As this class is a singelton it should not be clone-able
	 */
	protected function __clone() {
		// Singleton
	}

	protected function includes() {
	}

	protected function loader() {
	}
}