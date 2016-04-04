# GlotPress

GlotPress is a WordPress plugin to let you set up your own collaborative, web-based software translation tool.

[![Build Status](https://travis-ci.org/GlotPress/GlotPress-WP.svg?branch=develop)](https://travis-ci.org/GlotPress/GlotPress-WP) [![codecov.io](https://codecov.io/github/GlotPress/GlotPress-WP/coverage.svg?branch=develop)](https://codecov.io/github/GlotPress/GlotPress-WP?branch=develop) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GlotPress/GlotPress-WP/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/GlotPress/GlotPress-WP/?branch=develop)

## Contributing

Many open source projects span regions, countries and dialects and need to support a variety of translations, GlotPress is here to help you collaborate online with your translators to ensure your users see your software in their native language.

GlotPress has two versions, a standalone version and this WordPress plugin version.  At this time these two versions are functionally similar, but the plugin version is likely to start moving away from the standalone version in future versions.  For the rest of this document, any reference to "GlotPress" should be taken as the plugin.

For more information about GlotPress, feel free to visit the channels listed below in the "Communication" section.

So who should use GlotPress?

Any developer of software that uses [gettext](http://www.gnu.org/software/gettext/), like WordPress theme or plugin authors.  But that's just the start, anyone who uses a gettext bases system can use GlotPress to help their translators collaborate.

This plugin wouldn't be possible without all the hard work that has gone in to the standalone version of GlotPress and we'd like to thank all those who contribute to it.

## Installation

Search for "GlotPress" in the WordPress.org plugin directory and install it.

After activating the plugin, GlotPress can be accessed via `<home_url>/glotpress/`

# Manual Installation
```bash
$ cd /your/wordpress/folder/wp-content/plugins/
$ git clone git@github.com:GlotPress/GlotPress-WP.git glotpress
```

After activating the plugin, GlotPress can be accessed via `<home_url>/glotpress/`

# More Info

More information can be found on the [GlotPress Wiki](https://github.com/GlotPress/GlotPress-WP/wiki/6.-The-Manual).

## Communication

* [GitHub Home](https://github.com/GlotPress/GlotPress-WP)
* [Blog](http://blog.glotpress.org/)
* [WordPress Slack](https://chat.wordpress.org/): #glotpress (for development only)

## Running Tests

```bash
$ ./tests/bin/run-unittests.sh -d testdb_name [ -u dbuser ] [ -p dbpassword ] [ -h dbhost ] [ -x dbprefix ] [ -w wpversion ] [ -D (drop-db) ] [ -c coverage_file ] [ -f phpunit_filter ]
```
