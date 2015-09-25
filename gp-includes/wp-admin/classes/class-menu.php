<?php

/**
 * The WP Admin Menu class.
 *
 * This adds a top level menu for GlotPress, as well as sub menu items for managing
 * different parts of the plugin.
 *
 * @since      0.1
 * @package    GlotPress
 * @subpackage GlotPress/wp-admin/classes
 */
class GlotPress_WP_Admin_Menu {

	/**
	 * @var string
	 */
	protected $menu_name = 'GlotPress';

	/**
	 * @var string
	 */
	protected $menu_slug = 'glotpress';

	/**
	 * GlotPress_WP_Admin_Menu constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function admin_menu() {
		// Add top level GlotPress menu item
		add_menu_page( $this->menu_name, $this->menu_name, 'manage_options', $this->menu_slug, array( $this, 'render_page' ), 'dashicons-format-chat' );
	}
}


