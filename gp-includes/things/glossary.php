<?php
/**
 * Things: GP_Glossary class
 *
 * @package GlotPress
 * @subpackage Things
 * @since 1.0.0
 */

/**
 * Core class used to implement the glossaries.
 *
 * @since 1.0.0
 */
class GP_Glossary extends GP_Thing {

	var $table_basename = 'gp_glossaries';
	var $field_names = array( 'id', 'translation_set_id', 'description' );
	var $int_fields = array( 'id', 'translation_set_id' );
	var $non_updatable_attributes = array( 'id' );

	public $id;
	public $translation_set_id;
	public $description;

	/**
	 * Caches the array of Glossary_Entry objects.
	 *
	 * @since 2.3.0
	 * @var entries
	 */
	private $entries = array();

	/**
	 * Sets restriction rules for fields.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Validation_Rules $rules The validation rules instance.
	 */
	public function restrict_fields( $rules ) {
		$rules->translation_set_id_should_not_be( 'empty' );
	}

	/**
	 * Get the path to the glossary.
	 *
	 * @return string
	 */
	public function path() {
		$translation_set = GP::$translation_set->get( $this->translation_set_id );
		$project         = GP::$project->get( $translation_set->project_id );

		return gp_url_join( gp_url_project_locale( $project->path, $translation_set->locale, $translation_set->slug ), 'glossary' );
	}

	/**
	 * Get the glossary by set/project.
	 * If there's no glossary for this specific project, get the nearest parent glossary
	 *
	 * @param GP_Project $project
	 * @param GP_Translation_Set $translation_set
	 *
	 * @return GP_Glossary|bool
	 */
	public function by_set_or_parent_project( $translation_set, $project ) {
		$glossary = $this->by_set_id( $translation_set->id );

		if ( ! $glossary ) {
			if ( 0 === $project->id ) {
				// Auto-create the Locale Glossary.
				$glossary = $this->create( array( 'translation_set_id' => $translation_set->id ) );
			} elseif ( $project->parent_project_id ) {
				$locale = $translation_set->locale;
				$slug   = $translation_set->slug;

				while ( ! $glossary && $project->parent_project_id  ) {
					$project         = GP::$project->get( $project->parent_project_id );
					$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $slug, $locale );

					if ( $translation_set ) {
						$glossary = $this->by_set_id( $translation_set->id );
					}
				}
			}
		}

		return $glossary;
	}

	public function by_set_id( $set_id ) {
		return $this->one( "
		    SELECT * FROM $this->table
		    WHERE translation_set_id = %d LIMIT 1", $set_id );

	}

	/**
	 * Merges entries of a glossary with another one.
	 *
	 * @since 2.3.0
	 *
	 * @param GP_Glossary $merge The Glossary to merge into the current one.
	 * @return array Array of Glossary_Entry.
	 */
	public function merge_with_glossary( GP_Glossary $merge ) {
		$entry_map = array();
		foreach ( $this->get_entries() as $i => $entry ) {
			$entry_map[ $entry->key() ] = $i;
		}

		foreach ( $merge->get_entries() as $entry ) {
			if ( ! isset( $entry_map[ $entry->key() ] ) ) {
				$this->entries[] = $entry;
			}
		}

		return $this->entries;
	}

	/**
	 * Retrieves entries and cache them.
	 *
	 * @since 2.3.0
	 *
	 * @return array Array of Glossary_Entry.
	 */
	public function get_entries() {
		if ( ! $this->id ) {
			return false;
		}

		if ( ! empty( $this->entries ) ) {
			return $this->entries;
		}

		return $this->entries = GP::$glossary_entry->by_glossary_id( $this->id );
	}


	/**
	 * Copies glossary items from a glossary to the current one
	 * This function does not merge then, just copies unconditionally. If a translation already exists, it will be duplicated.
	 *
	 * @param int $source_glossary_id
	 *
	 * @return mixed
	 */
	public function copy_glossary_items_from( $source_glossary_id ) {
		global $wpdb;

		$current_date = $this->now_in_mysql_format();

		return $this->query("
			INSERT INTO $wpdb->gp_glossary_items (
				id, term, type, examples, comment, suggested_translation, last_update
			)
			SELECT
				%s AS id, term, type, examples, comment, suggested_translation, %s AS last_update
			FROM $wpdb->gp_glossary_items WHERE id = %s", $this->id, $current_date, $source_glossary_id
		);
	}

	/**
	 * Deletes a glossary and all of it's entries.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function delete() {
		GP::$glossary_entry->delete_many( array( 'glossary_id', $this->id ) );

		return parent::delete();
	}

	/**
	 * Get the virtual Locale Glossary project
	 *
	 * @since 2.3.0
	 *
	 * @return GP_Project The project
	 */
	public function get_locale_glossary_project() {
		/**
		 * Filters the prefix for the locale glossary path.
		 *
		 * @since 2.3.1
		 *
		 * @param string $$locale_glossary_path_prefix Prefix for the locale glossary path.
		 */
		$locale_glossary_path_prefix = apply_filters( 'gp_locale_glossary_path_prefix', '/languages' );
		return new GP::$project( array(
			'id'   => 0,
			'name' => 'Locale Glossary',
			'slug' => 0,
			'path' => "/$locale_glossary_path_prefix",
		) );
	}
}

GP::$glossary = new GP_Glossary();
