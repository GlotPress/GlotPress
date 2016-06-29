<?php
/**
 * Things: GP_Translation class
 *
 * @package GlotPress
 * @subpackage Things
 * @since 1.0.0
 */

/**
 * Core class used to implement the translations.
 *
 * @since 1.0.0
 */
class GP_Translation extends GP_Thing {

	public $per_page = 15;

	var $table_basename = 'gp_translations';
	var $field_names = array( 'id', 'original_id', 'translation_set_id', 'translation_0', 'translation_1', 'translation_2', 'translation_3', 'translation_4', 'translation_5','user_id', 'user_id_last_modified', 'status', 'date_added', 'date_modified', 'warnings' );
	var $int_fields = array( 'id', 'original_id', 'translation_set_id', 'user_id', 'user_id_last_modified' );
	var $non_updatable_attributes = array( 'id' );

	public $id;
	public $original_id;
	public $translation_set_id;
	public $translation_0;
	public $translation_1;
	public $translation_2;
	public $translation_3;
	public $translation_4;
	public $translation_5;
	public $user_id;
	/**
	 * User id of user (validator) who last changed the status of the translation.
	 *
	 * @since 2.1.0
	 *
	 * @var $user_id_last_modified
	 */
	public $user_id_last_modified;
	public $status;
	public $date_added;
	public $date_modified;
	public $warnings;
	public $found_rows;

	static $statuses = array( 'current', 'waiting', 'rejected', 'fuzzy', 'old', );
	static $number_of_plural_translations = 6;

	public function create( $args ) {
		$inserted = parent::create( $args );

		if ( $inserted && is_array( $args ) && isset( $args['translation_set_id'] ) ) {
			gp_clean_translation_set_cache( $args['translation_set_id'] );
		}

		return $inserted;
	}

