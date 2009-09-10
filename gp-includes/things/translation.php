<?php 
class GP_Translation extends GP_Thing {
	
	var $per_page = 50;
	
	var $table_basename = 'translations';
	var $field_names = array( 'id', 'original_id', 'translation_set_id', 'translation_0', 'translation_1', 'translation_2', 'translation_3',
	 	'user_id', 'status' );
	var $non_updatable_attributes = array( 'id', );


	function restrict_fields( $translation ) {
		$translation->translation_0_should_not_be( 'empty' );
		$translation->status_should_not_be( 'empty' );
		$translation->original_id_should_be( 'positive_int' );
		$translation->translation_set_id_should_be( 'positive_int' );
		$translation->user_id_should_be( 'positive_int' );
	}
	
	
	/**
	 * The best translation for each original string in the project
	 */
	function by_project_and_translation_set( $project, $translation_set, $page = 1 ) {
		return $this->by_project_and_translation_set_and_status( $project, $translation_set, '+', $page );
	}
	
	function by_project_and_translation_set_and_status( $project, $translation_set, $status, $page = 1 ) {
		global $gpdb;
		$page = intval( $page )? intval( $page ) : 1;
		$locale = GP_Locales::by_slug( $translation_set->locale );
		$status_cond = '';
		if ( in_array( $status, array('+', '-') ) ) {
			$status_cond = "t.status LIKE '$status%%'";
		} elseif ( is_array($status) ) {
			$args = array( implode( ' OR ', 't.status = %s' ) );
			$args = array_merge( $args, $status );
			$status_cond = call_user_func_array( array(&$gpdb, 'prepare'), $args );
		} else {
			$status_cond = $gpdb->prepare('t.status = %s', $status);
		}
		$limit = $this->sql_limit_for_paging( $page );
		$rows = $this->many( "
		    SELECT SQL_CALC_FOUND_ROWS t.*, o.*, t.id as id, o.id as original_id, t.status as translation_status, o.status as original_status
		    FROM $gpdb->originals as o
		    LEFT JOIN $gpdb->translations AS t ON o.id = t.original_id AND $status_cond AND t.translation_set_id = %d
		    WHERE o.project_id = %d AND o.status LIKE '+%%' ORDER BY t.status ASC $limit", $translation_set->id, $project->id );
		$translations = new Translations();
		foreach( $rows as $row ) {
			$row->translations = array($row->translation_0, $row->translation_1, $row->translation_2, $row->translation_3);
			$row->translations = array_slice( $row->translations, 0, $locale->nplurals );
			$row->extracted_comment = $row->comment;
			$row->references = preg_split('/\s+/', $row->references, -1, PREG_SPLIT_NO_EMPTY);
			
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
GP::$translation = new GP_Translation();