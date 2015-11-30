#!/bin/bash

RUN_CMD="tests/bin/run-unittests.sh -d wordpress_test"

if [ "latest" == "${WP_VERSION}" -a "0" == "${WP_MULTISITE}" -a "5.3" == "${TRAVIS_PHP_VERSION}" ]
then
	bash ${RUN_CMD} -c /tmp/clover.xml

	# Send coverage to Scrutinizer CI.
	curl -sSL https://scrutinizer-ci.com/ocular.phar -o ocular.phar
	php ocular.phar code-coverage:upload --format=php-clover /tmp/clover.xml

	# Quick check that coverage hasn't dropped.
	tests/bin/coverage-checker.php /tmp/clover.xml 20
else
	bash ${RUN_CMD}
fi