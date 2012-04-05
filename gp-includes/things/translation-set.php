<?php
class GP_Translation_Set extends GP_Thing {

	var $table_basename = 'translation_sets';
	var $field_names = array( 'id', 'name', 'slug', 'project_id', 'locale' );
	var $non_updatable_attributes = array( 'id' );

	function restrict_fields( $set ) {
		$set->name_should_not_be('empty');
		$set->slug_should_not_be('empty');
		$set->locale_should_not_be('empty');
		$set->project_id_should_not_be('empty');
	}
	
	function name_with_locale( $separator = '&rarr;') {
		$locale = GP_Locales::by_slug( $this->locale );
		$parts = array( $locale->english_name );
		if ( 'default' != $this->slug ) $parts[] = $this->name;
		return implode( '&nbsp;'.$separator.'&nbsp;', $parts );
	}
	
	function by_project_id_slug_and_locale( $project_id, $slug, $locale_slug ) {
		return $this->one( "
		    SELECT * FROM $this->table
		    WHERE slug = '%s' AND project_id= %d AND locale = %s", $slug, $project_id, $locale_slug );
	}
	
	function by_project_id( $project_id ) {
		return $this->many( "
		    SELECT * FROM $this->table
		    WHERE project_id = %d ORDER BY name ASC", $project_id );
	}
	
	function import( $translations ) {
		@ini_set('memory_limit', '256M');
		if ( !isset( $this->project ) || !$this->project ) $this->project = GP::$project->get( $this->project_id );
		$locale = GP_Locales::by_slug( $this->locale );
		
		$current_translations_list = GP::$translation->for_translation( $this->project, $this, 'no-limit', array('status' => 'current', 'translated' => 'yes') );
		$current_translations = new Translations();
		foreach( $current_translations_list as $entry ) {
			$current_translations->add_entry( $entry );
		}
		unset( $current_translations_list );
		$translations_added = 0;
		foreach( $translations->entries as $entry ) {
			if ( empty( $entry->translations ) ) continue;
			if ( in_array( 'fuzzy', $entry->flags ) ) continue;
			
			$create = false;
			
			if ( $translated = $current_translations->translate_entry( $entry ) ) {
				// we have the same string translated
				// create a new one if they don't match
				$entry->original_id = $translated->original_id;
				$create  = ( array_pad( $entry->translations, $locale->nplurals, null ) != $translated->translations );
			} else {
				// we don't have the string translated, let's see if the original is there
				$original = GP::$original->by_project_id_and_entry( $this->project->id, $entry, '+active' );
				if ( $original ) {
					$entry->original_id = $original->id;
					$create = true;
				}
			}
			if ( $create ) {
				$entry->translation_set_id = $this->id;
				$entry->status = 'current';
				// check for errors
				$translation = GP::$translation->create( $entry );
				$translation->set_status( 'current' );
				$translations_added += 1;
			}
		}
		wp_cache_delete( $this->id, 'translation_set_status_breakdown' );
		return $translations_added;
	}
	
	function waiting_count() {
		if ( !isset( $this->waiting_count ) ) $this->update_status_breakdown();
		return $this->waiting_count;
	}
	
	function untranslated_count() {
		if ( !isset( $this->untranslated_count ) ) $this->update_status_breakdown();
		return $this->untranslated_count;
	}
	
	function current_count() {
		if ( !isset( $this->current_count ) ) $this->update_status_breakdown();
		return $this->current_count;
	}

	function warnings_count() {
		if ( !isset( $this->warnings_count ) ) $this->update_status_breakdown();
		return $this->warnings_count;
	}

	function all_count() {
		if ( !isset( $this->all_count ) ) $this->all_count  = GP::$original->count_by_project_id( $this->project_id );
		return $this->all_count;
	}


	function update_status_breakdown() {
		$counts = wp_cache_get( $this->id, 'translation_set_status_breakdown' );
		if ( !is_array( $counts ) ) {
			/*
			 * TODO:
			 *  - calculate weighted coefficient by priority to know how much of the strings are translated
			 * 	- calculate untranslated
			 */
			$t = GP::$translation->table;
			$o = GP::$original->table;
			$counts = GP::$translation->many_no_map("
				SELECT t.status as translation_status, COUNT(*) as n
				FROM $t AS t INNER JOIN $o AS o ON t.original_id = o.id WHERE t.translation_set_id = %d AND o.status LIKE '+%%' GROUP BY t.status", $this->id);
			$warnings_count = GP::$translation->value_no_map("
				SELECT COUNT(*) FROM $t AS t INNER JOIN $o AS o ON t.original_id = o.id
				WHERE t.translation_set_id = %d AND o.status LIKE '+%%' AND (t.status = 'current' OR t.status = 'waiting') AND warnings IS NOT NULL", $this->id);
			$counts[] = (object)array( 'translation_status' => 'warnings', 'n' => $warnings_count );
			$counts[] = (object)array( 'translation_status' => 'all', 'n' => $this->all_count() );
			wp_cache_set( $this->id, $counts, 'translation_set_status_breakdown' );
		}
		$statuses = GP::$translation->get_static( 'statuses' );
		$statuses[] = 'warnings';
		$statuses[] = 'all';
		foreach( $statuses as $status ) {
			$this->{$status.'_count'} = 0;
		}
		$this->untranslated_count = 0;
		foreach( $counts as $count ) {
			if ( in_array( $count->translation_status, $statuses ) ) {
				$this->{$count->translation_status.'_count'} = $count->n;
			}
		}
		$this->untranslated_count = $this->all_count() - $this->current_count;
	}

	/**
	 * Copies translations from a translation set to the current one
	 * 
	 * This function doesn't merge then, just copies unconditionally. If a translation already exists, it will be duplicated.
	 */
	function copy_translations_from( $source_translation_set_id ) {
		global $gpdb;
		$current_date = $this->now_in_mysql_format();
		return $this->query("
			INSERT INTO $gpdb->translations (
				original_id,       translation_set_id, translation_0, translation_1, translation_2, user_id, status, date_added,       date_modified, warnings
			)
			SELECT
				original_id, %s AS translation_set_id, translation_0, translation_1, translation_2, user_id, status, date_added, %s AS date_modified, warnings
			FROM $gpdb->translations WHERE translation_set_id = %s", $this->id, $current_date, $source_translation_set_id
		); 
	}

	function percent_translated() {
		$original_count = GP::$original->count_by_project_id( $this->project_id );
		return sprintf( _x( '%d%%', 'language translation percent' ), $original_count ? $this->current_count() / $original_count * 100 : 0 );
	}
}
GP::$translation_set = new GP_Translation_Set();
