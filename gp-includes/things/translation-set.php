<?php
/**
 * Things: GP_Translation_Set class
 *
 * @package GlotPress
 * @subpackage Things
 * @since 1.0.0
 */

/**
 * Core class used to implement the translation sets.
 *
 * @since 1.0.0
 */
class GP_Translation_Set extends GP_Thing {

	var $table_basename = 'gp_translation_sets';
	var $field_names = array( 'id', 'name', 'slug', 'project_id', 'locale' );
	var $non_db_field_names = array( 'current_count', 'untranslated_count', 'waiting_count',  'fuzzy_count', 'percent_translated', 'wp_locale', 'last_modified' );
	var $int_fields = array( 'id', 'project_id' );
	var $non_updatable_attributes = array( 'id' );

	/**
	 * ID of the translation set.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Name of the translation set.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Slug of the translation set.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Project ID of the translation set.
	 *
	 * @var int
	 */
	public $project_id;

	/**
	 * Locale of the translation set.
	 *
	 * @var string
	 */
	public $locale;

	/**
	 * GP project of the translation set.
	 *
	 * @var GP_Project
	 */
	public $project;

	/**
	 * Number of waiting translations.
	 *
	 * @var int
	 */
	public $waiting_count;

	/**
	 * Number of fuzzy translations.
	 *
	 * @var int
	 */
	public $fuzzy_count;

	/**
	 * Number of untranslated originals.
	 *
	 * @var int
	 */
	public $untranslated_count;

	/**
	 * Number of current translations.
	 *
	 * @var int
	 */
	public $current_count;

	/**
	 * Number of translations with warnings.
	 *
	 * @var int
	 */
	public $warnings_count;

	/**
	 * Number of all originals.
	 *
	 * @var int
	 */
	public $all_count;

	/**
	 * Sets restriction rules for fields.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Validation_Rules $rules The validation rules instance.
	 */
	public function restrict_fields( $rules ) {
		$rules->name_should_not_be( 'empty' );
		$rules->slug_should_not_be( 'empty' );
		$rules->locale_should_not_be( 'empty' );
		$rules->project_id_should_not_be( 'empty' );
	}

	/**
	 * Normalizes an array with key-value pairs representing
	 * a GP_Translation_Set object.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments for a GP_Translation_Set object.
	 * @return array Normalized arguments for a GP_Translation_Set object.
	 */
	public function normalize_fields( $args ) {
		$args = (array) $args;

		if ( isset( $args['name'] ) && empty( $args['name'] ) ) {
			if ( isset( $args['locale'] ) && ! empty( $args['locale'] ) ) {
				$locale = GP_locales::by_slug( $args['locale'] );
				$args['name'] = $locale->english_name;
			}
		}

		if ( isset( $args['slug'] ) && ! $args['slug'] ) {
			$args['slug'] = 'default';
		}

		if ( ! empty( $args['slug'] ) ) {
			$args['slug'] = gp_sanitize_slug( $args['slug'] );
		}

		return $args;
	}

	/**
	 * Returns the English name of a locale.
	 *
	 * If the slug of the locale is not 'default' then the name of the
	 * current translation sets gets added as a suffix.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $separator Separator, in case the slug is not 'default'. Default: '&rarr;'.
	 * @return string The English name of a locale.
	 */
	public function name_with_locale( $separator = '&rarr;' ) {
		$locale = GP_Locales::by_slug( $this->locale );
		$parts = array( $locale->english_name );

		if ( 'default' !== $this->slug ) {
			$parts[] = $this->name;
		}

		return implode( '&nbsp;' . $separator . '&nbsp;', $parts );
	}