	/**
	 * Normalizes an array with key-value pairs representing
	 * a GP_Translation object.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments for a GP_Translation object.
	 * @return array Normalized arguments for a GP_Translation object.
	 */
	public function normalize_fields( $args ) {
		$args = (array) $args;

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

	public function prepare_fields_for_save( $args ) {
		$args = parent::prepare_fields_for_save( $args );
		if ( is_array( gp_array_get( $args, 'warnings' ) ) ) {
			$args['warnings'] = serialize( $args['warnings'] );
		}
		return $args;
	}

	public function fix_translation( $translation ) {
		// when selecting some browsers take the newlines and some don't
		// that's why we don't want to insert too many newlines for each ↵
		$translation = str_replace( "↵\n", "↵", $translation );
		return str_replace( '↵', "\n", $translation );
	}

	/**
	 * Sets restriction rules for fields.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Validation_Rules $rules The validation rules instance.
	 */
	public function restrict_fields( $rules ) {
		$rules->translation_0_should_not_be( 'empty_string' );
		$rules->translation_1_should_not_be( 'empty_string' );
		$rules->translation_2_should_not_be( 'empty_string' );
		$rules->translation_3_should_not_be( 'empty_string' );
		$rules->translation_4_should_not_be( 'empty_string' );
		$rules->translation_5_should_not_be( 'empty_string' );
		$rules->status_should_not_be( 'empty' );
		$rules->original_id_should_be( 'positive_int' );
		$rules->translation_set_id_should_be( 'positive_int' );
		$rules->user_id_should_be( 'positive_int' );
		$rules->user_id_last_modified_should_not_be( 'empty_string' );
	}


	public function set_fields( $db_object ) {
		parent::set_fields( $db_object );
		if ( $this->warnings ) {
			$this->warnings = maybe_unserialize( $this->warnings );
		}
	}

	public function for_export( $project, $translation_set, $filters =  null ) {
		return GP::$translation->for_translation( $project, $translation_set, 'no-limit', $filters? $filters : array( 'status' => 'current_or_untranslated' ) );
	}

	public function for_translation( $project, $translation_set, $page, $filters = array(), $sort = array() ) {
		global $wpdb;
		$locale = GP_Locales::by_slug( $translation_set->locale );

		$join_type = 'INNER';

		$sort_bys = array('original' => 'o.singular %s', 'translation' => 't.translation_0 %s', 'priority' => 'o.priority %s, o.date_added DESC',
			'random' => 'o.priority DESC, RAND()', 'translation_date_added' => 't.date_added %s', 'original_date_added' => 'o.date_added %s',
			'references' => 'o.references' );

		$default_sort = get_user_option( 'gp_default_sort' );
		if ( ! is_array( $default_sort ) ) {
			$default_sort = array(
				'by'  => 'priority',
				'how' => 'desc'
			);
		}

		$sort_by = gp_array_get( $sort_bys, gp_array_get( $sort, 'by' ),  gp_array_get( $sort_bys, $default_sort['by'] ) );
		$sort_hows = array('asc' => 'ASC', 'desc' => 'DESC', );
		$sort_how = gp_array_get( $sort_hows, gp_array_get( $sort, 'how' ), gp_array_get( $sort_hows, $default_sort['how'] ) );
		$collation = 'yes' === gp_array_get( $filters, 'case_sensitive' ) ? 'BINARY' : '';

		$where = array();
		if ( gp_array_get( $filters, 'term' ) ) {
			$like = "LIKE $collation '%" . ( esc_sql( $wpdb->esc_like( gp_array_get( $filters, 'term' ) ) ) ) . "%'";
			$where[] = '(' . implode( ' OR ', array_map( function( $x ) use ( $like ) { return "($x $like)"; }, array( 'o.singular', 't.translation_0', 'o.plural', 't.translation_1', 'o.context', 'o.references' ) ) ) . ')';
		}
		if ( gp_array_get( $filters, 'before_date_added' ) ) {
			$where[] = $wpdb->prepare( 't.date_added > %s', gp_array_get( $filters, 'before_date_added' ) );
		}
		if ( gp_array_get( $filters, 'translation_id' ) ) {
			$where[] = $wpdb->prepare( 't.id = %d', gp_array_get( $filters, 'translation_id' ) );
		}
		if ( gp_array_get( $filters, 'original_id' ) ) {
			$where[] = $wpdb->prepare( 'o.id = %d', gp_array_get( $filters, 'original_id' ) );
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
			$user = get_user_by( 'login', $filters['user_login'] );
			// do not return any entries if the user doesn't exist
			$where[] = $wpdb->prepare( 't.user_id = %d', ($user && $user->ID) ? $user->ID : -1 );
		}

		if ( ! GP::$permission->current_user_can( 'write', 'project', $project->id ) ) {
			$where[] = 'o.priority > -2';
		}

		$priorities = gp_array_get( $filters, 'priority' );
		if ( $priorities ) {
			$valid_priorities = array_keys( GP::$original->get_static( 'priorities' ) );
			$priorities = array_filter( gp_array_get( $filters, 'priority' ), function( $p ) use ( $valid_priorities ) {
				return in_array( $p, $valid_priorities, true );
			} );

			$priorities_where = array();
			foreach ( $priorities as $single_priority ) {
				$priorities_where[] = $wpdb->prepare( 'o.priority = %s', $single_priority );
			}

			if ( ! empty( $priorities_where ) ) {
				$priorities_where = '(' . implode( ' OR ', $priorities_where ) . ')';
				$where[] = $priorities_where;
			}
		};

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

		if ( ! empty( $statuses ) ) {
			$statuses_where = array();
			foreach( $statuses as $single_status ) {
				$statuses_where[] = $wpdb->prepare( 't.status = %s', $single_status );
			}
			$statuses_where = '(' . implode( ' OR ', $statuses_where ) . ')';
			$join_where[] = $statuses_where;
		}

		/**
		 * Filter the SQL WHERE clause to get available translations.
		 *
		 * @since 1.0.0
		 *
		 * @param array              $where           An array of where conditions.
		 * @param GP_Translation_Set $translation_set Current translation set.
		 */
		$where = apply_filters( 'gp_for_translation_where', $where, $translation_set );

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
			FROM $wpdb->gp_originals as o
			$join_type JOIN $wpdb->gp_translations AS t ON o.id = t.original_id AND t.translation_set_id = " . (int) $translation_set->id . " $join_where
			WHERE o.project_id = " . (int) $project->id . " AND o.status = '+active' $where ORDER BY $sql_sort $limit";
		$rows = $this->many_no_map( $sql_for_translations );
		$this->found_rows = $this->found_rows();
		$translations = array();
		foreach( (array)$rows as $row ) {
			$row->user = $row->user_last_modified = null;

			if ( $row->user_id && 'no-limit' !== $this->per_page ) {
				$row->user = get_userdata( $row->user_id );
			}

			if ( $row->user_id_last_modified && 'no-limit' !== $this->per_page ) {
				$row->user_last_modified = get_userdata( $row->user_id_last_modified );
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

	public function set_as_current() {
		$result = $this->update( array( 'status' => 'old' ),
		array( 'original_id' => $this->original_id, 'translation_set_id' => $this->translation_set_id, 'status' => 'current' ) )
		&& 	$this->update( array( 'status' => 'old' ),
		array( 'original_id' => $this->original_id, 'translation_set_id' => $this->translation_set_id, 'status' => 'waiting' ) )
		&& $this->update( array( 'status' => 'old' ),
		array( 'original_id' => $this->original_id, 'translation_set_id' => $this->translation_set_id, 'status' => 'fuzzy' ) )
		&& $this->save( array( 'status' => 'current', 'user_id_last_modified' => get_current_user_id() ) );

		return $result;
	}

	public function reject() {
		$this->set_status( 'rejected' );
	}

	public function set_status( $status ) {
		if ( 'current' == $status ) {
			$updated = $this->set_as_current();
		} else {
			$updated = $this->save( array( 'user_id_last_modified' => get_current_user_id(), 'status' => $status ) );
		}

		if ( $updated ) {
			gp_clean_translation_set_cache( $this->translation_set_id );
		}

		return $updated;
	}

	public function translations() {
		$translations = array();
		foreach( range( 0, $this->get_static( 'number_of_plural_translations' ) ) as $i ) {
			$translations[ $i ] = isset( $this->{"translation_$i"} ) ? $this->{"translation_$i"} : null;
		}
		return $translations;
	}

	public function last_modified( $translation_set ) {
		global $wpdb;

		$last_modified = wp_cache_get( $translation_set->id, 'translation_set_last_modified' );
		// Cached as "" if no translations.
		if ( "" === $last_modified ) {
			return false;
		} elseif ( false !== $last_modified ) {
			return $last_modified;
		}

		$last_modified = $wpdb->get_var( $wpdb->prepare( "SELECT date_modified FROM {$this->table} WHERE translation_set_id = %d AND status = %s ORDER BY date_modified DESC LIMIT 1", $translation_set->id, 'current' ) );
		wp_cache_set( $translation_set->id, (string) $last_modified, 'translation_set_last_modified' );
		return $last_modified;
	}

	// Triggers

	/**
	 * Executes after creating a translation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function after_create() {
		/**
		 * Fires after a translation was created.
		 *
		 * @since 1.0.0
		 *
		 * @param GP_Translation $translation Translation that was created.
		 */
		do_action( 'gp_translation_created', $this );

		return true;
	}

	/**
	 * Executes after saving a translation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function after_save() {
		/**
		 * Fires after a translation was saved.
		 *
		 * @since 1.0.0
		 *
		 * @param GP_Translation $translation Translation that was saved.
		 */
		do_action( 'gp_translation_saved', $this );

		return true;
	}

	/**
	 * Executes after deleting a translation.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function after_delete() {
		/**
		 * Fires after a translation was deleted.
		 *
		 * @since 2.0.0
		 *
		 * @param GP_Translation $translation Translation that was deleted.
		 */
		do_action( 'gp_translation_deleted', $this );

		return true;
	}
}

GP::$translation = new GP_Translation();
