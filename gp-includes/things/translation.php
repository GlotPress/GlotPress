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

	/**
	 * Number of translations per page.
	 *
	 * @var int $per_page
	 */
	public $per_page = 15;

	/**
	 * Name of the database table.
	 *
	 * @var string $table_basename
	 */
	public $table_basename = 'gp_translations';

	/**
	 * List of field names for a translation.
	 *
	 * @var array $field_names
	 */
	public $field_names = array(
		'id',
		'original_id',
		'translation_set_id',
		'translation_0',
		'translation_1',
		'translation_2',
		'translation_3',
		'translation_4',
		'translation_5',
		'user_id',
		'user_id_last_modified',
		'status',
		'date_added',
		'date_modified',
		'warnings',
	);

	/**
	 * List of field names which have an integer value.
	 *
	 * @var array $int_fields
	 */
	public $int_fields = array(
		'id',
		'original_id',
		'translation_set_id',
		'user_id',
		'user_id_last_modified',
	);

	/**
	 * List of field names which cannot be updated.
	 *
	 * @var array $non_updatable_attributes
	 */
	public $non_updatable_attributes = array( 'id' );

	/**
	 * ID of the translation.
	 *
	 * @var int $id
	 */
	public $id;

	/**
	 * ID of the original.
	 *
	 * @var int $original_id
	 */
	public $original_id;

	/**
	 * ID of the translation set.
	 *
	 * @var int $translation_set_id
	 */
	public $translation_set_id;

	/**
	 * Translation for a singular form.
	 *
	 * @var string $translation_1
	 */
	public $translation_0;

	/**
	 * Translation for a plural form.
	 *
	 * @var string $translation_1
	 */
	public $translation_1;

	/**
	 * Translation for a second plural form.
	 *
	 * @var string $translation_2
	 */
	public $translation_2;

	/**
	 * Translation for a third plural form.
	 *
	 * @var string $translation_3
	 */
	public $translation_3;

	/**
	 * Translation for a fourth plural form.
	 *
	 * @var string $translation_4
	 */
	public $translation_4;

	/**
	 * Translation for a fifth plural form.
	 *
	 * @var string $translation_5
	 */
	public $translation_5;

	/**
	 * ID of a user who submitted the translation.
	 *
	 * @var int $user_id
	 */
	public $user_id;

	/**
	 * ID of a user (validator) who last changed the status of the translation.
	 *
	 * @since 2.1.0
	 *
	 * @var int $user_id_last_modified
	 */
	public $user_id_last_modified;

	/**
	 * Status of the translation.
	 *
	 * @var string $status
	 */
	public $status;

	/**
	 * Date when the translation was added.
	 *
	 * @var string $date_added
	 */
	public $date_added;

	/**
	 * Date when the translation was modified.
	 *
	 * @var string $date_added
	 */
	public $date_modified;

	/**
	 * List of warnings when translation isn't correct.
	 *
	 * @var array $warnings
	 */
	public $warnings;

	/**
	 * Number of found results.
	 *
	 * @var int $found_rows
	 */
	public $found_rows;

	/**
	 * List of valid statuses.
	 *
	 * @var array $statuses
	 * @static
	 */
	public static $statuses = array( 'current', 'waiting', 'rejected', 'fuzzy', 'old' );

	/**
	 * Number of supported translations per original.
	 *
	 * @var int $number_of_plural_translations
	 * @static
	 */
	public static $number_of_plural_translations = 6;

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
			// Reduce range by one since we're starting at 0, see GH#516.
			foreach ( range( 0, $this->get_static( 'number_of_plural_translations' ) - 1 ) as $i ) {
				if ( isset( $args['translations'][ $i ] ) ) {
					$args["translation_$i"] = $args['translations'][ $i ];
				}
			}
			unset( $args['translations'] );
		}

		// Reduce range by one since we're starting at 0, see GH#516.
		foreach ( range( 0, $this->get_static( 'number_of_plural_translations' ) - 1 ) as $i ) {
			if ( isset( $args[ "translation_$i" ] ) ) {
				$args[ "translation_$i" ] = $args[ "translation_$i" ];
			}
		}

		if ( gp_array_get( $args, 'warnings' ) == array() ) {
			$args['warnings'] = null;
		}

		return $args;
	}

	public function prepare_fields_for_save( $args ) {
		$args = parent::prepare_fields_for_save( $args );

		/**
		 * Filters the translation fields before they are saved.
		 *
		 * @since 3.0.0
		 *
		 * @param array          $args Translation arguments.
		 * @param GP_Translation $this Translation class.
		 */
		$args = apply_filters( 'gp_translation_prepare_for_save', $args, $this );

		if ( is_array( gp_array_get( $args, 'warnings' ) ) ) {
			$args['warnings'] = serialize( $args['warnings'] );
		}

		return $args;
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

		$locale               = GP_Locales::by_slug( $translation_set->locale );
		$root_locale          = null;
		$root_translation_set = null;
		$has_root             = false;

		if ( null !== $locale->variant_root ) {
			$root_for_translation = array();

			$root_locale          = GP_Locales::by_slug( $locale->variant_root );
			$root_translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set->slug, $locale->variant_root );

			// Only set the root translation flag if we have a valid root translation set, otherwise there's no point in querying it later.
			if ( null !== $root_translation_set && false !== $root_translation_set ) {
				$has_root = true;
			}
		}

		$join_type = 'INNER';

		$sort_bys = wp_list_pluck( gp_get_sort_by_fields(), 'sql_sort_by' );

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

			$term_scope = gp_array_get( $filters, 'term_scope', 'scope_any' );

			switch ( $term_scope ) {
				case 'scope_originals':
					$scope_array = array( 'o.singular', 'o.plural' );
					break;
				case 'scope_translations':
					$scope_array = array( 't.translation_0', 't.translation_1' );
					break;
				case 'scope_context':
					$scope_array = array( 'o.context' );
					break;
				case 'scope_references':
					$scope_array = array( 'o.references' );
					break;
				case 'scope_both':
					$scope_array = array( 'o.singular', 't.translation_0', 'o.plural', 't.translation_1' );
					break;
				default:
					$scope_array = array( 'o.singular', 't.translation_0', 'o.plural', 't.translation_1', 'o.context', 'o.references' );
					break;
			}

			$mapped_scope_array = array_map(
				function( $x ) use ( $like ) {
					return "($x $like)";
				},
				$scope_array
			);

			$where[] = '(' . implode( ' OR ', $mapped_scope_array ) . ')';
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
				return in_array( intval( $p ), $valid_priorities, true );
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

		$join_on = array();
		$status = gp_array_get( $filters, 'status', 'current_or_waiting_or_fuzzy_or_untranslated' );
		$statuses = explode( '_or_', $status );
		if ( in_array( 'untranslated', $statuses ) ) {
			if ( $statuses == array( 'untranslated' ) ) {
				$where[] = 't.translation_0 IS NULL';
			}
			$join_type = 'LEFT';
			$join_on[] = 'status != "rejected"';
			$join_on[] = 'status != "old"';
			$statuses = array_filter( $statuses, function( $x ) {
				return 'untranslated' !== $x ;
			} );
		}

		$all_statuses = $this->get_static( 'statuses' );
		$statuses = array_filter( $statuses, function( $s ) use ( $all_statuses ) {
			return in_array( $s, $all_statuses );
		} );

		if ( ! empty( $statuses ) ) {
			$statuses_where = array();
			foreach( $statuses as $single_status ) {
				$statuses_where[] = $wpdb->prepare( 'status = %s', $single_status );
			}
			$statuses_where = '(' . implode( ' OR ', $statuses_where ) . ')';
			$join_on[] = $statuses_where;
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
			$where = 'AND ' . $where;
		}

		$where = 'o.project_id = ' . (int) $project->id . ' AND o.status = "+active" ' . $where;

		$join_on = implode( ' AND ', $join_on );
		if ( $join_on ) {
			$join_on = 'AND ' . $join_on;
		}

		$fields = array(
			't.*',
			'o.*',
			't.id as id',
			'o.id as original_id',
			't.status as translation_status',
			'o.status as original_status',
			't.date_added as translation_added',
			'o.date_added as original_added',
		);

		if ( $has_root ) {
			$fields[] = 'r.id as root_id';
			$fields[] = 'r.original_id as root_original_id';
			$fields[] = 'r.translation_set_id as root_translation_set_id';
			$fields[] = 'r.translation_0 as root_translation_0';
			$fields[] = 'r.translation_1 as root_translation_1';
			$fields[] = 'r.translation_2 as root_translation_2';
			$fields[] = 'r.translation_3 as root_translation_3';
			$fields[] = 'r.translation_4 as root_translation_4';
			$fields[] = 'r.translation_5 as root_translation_5';
			$fields[] = 'r.user_id as root_user_id';
			$fields[] = 'r.status as root_status';
			$fields[] = 'r.date_added as root_date_added';
			$fields[] = 'r.date_modified as root_date_modified';
			$fields[] = 'r.warnings as root_warnings';
			$fields[] = 'r.user_id_last_modified as root_user_id_last_modified';
		}

		$join      = "$join_type JOIN ( SELECT * FROM {$wpdb->gp_translations} WHERE translation_set_id = " . (int) $translation_set->id . " $join_on ) AS t ON o.id = t.original_id";
		$root_join = '';

		if ( $has_root ) {
			$root_join = "$join_type JOIN ( SELECT * FROM {$wpdb->gp_translations} WHERE translation_set_id = " . (int) $root_translation_set->id . " $join_on ) AS r ON o.id = r.original_id";
		}

		$orderby = sprintf( $sort_by, $sort_how );

		$limit = $this->sql_limit_for_paging( $page, $this->per_page );

		/**
		 * Filters the 'for_translation' query SQL clauses.
		 *
		 * @since 2.3.0
		 * @since 2.4.0 Removed $join_on and added $root_join clause.  Also added $root_translation_set.
		 *
		 * @param array              $pieces          {
		 *     Translation query SQL clauses.
		 *
		 *     @type array  $fields    Fields to select in the query.
		 *     @type string $join      JOIN clause of the query.
		 *     @type string $root_join JOIN clause for the root translation set if it exists, otherwise an empty string.
		 *     @type string $where     WHERE clause of the query.
		 *     @type string $orderby   Fields for ORDER BY clause.
		 *     @type string $limit     LIMIT clause of the query.
		 * }
		 * @param GP_Translation_Set      $translation_set      The translation set object being queried.
		 * @param array                   $filters              An array of search filters.
		 * @param array                   $sort                 An array of sort settings.
		 * @param GP_Translation_Set|null $root_translation_set The root translation set object if one exists, otherwise null.
		 */
		$clauses = apply_filters( 'gp_for_translation_clauses', compact( 'fields', 'join', 'root_join', 'where', 'orderby', 'limit' ), $translation_set, $filters, $sort, $root_translation_set );

		$fields = isset( $clauses['fields'] ) ? implode( ', ', $clauses['fields'] ) : '*';
		$join = isset( $clauses['join'] ) ? $clauses['join'] : '';
		$root_join = isset( $clauses['root_join'] ) ? $clauses['root_join'] : '';
		$where = isset( $clauses['where'] ) ? $clauses['where'] : '';
		$orderby = isset( $clauses['orderby'] ) ? 'ORDER BY ' . $clauses['orderby'] : '';
		$limit = isset( $clauses['limit'] ) ? $clauses['limit'] : '';

		$sql_for_translations = "
			SELECT SQL_CALC_FOUND_ROWS $fields
			FROM {$wpdb->gp_originals} as o
			$join
			$root_join
			WHERE $where $orderby $limit";

		$rows = $this->many_no_map( $sql_for_translations );

		/**
		 * Filters the rows returned from the for_translation query.
		 *
		 * @since 2.3.0
		 *
		 * @param array              $rows            Array of arrays returned by the query.
		 * @param GP_Translation_Set $translation_set The translation set object being queried.
		 */
		$rows = apply_filters( 'gp_for_translation_rows', $rows, $translation_set );

		$this->found_rows = $this->found_rows();
		$translations = array();

		foreach( (array)$rows as $row ) {
			if ( null === $row->id && $has_root ) {
				$row->id                    = $row->root_id;
				$row->original_id           = $row->root_original_id;
				$row->translation_set_id    = $row->root_translation_set_id;
				$row->translation_0         = $row->root_translation_0;
				$row->translation_1         = $row->root_translation_1;
				$row->translation_2         = $row->root_translation_2;
				$row->translation_3         = $row->root_translation_3;
				$row->translation_4         = $row->root_translation_4;
				$row->translation_5         = $row->root_translation_5;
				$row->user_id               = $row->root_user_id;
				$row->status                = $row->root_status;
				$row->date_added            = $row->root_date_added;
				$row->date_modified         = $row->root_date_modified;
				$row->warnings              = $row->root_warnings;
				$row->user_id_last_modified = $row->root_user_id_last_modified;
				$row->translation_status    = $row->root_status;
				$row->translation_added     = $row->root_date_added;
			}

			$row->user = $row->user_last_modified = null;

			if ( $row->user_id && 'no-limit' !== $this->per_page ) {
				$user = get_userdata( $row->user_id );
				if ( $user ) {
					$row->user = (object) array(
						'ID'            => $user->ID,
						'user_login'    => $user->user_login,
						'display_name'  => $user->display_name,
						'user_nicename' => $user->user_nicename,
					);
				}
			}

			if ( $row->user_id_last_modified && 'no-limit' !== $this->per_page ) {
				$user = get_userdata( $row->user_id_last_modified );
				if ( $user ) {
					$row->user_last_modified = (object) array(
						'ID'            => $user->ID,
						'user_login'    => $user->user_login,
						'display_name'  => $user->display_name,
						'user_nicename' => $user->user_nicename,
					);
				}
			}

			$row->translations = array();
			for( $i = 0; $i < $locale->nplurals; $i++ ) {
				$row->translations[] = $row->{"translation_".$i};
			}
			$row->references = preg_split('/\s+/', $row->references, -1, PREG_SPLIT_NO_EMPTY);
			$row->extracted_comments = $row->comment;
			$row->warnings = $row->warnings? maybe_unserialize( $row->warnings ) : null;
			unset($row->comment);

			// Reduce range by one since we're starting at 0, see GH#516.
			foreach ( range( 0, $this->get_static( 'number_of_plural_translations' ) - 1 ) as $i ) {
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

	/**
	 * Decides whether the status of a translation can be changed to a desired status.
	 *
	 * @since 2.3.0
	 *
	 * @param string $desired_status The desired status.
	 * @return bool Whether the status can be set.
	 */
	public function can_set_status( $desired_status ) {
		/**
		 * Filters the decision whether a translation can be set to a status.
		 *
		 * @since 2.3.0
		 *
		 * @param string|bool    $can_set_status Whether the user can set the desired status.
		 * @param GP_Translation $translation    The translation to decide this for.
		 * @param string         $desired_status The desired status.
		 */
		$can_set_status = apply_filters( 'gp_pre_can_set_translation_status', 'no-verdict', $this, $desired_status );
		if ( is_bool( $can_set_status ) ) {
			return $can_set_status;
		}

		if ( ! is_bool( $can_set_status ) && 'rejected' === $desired_status && get_current_user_id() === (int) $this->user_id ) {
			$can_set_status = true;
		}

		if ( ! is_bool( $can_set_status ) && ( 'current' === $desired_status || 'rejected' === $desired_status ) ) {
			if ( ! GP::$permission->current_user_can( 'approve', 'translation', $this->id, array( 'translation' => $this ) ) ) {
				$can_set_status = false;
			}
		}

		if ( ! is_bool( $can_set_status ) ) {
			$can_set_status = true;
		}

		/**
		 * Filters the decision whether a translation can be set to a status.
		 *
		 * @since 2.3.0
		 *
		 * @param bool           $can_set_status Whether the user can set the desired status.
		 * @param GP_Translation $translation    The translation to decide this for.
		 * @param string         $desired_status The desired status.
		 */
		$can_set_status = apply_filters( 'gp_can_set_translation_status', $can_set_status, $this, $desired_status );

		return $can_set_status;
	}

	/**
	 * Changes the status of a translation if possible.
	 *
	 * @since 2.3.0
	 *
	 * @param string $status The status to be set.
	 * @return bool Whether the setting of status was successful.
	 */
	public function set_status( $status ) {
		if ( ! $this->can_set_status( $status ) ) {
			return false;
		}

		if ( 'current' === $status ) {
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

		// Reduce range by one since we're starting at 0, see GH#516.
		foreach ( range( 0, $this->get_static( 'number_of_plural_translations' ) - 1 ) as $i ) {
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
	 * @since 2.4.0 Added the `$original_before` parameter.
	 *
	 * @param GP_Translation $translation_before Translation before the update.
	 * @return bool
	 */
	public function after_save( $translation_before ) {
		/**
		 * Fires after a translation was saved.
		 *
		 * @since 1.0.0
		 * @since 2.4.0 Added the `$original_before` parameter.
		 *
		 * @param GP_Translation $translation        Translation following the update.
		 * @param GP_Translation $translation_before Translation before the update.
		 */
		do_action( 'gp_translation_saved', $this, $translation_before );

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
