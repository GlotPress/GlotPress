<?php
class GP_Glossary extends GP_Thing {

	var $table_basename = 'gp_glossaries';
	var $field_names = array( 'id', 'translation_set_id', 'description' );
	var $int_fields = array( 'id', 'translation_set_id' );
	var $non_updatable_attributes = array( 'id' );

	public $id;
	public $translation_set_id;
	public $description;

	public function restrict_fields( $rules ) {
		$rules->translation_set_id_should_not_be('empty');
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

		if ( ! $glossary && $project->parent_project_id ) {
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

		return $glossary;
	}

	public function by_set_id( $set_id ) {
		return $this->one( "
		    SELECT * FROM $this->table
		    WHERE translation_set_id = %d LIMIT 1", $set_id );
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

}

GP::$glossary = new GP_Glossary();
