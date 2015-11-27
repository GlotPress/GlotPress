<?php

class GP_Test_Rewrite extends GP_UnitTestCase {

	function test_gp_generate_rewrite_rules_default() {
		$expected = '^' . trim( gp_url_base_path(), '/' ) . '/?(.*)$';
		$this->assertEquals( $expected, gp_generate_rewrite_rules() );
	}

	function test_gp_generate_rewrite_rules_empty() {
		$this->assertEquals( '^(.*)$', gp_generate_rewrite_rules( '' ) );
	}

	function test_gp_rewrite_rules_glotpress_path() {
		gp_rewrite_rules();
		global $wp_rewrite;

		$rewrite_rules = gp_generate_rewrite_rules();
		$this->assertArrayHasKey( $rewrite_rules, $wp_rewrite->extra_rules_top );
		$this->assertEquals( 'index.php?gp_route=$matches[1]', $wp_rewrite->extra_rules_top[ $rewrite_rules ] );
		$this->assertEquals( get_option( 'gp_rewrite_rule', false ), $rewrite_rules );
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	function test_gp_rewrite_rules_glotpress_root() {
		define( 'GP_URL_BASE', '' );
		gp_rewrite_rules();
		global $wp_rewrite;

		$rewrite_rules = gp_generate_rewrite_rules();
		$this->assertArrayHasKey( '$', $wp_rewrite->extra_rules_top );
		$this->assertEquals( 'index.php?gp_route', $wp_rewrite->extra_rules_top['$'] );

		$this->assertArrayHasKey( $rewrite_rules, $wp_rewrite->extra_rules_top );
		$this->assertEquals( 'index.php?gp_route=$matches[1]', $wp_rewrite->extra_rules_top[ $rewrite_rules ] );
		$this->assertEquals( get_option( 'gp_rewrite_rule', false ), $rewrite_rules );
	}

	function test_gp_query_vars() {
		$this->assertContains( 'gp_route', gp_query_vars( array() ) );
	}
}