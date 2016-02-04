<?php
class GP_Original extends GP_Thing {

	var $table_basename = 'gp_originals';
	var $field_names = array( 'id', 'project_id', 'context', 'singular', 'plural', 'references', 'comment', 'status', 'priority', 'date_added' );
	var $int_fields = array( 'id', 'project_id', 'priority' );
	var $non_updatable_attributes = array( 'id', 'path' );
	
	public $id;
	public $project_id;
	public $context;
	public $singular;
	public $plural;
	public $references;
	public $comment;
	public $status;
	public $priority;
	public $date_added;	

	static $priorities = array( '-2' => 'hidden', '-1' => 'low', '0' => 'normal', '1' => 'high' );
	static $count_cache_group = 'active_originals_count_by_project_id';

	function restrict_fields( $original ) {
		$original->singular_should_not_be('empty');
		$original->status_should_not_be('empty');
		$original->project_id_should_be('positive_int');
		$original->priority_should_be('int');
		$original->priority_should_be('between', -2, 1);
	}

	function normalize_fields( $args ) {
		$args = (array)$args;
		foreach ( array('plural', 'context', 'references', 'comment') as $field ) {
			if ( isset( $args['parent_project_id'] ) ) {
				$args[$field] = $this->force_false_to_null( $args[$field] );
			}
		}

		if ( isset( $args['priority'] ) && !is_numeric( $args['priority'] ) ) {
			$args['priority'] = $this->priority_by_name( $args['priority'] );
			if ( is_null( $args['priority'] ) ) {
				unset( $args['priority'] );
			}
		}

		return $args;
	}

	function by_project_id( $project_id ) {
		return $this->many( "SELECT * FROM $this->table WHERE project_id= %d AND status = '+active'", $project_id );
	}

	function count_by_project_id( $project_id ) {
		if ( false !== ( $cached = wp_cache_get( $project_id, self::$count_cache_group ) ) ) {
			return $cached;
		}
		$count = $this->value( "SELECT COUNT(*) FROM $this->table WHERE project_id= %d AND status = '+active'", $project_id );
		wp_cache_set( $project_id, $count, self::$count_cache_group );
		return $count;
	}


	function by_project_id_and_entry( $project_id, $entry, $status = null ) {
		global $wpdb;

		$entry->plural  = isset( $entry->plural ) ? $entry->plural : null;
		$entry->context = isset( $entry->context ) ? $entry->context : null;

		$where = array();
		// now each condition has to contain a %s not to break the sequence
		$where[] = is_null( $entry->context ) ? '(context IS NULL OR %s IS NULL)' : 'context = BINARY %s';
		$where[] = 'singular = BINARY %s';
		$where[] = is_null( $entry->plural ) ? '(plural IS NULL OR %s IS NULL)' : 'plural = BINARY %s';
		$where[] = 'project_id = %d';

		if ( ! is_null( $status ) ) {
			$where[] = $wpdb->prepare( 'status = %s', $status );
		}

		$where = implode( ' AND ', $where );

		return $this->one( "SELECT * FROM $this->table WHERE $where", $entry->context, $entry->singular, $entry->plural, $project_id );
	}

