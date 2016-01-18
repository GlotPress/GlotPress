# GlotPress

This is a community-backed plugin which brings [GlotPress](https://github.com/GlotPress/GlotPress) into WordPress as a plugin.

[![Build Status](https://travis-ci.org/GlotPress/GlotPress-WP.svg?branch=master)](https://travis-ci.org/GlotPress/GlotPress-WP) [![Code Coverage](https://scrutinizer-ci.com/g/GlotPress/GlotPress-WP/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/GlotPress/GlotPress-WP/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GlotPress/GlotPress-WP/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/GlotPress/GlotPress-WP/?branch=master)

## Contributing

GlotPress as a WordPress plugin is still a work in progress, we're working through [the issues](https://github.com/GlotPress/GlotPress-WP/issues) to keep improving it in future releases.

If you'd like to work on something and there's not currently an issue for it, open a new issue and describe your proposed change before jumping into coding.

## Installation

```bash
$ cd /your/wordpress/folder/wp-content/plugins/
$ git clone git@github.com:GlotPress/GlotPress-WP.git glotpress
```

After activating the plugin, GlotPress can be accessed via `<home_url>/glotpress/`

To access GlotPress under a different path, modify the `GP_URL_BASE` constant in `wp-config.php`, for example to run it in /, you'd add

```
define( 'GP_URL_BASE', '/' );
```

## Communication

* [WordPress Slack](https://chat.wordpress.org/): #glotpress
* [Blog](http://blog.glotpress.org/)

## Running Tests

```bash
$ ./tests/bin/run-unittests.sh -d testdb_name [ -u dbuser ] [ -p dbpassword ] [ -h dbhost ] [ -x dbprefix ] [ -w wpversion ] [ -D (drop-db) ] [ -c coverage_file ] [ -f phpunit_filter ]
```
