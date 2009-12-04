<?php 
class GP_Translation extends GP_Thing {
	
	var $per_page = 10;
	var $table_basename = 'translations';
	var $field_names = array( 'id', 'original_id', 'translation_set_id', 'translation_0', 'translation_1', 'translation_2', 'translation_3', 'user_id', 'status', 'date_added', 'date_modified', 'warnings');
	var $non_updatable_attributes = array( 'id', );
	
	static $statuses = array('rejected', 'waiting', 'old', 'current');

	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['translations'] ) && is_array( $args['translations'] ) ) {
		    foreach(range(0, 3) as $i) {
		        if ( isset( $args['translations'][$i] ) ) $args["translation_$i"] = $args['translations'][$i];
		    }
			unset( $args['translations'] );
		}
	    foreach(range(0, 3) as $i) {
	        if ( isset( $args["translation_$i"] ) ) {
				$args["translation_$i"] = $this->fix_translation( $args["translation_$i"] );
			}
	    }
		if ( gp_array_get( $args, 'warnings' ) == array() ) {
			$args['warnings'] = null;
		}
		return $args;
	}
	
	function prepare_fields_for_save( $args ) {
		$args = parent::prepare_fields_for_save( $args );
		if ( is_array( gp_array_get( $args, 'warnings' ) ) ) {
			$args['warnings'] = serialize( $args['warnings'] );
		}
		return $args;
	}
	
	function fix_translation( $translation ) {
		// when selecting some browsers take the newlines and some don't
		// that's why we don't want to insert too many newlines for each ↵
		$translation = str_replace( "↵\n", "↵", $translation );
		return str_replace( '↵', "\n", $translation );
	}
	
	function restrict_fields( $translation ) {
		$translation->translation_0_should_not_be( 'empty' );
		$translation->status_should_not_be( 'empty' );
		$translation->original_id_should_be( 'positive_int' );
		$translation->translation_set_id_should_be( 'positive_int' );
		$translation->user_id_should_be( 'positive_int' );
	}
	
	
	function set_fields( $db_object ) {
		parent::set_fields( $db_object );
		if ( $this->warnings ) {
			$this->warnings = maybe_unserialize( $this->warnings );
		}
	}
		
	function for_translation( $project, $translation_set, $page, $filters = array(), $sort = array() ) {
		global $gpdb;
		$locale = GP_Locales::by_slug( $translation_set->locale );
		$status_cond = '';

		$sort_bys = array('original' => 'o.singular', 'translation' => 't.translation_0', 'priority' => 'o.priority',
			'random' => 'RAND()', 'translation_date_added' => 't.date_added', 'original_date_added' => 'o.date_added' );
		$sort_by = gp_array_get( $sort_bys, gp_array_get( $sort, 'by' ), 'o.date_added' );
		$sort_hows = array('asc' => 'ASC', 'desc' => 'DESC', );
		$sort_how = gp_array_get( $sort_hows, gp_array_get( $sort, 'how' ), 'DESC' );

		$where = array();
		if ( gp_array_get( $filters, 'term' ) ) {
			// TODO: make it work if first letter is s. %%s is causing db::prepare trouble
			$like = "LIKE '%%".$this->like_escape_printf($gpdb->escape($filters['term']))."%%'";
			$where[] = '('.implode(' OR ', array_map( lambda('$x', '"($x $like)"', compact('like')), array('o.singular', 't.translation_0', 'o.plural', 't.translation_1')) ).')';
		}
		if ( 'yes' == gp_array_get( $filters, 'translated' ) ) {
			$where[] = 't.translation_0 IS NOT NULL';
		} elseif ( 'no' == gp_array_get( $filters, 'translated' ) ) {
			$where[] = 't.translation_0 IS NULL';
		}
		if ( gp_array_get( $filters, 'before_date_added' ) ) {
			$where[] = $gpdb->prepare( 't.date_added > %s', gp_array_get( $filters, 'before_date_added' ) );
		}
		if ( gp_array_get( $filters, 'translation_id' ) ) {
			$where[] = $gpdb->prepare( 't.id = %d', gp_array_get( $filters, 'translation_id' ) );
		}
		if ( gp_array_get( $filters, 'original_id' ) ) {
			$where[] = $gpdb->prepare( 'o.id = %d', gp_array_get( $filters, 'original_id' ) );
		}
		if ( 'yes' == gp_array_get( $filters, 'warnings' ) ) {
			$where[] = 't.warnings IS NOT NULL';
		} elseif ( 'no' == gp_array_get( $filters, 'warnings' ) ) {
			$where[] = 't.warnings IS NULL';
		}
				
		$where = implode( ' AND ', $where );
		if ( $where ) {
			$where = 'AND '.$where;
		}
		
		$join_where = array();
		$status = gp_array_get( $filters, 'status', 'current_or_waiting' );
		$all_in = true;
		$statuses = explode( '_or_', $status );
		foreach( $statuses as $single_status ) {
			if ( !in_array( $single_status, $this->get_static( 'statuses' ) ) ) {
				$all_in = false;
				break;
			}
		}
		if ( $all_in ) {
			$statuses_where = array();
			foreach( $statuses as $single_status ) {
				$statuses_where[] = $gpdb->prepare( 't.status = %s', $single_status );
			}
			$join_where[] = '(' . implode( ' OR ', $statuses_where ) . ')';
		}
		$join_where = implode( ' AND ', $join_where );
		if ( $join_where ) {
			$join_where = 'AND '.$join_where;
		}
		$limit = $this->sql_limit_for_paging( $page );
		$rows = $this->many_no_map( "
		    SELECT SQL_CALC_FOUND_ROWS t.*, o.*, t.id as id, o.id as original_id, t.status as translation_status, o.status as original_status, t.date_added as translation_added, o.date_added as original_added
		    FROM $gpdb->originals as o
		    LEFT JOIN $gpdb->translations AS t ON o.id = t.original_id AND t.translation_set_id = %d $join_where
		    WHERE o.project_id = %d AND o.status LIKE '+%%' $where ORDER BY $sort_by $sort_how $limit", $translation_set->id, $project->id );
		$this->found_rows = $this->found_rows();
		$translations = array();
		foreach( $rows as $row ) {
			if ( $row->user_id && $this->per_page != 'no-limit' ) {
				$user = GP::$user->get( $row->user_id );
				if ( $user ) $row->user_login = $user->user_login;
			} else {
				$row->user_login = '';
			}
			$row->translations = array($row->translation_0, $row->translation_1, $row->translation_2, $row->translation_3);
			$row->translations = array_slice( $row->translations, 0, $locale->nplurals );
			$row->references = preg_split('/\s+/', $row->references, -1, PREG_SPLIT_NO_EMPTY);
			$row->extracted_comment = $row->comment;
			$row->warnings = $row->warnings? maybe_unserialize( $row->warnings ) : null;
			unset($row->comment);
			foreach(range(0, 3) as $i) {
				$member = "translation_$i";
				unset($row->$member);
			}
			$row->row_id = $row->original_id . ( $row->id? "-$row->id" : '' );
			$translations[] = new Translation_Entry( (array)$row );
		}
		unset( $rows );
		return $translations;
	}
	
	function set_as_current() {
		return $this->update( array('status' => 'old'),
			array('original_id' => $this->original_id, 'translation_set_id' => $this->translation_set_id, 'status' => 'current') )
		&& 	$this->update( array('status' => 'old'),
				array('original_id' => $this->original_id, 'translation_set_id' => $this->translation_set_id, 'status' => 'waiting') )
	    && $this->update( array('status' => 'old'),
			array('original_id' => $this->original_id, 'translation_set_id' => $this->translation_set_id, 'status' => '-fuzzy') )
		&& $this->update( array('status' => 'current') );
	}
	
	function reject() {
		return $this->update( array('status' => 'rejected') );
	}
	
	function translations() {
		$translations = array();
	    foreach(range(0, 3) as $i) {
	        $translations[$i] = isset( $this->{"translation_$i"} )? $this->{"translation_$i"} : null;
	    }
		return $translations;
	}
}
GP::$translation = new GP_Translation();