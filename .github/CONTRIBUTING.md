# Contribute

Hi there! Thank you so much for your interest in contributing to GlotPress. We'll try to make things as easy as possible for you in this guide. There are a number of ways to help out, and every one of them is appreciated:

* Submitting patches: whether fixing a bug or adding new functionality
* Testing patches / PRs
* Running and writing tests

## Setting up

1. Clone this git repository on your development system. This is commonly inside your WordPress development site in the `wp-content/plugins/` directory.
2. Activate the plugin.
3. Visit `http://local.sitename/glotpress` to start using GlotPress. `local.sitename` is the domain where your development WordPress site runs, dependent on configuration.

### Alternative: wp-env

[`wp-env`](https://developer.wordpress.org/block-editor/packages/packages-env/) requires Docker to be installed. There are instructions available for installing Docker on [Windows 10 Pro](https://docs.docker.com/docker-for-windows/install/), [all other versions of Windows](https://docs.docker.com/toolbox/toolbox_install_windows/), [macOS](https://docs.docker.com/docker-for-mac/install/), and [Linux](https://docs.docker.com/v17.12/install/linux/docker-ce/ubuntu/#install-using-the-convenience-script).

1. Clone this git repository.
2. Run `npm install`.
3. Run `npm run env:start`.
4. Visit `http://localhost:8888/glotpress/` in your web browser.

Please check [the official documentation](https://developer.wordpress.org/block-editor/packages/packages-env/) for customizing any settings like port numbers and other available commands.

## Submitting patches

Whether you want to fix a bug or implement a new feature, the process is pretty much the same:

0. [Search existing issues](https://github.com/GlotPress/GlotPress-WP/issues); if you can't find anything related to what you want to work on, [open a new issue](#helpful-tips-for-writing-issues).
1. [Fork](https://github.com/GlotPress/GlotPress-WP/fork) the repository.
2. Create a branch for each issue you'd like to address. Base your new branch on top of `develop` branch. Branches are named as such: `ISSUEID-keywords` for branches where an issue exists, or `keywords` for a branch without an issue yet. For example issue 216 for adding contributing.md would be `216-add-contributing-md`. Commit your changes.
3. Push the code changes from your local clone to your fork.
4. Open a pull request from your fork's feature branch to GlotPress's `develop` branch. Pull request name should mirror the branch's name. Take a look at [one such pull request](https://github.com/GlotPress/GlotPress-WP/pull/241) to get an idea of it. Pull request should have `Fixes #issue` / `Closes #issue`, or, if it's a partial solution, `Part of #issue` in the message to [enable automatic issue closing when a PR is merged](https://help.github.com/articles/closing-issues-via-commit-messages/).

We use the [Git workflow](https://github.com/GlotPress/GlotPress-WP/wiki/5.-Git-workflow). Please have a read through, as it will make everyone's life easier. Should you have questions about that, reach out to us on Slack (see below).

It doesn't matter if the code isn't perfect. The idea is to get it reviewed early and iterate on it.

Lastly, please follow the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/).

## Testing patches / PRs

Testing of patches and PRs is a critical part of development. If you have contributed a PR or just want to help out, testing is a great place to start.

1. Check out the branch the PR was submitted on.
2. Leave feedback on the PR. Write up what you tested, what your results were and whether it solves the issue or not.

If you need help with checking out the branch, or the PR was submitted from someone's fork, GitHub provides help in checking out the correct branch. Navigate to the PR in question, and you should see a link to command line instructions. Follow those instructions.

![](https://cloud.githubusercontent.com/assets/617637/12953405/9df99808-d01a-11e5-88eb-958d23871f87.png)

## Running and writing tests

GlotPress includes automated test code, but coverage is not complete at this time. Contributing new tests for GlotPress is a great way to better understand the codebase and contribute to the project at the same time.

There are two types of automated tests:

* unit tests, implemented using [PHPUnit](http://phpunit.de/)
* general code metrics using [Scrutinizer](https://scrutinizer-ci.com/)

### Unit tests

You can run [PHPUnit](https://phpunit.de/) on Unix or Windows, however at this time instructions and scripts are only provided for Unix.  GlotPress tests are based on the WordPress test suite which is currently only compatible with PHPUnit up to 7.x. Please use the latest PHPUnit version from the 7.x branch.

The unit test files are in the `tests/` directory. To run the unit tests on a Unix machine, open a command shell, change to your development directory for GlotPress and run:

```
$ ./tests/phpunit/bin/run-unittests.sh -d testdb_name [ -u dbuser ] [ -p dbpassword ] [ -h dbhost ] [ -x dbprefix ] [ -w wpversion ] [ -D (drop-db) ] [ -c coverage_file ] [ -f phpunit_filter ]
```

To write unit tests, find the relevant file that has similar tests already in them (they are named accordingly), and add the tests there. If there isn't a file that has tests for the functionality you've written, create a new file, and name it `test_<functionality>.php`. PHPUnit will pick up the file automatically. Refer to the [PHPUnit documentation](https://phpunit.de/documentation.html) and existing files for how to write new tests.

### Scrutinizer

For the most part feedback from Scrutinizer will flag up potential issues with the submitted code. It will tell you whether anything has been introduced or fixed. It will also give you explanations of those.

## Helpful tips for writing issues

When submitting an issue on GitHub there are several things you should include to ensure we can verify the problem and have enough information to resolve it.

### 1. What it is that's causing the bug?

As a general rule, include all the things that are needed for someone else to reproduce the issue locally. Things like "Importing originals doesn't work" are not enough for us to start on as it's missing a lot of necessary context.

Instead, for example if the translatable string is not picked up correctly, we'd need the following to reproduce the issue:

* the PHP source file of the translatable string
* the generated .pot file, and how you generated it from the php
* the actual result in GlotPress
* what you're expecting it to be, and how it differs from it

A good issue body in that case would be:

> Here's the part of my php file that's causing trouble:
>
> ```
> <?php
> $string = sprintf( __( 'Some translatable string with a %1$s', 'text-domain' ), 'placeholder' );
> ```
>
> I get the pot file by using the wp-i18n-grunt package (https://github.com/cedaro/grunt-wp-i18n) at release version 0.5.3. This is the pot file generated: (link to a gist for example).
>
> I expect the translatable string to be `Some translatable string with a %1$s`, but instead it's `Some translatable string with a`. Placeholder is missing.

(not an actual bug)

Look at some of the issues open currently to get an idea of it.

### 2. It helps to know what your environment is like

* Which PHP version are you using?
* What version of MySQL or other database software are you using?
* Which version of GlotPress do you have? (Which is the last commit? Find it with `git log -1`)
* What version of WordPress are you running?
* What are the plugins that are active on your site, and their versions?

These should be enough for us to move forward.

## Finally...

Thanks! Working on GlotPress should be fun! If you find any of this hard to figure out, let us know so we can improve our process or documentation! You can also find us in the [GlotPress channel on the WordPress Slack](https://wordpress.slack.com/messages/glotpress/). If you don't have access to it yet, [you can sign up for Slack access](https://make.wordpress.org/chat/).
