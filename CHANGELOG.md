## 2.0.0-beta.1 (March 17, 2016)

* BREAKING: In `gp_update_meta()`/`gp_delete_meta()` remove meta handling for users and options ([#300](https://github.com/GlotPress/GlotPress-WP/issues/300))
* BREAKING: Remove deprecated `assets/img/glotpress-logo.png` ([#327](https://github.com/GlotPress/GlotPress-WP/issues/327))
* BREAKING: Replace `gp_sanitize_for_url()` with wp's `sanitize_title()`. ([#330](https://github.com/GlotPress/GlotPress-WP/pull/330))
* BREAKING: Enhance `gp_meta_update()` return values ([#318](https://github.com/GlotPress/GlotPress-WP/issues/318))
* BREAKING: Remove GP_CLI_WPorg2Slug ([#347](https://github.com/GlotPress/GlotPress-WP/issues/347))
* BREAKING: Move Translation Propagation into a plugin ([#337](https://github.com/GlotPress/GlotPress-WP/issues/337))
* New: File extension list for portable object message catalog should include `.pot` ([#230](https://github.com/GlotPress/GlotPress-WP/issues/230))
* New: Update locale definitions ([#246](https://github.com/GlotPress/GlotPress-WP/issues/246))
* New: Add cancel link to import page ([#268](https://github.com/GlotPress/GlotPress-WP/issues/268))
* New: Add warning and disable the plugin if permalinks are not set ([#218](https://github.com/GlotPress/GlotPress-WP/issues/218))
* New: Display warning if unsupported version of PHP is detected ([#276](https://github.com/GlotPress/GlotPress-WP/issues/276))
* New: Add UI to manage GP Administrators ([#233](https://github.com/GlotPress/GlotPress-WP/issues/233))
* New: Add doc blocks for actions and filters ([#50](https://github.com/GlotPress/GlotPress-WP/issues/50))
* New: Add auto detection of uploaded import files ([#290](https://github.com/GlotPress/GlotPress-WP/issues/290))
* New: Add backend support to allow for integration with WP's user profile ([#196](https://github.com/GlotPress/GlotPress-WP/issues/196))
* New: Things: Introduce `after_delete()` method and new actions ([#294](https://github.com/GlotPress/GlotPress-WP/issues/294))
* New: Translations: Include check button for case-sensitive search ([#312](https://github.com/GlotPress/GlotPress-WP/issues/312))
* New: Java properties file support ([#297](https://github.com/GlotPress/GlotPress-WP/issues/297))
* New: Standardize triggers in projects, translations and originals ([#294](https://github.com/GlotPress/GlotPress-WP/issues/294))
* New: Public and private profiles do not have the same design ([#325](https://github.com/GlotPress/GlotPress-WP/issues/325))
* New: Translation Sets: Validate slugs on saving ([#329](https://github.com/GlotPress/GlotPress-WP/issues/329))
* New: Make translation sets deletable ([#267](https://github.com/GlotPress/GlotPress-WP/issues/267))
* Bugfix: Mark Sindhi locale as RTL ([#243](https://github.com/GlotPress/GlotPress-WP/issues/243))
* Bugfix: Replace `current_user_can( 'manage_options' )` with GP permissions ([#254](https://github.com/GlotPress/GlotPress-WP/issues/254))
* Bugfix: Child projects are inaccessible if permalinks have trailing slash ([#265](https://github.com/GlotPress/GlotPress-WP/issues/265))
* Bugfix: Various code cleanups to improve code quality ([#237](https://github.com/GlotPress/GlotPress-WP/issues/237))
* Bugfix: Update readme files ([#228](https://github.com/GlotPress/GlotPress-WP/issues/228))
* Bugfix: Replace deprecated javascript back code ([#278](https://github.com/GlotPress/GlotPress-WP/issues/278))
* Bugfix: `[_]wp_specialchars()` is deprecated/private and should be replaced ([#280](https://github.com/GlotPress/GlotPress-WP/issues/280))
* Bugfix: Merge similar translation strings and avoid using HTML tags in translation strings ([#295](https://github.com/GlotPress/GlotPress-WP/pull/295))
* Bugfix: Actions for translations have no `gp_` prefix ([#232](https://github.com/GlotPress/GlotPress-WP/issues/232))
* Bugfix: `$original->validate()` fails if singular is '0' ([#301](https://github.com/GlotPress/GlotPress-WP/issues/301))
* Bugfix: Auto generation of a project slug doesn't work correctly in some cases ([#328](https://github.com/GlotPress/GlotPress-WP/issues/328))
* Bugfix: Suspend cache invalidation during original imports ([#332](https://github.com/GlotPress/GlotPress-WP/issues/332))
* Bugfix: Do not allow translations with empty plurals ([#308](https://github.com/GlotPress/GlotPress-WP/pull/308))
* Bugfix: Update schema definitions to work with WP's `dbDelta()` ([#343](https://github.com/GlotPress/GlotPress-WP/issues/343))
* Bugfix: Translation Set: Use the correct select helper for the edit form ([#351](https://github.com/GlotPress/GlotPress-WP/pull/351))
* Bugfix: Translation Sets: A failed update redirects to /sets/-new ([#349](https://github.com/GlotPress/GlotPress-WP/pull/349))

* BREAKING: Don't store counts as a public property of GP_Translation_Set ([#334](https://github.com/GlotPress/GlotPress-WP/issues/334))
* Bugfix: Prevent a PHP fatal error when importing originals ([#302](https://github.com/GlotPress/GlotPress-WP/pull/302))

Thanks to all the contributors so far: Aki Björklund, Daisuke Takahashi, Dominik Schilling, Gabor Javorszky, Greg Ross, Peter Dave Hello, Rami, Sergey Biryukov

## 1.0.2 (March 09, 2016)

* Bugfix: Sanitize messages in `gp_notice()`.

## 1.0.1 (January 21, 2016)

* Bugfix: Unslash PHP's superglobals to prevent extra slashes in translations. ([#220](https://github.com/GlotPress/GlotPress-WP/issues/220))
* Bugfix: Adjust add/delete glossary entry links with trailing slashes. ([#224](https://github.com/GlotPress/GlotPress-WP/issues/224))

## 1.0.0 (January 18, 2016)

* Initial release.
