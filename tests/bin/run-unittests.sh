#!/bin/bash

PLUGIN_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." && pwd )"
TESTS_DIR="${PLUGIN_DIR}/tests"

cd "${PLUGIN_DIR}"

usage() {
	echo "Usage: $0 -d testdb_name [ -u dbuser ] [ -p dbpassword ] [ -h dbhost ] [ -x dbprefix ] [ -w wpversion ] [ -D (drop-db) ] [ -c coverage_file ] [ -f phpunit_filter ]"
	exit 2
}

while getopts "c:d:u:p:h:x:w:Df:" ARG
do
	case ${ARG} in
		c)	COVERAGE_FILE=$OPTARG;;
		d)	DB_NAME=$OPTARG;;
		u)	DB_USER=$OPTARG;;
		p)	DB_PASS=$OPTARG;;
		h)	DB_HOST=$OPTARG;;
		x)	DB_PREFIX=$OPTARG;;
		w)	WP_VERSION=$OPTARG;;
		D)	DROP_DB=true;;
		f)	PHPUNIT_FILTER=$OPTARG;;
		\?)	usage;;
	esac
done
shift `expr $OPTIND - 1`

if [ -z ${DB_NAME} ]
then
	echo "Test Database Name required."
	usage
fi

DB_USER=${DB_USER-root}
DB_HOST=${DB_HOST-localhost}
DB_PREFIX=${DB_PREFIX-wptests_}
WP_VERSION=${WP_VERSION-nightly}
DROP_DB=${DROP_DB-false}

for PROG in mysqladmin composer
do
    which ${PROG}
    if [ 0 -ne $? ]
    then
        echo "${PROG} not found in path."
        exit 1
    fi
done

function init_env() {
    # Drop database.
    if [ 'true' == ${DROP_DB} ]
    then
        mysqladmin drop ${DB_NAME} --user=${DB_USER} --password="${DB_PASS}" --force
    fi
}

init_env

bash "$TESTS_DIR/bin/install-wp-tests.sh" ${DB_NAME} ${DB_USER} "${DB_PASS}" ${DB_HOST} ${WP_VERSION}

PHPUNIT_OPTS=""

if [ -n "${PHPUNIT_FILTER}" ]
then
	PHPUNIT_OPTS="$PHPUNIT_OPTS --filter ${PHPUNIT_FILTER}"
fi

if [ -n "${COVERAGE_FILE}" ]
then
	PHPUNIT_OPTS="$PHPUNIT_OPTS --coverage-clover=${COVERAGE_FILE}"
fi

phpunit ${PHPUNIT_OPTS}
