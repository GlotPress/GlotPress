<?php
require_once('init.php');

$GLOBALS['gpdb']->mytable = 'mytable';

class GP_My_Table extends GP_Thing {
	var $field_names = array('id', 'name', 'description');
	var $table_basename = 'mytable';
}

class GP_Test_Thing extends GP_UnitTestCase {
	
	function setUp() {
		$this->t = new GP_My_Table;
	}
	
	function test_sql_condition_from_php_value() {
		$this->assertEquals( '= 5', $this->t->sql_condition_from_php_value( 5 ) );
		$this->assertEquals( '= 5', $this->t->sql_condition_from_php_value( '5' ) );
		$this->assertEquals( "= 'baba'", $this->t->sql_condition_from_php_value( 'baba' ) );
		$this->assertEquals( "IS NULL", $this->t->sql_condition_from_php_value( null ) );
		$this->assertEquals( array('= 5', '= 10'), $this->t->sql_condition_from_php_value( array( 5, 10 ) ) );
		$this->assertEquals( array("= 'baba'", "= 10", "= 'don\\'t'"), $this->t->sql_condition_from_php_value( array( 'baba', '10', "don't" ) ) );
	}
	
	function test_sql_from_conditions() {
		$this->assertEquals( 'a = 5', $this->t->sql_from_conditions( array('a' => 5) ) );
		$this->assertEquals( "(a = 5 OR a = 6) AND b = 'baba'", $this->t->sql_from_conditions( array('a' => array(5, 6), 'b' => 'baba' ) ) );
	}
}