<?php
require_once 'PHPUnit/Framework.php';

define( 'GP_CONFIG_FILE', dirname( __FILE__ ) . '/unittests-config.php' );

require_once dirname( __FILE__ ) . '/../gp-load.php';

require dirname( __FILE__ ) . '/lib/testcase.php';
require dirname( __FILE__ ) . '/lib/testcase-route.php';
require dirname( __FILE__ ) . '/lib/testcase-request.php';
