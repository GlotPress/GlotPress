All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## 2.0.0-beta.2 (March 27, 2016)

### Security

* Implement nonces to URLs and forms to help protect against several types of attacks including CSRF. ([#355](https://github.com/GlotPress/GlotPress-WP/issues/355))

### Breaking Changes

* Remove user and option handling in `gp_update_meta()`/`gp_delete_meta()`. ([#300](https://github.com/GlotPress/GlotPress-WP/issues/300))
* Remove deprecated `assets/img/glotpress-logo.png`. ([#327](https://github.com/GlotPress/GlotPress-WP/issues/327))
* Remove `gp_sanitize_for_url()` in favor of `sanitize_title()` for enhanced slug generation. ([#330](https://github.com/GlotPress/GlotPress-WP/pull/330))
* Improve return values of `gp_meta_update()`. ([#318](https://github.com/GlotPress/GlotPress-WP/issues/318)).
* Remove CLI command `GP_CLI_WPorg2Slug`. ([#347](https://github.com/GlotPress/GlotPress-WP/issues/347))
* Remove Translation Propagation from core. [Now available as a plugin](https://github.com/GlotPress/gp-translation-propagation/). ([#337](https://github.com/GlotPress/GlotPress-WP/issues/337))

### Features

* Make projects, translation sets, and glossaries deletable. ([#267](https://github.com/GlotPress/GlotPress-WP/issues/267))
* Update several locale definitions with new Facebook and Google codes. ([#246](https://github.com/GlotPress/GlotPress-WP/issues/246))
* Add auto detection of format of uploaded import files. ([#290](https://github.com/GlotPress/GlotPress-WP/issues/290))
* Add UI to manage GlotPress administrators. ([#233](https://github.com/GlotPress/GlotPress-WP/issues/233))
* Add checkbox for case-sensitive translation searches. ([#312](https://github.com/GlotPress/GlotPress-WP/issues/312))
* Add support for Java properties files. ([#297](https://github.com/GlotPress/GlotPress-WP/issues/297))
* Add cancel link to import pages. ([#268](https://github.com/GlotPress/GlotPress-WP/issues/268))
* Add warning and disable the plugin if permalinks are not set. ([#218](https://github.com/GlotPress/GlotPress-WP/issues/218))
* Add warning and disable the plugin if unsupported version of PHP is detected. ([#276](https://github.com/GlotPress/GlotPress-WP/issues/276))
* Add inline documentation for actions and filters. ([#50](https://github.com/GlotPress/GlotPress-WP/issues/50))
* Add backend support to allow for integration with WordPress' user profiles. ([#196](https://github.com/GlotPress/GlotPress-WP/issues/196))
* Introduce a separate page for settings. ([#325](https://github.com/GlotPress/GlotPress-WP/issues/325))
* Validate slugs for translation sets on saving. ([#329](https://github.com/GlotPress/GlotPress-WP/issues/329))
* Standardize triggers in projects, translations and originals. ([#294](https://github.com/GlotPress/GlotPress-WP/issues/294))
* Introduce `GP_Thing::after_delete()` method and new actions. ([#294](https://github.com/GlotPress/GlotPress-WP/issues/294))
* Add .pot extension to `GP_Format_PO`. ([#230](https://github.com/GlotPress/GlotPress-WP/issues/230))


### Bugfixes

* Mark Sindhi locale as RTL. ([#243](https://github.com/GlotPress/GlotPress-WP/issues/243))
* Replace `current_user_can( 'manage_options' )` with GP permissions ([#254](https://github.com/GlotPress/GlotPress-WP/issues/254))
* Child projects are inaccessible if permalinks have trailing slash ([#265](https://github.com/GlotPress/GlotPress-WP/issues/265))
* Various code cleanups to improve code quality ([#237](https://github.com/GlotPress/GlotPress-WP/issues/237))
* Replace deprecated javascript back code ([#278](https://github.com/GlotPress/GlotPress-WP/issues/278))
* `[_]wp_specialchars()` is deprecated/private and should be replaced ([#280](https://github.com/GlotPress/GlotPress-WP/issues/280))
* Merge similar translation strings and avoid using HTML tags in translation strings ([#295](https://github.com/GlotPress/GlotPress-WP/pull/295))
* Actions for translations have no `gp_` prefix ([#232](https://github.com/GlotPress/GlotPress-WP/issues/232))
* `$original->validate()` fails if singular is '0' ([#301](https://github.com/GlotPress/GlotPress-WP/issues/301))
* Auto generation of a project slug doesn't work correctly in some cases ([#328](https://github.com/GlotPress/GlotPress-WP/issues/328))
* Suspend cache invalidation during original imports ([#332](https://github.com/GlotPress/GlotPress-WP/issues/332))
* Do not allow translations with empty plurals ([#308](https://github.com/GlotPress/GlotPress-WP/pull/308))
* Update schema definitions to work with WP's `dbDelta()` ([#343](https://github.com/GlotPress/GlotPress-WP/issues/343))
* Translation Set: Use the correct select helper for the edit form ([#351](https://github.com/GlotPress/GlotPress-WP/pull/351))
* Translation Sets: A failed update redirects to /sets/-new ([#349](https://github.com/GlotPress/GlotPress-WP/pull/349))
* Prevent a PHP fatal error when importing originals ([#302](https://github.com/GlotPress/GlotPress-WP/pull/302))

Thanks to all the contributors so far: Aki Björklund, Daisuke Takahashi, Dominik Schilling, Gabor Javorszky, Greg Ross, Peter Dave Hello, Rami, Sergey Biryukov

## 1.0.2 (March 09, 2016)

* Bugfix: Sanitize messages in `gp_notice()`.

## 1.0.1 (January 21, 2016)

* Bugfix: Unslash PHP's superglobals to prevent extra slashes in translations. ([#220](https://github.com/GlotPress/GlotPress-WP/issues/220))
* Bugfix: Adjust add/delete glossary entry links with trailing slashes. ([#224](https://github.com/GlotPress/GlotPress-WP/issues/224))

## 1.0.0 (January 18, 2016)

* Initial release.
