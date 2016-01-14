<?php

require_once dirname( __FILE__ ) . '/factory.php';

class GP_UnitTestCase extends WP_UnitTestCase {

	var $url = 'http://example.org/';

	function setUp() {
		parent::setUp();

		$this->factory = new GP_UnitTest_Factory;
		$this->url_filter = returner( $this->url );

		global $wp_rewrite;
		if ( $wp_rewrite->permalink_structure ) {
			$this->set_permalink_structure( '' );
		}
	}

	function clean_up_global_scope() {
		parent::clean_up_global_scope();

		$locales = &GP_Locales::instance();
		$locales->locales = array();
		$_GET = array();
		$_POST = array();
		/**
		 * @todo re-initialize all thing objects
		 */
		GP::$translation_set = new GP_Translation_Set;
		GP::$original = new GP_Original;
	}

	function set_normal_user_as_current() {
		$user = $this->factory->user->create();
		wp_set_current_user( $user );
		return $user;
	}

	function set_admin_user_as_current() {
		$admin = $this->factory->user->create_admin();
		wp_set_current_user( $admin );
		return $admin;
	}
}
