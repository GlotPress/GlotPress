All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## 2.1.0-beta.1 (June 29, 2016)

**Bugfixes**

* Replace `LIKE` queries for the status of an original with an exact match. ([#419](https://github.com/GlotPress/GlotPress-WP/issues/419))
* Move `gp_translation_set_filters` hook to allow additions to the filter form. ([#391](https://github.com/GlotPress/GlotPress-WP/issues/391))
* Fix wrong error message for translations with a missing set ID. ([#341](https://github.com/GlotPress/GlotPress-WP/issues/341))
* Fix Android exports with translation that start with an @. ([#469](https://github.com/GlotPress/GlotPress-WP/issues/469))
* Improve performance of default `GP_Translation->for_translation()` query. ([#376](https://github.com/GlotPress/GlotPress-WP/issues/376))
* Use `__DIR__` constant for `GP_PATH`. ([#455](https://github.com/GlotPress/GlotPress-WP/issues/455))
* Use lowercase field types in schema.php. ([#461](https://github.com/GlotPress/GlotPress-WP/issues/461))
* Change field type for user IDs to `bigint(20)`. ([#464](https://github.com/GlotPress/GlotPress-WP/issues/464))
* Don't call `gp_upgrade_data()` in `gp_upgrade_db()` on install. ([#361](https://github.com/GlotPress/GlotPress-WP/issues/361))
* Define max index length for `user_id_action` column. ([#462](https://github.com/GlotPress/GlotPress-WP/issues/462))


**Features**

* Allow export by priority of originals. ([#405](https://github.com/GlotPress/GlotPress-WP/issues/405))
* Check imported translations for warnings. ([#401](https://github.com/GlotPress/GlotPress-WP/issues/401))
* Allow translations to be imported with status waiting. ([#377](https://github.com/GlotPress/GlotPress-WP/issues/377))
* Add `Language` header to PO exports. ([#428](https://github.com/GlotPress/GlotPress-WP/issues/428))
* Add option to overwrite existing glossary when importing. ([#395](https://github.com/GlotPress/GlotPress-WP/issues/395))
* Allow modification of accepted HTTP methods in the router. ([#393](https://github.com/GlotPress/GlotPress-WP/issues/393))
* Update the `Project-Id-Version` header PO exports to better handle sub projects and be filterable. ([#442](https://github.com/GlotPress/GlotPress-WP/issues/442))
* Convert the permissions list to a table. ([#99](https://github.com/GlotPress/GlotPress-WP/issues/99))
* Split translation status counts by hidden and public. ([#397](https://github.com/GlotPress/GlotPress-WP/issues/397))
* Store user ID of validator/approver on translation status changes. ([#293](https://github.com/GlotPress/GlotPress-WP/issues/293))

Thanks to all the contributors so far: Dominik Schilling, Greg Ross, Yoav Farhi, Alexander Kirk, Anton Timmermans, Mattias Tengblad

## 2.0.1 (April 25, 2016)

**Bugfixes**

* Avoid a PHP warning when a user had made translations and the user was then deleted. ([#386](https://github.com/GlotPress/GlotPress-WP/issues/386))
* Update all delete permission levels to be consistent in different areas of GlotPress. ([#390](https://github.com/GlotPress/GlotPress-WP/issues/390))
* Fix the CLI export command to properly use the "status" option. ([#404](https://github.com/GlotPress/GlotPress-WP/issues/404))
* Add upgrade script to remove trailing slashes left of project paths from 1.0 which are no longer supported. ([#410](https://github.com/GlotPress/GlotPress-WP/issues/410))
* Fix conflict with other plugins that also use the `GP_Locales` class. ([#413](https://github.com/GlotPress/GlotPress-WP/issues/413))
* Exclude the art-xemoji locale from length check that caused spurious warnings. ([#417](https://github.com/GlotPress/GlotPress-WP/issues/417))

**Features**

* Add Haitian Creole locale definition. ([#411](https://github.com/GlotPress/GlotPress-WP/issues/411))
* Update Asturian locale definition. ([#412](https://github.com/GlotPress/GlotPress-WP/issues/412))

Thanks to all the contributors so far: Dominik Schilling, Greg Ross, Yoav Farhi

## 2.0.0 (April 04, 2016)

**Bugfixes**

* Delete cookies for notices on installs without a base. ([#379](https://github.com/GlotPress/GlotPress-WP/issues/379))
* Fix "Use as name" link on translation set creation page. ([#381](https://github.com/GlotPress/GlotPress-WP/issues/381))

## 2.0.0-rc.1 (March 29, 2016)

(this space intentionally left blank)

## 2.0.0-beta.2 (March 27, 2016)

**Security**

* Implement nonces for URLs and forms to help protect against several types of attacks including CSRF. ([#355](https://github.com/GlotPress/GlotPress-WP/issues/355))

**Bugfixes**

* Avoid a PHP warning when updating a glossary entry. ([#366](https://github.com/GlotPress/GlotPress-WP/issues/366))
* Improve mb_* compat functions to support all parameters and utilize WordPress' compat functions. ([#364](https://github.com/GlotPress/GlotPress-WP/issues/364))

## 2.0.0-beta.1 (March 17, 2016)

**Breaking Changes**

* Remove Translation Propagation from core. [Now available as a plugin](https://github.com/GlotPress/gp-translation-propagation/). ([#337](https://github.com/GlotPress/GlotPress-WP/issues/337))
* Remove user and option handling in `gp_update_meta()`/`gp_delete_meta()`. ([#300](https://github.com/GlotPress/GlotPress-WP/issues/300))
* Remove deprecated `assets/img/glotpress-logo.png`. ([#327](https://github.com/GlotPress/GlotPress-WP/issues/327))
* Remove `gp_sanitize_for_url()` in favor of `sanitize_title()` for enhanced slug generation. ([#330](https://github.com/GlotPress/GlotPress-WP/pull/330))
* Improve return values of `gp_meta_update()`. ([#318](https://github.com/GlotPress/GlotPress-WP/issues/318)).
* Remove CLI command `GP_CLI_WPorg2Slug`. ([#347](https://github.com/GlotPress/GlotPress-WP/issues/347))

**Features**

* Make projects, translation sets, and glossaries deletable via UI. ([#267](https://github.com/GlotPress/GlotPress-WP/issues/267))
* Update several locale definitions to use new Facebook and Google codes and correct country codes. ([#246](https://github.com/GlotPress/GlotPress-WP/issues/246))
* Add Greenlandic, Spanish (Guatemala), and Tahitian locale definition. ([#246](https://github.com/GlotPress/GlotPress-WP/issues/246))
* Add auto detection for format of uploaded import files. ([#290](https://github.com/GlotPress/GlotPress-WP/issues/290))
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
* Various code cleanups to improve code quality. ([#237](https://github.com/GlotPress/GlotPress-WP/issues/237))

**Bugfixes**

* Mark Sindhi locale definition as RTL. ([#243](https://github.com/GlotPress/GlotPress-WP/issues/243))
* Replace `current_user_can( 'manage_options' )` with GlotPress permissions. ([#254](https://github.com/GlotPress/GlotPress-WP/issues/254))
* Make child projects accessible if permalink structure has a trailing slash. ([#265](https://github.com/GlotPress/GlotPress-WP/issues/265))
* Use real URLs for back links instead of JavaScript's history `back()` method. ([#278](https://github.com/GlotPress/GlotPress-WP/issues/278))
* Replace deprecated/private `[_]wp_specialchars()` function with `htmlspecialchars()`. ([#280](https://github.com/GlotPress/GlotPress-WP/issues/280))
* Merge similar translation strings and avoid using HTML tags in translation strings. ([#295](https://github.com/GlotPress/GlotPress-WP/pull/295))
* Add missing `gp_` prefix for for translation actions. ([#232](https://github.com/GlotPress/GlotPress-WP/issues/232))
* Fix case where `$original->validate()` fails if singular is '0'. ([#301](https://github.com/GlotPress/GlotPress-WP/issues/301))
* Fix auto generation of project slugs with special characters. ([#328](https://github.com/GlotPress/GlotPress-WP/issues/328))
* Suspend cache invalidation during original imports. ([#332](https://github.com/GlotPress/GlotPress-WP/issues/332))
* Prevent submitting translations with empty plurals. ([#308](https://github.com/GlotPress/GlotPress-WP/pull/308))
* Update schema definitions to work with WordPress' `dbDelta()` function. ([#343](https://github.com/GlotPress/GlotPress-WP/issues/343))
* Fix redirect when a translation set update failed. ([#349](https://github.com/GlotPress/GlotPress-WP/pull/349))
* Prevent a PHP fatal error when importing originals. ([#302](https://github.com/GlotPress/GlotPress-WP/pull/302))

Thanks to all the contributors so far: Aki Björklund, Daisuke Takahashi, Dominik Schilling, Gabor Javorszky, Greg Ross, Peter Dave Hello, Rami, Sergey Biryukov

## 1.0.2 (March 09, 2016)

**Security**

* Sanitize messages in `gp_notice()`.

## 1.0.1 (January 21, 2016)

**Bugfixes**

* Unslash PHP's superglobals to prevent extra slashes in translations. ([#220](https://github.com/GlotPress/GlotPress-WP/issues/220))
* Adjust add/delete glossary entry links with trailing slashes. ([#224](https://github.com/GlotPress/GlotPress-WP/issues/224))

## 1.0.0 (January 18, 2016)

* Initial release.
