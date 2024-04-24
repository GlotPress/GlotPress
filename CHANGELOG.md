All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [4.0.1] (April 3, 2024)

**Bugfixes**
* Fix errors when URL parameters like "filter[status]" aren't strings ([#1814])

## [4.0.0] (March 7, 2024)

(this space intentionally left blank)

## [4.0.0-rc.1] (March 4, 2024)

(this space intentionally left blank)

## [4.0.0-beta.3] (February 28, 2024)

**Features**
* Add a 'gp_before_translation_table' hook ([#1792])

**Bugfixes**

* Add trailing slash to current_url to fix matching URLs without trailing slash ([#1785])
* Breadcrumbs: Improve consistency across all content types ([#1789])
* Glossary: Match single word entries of parts of speech that have no suffix rules. ([#1791])
* Fix redirecting URL with query args ([#1797])
* Add the breadcrumbs for the "New project" actions ([#1800])
* Make the glossary regex more deterministic ([#1801])

## [4.0.0-beta.2] (January 26, 2024)

**Bugfixes**

* Solve some warnings with the glossary suffixes ([#1779])
* Remove Tour code in favor of using the dedicated Tour plugin ([#1745])

## [4.0.0-beta.1] (January 25, 2024)

**Features**
* Document how to use wp-env to run tests ([#1776])
* Improve placeholders match and visibility ([#1620])
* Change file extension for PHP format ([#1774])
* Set the slug ([#1768])
* Update readme.txt ([#1773])
* Add Plugin Preview to GlotPress ([#1748])
* Make it possible to run tests in PHP 8 ([#1760])
* Add suffixes for matching glossary terms ([#1373])
* Add new PHP format ([#1626])

**Bugfixes**
* Glossary: Fix fatal array in PHP8 for invalid post data ([#1750])
* Fix glossary matching left bounded by placeholder. ([#1733])
* Remove unused files ([#1723])
* Fix fatal error in PHP8.x for invalid get data ([#1754])

**Locales**
* Add a new locale: Andalusian (Andalûh) ([#1729])

## [4.0.0-alpha.11] (October 19, 2023)

**Bugfixes**
* Don't set context to empty string by default ([#1726])

## [4.0.0-alpha.10] (October 16, 2023)

**Features**
* Move the highlight label from the active projects to the inactive ([#1680])
* Add a Grunt action to set GP_SCRIPT_DEBUG to false when we deploy to wporg ([#1697])
* Site Tour: Improve the pulse ([#1711])
* Add templates for bug and feature report ([#1713])
* Site tour: Use driver.js ([#1714])

**Bugfixes**
* Filter the placeholders to show them with the glossary words ([#1696])
* Avoid passing `null` to `trim()` ([#1698])
* Update a CSS comment that causes an build error ([#1703])
* Check if the project slug exists and is not null ([#1704])
* Add some properties in the translation-set to avoid deprecation notices ([#1705])
* Resolve a bug with the glossary variations ([#1706])
* Deprecation. Check if the preg_split parameter is null ([#1709])
* Deprecation. Check if the ctype_digit parameter is null ([#1708])

## [4.0.0-alpha.9] (September 25, 2023)

**Features**
* Use filemtime to set the asset's version ([#1693])

**Bugfixes**
* Site Tour: Fix CORS error in accessing tour stylesheet ([#1694])
* Site Tour: Add popover offset property ([#1695])
* Add a slash at the end of a requested URI ([#1701])

## [4.0.0-alpha.8] (September 19, 2023)

**Features**
* Add a Tour Component ([#1632])
* Add a filter for the classes of the translation table and an action after the translation table is displayed ([#1665])
* Dependencies for site tour ([#1684])

**Bugfixes**
* Fix button margins ([#1677])
* Remove the glossary tooltip inside the HTML tags ([#1679])
* Correction of plurals parameters for Saraiki ([#1682])

## [4.0.0] (August 18, 2023)

(this space intentionally left blank)

## [4.0.0-alpha.7] (August 08, 2023)

**Bugfixes**
* Fix wordpress.org plugin deploy if the minified files are already built ([#1662])
* Avoid adding the invisible classes in the glossary ([#1664])

## [4.0.0-alpha.6] (July 25, 2023)
* Increase the cache buster ([#1661])

## [4.0.0-alpha.5] (July 12, 2023)

**Features**
* Set as old the previous translations with waiting status for this user ([#1536])
* Highlight leading and trailing spaces, and double/multiple spaces in the middle ([#1500])
* Add I18n to JavaScript ([#1369])
* Add plural and plural forms labels to row previews and format the row editor accordingly ([#1506])
* Reorder glossary row editor items ([#1622])

**Bugfixes**
* Fix notice accessing undefined variable ([#1582])
* Improve translation strings consistency and comments to translators ([#1600])
* Fix PHP error for parameter after optional parameter ([#1465])
* Combine the suffixes for shorter regular expression. ([#1651])

**Locales**
* Update plural expression for Kyrgyz ([#1634])
* Shorten the slug for Valencia (Catalan) ([#1635])

## [4.0.0-alpha.4] (Feb 28, 2023)

**Features**
* Show the exact amount of spaces added or missing on the translation warnings ([#1490])
* Glossary: Add sorting to table ([#1426])
* Add characters and words counts to editor ([#1478])
* Check the missing uppercase in the beginning of the translations ([#1450])
* Explain when the wp_locale filed should be used ([#1537])
* Add an action gp_after_project_form_fields to enable additional fields to be added ([#1522])
* Get supported formats extensions dynamically ([#1524])

**Bugfixes**
* Set as old the previous translations with changes requested ([#1497])
* Update the Facebook locale for es_MX ([#1538])
* Check JSON for double array to fix fatal error ([#1569])

**Locales**
* Add Tarifit locale ([#1477])
* Correct de_AT country_code, add fa_AF country code. ([#1491])
* Add locale en_IE / English (Ireland) ([#1520])
* Add locale es_PA / Spanish (Panama) ([#1521])

## [4.0.0-alpha.3] (Sep 20, 2022)

**Bugfixes**
* Fix bulk rejection ([#1486])

## [4.0.0-alpha.2] (Sep 15, 2022)

**Features**
* Add a new status: changes requested ([#1451]). Currently, only available using the gp-translation-helpers plugin.
* Add "Last Modified" column to Glossary ([#1428])
* Add word_count_type for each locale ([#1482])
* Add the alphabet for each locale ([#1479])
* Add a template for the PR ([#1448])
* Translations: Add sort by original string length ([#1449])

**Bugfixes**
* Fixes deprecation notice in PHP8 for usort returning bool ([#1464])
* Fix warning discard link margin ([#1455])

## [4.0.0-alpha.1] (Mai 3, 2022)

**Breaking Changes**
* GlotPress now requires PHP 7.4. ([#1417])

**Bugfixes**
* Enhance `gp_levenshtein()` for speed improvements. Props @dd32 ([#1408])
* Highlight current navigation menu item. Props @pedro-mendonca ([#1379])

**Features**
* Improve links to glossaries. Props @pedro-mendonca ([#1375])

## [3.0.0] (April 9, 2022)

(this space intentionally left blank)

## [3.0.0-rc.4] (April 7, 2022)

**Breaking Changes**
* Developers: Renamed `gp_sort_glossary_entries_terms()` to `gp_glossary_add_suffixes()` and removed third parameter of `map_glossary_entries_to_translation_originals()`. ([#1395])

**Bugfixes**
* Glossary: Improve performance of parsing translations for adding tooltips. Props @akirk ([#1395])

## [3.0.0-rc.3] (April 5, 2022)

**Bugfixes**
* Update styles for links and glossary words to not cross any descenders. ([#1391])
* Glossary: Add missing primary styles to import button. ([#1393])
* Translations: Don't add flag for default priority to exports. ([#1392])

## [3.0.0-rc.2] (April 1, 2022)

**Bugfixes**
* Glossary: Provide fallback if term splitting has failed. ([#1378])

## [3.0.0-rc.1] (March 31, 2022)

**Bugfixes**
* Glossary: Restore matching for terms with multiple words and words with hyphens. ([#1359])
* Glossary: Use key of part of speech values to fix validation for non-English locales. ([#1367])

**Features**
* Glossary: Improve validation feedback for glossary entries. ([#1364])
* Translations: Convert status filter into list of checkboxes for each status. ([#1360])

## [3.0.0-beta.1] (March 24, 2022)

**Breaking Changes**

* Revert support for locale variants. ([#1327])
* Design: Reduce reliance on default browser style by updating link and button styles. ([#1332])
* Design: Unify and simplify styling for all tables. ([#1338])

**Features**

* Add a 'Waiting approval, including fuzzy' status filter option for translations. ([#1344])
* Add a 'Fuzzy' status filter option for translations. ([#1345])
* Allow specifying the priority for PO import/exports in the flags. Props @dd32 ([#1341])
* Developers: Add the entry as context to the `gp_import_original_array` filter. Props @dd32 ([#1342])
* Locales: Add definition for Catalan Valencian. Props @xavivars ([#1305])

**Bugfixes**

* Remove unused `translated=yes` filter for translations. ([#1343])
* Fix copy original action for plurals. ([#1326])
* Locales: Update native name for Chinese (Hong Kong). Props @ckykenken ([#1309])
* Show new lines and tab characters and wrap non-translatable items for strings with plurals too. Props @vlad-timotei ([#1292])

## [3.0.0-alpha.4] (November 2, 2021)

**Breaking Changes**

* Developers: Removed `GP_Thing::set_memory_limit()` in favor of `wp_raise_memory_limit()`. ([#1246])

**Features**

* Integrate three new translation warnings for mismatched URLs, missing named placeholders, and unexpected sprintf tokens.
  Also improve the check for HTML tags. Props @amieiro ([#1243](https://github.com/GlotPress/GlotPress/pull/1243))
* Locales: Add definition for Western Balochi. Props @varlese, @tobifjellner ([#1254](https://github.com/GlotPress/GlotPress/issues/1254))
* Add 'abbreviation' as new part of speech value for glossary entries. ([#1199](https://github.com/GlotPress/GlotPress/issues/1199))
* Add sort by option for modified date of translation. ([#1273](https://github.com/GlotPress/GlotPress/issues/1273))

**Bugfixes**

* Allow to use the same glossary term and translation for a different part of speech. ([#1251](https://github.com/GlotPress/GlotPress/pull/1251))
* Validate part of speech value for a glossary entry before inserting into database. ([#1200](https://github.com/GlotPress/GlotPress/issues/1200))
* Fix typo in validation array key for negative validations. Props @pedro-mendonca ([#1279](https://github.com/GlotPress/GlotPress/pull/1279))

## [3.0.0-alpha.3] (April 29, 2021)

**Bugfixes**

* Use single quotes around string literals in SQL queries. ([#1221](https://github.com/GlotPress/GlotPress/pull/1221))
* Fix 'original_id is invalid' error when submitting a translation. ([#1231](https://github.com/GlotPress/GlotPress/pull/1231))
* Fix some UI inconsistencies. ([#1209](https://github.com/GlotPress/GlotPress/issues/1209))
* Remove tabindex attribute on action buttons. ([#1215](https://github.com/GlotPress/GlotPress/issues/1215))
* Locales: Add/update definitions for Punjabi in Pakistan and India. ([#1184](https://github.com/GlotPress/GlotPress/pull/1184))

**Features**

* Delete translations if an original gets deleted. ([#1002](https://github.com/GlotPress/GlotPress/issues/1209))
* Developers: Add short-circuit hook `gp_pre_closest_original` in `GP_Original::closest_original()`. ([#1204](https://github.com/GlotPress/GlotPress/issues/1204))

## [3.0.0-alpha.2] (June 28, 2020)

**Breaking Changes**

* Developers: Remove all compatibility functions for multibyte string functions. ([#1123](https://github.com/GlotPress/GlotPress/issues/1123))
* Developers: Require the query argument to be set for all database query related `GP_Thing` methods.
* Developers: Rename `gp_locale_definitions_arrary` to `gp_locale_definitions_array`.
* Locales: Remove the no-variants version of `locales.php`. ([#1173](https://github.com/GlotPress/GlotPress/issues/1173))

**Bugfixes**

* Locales: Fix country code for Tahitian. ([#1127](https://github.com/GlotPress/GlotPress/pull/1127))
* Locales: Fix country code for Tajik. ([#1128](https://github.com/GlotPress/GlotPress/pull/1128))
* Locales: Update plural expression for various locales. ([#1129](https://github.com/GlotPress/GlotPress/pull/1129), [#1130](https://github.com/GlotPress/GlotPress/pull/1130), [#1131](https://github.com/GlotPress/GlotPress/pull/1131), [#1132](https://github.com/GlotPress/GlotPress/pull/1132), [#1133](https://github.com/GlotPress/GlotPress/pull/1133), [#1134](https://github.com/GlotPress/GlotPress/pull/1134), [#1135](https://github.com/GlotPress/GlotPress/pull/1135), [#1136](https://github.com/GlotPress/GlotPress/pull/1136), [#1137](https://github.com/GlotPress/GlotPress/pull/1137), [#1138](https://github.com/GlotPress/GlotPress/pull/1138), [#1139](https://github.com/GlotPress/GlotPress/pull/1139), [#1140](https://github.com/GlotPress/GlotPress/pull/1140), [#1141](https://github.com/GlotPress/GlotPress/pull/1141), [#1142](https://github.com/GlotPress/GlotPress/pull/1142), [#1143](https://github.com/GlotPress/GlotPress/pull/1143), [#1144](https://github.com/GlotPress/GlotPress/pull/1144), [#1145](https://github.com/GlotPress/GlotPress/pull/1145), [#1146](https://github.com/GlotPress/GlotPress/pull/1146), [#1147](https://github.com/GlotPress/GlotPress/pull/1147), [#1148](https://github.com/GlotPress/GlotPress/pull/1148), [#1149](https://github.com/GlotPress/GlotPress/pull/1149), [#1150](https://github.com/GlotPress/GlotPress/pull/1150), [#1151](https://github.com/GlotPress/GlotPress/pull/1151), [#1152](https://github.com/GlotPress/GlotPress/pull/1152), [#1153](https://github.com/GlotPress/GlotPress/pull/1153))
* Locales: Add missing parenthesis in native name for German (Austria). ([#1156](https://github.com/GlotPress/GlotPress/pull/1156))
* Locales: Remove `wp_locale` from currently unsupported locales in WordPress. ([#1155](https://github.com/GlotPress/GlotPress/pull/1155))

**Features**

* Locales: Add `wp_locale` for Aragonese. ([#1154](https://github.com/GlotPress/GlotPress/pull/1154))
* Locales: Add definition for Nigerian Pidgin. ([#1157](https://github.com/GlotPress/GlotPress/pull/1157))
* Locales: Add definition for Spanish (Uruguay). ([#1161](https://github.com/GlotPress/GlotPress/pull/1161))
* Locales: Add definition for Cornish. ([#1162](https://github.com/GlotPress/GlotPress/pull/1162))
* Locales: Add definition for Bengali (India). ([#1165](https://github.com/GlotPress/GlotPress/pull/1165))
* Locales: Add definition for Ligurian. ([#1163](https://github.com/GlotPress/GlotPress/pull/1163))
* Locales: Add definition for Borana-Arsi-Guji Oromo. ([#1164](https://github.com/GlotPress/GlotPress/pull/1164))
* Locales: Add "Portuguese (Portugal, AO90)" variant. ([#1017](https://github.com/GlotPress/GlotPress/pull/1017))
* Locales: Add definitions for Fon, Tamazight and Ecuadorian Spanish. ([#1171](https://github.com/GlotPress/GlotPress/pull/1171))
* Locales: Locales: Update native name for Venetian, add WP locale. ([#1183](https://github.com/GlotPress/GlotPress/pull/1183))

Thanks to all the contributors: Dominik Schilling, Pedro Mendonça and Naoko Takano.

## [3.0.0-alpha.1] (May 2, 2020)

**Breaking Changes**

* GlotPress now requires PHP 7.2 or newer and WordPress 4.6 or newer. ([#572](https://github.com/GlotPress/GlotPress/issues/572), [#1097](https://github.com/GlotPress/GlotPress/issues/1097), [#1118](https://github.com/GlotPress/GlotPress/issues/1118))
* iOS .strings file import/exports now are UTF8 encoded instead of UTF16-LE. ([#903](https://github.com/GlotPress/GlotPress/issues/903))
* Glossary entries are now case insensitive. ([#703](https://github.com/GlotPress/GlotPress/issues/703))
* Developers: Various template changes. ([#914](https://github.com/GlotPress/GlotPress/issues/914), [#1102](https://github.com/GlotPress/GlotPress/issues/1102))
* Developers: Remove `GP_CLI` and `GP_Translation_Set_Script`. ([#452](https://github.com/GlotPress/GlotPress/issues/452))
* Developers: Remove `gp_urldecode_deep()`. ([#1053](https://github.com/GlotPress/GlotPress/issues/1053))
* Developers: Remove `gp_url_ssl()`. ([#1055](https://github.com/GlotPress/GlotPress/issues/1055))
* Developers: Types of integer fields are now always normalized to integers. ([#1050](https://github.com/GlotPress/GlotPress/issues/1050))

**Bugfixes**

* Fix incorrect key in Apple strings file exports. ([#1105](https://github.com/GlotPress/GlotPress/issues/1105))
* Fix querying translations by priority. ([#664](https://github.com/GlotPress/GlotPress/issues/664))
* Fix glossary import nonce check. ([#673](https://github.com/GlotPress/GlotPress/issues/673))
* Fix flushing of existing glossary on import. ([#675](https://github.com/GlotPress/GlotPress/issues/675))
* Fix removing of starting newline from translations. ([#701](https://github.com/GlotPress/GlotPress/issues/701))
* Fix glossary tooltips after editing a row. ([#704](https://github.com/GlotPress/GlotPress/issues/704))
* Fix duplicate translation entry on dismissing warnings. ([#699](https://github.com/GlotPress/GlotPress/issues/699))
* Fix missing styles for jQuery UI. ([#758](https://github.com/GlotPress/GlotPress/issues/758))
* Improve calculation of translation status counts. ([#790](https://github.com/GlotPress/GlotPress/issues/790))
* Fix missing escaping for quotation marks in Android output. ([#809](https://github.com/GlotPress/GlotPress/issues/809))
* Fix translation preview with long words. ([#875](https://github.com/GlotPress/GlotPress/issues/875), [#1102](https://github.com/GlotPress/GlotPress/issues/1102))
* Fix import of escaped unicodes in Android strings files. ([#910](https://github.com/GlotPress/GlotPress/issues/910))
* Fix duplicate glossary tooltips for the same term. ([#915](https://github.com/GlotPress/GlotPress/issues/915))
* Fix long strings for Apple strings file export. ([#921](https://github.com/GlotPress/GlotPress/issues/921))
* Fix submitting a translation containing the UTF-8 characters → and ↵. ([#648](https://github.com/GlotPress/GlotPress/issues/648))
* Fix warnings count and filter view for warnings differ. ([#919](https://github.com/GlotPress/GlotPress/issues/919))
* Fix project paths with utf8mb4 characters. ([#415](https://github.com/GlotPress/GlotPress/issues/415))
* Fix mangled HTML tags in originals with some glossary entries. ([#869](https://github.com/GlotPress/GlotPress/issues/869))
* Prevent adding duplicate glossary entries. ([#745](https://github.com/GlotPress/GlotPress/issues/745))
* Improve position of action buttons in the translations editor. ([#684](https://github.com/GlotPress/GlotPress/issues/684))
* Abort changing translation status when the translation has been altered. ([#707](https://github.com/GlotPress/GlotPress/issues/707))
* Fix multi-select for forward and backwards selections. ([#979](https://github.com/GlotPress/GlotPress/issues/979))
* Locales: Remove invalid 'me' language code for Montenegrin. ([#619](https://github.com/GlotPress/GlotPress/issues/619))
* Locales: Remove extra control character after locale name for Czech. ([#696](https://github.com/GlotPress/GlotPress/issues/696))
* Locales: Fix Google code for German (Switzerland). ([#743](https://github.com/GlotPress/GlotPress/issues/743))
* Locales: Fix native locale name for Uighur. ([#870](https://github.com/GlotPress/GlotPress/issues/870))
* Locales: Fix plural expression for Persian locales. ([#1012](https://github.com/GlotPress/GlotPress/issues/1012))

**Features**

* Add locale variants support. ([#226](https://github.com/GlotPress/GlotPress/issues/226))
* Support XLIFF tags in Android imports. ([#628](https://github.com/GlotPress/GlotPress/issues/628))
* Mark new projects as active by default. ([#662](https://github.com/GlotPress/GlotPress/issues/662))
* Highlight current selected translation filters. ([#764](https://github.com/GlotPress/GlotPress/issues/764))
* Block adding glossary terms containing punctuation with error/warning. ([#768](https://github.com/GlotPress/GlotPress/issues/768))
* Show all translation warnings at once. ([#370](https://github.com/GlotPress/GlotPress/issues/370))
* Add tooltips to the accept/reject/fuzzy buttons. ([#460](https://github.com/GlotPress/GlotPress/issues/460))
* Add a second bulk action toolbar at the bottom of the translations list. ([#793](https://github.com/GlotPress/GlotPress/issues/793))
* Make notices dismissable. ([#658](https://github.com/GlotPress/GlotPress/issues/658))
* Add Korean locale to warnings length exclusions. ([#850](https://github.com/GlotPress/GlotPress/pull/850))
* Show fuzzy, waiting, and warnings filters to everyone. ([#881](https://github.com/GlotPress/GlotPress/issues/881))
* Provide translation count when filtering. ([#925](https://github.com/GlotPress/GlotPress/issues/925))
* Highlight glossary terms on translation previews. ([#899](https://github.com/GlotPress/GlotPress/issues/899))
* Add translation filter option to search in originals or translations only. ([#860](https://github.com/GlotPress/GlotPress/issues/860))
* Add translation filter 'with plural'. ([#959](https://github.com/GlotPress/GlotPress/issues/959))
* Add a 'translated' filter to match table column. ([#1121](https://github.com/GlotPress/GlotPress/issues/1121))
* Remove default sorting for untranslated view. ([#1106](https://github.com/GlotPress/GlotPress/issues/1106))
* Locales: Add Mauritian. ([#909](https://github.com/GlotPress/GlotPress/issues/909))
* Locales: Add WordPress locale for Chinese (Singapore). ([#823](https://github.com/GlotPress/GlotPress/issues/823))
* Locales: Add Northern Sotho. ([#1049](https://github.com/GlotPress/GlotPress/issues/1049))
* Locales: Add Fula. ([#1048](https://github.com/GlotPress/GlotPress/issues/1048))
* Locales: Add Papiamento (Curaçao and Bonaire) and Papiamento (Aruba). ([#1047](https://github.com/GlotPress/GlotPress/issues/1047))
* Locales: Add Maithili. ([#1029](https://github.com/GlotPress/GlotPress/issues/1029))
* Locales: Add Lower Sorbian, Ch’ti (France), Wolof. ([#1025](https://github.com/GlotPress/GlotPress/issues/1025))
* Locales: Add Bhojpuri, Bodo, German (Austria), Spanish (Dominican Republic), Spanish (Honduras), Upper Sorbian, Igbo, Karakalpak, N’ko, and Portuguese (Angola). ([#994](https://github.com/GlotPress/GlotPress/issues/994))
* Developers: Add filter to customize translations before saving. ([#517](https://github.com/GlotPress/GlotPress/issues/517))
* Developers: Add actions when a translation set is created/saved/deleted. ([#659](https://github.com/GlotPress/GlotPress/issues/659))
* Developers: Pass previous state to saved actions. ([#335](https://github.com/GlotPress/GlotPress/issues/335))
* Developers: Add filter to customize path prefix for locale glossaries. ([#655](https://github.com/GlotPress/GlotPress/issues/655))

Thanks to all the contributors: Alex Kirk, Alin Marcu, Chris Gårdenberg, Daniel James, Daniele Scasciafratte, David Stone, Dominik Schilling, Garrett Hyder, Greg Ross, Ignacio, Pedro Mendonça, Petya Raykovska, Ramon, Sergey Biryukov, SVANNER, Tor-Björn Fjellner and Yoav Farhi.

## [2.3.1] (March 1, 2017)

**Bugfixes**

* Don't require a project glossary to show terms of a locale glossary. ([#656](https://github.com/GlotPress/GlotPress/issues/656))
* Allow querying translations by priority. ([#664](https://github.com/GlotPress/GlotPress/issues/664))
* Fix incorrect nonce check for locale glossary imports. ([#673](https://github.com/GlotPress/GlotPress/issues/673))
* Fix flushing existing glossary on import. ([#675](https://github.com/GlotPress/GlotPress/issues/675))

**Features**

* Add `gp_locale_glossary_path_prefix` filter for the locale glossary path prefix. ([#655](https://github.com/GlotPress/GlotPress/issues/655))

Thanks to all the contributors so far: Alex Kirk, Dominik Schilling, Greg Ross, and Yoav Farhi.

## [2.3.0] (February 6, 2017)

(this space intentionally left blank)

## [2.3.0-rc.1] (January 31, 2017)

**Bugfixes**

* Allow project glossaries to override terms of a locale glossary. ([#640](https://github.com/GlotPress/GlotPress/issues/640))
* Remove hard coded 'default' slug which prevented locale glossaries for locale variants. ([#641](https://github.com/GlotPress/GlotPress/issues/641))
* During imports, don't change status `fuzzy` to `waiting` if a translation has warnings. ([#646](https://github.com/GlotPress/GlotPress/issues/646))
* Allow CLI imports to set the status of translations to `current`. ([#644](https://github.com/GlotPress/GlotPress/issues/644))

Thanks to all the contributors so far: Dominik Schilling, Greg Ross and Sergey Biryukov.

## [2.3.0-beta.1] (January 17, 2017)

**Bugfixes**

* Fix incorrect URL's in some cases on locale page. ([#632](https://github.com/GlotPress/GlotPress/issues/632))
* Fix truncation of download names if they contain a space. ([#633](https://github.com/GlotPress/GlotPress/issues/633))
* Improve glossary plural handling. ([#595](https://github.com/GlotPress/GlotPress/issues/595))
* Restore edit text for non translated entries. ([#519](https://github.com/GlotPress/GlotPress/issues/519))
* Don't create duplicate translations during imports. ([#579](https://github.com/GlotPress/GlotPress/issues/579))
* Redirect users back to previous page when they have to log in. ([#558](https://github.com/GlotPress/GlotPress/issues/558))
* Fix default file extension for .NET Resources files. ([#573](https://github.com/GlotPress/GlotPress/issues/573))
* Fix radio button code to remove spurious single quote. ([#610](https://github.com/GlotPress/GlotPress/issues/610))

**Features**

* Add JSON format for [JED](http://messageformat.github.io/Jed/) and plain JSON. ([#523](https://github.com/GlotPress/GlotPress/issues/523))
* Add support for locale glossaries. ([#227](https://github.com/GlotPress/GlotPress/issues/227))
* Add ability to mark translations as fuzzy. ([#620](https://github.com/GlotPress/GlotPress/issues/620))
* Enhance display of previous translations with special characters in them. ([#625](https://github.com/GlotPress/GlotPress/issues/625))
* Add support for importing fuzzy translations. ([#596](https://github.com/GlotPress/GlotPress/issues/596))
* Add keyboard shortcut for copying original strings. ([#554](https://github.com/GlotPress/GlotPress/issues/554))
* Developers: Add filters for translations queries. ([#524](https://github.com/GlotPress/GlotPress/issues/524))
* Developers: Add Fine-grained permissions per translations. ([#537](https://github.com/GlotPress/GlotPress/issues/537))
* Developers: Add filter for adding links to the translation editor. ([#597](https://github.com/GlotPress/GlotPress/issues/597))
* Add meta data to all file formats that can support it. ([#575](https://github.com/GlotPress/GlotPress/issues/575))
* Update ROH locale information. ([#605](https://github.com/GlotPress/GlotPress/issues/605))

Thanks to all the contributors so far: Alex Kirk, Anton Timmermans, Dominik Schilling, Greg Ross, Nikhil, Pascal Birchler, and Yoav Farhi.

## [2.2.2] (November 21, 2016)

**Security**

* Fix an information leak in the API, reported by Alex Kirk.

## [2.2.1] (November 11, 2016)

**Bugfixes**

* Fix missing header fields in .mo files. ([#594](https://github.com/GlotPress/GlotPress/issues/594))
* Add padding to table headers to avoid overlapping with sorting graphics. ([#565](https://github.com/GlotPress/GlotPress/issues/565))
* Fix for "Only variables should be passed by reference" warning when importing translations. ([#566](https://github.com/GlotPress/GlotPress/issues/566))

**Features**

* Add locale information for Xhosa. ([#603](https://github.com/GlotPress/GlotPress/issues/603))

Thanks to all the contributors so far: Alex Kirk, Dominik Schilling, and Greg Ross.

## [2.2.0] (September 30, 2016)

(this space intentionally left blank)

## [2.2.0-rc.1] (September 22, 2016)

(this space intentionally left blank)

## [2.2.0-beta.1] (September 19, 2016)

**Breaking Changes**

* Change the slug of the Kyrgyz locale from `ky` to `kir`. ([#550](https://github.com/GlotPress/GlotPress/pull/550))

**Bugfixes**

* Fix broken cancel link on project create form. ([#547](https://github.com/GlotPress/GlotPress/issues/547))
* Fix native name of the Tibetan locale name from བོད་སྐད to བོད་ཡིག. ([#539](https://github.com/GlotPress/GlotPress/pull/539))
* Fix extra entry in `GP_Translation::translations()`. ([#516](https://github.com/GlotPress/GlotPress/issues/516))
* Merge similar strings to improve translation. ([#535](https://github.com/GlotPress/GlotPress/issues/535))
* Refactor script and style registration to make them more reliable. ([#476](https://github.com/GlotPress/GlotPress/issues/476))
* Update locale information for Kyrgyz to use correct data. ([#550](https://github.com/GlotPress/GlotPress/pull/550))

**Features**

* Add locale information for the Latin version of Hausa. ([#549](https://github.com/GlotPress/GlotPress/pull/549))
* Fix translations which are using the placeholder for tab characters. ([#473](https://github.com/GlotPress/GlotPress/pull/473))
* Add `gp_reference_source_url` filter for the source URL of a project. ([#522](https://github.com/GlotPress/GlotPress/pull/522))
* Provide minified assets. ([#505](https://github.com/GlotPress/GlotPress/issue/505))
* Update JavaScript library for table sorting. ([#502](https://github.com/GlotPress/GlotPress/issue/502))

Thanks to all the contributors so far: Alex Kirk, David Decker, Dominik Schilling, Greg Ross, Pedro Mendonça, Petya Raykovska, and Sergey Biryukov.

## [2.1.0] (July 13, 2016)

(this space intentionally left blank)

## [2.1.0-rc.1] (July 7, 2016)

**Bugfixes**

* Allow project slugs to contain periods. ([#492](https://github.com/GlotPress/GlotPress/issues/492))

**Features**

* Add confirmation message when saving settings. ([#490](https://github.com/GlotPress/GlotPress/issues/490))
* Convert sort by fields from hard coded to a filterable function call. ([#488](https://github.com/GlotPress/GlotPress/issues/488)

## [2.1.0-beta.1] (June 29, 2016)

**Bugfixes**

* Replace `LIKE` queries for the status of an original with an exact match. ([#419](https://github.com/GlotPress/GlotPress/issues/419))
* Move `gp_translation_set_filters` hook to allow additions to the filter form. ([#391](https://github.com/GlotPress/GlotPress/issues/391))
* Fix wrong error message for translations with a missing set ID. ([#341](https://github.com/GlotPress/GlotPress/issues/341))
* Fix Android exports with translation that start with an @. ([#469](https://github.com/GlotPress/GlotPress/issues/469))
* Improve performance of default `GP_Translation->for_translation()` query. ([#376](https://github.com/GlotPress/GlotPress/issues/376))
* Use `__DIR__` constant for `GP_PATH`. ([#455](https://github.com/GlotPress/GlotPress/issues/455))
* Use lowercase field types in schema.php. ([#461](https://github.com/GlotPress/GlotPress/issues/461))
* Change field type for user IDs to `bigint(20)`. ([#464](https://github.com/GlotPress/GlotPress/issues/464))
* Don't call `gp_upgrade_data()` in `gp_upgrade_db()` on install. ([#361](https://github.com/GlotPress/GlotPress/issues/361))
* Define max index length for `user_id_action` column. ([#462](https://github.com/GlotPress/GlotPress/issues/462))

**Features**

* Allow export by priority of originals. ([#405](https://github.com/GlotPress/GlotPress/issues/405))
* Check imported translations for warnings. ([#401](https://github.com/GlotPress/GlotPress/issues/401))
* Allow translations to be imported with status waiting. ([#377](https://github.com/GlotPress/GlotPress/issues/377))
* Add `Language` header to PO exports. ([#428](https://github.com/GlotPress/GlotPress/issues/428))
* Add option to overwrite existing glossary when importing. ([#395](https://github.com/GlotPress/GlotPress/issues/395))
* Allow modification of accepted HTTP methods in the router. ([#393](https://github.com/GlotPress/GlotPress/issues/393))
* Update the `Project-Id-Version` header PO exports to better handle sub projects and be filterable. ([#442](https://github.com/GlotPress/GlotPress/issues/442))
* Convert the permissions list to a table. ([#99](https://github.com/GlotPress/GlotPress/issues/99))
* Split translation status counts by hidden and public. ([#397](https://github.com/GlotPress/GlotPress/issues/397))
* Store user ID of validator/approver on translation status changes. ([#293](https://github.com/GlotPress/GlotPress/issues/293))

Thanks to all the contributors so far: Dominik Schilling, Greg Ross, Yoav Farhi, Alex Kirk, Anton Timmermans, Mattias Tengblad

## [2.0.1] (April 25, 2016)

**Bugfixes**

* Avoid a PHP warning when a user had made translations and the user was then deleted. ([#386](https://github.com/GlotPress/GlotPress/issues/386))
* Update all delete permission levels to be consistent in different areas of GlotPress. ([#390](https://github.com/GlotPress/GlotPress/issues/390))
* Fix the CLI export command to properly use the "status" option. ([#404](https://github.com/GlotPress/GlotPress/issues/404))
* Add upgrade script to remove trailing slashes left of project paths from 1.0 which are no longer supported. ([#410](https://github.com/GlotPress/GlotPress/issues/410))
* Fix conflict with other plugins that also use the `GP_Locales` class. ([#413](https://github.com/GlotPress/GlotPress/issues/413))
* Exclude the art-xemoji locale from length check that caused spurious warnings. ([#417](https://github.com/GlotPress/GlotPress/issues/417))

**Features**

* Add Haitian Creole locale definition. ([#411](https://github.com/GlotPress/GlotPress/issues/411))
* Update Asturian locale definition. ([#412](https://github.com/GlotPress/GlotPress/issues/412))

Thanks to all the contributors so far: Dominik Schilling, Greg Ross, and Yoav Farhi.

## [2.0.0] (April 04, 2016)

**Bugfixes**

* Delete cookies for notices on installs without a base. ([#379](https://github.com/GlotPress/GlotPress/issues/379))
* Fix "Use as name" link on translation set creation page. ([#381](https://github.com/GlotPress/GlotPress/issues/381))

## [2.0.0-rc.1] (March 29, 2016)

(this space intentionally left blank)

## [2.0.0-beta.2] (March 27, 2016)

**Security**

* Implement nonces for URLs and forms to help protect against several types of attacks including CSRF. ([#355](https://github.com/GlotPress/GlotPress/issues/355))

**Bugfixes**

* Avoid a PHP warning when updating a glossary entry. ([#366](https://github.com/GlotPress/GlotPress/issues/366))
* Improve mb_* compat functions to support all parameters and utilize WordPress' compat functions. ([#364](https://github.com/GlotPress/GlotPress/issues/364))

## [2.0.0-beta.1] (March 17, 2016)

**Breaking Changes**

* Remove Translation Propagation from core. [Now available as a plugin](https://github.com/GlotPress/gp-translation-propagation/). ([#337](https://github.com/GlotPress/GlotPress/issues/337))
* Remove user and option handling in `gp_update_meta()`/`gp_delete_meta()`. ([#300](https://github.com/GlotPress/GlotPress/issues/300))
* Remove deprecated `assets/img/glotpress-logo.png`. ([#327](https://github.com/GlotPress/GlotPress/issues/327))
* Remove `gp_sanitize_for_url()` in favor of `sanitize_title()` for enhanced slug generation. ([#330](https://github.com/GlotPress/GlotPress/pull/330))
* Improve return values of `gp_meta_update()`. ([#318](https://github.com/GlotPress/GlotPress/issues/318)).
* Remove CLI command `GP_CLI_WPorg2Slug`. ([#347](https://github.com/GlotPress/GlotPress/issues/347))

**Features**

* Make projects, translation sets, and glossaries deletable via UI. ([#267](https://github.com/GlotPress/GlotPress/issues/267))
* Update several locale definitions to use new Facebook and Google codes and correct country codes. ([#246](https://github.com/GlotPress/GlotPress/issues/246))
* Add Greenlandic, Spanish (Guatemala), and Tahitian locale definition. ([#246](https://github.com/GlotPress/GlotPress/issues/246))
* Add auto detection for format of uploaded import files. ([#290](https://github.com/GlotPress/GlotPress/issues/290))
* Add UI to manage GlotPress administrators. ([#233](https://github.com/GlotPress/GlotPress/issues/233))
* Add checkbox for case-sensitive translation searches. ([#312](https://github.com/GlotPress/GlotPress/issues/312))
* Add support for Java properties files. ([#297](https://github.com/GlotPress/GlotPress/issues/297))
* Add cancel link to import pages. ([#268](https://github.com/GlotPress/GlotPress/issues/268))
* Add warning and disable the plugin if permalinks are not set. ([#218](https://github.com/GlotPress/GlotPress/issues/218))
* Add warning and disable the plugin if unsupported version of PHP is detected. ([#276](https://github.com/GlotPress/GlotPress/issues/276))
* Add inline documentation for actions and filters. ([#50](https://github.com/GlotPress/GlotPress/issues/50))
* Add backend support to allow for integration with WordPress' user profiles. ([#196](https://github.com/GlotPress/GlotPress/issues/196))
* Introduce a separate page for settings. ([#325](https://github.com/GlotPress/GlotPress/issues/325))
* Validate slugs for translation sets on saving. ([#329](https://github.com/GlotPress/GlotPress/issues/329))
* Standardize triggers in projects, translations and originals. ([#294](https://github.com/GlotPress/GlotPress/issues/294))
* Introduce `GP_Thing::after_delete()` method and new actions. ([#294](https://github.com/GlotPress/GlotPress/issues/294))
* Add .pot extension to `GP_Format_PO`. ([#230](https://github.com/GlotPress/GlotPress/issues/230))
* Various code cleanups to improve code quality. ([#237](https://github.com/GlotPress/GlotPress/issues/237))

**Bugfixes**

* Mark Sindhi locale definition as RTL. ([#243](https://github.com/GlotPress/GlotPress/issues/243))
* Replace `current_user_can( 'manage_options' )` with GlotPress permissions. ([#254](https://github.com/GlotPress/GlotPress/issues/254))
* Make child projects accessible if permalink structure has a trailing slash. ([#265](https://github.com/GlotPress/GlotPress/issues/265))
* Use real URLs for back links instead of JavaScript's history `back()` method. ([#278](https://github.com/GlotPress/GlotPress/issues/278))
* Replace deprecated/private `[_]wp_specialchars()` function with `htmlspecialchars()`. ([#280](https://github.com/GlotPress/GlotPress/issues/280))
* Merge similar translation strings and avoid using HTML tags in translation strings. ([#295](https://github.com/GlotPress/GlotPress/pull/295))
* Add missing `gp_` prefix for for translation actions. ([#232](https://github.com/GlotPress/GlotPress/issues/232))
* Fix case where `$original->validate()` fails if singular is '0'. ([#301](https://github.com/GlotPress/GlotPress/issues/301))
* Fix auto generation of project slugs with special characters. ([#328](https://github.com/GlotPress/GlotPress/issues/328))
* Suspend cache invalidation during original imports. ([#332](https://github.com/GlotPress/GlotPress/issues/332))
* Prevent submitting translations with empty plurals. ([#308](https://github.com/GlotPress/GlotPress/pull/308))
* Update schema definitions to work with WordPress' `dbDelta()` function. ([#343](https://github.com/GlotPress/GlotPress/issues/343))
* Fix redirect when a translation set update failed. ([#349](https://github.com/GlotPress/GlotPress/pull/349))
* Prevent a PHP fatal error when importing originals. ([#302](https://github.com/GlotPress/GlotPress/pull/302))

Thanks to all the contributors so far: Aki Björklund, Daisuke Takahashi, Dominik Schilling, Gabor Javorszky, Greg Ross, Peter Dave Hello, Rami, and Sergey Biryukov.

## [1.0.2] (March 09, 2016)

**Security**

* Sanitize messages in `gp_notice()`.

## [1.0.1] (January 21, 2016)

**Bugfixes**

* Unslash PHP's superglobals to prevent extra slashes in translations. ([#220](https://github.com/GlotPress/GlotPress/issues/220))
* Adjust add/delete glossary entry links with trailing slashes. ([#224](https://github.com/GlotPress/GlotPress/issues/224))

## [1.0.0] (January 18, 2016)

* Initial release.

[Unreleased]: https://github.com/GlotPress/GlotPress/compare/4.0.1...HEAD
[4.0.1]: https://github.com/GlotPress/GlotPress/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/GlotPress/GlotPress/compare/4.0.0-rc.1...4.0.0
[4.0.0-rc.1]: https://github.com/GlotPress/GlotPress/compare/4.0.0-beta.3...4.0.0-rc.1
[4.0.0-beta.3]: https://github.com/GlotPress/GlotPress/compare/4.0.0-beta.2...4.0.0-beta.3
[4.0.0-beta.2]: https://github.com/GlotPress/GlotPress/compare/4.0.0-beta.1...4.0.0-beta.2
[4.0.0-beta.1]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.11...4.0.0-beta.1
[4.0.0-alpha.11]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.10...4.0.0-alpha.11
[4.0.0-alpha.10]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.9...4.0.0-alpha.10
[4.0.0-alpha.9]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.8...4.0.0-alpha.9
[4.0.0-alpha.8]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.7...4.0.0-alpha.8
[4.0.0-alpha.7]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.6...4.0.0-alpha.7
[4.0.0-alpha.6]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.5...4.0.0-alpha.6
[4.0.0-alpha.5]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.4...4.0.0-alpha.5
[4.0.0-alpha.4]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.3...4.0.0-alpha.4
[4.0.0-alpha.3]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.2...4.0.0-alpha.3
[4.0.0-alpha.2]: https://github.com/GlotPress/GlotPress/compare/4.0.0-alpha.1...4.0.0-alpha.2
[4.0.0-alpha.1]: https://github.com/GlotPress/GlotPress/compare/3.0.0...4.0.0-alpha.1
[3.0.0]: https://github.com/GlotPress/GlotPress/compare/3.0.0-rc.4...3.0.0
[3.0.0-rc.4]: https://github.com/GlotPress/GlotPress/compare/3.0.0-rc.3...3.0.0-rc.4
[3.0.0-rc.3]: https://github.com/GlotPress/GlotPress/compare/3.0.0-rc.2...3.0.0-rc.3
[3.0.0-rc.2]: https://github.com/GlotPress/GlotPress/compare/3.0.0-rc.1...3.0.0-rc.2
[3.0.0-rc.1]: https://github.com/GlotPress/GlotPress/compare/3.0.0-beta.1...3.0.0-rc.1
[3.0.0-beta.1]: https://github.com/GlotPress/GlotPress/compare/3.0.0-alpha.4...3.0.0-beta.1
[3.0.0-alpha.4]: https://github.com/GlotPress/GlotPress/compare/3.0.0-alpha.3...3.0.0-alpha.4
[3.0.0-alpha.3]: https://github.com/GlotPress/GlotPress/compare/3.0.0-alpha.2...3.0.0-alpha.3
[3.0.0-alpha.2]: https://github.com/GlotPress/GlotPress/compare/3.0.0-alpha.1...3.0.0-alpha.2
[3.0.0-alpha.1]: https://github.com/GlotPress/GlotPress/compare/2.3.1...3.0.0-alpha.1
[2.3.1]: https://github.com/GlotPress/GlotPress/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/GlotPress/GlotPress/compare/2.3.0-rc.1...2.3.0
[2.3.0-rc.1]: https://github.com/GlotPress/GlotPress/compare/2.3.0-beta.1...2.3.0-rc.1
[2.3.0-beta.1]: https://github.com/GlotPress/GlotPress/compare/2.2.2...2.3.0-beta.1
[2.2.2]: https://github.com/GlotPress/GlotPress/compare/2.2.1...2.2.2
[2.2.1]: https://github.com/GlotPress/GlotPress/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/GlotPress/GlotPress/compare/2.2.0-rc.1...2.2.0
[2.2.0-rc.1]: https://github.com/GlotPress/GlotPress/compare/2.2.0-beta.1...2.2.0-rc.1
[2.2.0-beta.1]: https://github.com/GlotPress/GlotPress/compare/2.1.1...2.2.0-beta.1
[2.1.1]: https://github.com/GlotPress/GlotPress/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/GlotPress/GlotPress/compare/2.1.0-rc.1...2.1.0
[2.1.0-rc.1]: https://github.com/GlotPress/GlotPress/compare/2.1.0-beta.1...2.1.0-rc.1
[2.1.0-beta.1]: https://github.com/GlotPress/GlotPress/compare/2.0.1...2.1.0-beta.1
[2.0.1]: https://github.com/GlotPress/GlotPress/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/GlotPress/GlotPress/compare/2.0.0-rc.1...2.0.0
[2.0.0-rc.1]: https://github.com/GlotPress/GlotPress/compare/2.0.0-beta.2...2.0.0-rc.1
[2.0.0-beta.2]: https://github.com/GlotPress/GlotPress/compare/2.0.0-beta.1...2.0.0-beta.2
[2.0.0-beta.1]: https://github.com/GlotPress/GlotPress/compare/1.0.2...2.0.0-beta.1
[1.0.2]: https://github.com/GlotPress/GlotPress/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/GlotPress/GlotPress/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/GlotPress/GlotPress/compare/1.0.0-rc.2...1.0.0
[1.0.0-rc.2]: https://github.com/GlotPress/GlotPress/compare/1.0.0-rc.1...1.0.0-rc.2
[1.0.0-rc.1]: https://github.com/GlotPress/GlotPress/compare/1.0.0-beta.1...1.0-rc.1
[1.0-beta1]: https://github.com/GlotPress/GlotPress/releases/tag/1.0-beta1

[#1661]: https://github.com/GlotPress/GlotPress/pull/1661
[#1327]: https://github.com/GlotPress/GlotPress/pull/1327
[#1332]: https://github.com/GlotPress/GlotPress/pull/1332
[#1338]: https://github.com/GlotPress/GlotPress/pull/1338
[#1344]: https://github.com/GlotPress/GlotPress/pull/1344
[#1345]: https://github.com/GlotPress/GlotPress/pull/1345
[#1342]: https://github.com/GlotPress/GlotPress/pull/1342
[#1341]: https://github.com/GlotPress/GlotPress/pull/1341
[#1305]: https://github.com/GlotPress/GlotPress/pull/1305
[#1343]: https://github.com/GlotPress/GlotPress/pull/1343
[#1326]: https://github.com/GlotPress/GlotPress/pull/1326
[#1309]: https://github.com/GlotPress/GlotPress/pull/1309
[#1292]: https://github.com/GlotPress/GlotPress/pull/1292
[#1246]: https://github.com/GlotPress/GlotPress/pull/1246
[#1359]: https://github.com/GlotPress/GlotPress/pull/1359
[#1367]: https://github.com/GlotPress/GlotPress/pull/1367
[#1364]: https://github.com/GlotPress/GlotPress/pull/1364
[#1360]: https://github.com/GlotPress/GlotPress/pull/1360
[#1378]: https://github.com/GlotPress/GlotPress/pull/1378
[#1391]: https://github.com/GlotPress/GlotPress/pull/1391
[#1392]: https://github.com/GlotPress/GlotPress/pull/1392
[#1393]: https://github.com/GlotPress/GlotPress/pull/1393
[#1395]: https://github.com/GlotPress/GlotPress/pull/1395
[#1417]: https://github.com/GlotPress/GlotPress/pull/1417
[#1408]: https://github.com/GlotPress/GlotPress/pull/1408
[#1379]: https://github.com/GlotPress/GlotPress/pull/1379
[#1375]: https://github.com/GlotPress/GlotPress/pull/1375
[#1417]: https://github.com/GlotPress/GlotPress/pull/1417
[#1451]: https://github.com/GlotPress/GlotPress/pull/1451
[#1428]: https://github.com/GlotPress/GlotPress/pull/1428
[#1482]: https://github.com/GlotPress/GlotPress/pull/1482
[#1479]: https://github.com/GlotPress/GlotPress/pull/1479
[#1448]: https://github.com/GlotPress/GlotPress/pull/1448
[#1449]: https://github.com/GlotPress/GlotPress/pull/1449
[#1464]: https://github.com/GlotPress/GlotPress/pull/1464
[#1455]: https://github.com/GlotPress/GlotPress/pull/1455
[#1486]: https://github.com/GlotPress/GlotPress/pull/1486
[#1490]: https://github.com/GlotPress/GlotPress/pull/1490
[#1491]: https://github.com/GlotPress/GlotPress/pull/1491
[#1426]: https://github.com/GlotPress/GlotPress/pull/1426
[#1478]: https://github.com/GlotPress/GlotPress/pull/1478
[#1450]: https://github.com/GlotPress/GlotPress/pull/1450
[#1497]: https://github.com/GlotPress/GlotPress/pull/1497
[#1537]: https://github.com/GlotPress/GlotPress/pull/1537
[#1520]: https://github.com/GlotPress/GlotPress/pull/1520
[#1521]: https://github.com/GlotPress/GlotPress/pull/1521
[#1538]: https://github.com/GlotPress/GlotPress/pull/1538
[#1522]: https://github.com/GlotPress/GlotPress/pull/1522
[#1524]: https://github.com/GlotPress/GlotPress/pull/1524
[#1477]: https://github.com/GlotPress/GlotPress/pull/1477
[#1569]: https://github.com/GlotPress/GlotPress/pull/1569
[#1536]: https://github.com/GlotPress/GlotPress/pull/1536
[#1500]: https://github.com/GlotPress/GlotPress/pull/1500
[#1582]: https://github.com/GlotPress/GlotPress/pull/1582
[#1600]: https://github.com/GlotPress/GlotPress/pull/1600
[#1465]: https://github.com/GlotPress/GlotPress/pull/1465
[#1369]: https://github.com/GlotPress/GlotPress/pull/1369
[#1506]: https://github.com/GlotPress/GlotPress/pull/1506
[#1622]: https://github.com/GlotPress/GlotPress/pull/1622
[#1632]: https://github.com/GlotPress/GlotPress/pull/1632
[#1634]: https://github.com/GlotPress/GlotPress/pull/1634
[#1635]: https://github.com/GlotPress/GlotPress/pull/1635
[#1651]: https://github.com/GlotPress/GlotPress/pull/1651
[#1662]: https://github.com/GlotPress/GlotPress/pull/1662
[#1664]: https://github.com/GlotPress/GlotPress/pull/1664
[#1665]: https://github.com/GlotPress/GlotPress/pull/1665
[#1677]: https://github.com/GlotPress/GlotPress/pull/1677
[#1679]: https://github.com/GlotPress/GlotPress/pull/1679
[#1682]: https://github.com/GlotPress/GlotPress/pull/1682
[#1684]: https://github.com/GlotPress/GlotPress/pull/1684
[#1693]: https://github.com/GlotPress/GlotPress/pull/1693
[#1694]: https://github.com/GlotPress/GlotPress/pull/1694
[#1695]: https://github.com/GlotPress/GlotPress/pull/1695
[#1701]: https://github.com/GlotPress/GlotPress/pull/1701
[#1680]: https://github.com/GlotPress/GlotPress/pull/1680
[#1696]: https://github.com/GlotPress/GlotPress/pull/1696
[#1697]: https://github.com/GlotPress/GlotPress/pull/1697
[#1698]: https://github.com/GlotPress/GlotPress/pull/1698
[#1703]: https://github.com/GlotPress/GlotPress/pull/1703
[#1704]: https://github.com/GlotPress/GlotPress/pull/1704
[#1705]: https://github.com/GlotPress/GlotPress/pull/1705
[#1706]: https://github.com/GlotPress/GlotPress/pull/1706
[#1708]: https://github.com/GlotPress/GlotPress/pull/1708
[#1709]: https://github.com/GlotPress/GlotPress/pull/1709
[#1711]: https://github.com/GlotPress/GlotPress/pull/1711
[#1713]: https://github.com/GlotPress/GlotPress/pull/1713
[#1714]: https://github.com/GlotPress/GlotPress/pull/1714
[#1726]: https://github.com/GlotPress/GlotPress/pull/1726
[#1776]: https://github.com/GlotPress/GlotPress/pull/1776
[#1754]: https://github.com/GlotPress/GlotPress/pull/1754
[#1620]: https://github.com/GlotPress/GlotPress/pull/1620
[#1772]: https://github.com/GlotPress/GlotPress/pull/1772
[#1771]: https://github.com/GlotPress/GlotPress/pull/1771
[#1774]: https://github.com/GlotPress/GlotPress/pull/1774
[#1768]: https://github.com/GlotPress/GlotPress/pull/1768
[#1773]: https://github.com/GlotPress/GlotPress/pull/1773
[#1723]: https://github.com/GlotPress/GlotPress/pull/1723
[#1748]: https://github.com/GlotPress/GlotPress/pull/1748
[#1761]: https://github.com/GlotPress/GlotPress/pull/1761
[#1760]: https://github.com/GlotPress/GlotPress/pull/1760
[#1733]: https://github.com/GlotPress/GlotPress/pull/1733
[#1756]: https://github.com/GlotPress/GlotPress/pull/1756
[#1373]: https://github.com/GlotPress/GlotPress/pull/1373
[#1626]: https://github.com/GlotPress/GlotPress/pull/1626
[#1736]: https://github.com/GlotPress/GlotPress/pull/1736
[#1689]: https://github.com/GlotPress/GlotPress/pull/1689
[#1747]: https://github.com/GlotPress/GlotPress/pull/1747
[#1750]: https://github.com/GlotPress/GlotPress/pull/1750
[#1752]: https://github.com/GlotPress/GlotPress/pull/1752
[#1734]: https://github.com/GlotPress/GlotPress/pull/1734
[#1688]: https://github.com/GlotPress/GlotPress/pull/1688
[#1731]: https://github.com/GlotPress/GlotPress/pull/1731
[#1730]: https://github.com/GlotPress/GlotPress/pull/1730
[#1729]: https://github.com/GlotPress/GlotPress/pull/1729
[#1779]: https://github.com/GlotPress/GlotPress/pull/1779
[#1745]: https://github.com/GlotPress/GlotPress/pull/1745
[#1801]: https://github.com/GlotPress/GlotPress/pull/1801
[#1798]: https://github.com/GlotPress/GlotPress/pull/1798
[#1800]: https://github.com/GlotPress/GlotPress/pull/1800
[#1797]: https://github.com/GlotPress/GlotPress/pull/1797
[#1785]: https://github.com/GlotPress/GlotPress/pull/1785
[#1791]: https://github.com/GlotPress/GlotPress/pull/1791
[#1789]: https://github.com/GlotPress/GlotPress/pull/1789
[#1796]: https://github.com/GlotPress/GlotPress/pull/1796
[#1786]: https://github.com/GlotPress/GlotPress/pull/1786
[#1792]: https://github.com/GlotPress/GlotPress/pull/1792
[#1803]: https://github.com/GlotPress/GlotPress/pull/1803
[#1814]: https://github.com/GlotPress/GlotPress/pull/1814
[#1815]: https://github.com/GlotPress/GlotPress/pull/1815
[#1820]: https://github.com/GlotPress/GlotPress/pull/1820
[#1821]: https://github.com/GlotPress/GlotPress/pull/1821
[#1819]: https://github.com/GlotPress/GlotPress/pull/1819
[#1818]: https://github.com/GlotPress/GlotPress/pull/1818
[#1809]: https://github.com/GlotPress/GlotPress/pull/1809
[#1807]: https://github.com/GlotPress/GlotPress/pull/1807
[#1773]: https://github.com/GlotPress/GlotPress/pull/1773
