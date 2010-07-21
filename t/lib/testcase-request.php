<?php

class GP_UnitTestCase_Request extends GP_UnitTestCase {
    var $body = null;
    
    function get( $uri, $get_vars = array() ) {
        $this->request( $uri, 'GET', $get_vars );
    }

    function post( $uri, $get_vars = array() ) {
        $this->request( $uri, 'POST', $get_vars );
    }

    private function request( $uri, $method, $vars ) {
        $tmp_file_name = tempnam( sys_get_temp_dir(), 'gp-test-request-config' );
        if ( !$tmp_file_name) {
            return false;
        }
        $config_vars = array(
            'upper_method' => strtoupper( $method ),
            'vars' => $vars,
            'uri' => $uri,
            'gp_config_path' => dirname( __FILE__ ) . '/../unittests-config.php',
        );
        extract( array_map( create_function( '$value', 'return var_export( $value, true );' ), $config_vars ) );
        $config_php_code = <<<CONFIG
<?php
\$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
\$_SERVER['REQUEST_URI'] = $uri;
\$GLOBALS['_'.$upper_method] = $vars;
\$_SERVER['REQUEST_METHOD'] = $upper_method;
define( 'GP_CONFIG_FILE', $gp_config_path );
CONFIG;
        file_put_contents( $tmp_file_name, $config_php_code );        
        ob_start();
        /* We need to start a new PHP process, because header() doesn't like previous output and we have plenty */
        system('php '.escapeshellarg( dirname( __FILE__ ) . '/../bin/request.php' ).' '.escapeshellarg( $tmp_file_name ) );
        /* We can't get the headers, because there is no way to tell the CLI SAPI to return them */
        $this->body = ob_get_contents();
        ob_end_clean();
    }

    function assertRedirect() {
        $this->assertTrue( gp_startswith( $this->body, 'Redirecting to: ') );
    }
    
    function assertResponseContains( $needle ) {
        $this->assertTrue( gp_in( $needle, $this->body ) );
    }
    
    function assertResponseNotContains( $needle ) {
        $this->assertFalse( gp_in( $needle, $this->body ) );
    }   
}