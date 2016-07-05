<?php

/**
 * @group strings
 */
class GP_Test_Strings extends GP_UnitTestCase {
	function test_gp_string_similarity() {
		$string1 = 'Word';
		$string2 = 'Word!';
		$string3 = 'Word';

		$similarity = gp_string_similarity( $string1, $string2 );
		$similarity_2 = gp_string_similarity( $string1, $string3 );

		$this->assertEquals( $similarity, 0.775 );
		$this->assertEquals( $similarity_2, 1 );
	}

	/**
	 * @dataProvider data_attributes_with_entities
	 */
	function test_gp_esc_attr_with_entities( $expected, $attribute ) {
		$this->assertEquals( $expected, gp_esc_attr_with_entities( $attribute ) );
	}

	function data_attributes_with_entities() {
		return array(
			array( '&amp;#8212;', '&#8212;' ), // https://glotpress.trac.wordpress.org/ticket/12
			array( 'Foo &amp; Bar', 'Foo & Bar' ),
			array( '&quot;&amp;hellip;&quot;', '"&hellip;"' ),
		);
	}

	/**
	 * @dataProvider data_translations_with_entities
	 */
	function test_esc_translation( $expected, $translation ) {
		$this->assertEquals( $expected, esc_translation( $translation ) );
	}

	function data_translations_with_entities() {
		return array(
			array( 'Foo bar&amp;hellip;', 'Foo bar&hellip;' ),
			array( 'Foo &lt;span class="count"&gt;(%s)&lt;/span&gt;', 'Foo <span class="count">(%s)</span>' ),
			array( '"&amp;hellip;"', '"&hellip;"' ),
		);
	}

	function test_gp_sanitize_project_name() {
		$this->assertEquals( gp_sanitize_project_name( 'plugin V1.2.1' ), 'plugin-v1.2.1' );
		$this->assertEquals( gp_sanitize_project_name( 'plugin \/<1.2.1>' ), 'plugin' );
		$this->assertEquals( gp_sanitize_project_name( 'GlotPress&Plugin@1.1.1' ), 'glotpressplugin1.1.1' );
	}
}
