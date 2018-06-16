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
	if ( ! isset( $glossary_entries_terms ) ) {
		$glossary_entries       = $glossary->get_entries();
		$glossary_entries_terms = gp_sort_glossary_entries_terms( $glossary_entries );
	}

	$translation = map_glossary_entries_to_translation_originals( $translation, $glossary, $glossary_entries_terms );
}

gp_tmpl_load(
	'translation-row-preview', array(
		'translation'             => $translation,
		'can_edit'                => $can_edit,
		'can_approve'             => $can_approve,
		'can_approve_translation' => $can_approve_translation,
	)
);
gp_tmpl_load(
	'translation-row-editor', array(
		'translation'             => $translation,
		'can_write'               => $can_write,
		'can_edit'                => $can_edit,
		'can_approve'             => $can_approve,
		'can_approve_translation' => $can_approve_translation,
		'can_edit'                => $can_edit,
		'locale'                  => $locale,
		'project'                 => $project,
		'translation_set'         => $translation_set,
	)
);
