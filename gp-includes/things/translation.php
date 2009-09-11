<?php 
class GP_Translation extends GP_Thing {
	
	var $per_page = 10;
	
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
	
	function for_translation( $project, $translation_set, $page, $filters = array(), $sort = array() ) {
		global $gpdb;
		$page = intval( $page )? intval( $page ) : 1;
		$locale = GP_Locales::by_slug( $translation_set->locale );
		$status_cond = '';

		$sort_bys = array('original' => 'o.singular', 'translation' => 't.translation_0', 'priority' => 'o.priority',
			'random' => 'RAND()');
		$sort_by = gp_array_get( $sort_bys, gp_array_get( $sort, 'by' ), 'o.singular' );
		$sort_hows = array('asc' => 'ASC', 'desc' => 'DESC', );
		$sort_how = gp_array_get( $sort_hows, gp_array_get( $sort, 'how' ), 'DESC' );

		$where = array();
		if ( gp_array_get( $filters, 'term' ) ) {
			// TODO: make it work if first letter is s. %%s is causing db::prepare trouble
			$like = "LIKE '%%".$this->like_escape_printf($gpdb->escape($filters['term']))."%%'";
			$where[] = '('.implode(' OR ', array_map( lambda('$x', '"($x $like)"', compact('like')), array('o.singular', 't.translation_0')) ).')';
		}
		if ( 'yes' == gp_array_get( $filters, 'translated' ) ) {
			$where[] = 't.translation_0 IS NOT NULL';
		} elseif ( 'no' == gp_array_get( $filters, 'translated' ) ) {
			$where[] = 't.translation_0 IS NULL';
		}
		$where = implode( ' AND ', $where );
		if ( $where ) {
			$where = 'AND '.$where;
		}
		
		$join_where = array();
		// TODO: keep possible values in central place and use it from the template, too
		// TODO: filterable
		$statuses = array('-rejected', '-waiting', '-old', '+current');
		$status = gp_array_get( $filters, 'status' );
		if ( in_array( $status, $statuses ) ) {
			$join_where[] = $gpdb->prepare( 't.status = %s', $status );
		} elseif ( in_array( $status, array('+', '-') ) ) {
			$join_where[] = "t.status LIKE '$status%'";
		}
		$join_where = implode( ' AND ', $join_where );
		if ( $join_where ) {
			$join_where = 'AND '.$join_where;
		}
		
		$limit = $this->sql_limit_for_paging( $page );
		$rows = $this->many( "
		    SELECT SQL_CALC_FOUND_ROWS t.*, o.*, t.id as id, o.id as original_id, t.status as translation_status, o.status as original_status
		    FROM $gpdb->originals as o
		    LEFT JOIN $gpdb->translations AS t ON o.id = t.original_id AND t.translation_set_id = %d $join_where
		    WHERE o.project_id = %d AND o.status LIKE '+%%' $where ORDER BY $sort_by $sort_how $limit", $translation_set->id, $project->id );
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