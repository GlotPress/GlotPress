<?php
define( 'GP_NO_ROUTING', true );
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

$progname = array_shift( $argv );

list( $project_path, $locale_slug, $translation_set_slug ) = $argv;

$project = GP_Project::by_path( $project_path );
$locale = GP_Locales::by_slug( $locale_slug );
$translation_set = &GP_Translation_Set::by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );


$po = new PO();

$po->set_header('MIME-Version', '1.0');
$po->set_header('Content-Transfer-Encoding', '8bit');
$po->set_header('X-Generator', 'GlotPress/' . gp_get_option('version'));
$po->set_header('Content-Type', 'text/plain; charset=UTF-8');
$po->set_header('Plural-Forms', "nplurals=$locale->nplurals; plural=$locale->plural_expression;");
$po->merge_with(GP_Translation::by_project_and_translation_set_and_status( $project, $translation_set, '+current' ));
echo "# Translation of {$project->name} in {$locale->english_name}\n";
echo "# This file is distributed under the same license as the {$project->name} package.\n";
echo $po->export();
echo "\n"
?>