	function import_for_project( $project, $translations ) {
		global $wpdb;

		$originals_added = $originals_existing = $originals_obsoleted = $originals_fuzzied = 0;

		$all_originals_for_project = $this->many_no_map( "SELECT * FROM $this->table WHERE project_id= %d", $project->id );
		$originals_by_key = array();
		foreach( $all_originals_for_project as $original ) {
			$entry = new Translation_Entry( array(
				'singular' => $original->singular,
				'plural'   => $original->plural,
				'context'  => $original->context
			) );
			$originals_by_key[ $entry->key() ] = $original;
		}

		$obsolete_originals = array_filter( $originals_by_key, function( $entry ) {
			return ( '-obsolete' == $entry->status );
		} );

		$possibly_added = $possibly_dropped = array();

		foreach( $translations->entries as $entry ) {
			$wpdb->queries = array();
			$data = array(
				'project_id' => $project->id,
				'context'    => $entry->context,
				'singular'   => $entry->singular,
				'plural'     => $entry->plural,
				'comment'    => $entry->extracted_comments,
				'references' => implode( ' ', $entry->references ),
				'status'     => '+active'
			);
			$data = apply_filters( 'gp_import_original_array', $data );

			// Original exists, let's update it.
			if ( isset( $originals_by_key[ $entry->key() ] ) ) {
				$original = $originals_by_key[ $entry->key() ];
				// But only if it's different, like a changed 'references', 'comment', or 'status' field.
				if ( GP::$original->is_different_from( $data, $original ) ) {
					$this->update( $data, array( 'id' => $original->id ) );
					$originals_existing++;
				}
			} else {
				// We can't find this in our originals. Let's keep it for later.
				$possibly_added[] = $entry;
			}
		}

		// Mark missing strings as possible removals.
		foreach ( $originals_by_key as $key => $value) {
			if ( $value->status != '-obsolete' && is_array( $translations->entries ) && ! array_key_exists( $key, $translations->entries ) ) {
				$possibly_dropped[ $key ] = $value;
			}
		}
		$comparison_array = array_unique( array_merge( array_keys( $possibly_dropped ), array_keys( $obsolete_originals ) ) );

		foreach ( $possibly_added as $entry ) {
			$data = array(
				'project_id' => $project->id,
				'context'    => $entry->context,
				'singular'   => $entry->singular,
				'plural'     => $entry->plural,
				'comment'    => $entry->extracted_comments,
				'references' => implode( ' ', $entry->references ),
				'status'     => '+active'
			);
			$data = apply_filters( 'gp_import_original_array', $data );

			// Search for match in the dropped strings and existing obsolete strings.
			$close_original = $this->closest_original( $entry->key(), $comparison_array );

			// We found a match - probably a slightly changed string.
			if ( $close_original ) {
				$original = $originals_by_key[ $close_original ];

				// We'll update the old original...
				$this->update( $data, array( 'id' => $original->id ) );

				// and set existing translations to fuzzy.
				$this->set_translations_for_original_to_fuzzy( $original->id );

				// No need to obsolete it now.
				unset( $possibly_dropped[ $close_original ] );

				$originals_fuzzied++;
				continue;
			} else { // Completely new string
				$created = GP::$original->create( $data );

				if ( apply_filters( 'gp_enable_add_translations_from_other_projects', true ) ) {
					$created->add_translations_from_other_projects();
				}

				$originals_added++;
			}
		}

		// Mark remaining possibly dropped strings as obsolete.
		foreach ( $possibly_dropped as $key => $value) {
			$this->update( array( 'status' => '-obsolete' ), array( 'id' => $value->id ) );
			$originals_obsoleted++;
		}

		// Clear cache when the amount of strings are changed.
		if ( $originals_added > 0 || $originals_existing > 0 || $originals_fuzzied > 0 || $originals_obsoleted > 0 ) {
			wp_cache_delete( $project->id, self::$count_cache_group );
		}

		do_action( 'gp_originals_imported', $project->id, $originals_added, $originals_existing, $originals_obsoleted, $originals_fuzzied );

		return array( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted );
	}

	function set_translations_for_original_to_fuzzy( $original_id ) {
		$translations = GP::$translation->find_many( "original_id = '$original_id' AND status = 'current'" );
		foreach ( $translations as $translation ) {
			$translation->set_status( 'fuzzy' );
		}
	}

	function is_different_from( $data, $original = null ) {
		if ( ! $original ) {
			$original = $this;
		}

		foreach ( $data as $field => $value ) {
			if ( $original->$field != $value ) {
				return true;
			}
		}
		return false;
	}

	function priority_by_name( $name ) {
		$by_name = array_flip( self::$priorities );
		return isset( $by_name[ $name ] )? $by_name[ $name ] : null;
	}

