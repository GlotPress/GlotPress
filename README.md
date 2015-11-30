# GlotPress

This is a community-backed experiment dedicated to converting [GlotPress](https://github.com/GlotPress/GlotPress) into a WordPress plugin.

[![Build Status](https://travis-ci.org/deliciousbrains/GlotPress.svg?branch=wordpress-plugin-dbi)](https://travis-ci.org/deliciousbrains/GlotPress) [![Code Coverage](https://scrutinizer-ci.com/g/deliciousbrains/GlotPress/badges/coverage.png?b=wordpress-plugin-dbi)](https://scrutinizer-ci.com/g/deliciousbrains/GlotPress/?branch=wordpress-plugin-dbi) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/deliciousbrains/GlotPress/badges/quality-score.png?b=wordpress-plugin-dbi)](https://scrutinizer-ci.com/g/deliciousbrains/GlotPress/?branch=wordpress-plugin-dbi)

## Contributing

We are still in the process of converting GlotPress into a WordPress plugin. We're working through [the issues](https://github.com/deliciousbrains/GlotPress/milestones/1.0) to get it ready for its first release. The idea is to change as little as possible to get it working well.

If you'd like to work on something and there's not currently an issue for it, open a new issue and describe your proposed change before jumping into coding.

## Installation

```bash
$ cd /your/wordpress/folder/wp-content/plugins/
$ git clone git@github.com:deliciousbrains/GlotPress.git glotpress
```

## Communication

* [WordPress Slack](https://chat.wordpress.org/): #glotpress
* [Blog](http://blog.glotpress.org/)

## Running Tests

```bash
$ ./tests/bin/run-unittests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [drop-db]
```
