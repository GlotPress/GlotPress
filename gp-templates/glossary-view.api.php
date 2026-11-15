<?php
/**
 * API: Glossary entries.
 *
 * Editor details (`last_edited_by`, `user_login`, `user_display_name`) are not
 * part of the response.
 *
 * @package GlotPress
 * @subpackage Templates
 */

$entries = array();

foreach ( $glossary_entries as $entry ) {
	$entries[] = array(
		'id'             => (int) $entry->id,
		'glossary_id'    => (int) $entry->glossary_id,
		'term'           => $entry->term,
		'part_of_speech' => $entry->part_of_speech,
		'translation'    => $entry->translation,
		'comment'        => $entry->comment,
		'date_modified'  => $entry->date_modified,
	);
}

echo wp_json_encode(
	array(
		'glossary'        => array(
			'id'          => (int) $glossary->id,
			'description' => $glossary->description,
		),
		'locale'          => $locale->slug,
		'translation_set' => $translation_set->slug,
		'term'            => '' !== $term ? $term : null,
		'extended'        => $extended,
		'entries'         => $entries,
	)
);
