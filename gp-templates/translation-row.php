<?php
/**
 * Template for a single translation row in a translation set display
 *
 * @package GlotPress
 * @subpackage Templates
 */

$user            = wp_get_current_user();
$can_reject_self = ( isset( $translation->user->user_login ) && $user->user_login === $translation->user->user_login && 'waiting' === $translation->translation_status );

if ( is_object( $glossary ) ) {
	$translation = map_glossary_entries_to_translation_originals( $translation, $glossary );
}

$translation_singular = isset( $translation->singular_glossary_markup ) ? $translation->singular_glossary_markup : esc_translation( $translation->singular );

gp_tmpl_load( 'translation-row-preview', get_defined_vars() );
gp_tmpl_load( 'translation-row-editor', get_defined_vars() );
