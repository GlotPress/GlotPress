<?php 
class GP_Translation {
	/**
	 * The best translation for each original string in the project
	 */
	function by_project_and_translation_set( $project, $translation_set ) {
		return GP_Translation::by_project_and_translation_set_and_status( $project, $translation_set, '+' );
	}
	
	function by_project_and_translation_set_and_status( $project, $translation_set, $status ) {
		global $gpdb;
		$status_cond = '';
		if ( in_array( $status, array('+', '-') ) ) {
			$status_cond = "t.status LIKE '$status%%'";
		} elseif ( is_array($status) ) {
			$args = array( implode( ' OR ', 't.status = %s' ) );
			$args = array_merge( $args, $status );
			$status_cond = call_user_func_array( array($gpdb, 'prepare'), $args );
		} else {
			$status_cond = $gpdb->prepare('t.status = %s', $status);
		}
		$rows = $gpdb->get_results( $gpdb->prepare( "
		    SELECT t.*, o.*, t.id as id, o.id as original_id, t.status as translation_status, o.status as original_status
		    FROM $gpdb->originals as o
		    LEFT JOIN $gpdb->translations AS t ON o.id = t.original_id AND $status_cond AND t.translation_set_id = %d
		    WHERE o.project_id = %d AND o.status LIKE '+%%' ORDER BY t.status ASC", $translation_set->id, $project->id ) );
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