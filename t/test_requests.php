<?php
require_once('init.php');

class GP_Test_Requests extends GP_UnitTestCase_Request {
    function test_index() {
        $this->get( '/' );
        $this->assertRedirect();
    }
    
    function test_projects() {
        $this->get( '/projects' );
        $this->assertResponseContains( 'Projects' );
        $this->assertResponseNotContains( 'baba' );
    }   
}