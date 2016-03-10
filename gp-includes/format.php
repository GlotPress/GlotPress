<?php

/**
 * GlotPress Format base class. It is supposed to be inherited.
 */
abstract class GP_Format {

	public $name = '';
	public $extension = '';
	public $alt_extensions = array();
	public $filename_pattern = '%s-%s';

	public abstract function print_exported_file( $project, $locale, $translation_set, $entries );
	public abstract function read_originals_from_file( $file_name );

	/**
	 * Gets the list of supported file extensions.
	 *
	 * @since 1.1.0
	 *
	 * @return array Supported file extensions.
	 */
	public function get_file_extensions() {
		return array_merge( array( $this->extension ), $this->alt_extensions );
	}
	
	public function read_translations_from_file( $file_name, $project = null ) {
		if ( is_null( $project ) ) {
			return false;
		}

		$translations = $this->read_originals_from_file( $file_name );

		if ( ! $translations ) {
			return false;
		}

		$originals        = GP::$original->by_project_id( $project->id );
		$new_translations = new Translations;

		foreach( $translations->entries as $key => $entry ) {
			// we have been using read_originals_from_file to parse the file
			// so we need to swap singular and translation
			if ( $entry->context == $entry->singular ) {
				$entry->translations = array();
			} else {
				$entry->translations = array( $entry->singular );
			}

			$entry->singular = null;

			foreach( $originals as $original ) {
				if ( $original->context == $entry->context ) {
					$entry->singular = $original->singular;
					break;
				}
			}

			if ( ! $entry->singular ) {
				error_log( sprintf( __( 'Missing context %s in project #%d', 'glotpress' ), $entry->context, $project->id ) );
				continue;
			}

			$new_translations->add_entry( $entry );
		}

		return $new_translations;
	}

}