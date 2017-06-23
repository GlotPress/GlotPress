<?php
/**
 * GlotPress Format GetText PO/MO class
 *
 * @since 1.0.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support GetText PO file format.
 *
 * @since 1.0.0
 */
class GP_Format_PO extends GP_Format {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $name = 'Portable Object Message Catalog (.po/.pot)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $extension = 'po';

	/**
	 * Alternate file extensions of the file format, used to autodetect formats and when importing them.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $alt_extensions = array( 'pot' );

	/**
	 * Which plural rules to use for this format.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public $plurals_format = 'gettext';

	/**
	 * An internal variable used to support the MO class extending this class.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $class = 'PO';

	/**
	 * Generates a string the contains the $entries to export in the PO file format.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Project         $project         The project the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Locale          $locale          The locale object the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Translation_Set $translation_set The locale object the strings are being
	 *                                            exported for. not used in this format but part
	 *                                            of the scaffold of the parent object.
	 * @param GP_Translation     $entries         The entries to export.
	 *
	 * @return string The exported PO string.
	 */
	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$po = new $this->class;

		// See https://www.gnu.org/software/gettext/manual/html_node/Header-Entry.html for header details.
		// TODO: add more meta data in the project: language team, report URL.
		$this->set_header( $po, 'PO-Revision-Date', GP::$translation->last_modified( $translation_set ) . '+0000' );
		$this->set_header( $po, 'MIME-Version', '1.0' );
		$this->set_header( $po, 'Content-Type', 'text/plain; charset=UTF-8' );
		$this->set_header( $po, 'Content-Transfer-Encoding', '8bit' );
		$this->set_header( $po, 'Plural-Forms', "nplurals=$locale->nplurals; plural=$locale->plural_expression;" );
		$this->set_header( $po, 'X-Generator', 'GlotPress/' . GP_VERSION );

		$language_code = $this->get_language_code( $locale );
		if ( false !== $language_code ) {
			$this->set_header( $po, 'Language', $language_code );
		}

		// Force export only current translations.
		$filters = array();
		$filters['status'] = 'current';

		foreach( $entries as $entry ) {
			$po->add_entry( $entry );
		}

		$current = $project;
		$project_tree = array();
		$project_tree[] = $current->name;

		while ( $current->parent_project_id > 0 ) {
			$current = GP::$project->get( $current->parent_project_id );
			$project_tree[] = $current->name;
		}

		$project_tree = array_reverse( $project_tree );

		$project_id_version = implode( ' - ', $project_tree );

		/**
		 * Filter the project name and version header before export.
		 *
		 * @since 2.1.0
		 *
		 * @param string $project_id_version The default project name/version to use in the header and
		 *                                   comments ( "Parent - Child - GrandChild - etc." by default).
		 * @param array  $project_tree       An array of the parent/child project tree, ordered from Parent
		 *                                   to child to grandchild to etc...
		 */
		$project_id_version = apply_filters( 'gp_pomo_export_project_id_version', $project_id_version, $project_tree );

		$this->set_header( $po, 'Project-Id-Version', $project_id_version );

		$this->add_comments_before_headers( $po, "Translation of {$project_id_version} in {$locale->english_name}\n" );
		$this->add_comments_before_headers( $po, "This file is distributed under the same license as the {$project_id_version} package.\n" );

		return $po->export();
	}

	public function read_translations_from_file( $file_name, $project = null ) {
		$po     = new $this->class;
		$result = $po->import_from_file( $file_name );

		return $result ? $po : $result;
	}

	/**
	 * Reads a set of original strings from an MO file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_name The name of the uploaded Android XML file.
	 *
	 * @return Translations|bool The extracted originals on success, false on failure.
	 */
	public function read_originals_from_file( $file_name ) {
		return $this->read_translations_from_file( $file_name );
	}

	/**
	 * Add a header to the selected format, overrideable by child classes.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Format $format The format object to set the header for.
	 * @param string    $header The header name to set.
	 * @param string    $text   The text to set the header to.
	 */
	protected function set_header( $format, $header, $text ) {
		$format->set_header( $header, $text );
	}

	/**
	 * Add a comment before the headers for the selected format, overrideable by child classes.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Format $format The format object to set the header for.
	 * @param string    $text   The text to add to the comment.
	 */
	protected function add_comments_before_headers( $format, $text ) {
		$format->comments_before_headers .= $text;
	}
}

/**
 * Format class used to support GetText MO file format.
 *
 * @since 1.0.0
 */
class GP_Format_MO extends GP_Format_PO {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $name = 'Machine Object Message Catalog (.mo)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $extension = 'mo';

	/**
	 * Alternate file extensions of the file format, used to autodetect formats and when importing them.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $alt_extensions = array();

	/**
	 * An internal variable used to support the MO class extending this class.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $class = 'MO';

	/**
	 * Override the comments function as PO files do not use it.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Format $format The format object to set the header for.
	 * @param string    $text   The text to add to the comment.
	 */
	protected function add_comments_before_headers( $format, $text ) {
		return;
	}
}

GP::$formats['po'] = new GP_Format_PO;
GP::$formats['mo'] = new GP_Format_MO;
