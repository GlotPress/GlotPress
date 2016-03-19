<?php

require_once dirname( __FILE__ ) . '/factory.php';

class GP_UnitTestCase extends WP_UnitTestCase {

	var $url = 'http://example.org/';

	function setUp() {
		parent::setUp();

		$this->factory = new GP_UnitTest_Factory;
		$this->url_filter = returner( $this->url );

		global $wp_rewrite;
		if ( GP_TESTS_PERMALINK_STRUCTURE != $wp_rewrite->permalink_structure ) {
			$this->set_permalink_structure( GP_TESTS_PERMALINK_STRUCTURE );
		}
	}

	/**
	 * Utility method that resets permalinks and flushes rewrites.
	 *
	 * Also updates the pre_option filter for `permalink_structure`.
	 *
	 * @global WP_Rewrite $wp_rewrite
	 *
	 * @param string $structure Optional. Permalink structure to set. Default empty.
	 */
	public function set_permalink_structure( $structure = '' ) {
		global $wp_tests_options;

		$wp_tests_options['permalink_structure'] = $structure;

		parent::set_permalink_structure( $structure );
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