	public function by_project_id_slug_and_locale( $project_id, $slug, $locale_slug ) {
		$result = $this->one( "
		    SELECT * FROM $this->table
		    WHERE slug = %s AND project_id= %d AND locale = %s", $slug, $project_id, $locale_slug );

		if ( ! $result && 0 === $project_id ) {
			$result = $this->create( array( 'project_id' => $project_id, 'name' => GP_Locales::by_slug( $locale_slug )->english_name, 'slug' => $slug, 'locale' => $locale_slug ) );
		}

		return $result;
	}

	public function by_locale( $locale_slug ) {
		return $this->many( "
		    SELECT * FROM $this->table
		    WHERE locale = %s", $locale_slug );
	}

	public function existing_locales() {
		global $wpdb;

		return $wpdb->get_col( "SELECT DISTINCT(locale) FROM $this->table" );
	}

	public function existing_slugs() {
		global $wpdb;

		return $wpdb->get_col( "SELECT DISTINCT(slug) FROM $this->table" );
	}

	public function by_project_id( $project_id ) {
		return $this->many( "
		    SELECT * FROM $this->table
		    WHERE project_id = %d ORDER BY name ASC", $project_id );
	}

	/**
	 * Import translations from a Translations object.
	 *
	 * @param  Translations $translations   the translations to be imported to this translation-set.
	 * @param  string       $desired_status 'current', 'waiting' or 'fuzzy'.
	 * @return boolean or void
	 */
	public function import( $translations, $desired_status = 'current' ) {
		$this->set_memory_limit( '256M' );

		if ( ! isset( $this->project ) || ! $this->project ) {
			$this->project = GP::$project->get( $this->project_id );
		}

		// Fuzzy is also checked in the flags, but if all strings in an import are fuzzy, fuzzy status can be forced.
		if ( ! in_array( $desired_status, array( 'current', 'waiting', 'fuzzy' ), true ) ) {
			return false;
		}

		$locale = GP_Locales::by_slug( $this->locale );
		$user = wp_get_current_user();

		$existing_translations = array();

		$current_translations_list = GP::$translation->for_translation( $this->project, $this, 'no-limit', array( 'status' => 'current', 'translated' => 'yes' ) );
		$existing_translations['current'] = new Translations();
		foreach ( $current_translations_list as $entry ) {
			$existing_translations['current']->add_entry( $entry );
		}
		unset( $current_translations_list );

		$translations_added = 0;
		foreach ( $translations->entries as $entry ) {
			if ( empty( $entry->translations ) ) {
				continue;
			}

			$is_fuzzy = in_array( 'fuzzy', $entry->flags, true );

			/**
			 * Filter whether to import fuzzy translations.
			 *
			 * @since 1.0.0
			 *
			 * @param bool              $import_over  Import fuzzy translation. Default true.
			 * @param Translation_Entry $entry        Translation entry object to import.
			 * @param Translations      $translations Translations collection.
			 */
			if ( $is_fuzzy && ! apply_filters( 'gp_translation_set_import_fuzzy_translations', true, $entry, $translations ) ) {
				continue;
			}

			/**
			 * Filters the the status of imported translations of a translation set.
			 *
			 * @since 1.0.0
			 * @since 2.3.0 Added `$new_translation` and `$old_translation` parameters.
			 *
			 * @param string              $status          The status of imported translations.
			 * @param Translation_Entry   $new_translation Translation entry object to import.
			 * @param GP_Translation|null $old_translation The previous translation.
			 */
			$entry->status = apply_filters( 'gp_translation_set_import_status', $is_fuzzy ? 'fuzzy' : $desired_status, $entry, null );

			$entry->warnings = maybe_unserialize( GP::$translation_warnings->check( $entry->singular, $entry->plural, $entry->translations, $locale ) );
			if ( ! empty( $entry->warnings ) && 'current' === $entry->status ) {
				$entry->status = 'waiting';
			}

			// Lazy load other entries.
			if ( ! isset( $existing_translations[ $entry->status ] ) ) {
				$existing_translations_list = GP::$translation->for_translation( $this->project, $this, 'no-limit', array( 'status' => $entry->status, 'translated' => 'yes' ) );
				$existing_translations[ $entry->status ] = new Translations();
				foreach ( $existing_translations_list as $_entry ) {
					$existing_translations[ $entry->status ]->add_entry( $_entry );
				}
				unset( $existing_translations_list );
			}

			$create = false;
			$translated = $existing_translations[ $entry->status ]->translate_entry( $entry );
			if ( 'current' !== $entry->status && ! $translated ) {
				// Don't create an entry if it already exists as current.
				$translated = $existing_translations['current']->translate_entry( $entry );
			}

			if ( $translated ) {
				// We have the same string translated, so create a new one if they don't match.
				$entry->original_id = $translated->original_id;
				$translated_is_different = array_pad( $entry->translations, $locale->nplurals, null ) !== $translated->translations;

				/**
				 * Filter whether to import over an existing translation on a translation set.
				 *
				 * @since 1.0.0
				 *
				 * @param bool $import_over Import over an existing translation.
				 */
				$create = apply_filters( 'gp_translation_set_import_over_existing', $translated_is_different );
			} else {
				// we don't have the string translated, let's see if the original is there
				$original = GP::$original->by_project_id_and_entry( $this->project->id, $entry, '+active' );
				if ( $original ) {
					$entry->original_id = $original->id;
					$create = true;
				}
			}
			if ( $create ) {
				if ( $user ) {
					$entry->user_id = $user->ID;
				}

				$entry->translation_set_id = $this->id;

				$entry->status = apply_filters( 'gp_translation_set_import_status', $entry->status, $entry, $translated );
				// Check for errors.
				$translation = GP::$translation->create( $entry );
				if ( is_object( $translation ) ) {
					$translation->set_status( $entry->status );
					$translations_added += 1;
				}
			}
		}

		gp_clean_translation_set_cache( $this->id );

		/**
		 * Fires after translations have been imported to a translation set.
		 *
		 * @since 1.0.0
		 *
		 * @param int $translation_set The ID of the translation set the import was made into.
		 */
		do_action( 'gp_translations_imported', $this->id );

		return $translations_added;
	}

	/**
	 * Retrieves the number of waiting translations.
	 *
	 * @return int Number of waiting translations.
	 */
	public function waiting_count() {
		if ( ! isset( $this->waiting_count ) ) {
			$this->update_status_breakdown();
		}

		return $this->waiting_count;
	}

	/**
	 * Retrieves the number of untranslated originals.
	 *
	 * @return int Number of untranslated originals.
	 */
	public function untranslated_count() {
		if ( ! isset( $this->untranslated_count ) ) {
			$this->update_status_breakdown();
		}

		return $this->untranslated_count;
	}

	/**
	 * Retrieves the number of fuzzy translations.
	 *
	 * @return int Number of fuzzy translations.
	 */
	public function fuzzy_count() {
		if ( ! isset( $this->fuzzy_count ) ) {
			$this->update_status_breakdown();
		}

		return $this->fuzzy_count;
	}

	/**
	 * Retrieves the number of current translations.
	 *
	 * @return int Number of current translations.
	 */
	public function current_count() {
		if ( ! isset( $this->current_count ) ) {
			$this->update_status_breakdown();
		}

		return $this->current_count;
	}

	/**
	 * Retrieves the number of translations with warnings.
	 *
	 * @return int Number of translations with warnings.
	 */
	public function warnings_count() {
		if ( ! isset( $this->warnings_count ) ) {
			$this->update_status_breakdown();
		}

		return $this->warnings_count;
	}

	/**
	 * Retrieves the number of all originals.
	 *
	 * @return int Number of all originals.
	 */
	public function all_count() {
		if ( ! isset( $this->all_count ) ) {
			$this->update_status_breakdown();
		}

		return $this->all_count;
	}

	/**
	 * Populates the count properties.
	 */
	public function update_status_breakdown() {
		$counts = wp_cache_get( $this->id, 'translation_set_status_breakdown' );

		if ( ! is_array( $counts ) || ! isset( $counts[0]->total ) ) { // The format was changed in 2.1.
			global $wpdb;
			$counts = array();

			$status_counts = $wpdb->get_results( $wpdb->prepare( "
				SELECT
					t.status AS translation_status,
					COUNT(*) AS total,
					COUNT( CASE WHEN o.priority = '-2' THEN o.priority END ) AS `hidden`,
					COUNT( CASE WHEN o.priority <> '-2' THEN o.priority END ) AS `public`
				FROM {$wpdb->gp_translations} AS t
				INNER JOIN {$wpdb->gp_originals} AS o ON t.original_id = o.id
				WHERE
					t.translation_set_id = %d
					AND o.status = '+active'
				GROUP BY t.status
			", $this->id ) );

			if ( $status_counts ) {
				$counts = $status_counts;
			}

			$warnings_counts = $wpdb->get_row( $wpdb->prepare( "
				SELECT
					COUNT(*) AS total,
					COUNT( CASE WHEN o.priority = '-2' THEN o.priority END ) AS `hidden`,
					COUNT( CASE WHEN o.priority <> '-2' THEN o.priority END ) AS `public`
				FROM {$wpdb->gp_translations} AS t
				INNER JOIN {$wpdb->gp_originals} AS o ON t.original_id = o.id
				WHERE
					t.translation_set_id = %d AND
					o.status = '+active' AND
					( t.status = 'current' OR t.status = 'waiting' )
					AND warnings IS NOT NULL
			", $this->id ) );

			if ( $warnings_counts ) {
				$counts[] = (object) array(
					'translation_status' => 'warnings',
					'total'              => (int) $warnings_counts->total,
					'hidden'             => (int) $warnings_counts->hidden,
					'public'             => (int) $warnings_counts->public,
				);
			}
			wp_cache_set( $this->id, $counts, 'translation_set_status_breakdown' );
		}

		$all_count = GP::$original->count_by_project_id( $this->project_id, 'all' );
		$counts[] = (object) array(
			'translation_status' => 'all',
			'total'              => $all_count->total,
			'hidden'             => $all_count->hidden,
			'public'             => $all_count->public,
		);

		$statuses = GP::$translation->get_static( 'statuses' );
		$statuses[] = 'warnings';
		$statuses[] = 'all';
		foreach ( $statuses as $status ) {
			$this->{$status . '_count'} = 0;
		}

		$user_can_view_hidden = GP::$permission->current_user_can( 'write', 'project', $this->project_id );
		foreach ( $counts as $count ) {
			if ( in_array( $count->translation_status, $statuses, true ) ) {
				$this->{$count->translation_status . '_count'} = $user_can_view_hidden ? (int) $count->total : (int) $count->public;
			}
		}

		$this->untranslated_count = $this->all_count - $this->current_count; // @todo Improve this.
	}

	/**
	 * Copies translations from a translation set to the current one
	 *
	 * This function doesn't merge then, just copies unconditionally. If a translation already exists, it will be duplicated.
	 * When copying translations from another project, it will search to find the original first.
	 */
	public function copy_translations_from( $source_translation_set_id ) {
		global $wpdb;
		$current_date = $this->now_in_mysql_format();

		$source_set = GP::$translation_set->get( $source_translation_set_id );
		if ( $source_set->project_id != $this->project_id ) {
			$translations = GP::$translation->find_many_no_map( "translation_set_id = '{$source_set->id}'" );
			foreach ( $translations as $entry ) {
				$source_original = GP::$original->get( $entry->original_id );
				$original = GP::$original->by_project_id_and_entry( $this->project_id, $source_original );
				if ( $original ) {
					$entry->original_id = $original->id;
					$entry->translation_set_id = $this->id;
					GP::$translation->create( $entry );
				}
			}
		} else {
			return $this->query( "
				INSERT INTO $wpdb->gp_translations (
					original_id,       translation_set_id, translation_0, translation_1, translation_2, user_id, status, date_added,       date_modified, warnings
				)
				SELECT
					original_id, %s AS translation_set_id, translation_0, translation_1, translation_2, user_id, status, date_added, %s AS date_modified, warnings
				FROM $wpdb->gp_translations WHERE translation_set_id = %s", $this->id, $current_date, $source_translation_set_id
			);
		}
	}


	public function percent_translated() {
		$original_counts = GP::$original->count_by_project_id( $this->project_id, 'all' );

		if ( GP::$permission->current_user_can( 'write', 'project', $this->project_id ) ) {
			$original_count = $original_counts->total;
		} else {
			$original_count = $original_counts->public;
		}

		return $original_count ? floor( $this->current_count() / $original_count * 100 ) : 0;
	}

	public function last_modified() {
		return GP::$translation->last_modified( $this );
	}

	/**
	 * Deletes a translation set and all of it's translations and glossaries.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function delete() {
		GP::$translation->delete_many( array( 'translation_set_id' => $this->id ) );

		GP::$glossary->delete_many( array( 'translation_set_id', $this->id ) );

		return parent::delete();
	}
}
GP::$translation_set = new GP_Translation_Set();
