<?php
class GP_Translation extends GP_Thing {

	var $per_page = 15;
	var $table_basename = 'translations';
	var $field_names = array( 'id', 'original_id', 'translation_set_id', 'translation_0', 'translation_1', 'translation_2', 'translation_3', 'translation_4', 'translation_5','user_id', 'status', 'date_added', 'date_modified', 'warnings' );
	var $non_updatable_attributes = array( 'id', );

	static $statuses = array( 'current', 'waiting', 'rejected', 'fuzzy', 'old', );
	static $number_of_plural_translations = 6;

	function create( $args ) {
		$inserted = parent::create( $args );

		if ( $inserted && is_array( $args ) && isset( $args['translation_set_id'] ) ) {
			gp_clean_translation_set_cache( $args['translation_set_id'] );
		}

		return $inserted;
	}

	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['translations'] ) && is_array( $args['translations'] ) ) {
			foreach( range( 0, $this->get_static( 'number_of_plural_translations' ) ) as $i ) {
				if ( isset( $args['translations'][ $i ] ) ) {
					$args["translation_$i"] = $args['translations'][ $i ];
				}
			}
			unset( $args['translations'] );
		}
		foreach( range( 0, $this->get_static( 'number_of_plural_translations' ) ) as $i ) {
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
		$translation->translation_0_should_not_be( 'empty_string' );
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

	function for_export( $project, $translation_set, $filters =  null ) {
		return GP::$translation->for_translation( $project, $translation_set, 'no-limit', $filters? $filters : array( 'status' => 'current_or_untranslated' ) );
	}

	function for_translation( $project, $translation_set, $page, $filters = array(), $sort = array() ) {
		global $gpdb;
		$locale = GP_Locales::by_slug( $translation_set->locale );

		$join_type = 'INNER';

		$sort_bys = array('original' => 'o.singular %s', 'translation' => 't.translation_0 %s', 'priority' => 'o.priority %s, o.date_added DESC',
			'random' => 'o.priority DESC, RAND()', 'translation_date_added' => 't.date_added %s', 'original_date_added' => 'o.date_added %s',
			'references' => 'o.references' );

		$default_sort = GP::$user->current()->sort_defaults();

		$sort_by = gp_array_get( $sort_bys, gp_array_get( $sort, 'by' ),  gp_array_get( $sort_bys, $default_sort['by'] ) );
		$sort_hows = array('asc' => 'ASC', 'desc' => 'DESC', );
		$sort_how = gp_array_get( $sort_hows, gp_array_get( $sort, 'how' ), gp_array_get( $sort_hows, $default_sort['how'] ) );

		$where = array();
		if ( gp_array_get( $filters, 'term' ) ) {
			$like = "LIKE '%" . ( $gpdb->escape( like_escape ( gp_array_get( $filters, 'term' ) ) ) ) . "%'";
			$where[] = '(' . implode( ' OR ', array_map( function( $x ) use ( $like ) { return "($x $like)"; }, array( 'o.singular', 't.translation_0', 'o.plural', 't.translation_1', 'o.context', 'o.references' ) ) ) . ')';
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
			$where[] = 't.warnings != ""';
		} elseif ( 'no' == gp_array_get( $filters, 'warnings' ) ) {
			$where[] = 't.warnings IS NULL';
		}
		if ( 'yes' == gp_array_get( $filters, 'with_context' ) ) {
			$where[] = 'o.context IS NOT NULL';
		}
		if ( 'yes' == gp_array_get( $filters, 'with_comment' ) ) {
			$where[] = 'o.comment IS NOT NULL AND o.comment <> ""';
		}

		if ( gp_array_get( $filters, 'user_login' ) ) {
			$user = GP::$user->by_login( $filters['user_login'] );
			// do not return any entries if the user doesn't exist
			$where[] = $gpdb->prepare( 't.user_id = %d', ($user && $user->id)? $user->id : -1 );
		}

		if ( !GP::$user->current()->can( 'write', 'project', $project->id ) ) {
			$where[] = 'o.priority > -2';
		}

		$join_where = array();
		$status = gp_array_get( $filters, 'status', 'current_or_waiting_or_fuzzy_or_untranslated' );
		$statuses = explode( '_or_', $status );
		if ( in_array( 'untranslated', $statuses ) ) {
			if ( $statuses == array( 'untranslated' ) ) {
				$where[] = 't.translation_0 IS NULL';
			}
			$join_type = 'LEFT';
			$join_where[] = 't.status != "rejected"';
			$join_where[] = 't.status != "old"';
			$statuses = array_filter( $statuses, function( $x ) { return $x != 'untranslated'; } );
		}

		$all_statuses = $this->get_static( 'statuses' );
		$statuses = array_filter( $statuses, function( $s ) use ( $all_statuses ) {
			return in_array( $s, $all_statuses );
		} );

		if ( $statuses ) {
			$statuses_where = array();
			foreach( $statuses as $single_status ) {
				$statuses_where[] = $gpdb->prepare( 't.status = %s', $single_status );
			}
			$statuses_where = '(' . implode( ' OR ', $statuses_where ) . ')';
			$join_where[] = $statuses_where;
		}

		$where = apply_filters( 'for_translation_where', $where, $translation_set );

		$where = implode( ' AND ', $where );
		if ( $where ) {
			$where = 'AND '.$where;
		}

		$join_where = implode( ' AND ', $join_where );
		if ( $join_where ) {
			$join_where = 'AND '.$join_where;
		}

		$sql_sort = sprintf( $sort_by, $sort_how );

		$limit = $this->sql_limit_for_paging( $page, $this->per_page );

		$sql_for_translations = "
			SELECT SQL_CALC_FOUND_ROWS t.*, o.*, t.id as id, o.id as original_id, t.status as translation_status, o.status as original_status, t.date_added as translation_added, o.date_added as original_added
			FROM $gpdb->originals as o
			$join_type JOIN $gpdb->translations AS t ON o.id = t.original_id AND t.translation_set_id = ".$gpdb->escape($translation_set->id)." $join_where
			WHERE o.project_id = ".$gpdb->escape( $project->id )." AND o.status LIKE '+%' $where ORDER BY $sql_sort $limit";
		$rows = $this->many_no_map( $sql_for_translations );
		$this->found_rows = $this->found_rows();
		$translations = array();
		foreach( (array)$rows as $row ) {
			if ( $row->user_id && $this->per_page != 'no-limit' ) {
				$user = GP::$user->get( $row->user_id );
				if ( $user ) {
					$row->user_login = $user->user_login;
					$row->user_display_name = $user->display_name;
					$row->user_nicename = $user->user_nicename;
				}
			} else {
				$row->user_login = $row->user_display_name = $row->user_nicename = '';
			}
			$row->translations = array();
			for( $i = 0; $i < $locale->nplurals; $i++ ) {
				$row->translations[] = $row->{"translation_".$i};
			}
			$row->references = preg_split('/\s+/', $row->references, -1, PREG_SPLIT_NO_EMPTY);
			$row->extracted_comments = $row->comment;
			$row->warnings = $row->warnings? maybe_unserialize( $row->warnings ) : null;
			unset($row->comment);
			foreach( range( 0, $this->get_static( 'number_of_plural_translations' ) ) as $i ) {
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
		$result = $this->update( array('status' => 'old'),
			array('original_id' => $this->original_id, 'translation_set_id' => $this->translation_set_id, 'status' => 'current') )
		&& 	$this->update( array('status' => 'old'),
				array('original_id' => $this->original_id, 'translation_set_id' => $this->translation_set_id, 'status' => 'waiting') )
		&& $this->update( array('status' => 'old'),
			array('original_id' => $this->original_id, 'translation_set_id' => $this->translation_set_id, 'status' => 'fuzzy') )
		&& $this->save( array('status' => 'current') );

		if ( apply_filters( 'enable_propagate_translations_across_projects', true ) ) {
			$this->propagate_across_projects();
		}

		return $result;
	}

	function reject() {
		$this->set_status( 'rejected' );
	}

	function copy_into_set( $new_translation_set_id, $new_original_id, $status = 'fuzzy' ) {
		if ( ! in_array( $status, $this->get_static( 'statuses' ) ) ) {
			return;
		}

		$copy = new GP_Translation( $this->fields() );
		$copy->original_id = $new_original_id;
		$copy->translation_set_id = $new_translation_set_id;
		$copy->status = $status;

		GP::$translation->create( $copy );
		// Flush cache, create() doesn't flush caches for copies, see r994.
		gp_clean_translation_set_cache( $new_translation_set_id );
	}

	function propagate_across_projects() {
		if ( $this->status != 'current' ) {
			return;
		}

		$original = GP::$original->get( $this->original_id );
		$originals_in_other_projects = $original->get_matching_originals_in_other_projects();

		if ( ! $originals_in_other_projects ) {
			return;
		}

		$translation_set = GP::$translation_set->get( $this->translation_set_id );
		foreach ( $originals_in_other_projects as $o ) {
			$o_translation_set = GP::$translation_set->by_project_id_slug_and_locale( $o->project_id, $translation_set->slug, $translation_set->locale );

			if ( ! $o_translation_set ) {
				continue;
			}

			$current_translation = GP::$translation->find_no_map( array( 'translation_set_id' => $o_translation_set->id, 'original_id' => $o->id, 'status' => 'current' ) );

			if ( ! $current_translation  ) {
				$copy_status = apply_filters( 'translations_to_other_projects_status', 'current' );
				$this->copy_into_set( $o_translation_set->id, $o->id, $copy_status );
			}
		}
	}

	function set_status( $status ) {
		if ( 'current' == $status ) {
			$updated = $this->set_as_current();
		} else {
			$updated = $this->save( array( 'status' => $status ) );
		}

		if ( $updated ) {
			gp_clean_translation_set_cache( $this->translation_set_id );
		}

		return $updated;
	}

	function translations() {
		$translations = array();
		foreach( range( 0, $this->get_static( 'number_of_plural_translations' ) ) as $i ) {
			$translations[ $i ] = isset( $this->{"translation_$i"} ) ? $this->{"translation_$i"} : null;
		}
		return $translations;
	}

	function last_modified( $translation_set ) {
		global $gpdb;

		$last_modified = wp_cache_get( $translation_set->id, 'translation_set_last_modified' );
		// Cached as "" if no translations.
		if ( "" === $last_modified ) {
			return false;
		} elseif ( false !== $last_modified ) {
			return $last_modified;
		}

		$last_modified = $gpdb->get_var( $gpdb->prepare( "SELECT date_modified FROM {$this->table} WHERE translation_set_id = %d AND status = %s ORDER BY date_modified DESC LIMIT 1", $translation_set->id, 'current' ) );
		wp_cache_set( $translation_set->id, (string) $last_modified, 'translation_set_last_modified' );
		return $last_modified;
	}

	function after_create() {
		do_action( 'translation_created', $this );
		return true;
	}

	function after_save() {
		do_action( 'translation_saved', $this );
		return true;
	}

}

GP::$translation = new GP_Translation();
