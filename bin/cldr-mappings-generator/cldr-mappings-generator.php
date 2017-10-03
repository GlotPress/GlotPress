<?php
/**
 * Generates the CLDR plural mapping and updates the locales.php file.
 *
 * @package GlotPress
 */

$locales_file = __DIR__ . '/../../locales/locales.php';
require __DIR__ . '/class-cldr-plural-mapper.php';
require $locales_file;

$pm = new CLDR_Plural_Mapper();

$pm->generate_mapping();
$pm->update_locales( $locales_file );
