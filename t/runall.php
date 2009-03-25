<?php
require_once('init.php');
require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');


$test = &new TestSuite('All GloPress tests');

foreach(glob('test_*.php') as $test_file)  {
    $test->addFile($test_file);
}

$test->run(new DefaultReporter());
?>