	function closest_original( $input, $other_strings ) {
		if ( empty( $other_strings ) ) {
			return null;
		}

		$input_length = gp_strlen( $input );
		$closest_similarity = 0;

		foreach ( $other_strings as $compared_string ) {
			$compared_string_length = gp_strlen( $compared_string );
			$max_length_diff = apply_filters( 'gp_original_import_max_length_diff', 0.5 );

			if ( abs( ( $input_length - $compared_string_length ) / $input_length ) > $max_length_diff ) {
				continue;
			}

			$similarity = gp_string_similarity( $input, $compared_string );

			if ( $similarity > $closest_similarity ) {
				$closest = $compared_string;
				$closest_similarity = $similarity;
			}
		}

		if ( ! isset( $closest ) ) {
			return null;
		}

		$min_score = apply_filters( 'gp_original_import_min_similarity_diff', 0.8 );
		$close_enough = ( $closest_similarity > $min_score );

		do_action( 'gp_post_string_similiary_test', $input, $closest, $closest_similarity, $close_enough );

		if ( $close_enough ) {
			return $closest;
		} else {
			return null;
		}
	}

	function get_matching_originals_in_other_projects() {
		$where = array();
		$where[] = 'singular = BINARY %s';
		$where[] = is_null( $this->plural ) ? '(plural IS NULL OR %s IS NULL)' : 'plural = BINARY %s';
		$where[] = is_null( $this->context ) ? '(context IS NULL OR %s IS NULL)' : 'context = BINARY %s';
		$where[] = 'project_id != %d';
		$where[] = "status = '+active'";
		$where = implode( ' AND ', $where );

		return GP::$original->many( "SELECT * FROM $this->table WHERE $where", $this->singular, $this->plural, $this->context, $this->project_id );
	}

	function add_translations_from_other_projects() {
		global $wpdb;

		$project_translations_sets = GP::$translation_set->many_no_map( "SELECT * FROM $wpdb->gp_translation_sets WHERE project_id = %d", $this->project_id );
		if ( empty( $project_translations_sets ) ) {
			return;
		}

		$matched_sets = array();

		$sql_project  = $wpdb->prepare( 'o.project_id != %d', $this->project_id );
		$sql_singular = $wpdb->prepare( 'o.singular = BINARY %s', $this->singular );
		$sql_plural = is_null( $this->plural ) ? 'o.plural IS NULL' : $wpdb->prepare( 'o.plural = BINARY %s', $this->plural );
		$sql_context = is_null( $this->context ) ? 'o.context IS NULL' : $wpdb->prepare( 'o.context = BINARY %s', $this->context );

		$sql = "SELECT t.*, s.locale, s.slug
			FROM {$this->table} o
				JOIN {$wpdb->gp_translations} t ON o.id = t.original_id
				JOIN {$wpdb->gp_translation_sets} s ON t.translation_set_id = s.id
			WHERE
				$sql_context AND $sql_singular AND $sql_plural
				AND o.status = '+active' AND $sql_project
				AND t.status = 'current'
			GROUP BY t.translation_0, t.translation_1, t.translation_2, t.translation_3, t.translation_4, t.translation_5, s.locale, s.slug
			ORDER BY t.date_modified DESC, t.id DESC";

		$other_project_translations = GP::$translation->many( $sql );

		foreach ( $other_project_translations as $t ) {
			$o_translation_set = array_filter( $project_translations_sets, function( $set ) use ( $t ) {
				return $set->locale == $t->locale && $set->slug == $t->slug;
			} );

			if ( empty( $o_translation_set ) ) {
				continue;
			}

			$o_translation_set = reset( $o_translation_set );
			if ( in_array( $o_translation_set->id, $matched_sets ) ) {
				// We already have a translation for this set.
				continue;
			}

			$matched_sets[] = $o_translation_set->id;

			$copy_status = apply_filters( 'gp_translations_from_other_projects_status', 'current' );
			$t->copy_into_set( $o_translation_set->id, $this->id, $copy_status );
		}
	}

	function after_create() {
		do_action( 'gp_original_created', $this );
		return true;
	}
}
GP::$original = new GP_Original();
