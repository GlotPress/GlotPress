<?php

class GP_Test_Strings extends GP_UnitTestCase {
	function test_gp_sanitize_for_url() {
		$this->assertEquals( 'baba', gp_sanitize_for_url( 'baba') );
		$this->assertEquals( 'baba-and-dyado', gp_sanitize_for_url( 'Baba and Dyado') );
		$this->assertEquals( 'баба-и-дядо', gp_sanitize_for_url( 'Баба и Дядо') );
		$this->assertEquals( 'баба-и-дядо', gp_sanitize_for_url( 'Баба 	и 	Дядо') );
		$this->assertEquals( 'mu', gp_sanitize_for_url( '/Mu#/') );
		$this->assertEquals( 'баба-дядо', gp_sanitize_for_url( 'Баба &amp; дядо') );
		$this->assertEquals( 'caca-и-цацабууу', gp_sanitize_for_url( 'Caca и цацаБУУУ') );
	}

	function test_gp_string_similarity() {
		$string1 = 'Word';
		$string2 = 'Word!';
		$string3 = 'Word';

		$similarity = gp_string_similarity( $string1, $string2 );
		$similarity_2 = gp_string_similarity( $string1, $string3 );

		$this->assertEquals( $similarity, 0.775 );
		$this->assertEquals( $similarity_2, 1 );
	}

}