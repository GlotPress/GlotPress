<?php
require_once('init.php');

class GP_Test_Urls extends GP_UnitTestCase {
	
	function setUp() {
	    $this->sub_dir = '/gp/';
		$this->url = 'http://example.org' . $this->sub_dir;
		parent::setUp();
	}
	
	function test_gp_url_should_just_add_simple_path_string_if_query_is_missing() {
		$this->assertEquals( $this->sub_dir . 'baba', gp_url( 'baba' ) );
	}
	function test_gp_url_should_not_add_query_string_if_query_is_empty_string() {
		$this->assertEquals( $this->sub_dir . 'baba', gp_url( 'baba', '' ) );
	}
	
	function test_gp_url_should_not_add_query_string_if_query_is_empty_array() {
		$this->assertEquals( $this->sub_dir . 'baba', gp_url( 'baba', array() ) );
	}

	function test_gp_url_should_properly_add_query_string_if_path_is_empty() {
		$this->assertEquals( $this->sub_dir . '?a=b', gp_url( '', '?a=b' ) );
	}

	function test_gp_url_should_add_question_mark_if_query_string_does_not_have_one() {
		$this->assertEquals( $this->sub_dir . 'baba?a=b', gp_url( 'baba', 'a=b' ) );
	}
		
	function test_gp_url_should_expand_query_array() {
		$this->assertEquals( $this->sub_dir . '?a=b', gp_url( '', array('a' => 'b') ) );
	}
		
	function test_gp_url_should_add_ampersand_if_path_is_empty_and_query_array_has_more_than_one_value() {
		$this->assertEquals( $this->sub_dir . '?a=b&b=c', gp_url( '', array('a' => 'b', 'b' => 'c') ) );
	}

	function test_gp_url_should_add_ampersand_if_query_array_has_more_than_one_value() {
		$this->assertEquals( $this->sub_dir . 'baba?a=b&b=c', gp_url( 'baba', array('a' => 'b', 'b' => 'c') ) );
	}
		
	function test_gp_url_should_not_add_double_slash_if_path_starts_with_slash() {
		$this->assertEquals( $this->sub_dir . 'baba/wink', gp_url( '/baba/wink' ) );
	}
	
	function test_gp_url_should_urlencode_query_var_values() {
		$this->assertEquals( $this->sub_dir . 'baba?a=a%26b&b=c', gp_url( 'baba', array('a' => 'a&b', 'b' => 'c') ) );
	}
	
	function test_gp_url_join_should_return_the_string_if_single_string_without_slashes_is_passed() {
		$this->assertEquals( 'baba', gp_url_join( 'baba' ) );
	}
	
	function test_gp_url_join_should_join_with_slash_two_strings_without_slashes() {
		$this->assertEquals( 'baba/dyado', gp_url_join( 'baba', 'dyado' ) );
	}
	
	function test_gp_url_join_should_include_only_one_slash_if_first_string_ends_with_slash_and_next_begins_with_slash() {
		$this->assertEquals( 'baba/dyado', gp_url_join( 'baba/', '/dyado' ) );
	}
	
	function test_gp_url_join_should_discard_multiple_slashes_in_the_end_of_component() {
		$this->assertEquals( '/baba/dyado', gp_url_join( '/baba//', 'dyado' ) );
	}
	
	function test_gp_url_join_should_discard_multiple_slashes_in_the_beginning_of_component() {
		$this->assertEquals( '/baba/dyado', gp_url_join( '/baba/', '//dyado' ) );
	}

	function test_gp_url_join_should_not_discard_slash_if_the_whole_first_component_is_slash() {
			$this->assertEquals( '/baba/', gp_url_join( '/baba/', '/' ) );
			$this->assertEquals( '/baba/', gp_url_join( '/baba/', '//' ) );
	}

	function test_gp_url_join_should_not_discard_slash_if_the_whole_last_component_is_slash() {
			$this->assertEquals( '/baba/', gp_url_join( '/', '/baba/' ) );
			$this->assertEquals( '/baba/', gp_url_join( '//', '/baba/' ) );
	}
	
	function test_gp_url_join_should_return_only_one_slash_if_the_only_component_is_a_slash() {
		$this->assertEquals( '/', gp_url_join( '/' ) );
	}
	
	function test_gp_url_join_should_return_only_one_slash_if_all_components_are_slashes() {
		$this->assertEquals( '/', gp_url_join( '/', '/' ) );
	}
	
	function test_gp_url_join_should_return_only_one_slash_if_all_components_are_multiple_slashes() {
		$this->assertEquals( '/', gp_url_join( '///', '///' ) );
	}
	
	function test_gp_url_join_should_skip_empty_components() {
		$this->assertEquals( 'a/b', gp_url_join( 'a', '', 'b' ) );
	}
	
	function test_gp_url_join_should_skip_empty_components_in_the_beginning() {
		$this->assertEquals( 'a/b', gp_url_join( '', 'a', 'b' ) );
	}

	function test_gp_url_join_should_skip_empty_components_in_the_end() {
		$this->assertEquals( 'a/b', gp_url_join( 'a', 'b', '' ) );
	}
		
	function test_gp_url_join_should_accept_array_component_with_one_element_and_return_this_element() {
		$this->assertEquals( 'baba', gp_url_join( array( 'baba' ) ) );
	}
	
	function test_gp_url_join_should_join_array_component_values_as_if_they_were_given_as_different_arguments() {
		$this->assertEquals( 'baba/dyado', gp_url_join( array( 'baba', 'dyado' ) ) );
	}
	
	function test_gp_url_join_should_flatten_nested_arrays() {
		$this->assertEquals( 'baba/dyado/chicho/lelya', gp_url_join( array( 'baba', array( 'dyado', array( 'chicho' ), 'lelya' ) ) ) );
	}
	
	function test_gp_url_join_should_return_empty_string_with_nested_empty_arrays() {
		$this->assertEquals( '', gp_url_join( array( array() ), array() ) );
	}
	
	function test_gp_url_join_should_not_break_http() {
		$this->assertEquals( 'http://dir.bg/baba', gp_url_join( 'http://dir.bg/', 'baba' ) );
	}
	
	function test_gp_url_project_should_join_its_arguments() {
		$url_from_gp_url_project = gp_url_project( '/x', 'import-originals' );
		$url_manually_joined = gp_url_join( gp_url_project( '/x' ), 'import-originals' );
 		$this->assertEquals( $url_manually_joined, $url_from_gp_url_project );
	}
	function test_gp_url_project_should_join_its_array_arguments() {
		$url_from_gp_url_project = gp_url_project( '/x', array( 'slug', 'slugslug', 'import-translations' ) );
		$url_manually_joined = gp_url_join( gp_url_project( '/x' ), 'slug', 'slugslug', 'import-translations' );
		$this->assertEquals( $url_manually_joined, $url_from_gp_url_project );
	}	
}
