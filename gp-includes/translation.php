<?php 
class GP_Translation {
	/**
	 * The current translation for each original string in the project
	 */
	function current_by_project_and_translation_set( $project, $translation_set ) {
		global $gpdb;
		$rows = $gpdb->get_results( $gpdb->prepare( "
		    SELECT t.*, o.*, t.id as id, o.id as original_id
		    FROM $gpdb->originals as o
		    LEFT JOIN $gpdb->translations AS t ON o.id = t.original_id AND t.status = 'current' AND t.translation_set_id = %d
		    WHERE o.project_id = %d AND o.status LIKE '+%%' ORDER BY t.id ASC", $translation_set->id, $project->id ) );
		$translations = new Translations();
		foreach( $rows as $row ) {
			$row->translations = array($row->translation_0, $row->translation_1, $row->translation_2, $row->translation_3);
			$row->extracted_comment = $row->comment;
			$row->references = preg_split('/\s+/', $row->references, PREG_SPLIT_NO_EMPTY);
			unset($row->comment);
			foreach(range(0, 3) as $i) {
				$member = "translation_$i";
				unset($row->$member);
			}
			$translations->add_entry((array)$row);
		}
		return $translations;
	}
}
?>