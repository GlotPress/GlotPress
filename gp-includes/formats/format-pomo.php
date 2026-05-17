<?php
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

class GP_Format_PO extends GP_Format {

	public $name           = 'Portable Object Message Catalog (.po/.pot)';
	public $extension      = 'po';
	public $alt_extensions = array( 'pot' );

	public $class = 'PO';

	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$po = new $this->class();

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
		$filters           = array();
		$filters['status'] = 'current';

		foreach ( $entries as $entry ) {
			// Convert NULL to empty string to prevent PHP 8 passing NULL errors.
			$entry->translations = array_map(
				function ( $translation ) {
					return $translation ? $translation : '';
				},
				$entry->translations
			);
			// Skip entries where the translation is missing placeholders from the original.
			if ( ! gp_entry_has_all_placeholders( $entry ) ) {
				continue;
			}
			$po->add_entry( $entry );
		}

		$current        = $project;
		$project_tree   = array();
		$project_tree[] = $current->name;

		while ( $current->parent_project_id > 0 ) {
			$current        = GP::$project->get( $current->parent_project_id );
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
		$po     = new $this->class();
		$result = $po->import_from_file( $file_name );

		return $result ? $po : $result;
	}

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
 * Checks whether a translation entry contains all placeholders present in the original.
 *
 * Uses the same placeholder regex as GP_Builtin_Translation_Warnings::warning_placeholders()
 * so behaviour is consistent between the warnings system and export.
 *
 * Examples of placeholders that are checked: %s, %d, %1$s, %2$d, %.2f
 *
 * Usage:
 *   if ( ! gp_entry_has_all_placeholders( $entry ) ) {
 *       // skip or reject this entry
 *   }
 *
 * @since 4.0.0
 *
 * @param Translation_Entry $entry The translation entry to check.
 * @return bool True if all placeholders from the original are present in every
 *              translation form. False if any are missing.
 */
 // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
function gp_entry_has_all_placeholders( Translation_Entry $entry ): bool {
	$original = $entry->singular ?? '';

	if ( empty( $original ) || empty( $entry->translations ) ) {
		return true;
	}

	/**
	 * Use the same filter as warning_placeholders() in warnings.php so that
	 * any customisation of the regex applies consistently to both warnings and export.
	 *
	 * This filter is documented in gp-includes/warnings.php.
	 */
	$placeholders_re = apply_filters( 'gp_warning_placeholders_re', '(?<!%)%(\d+\$(?:\d+)?)?(\.\d+)?[bcdefgosuxEFGX%l@]' );

	// Count each placeholder in the original string.
	$original_counts = array();
	preg_match_all( "/$placeholders_re/", $original, $matches );
	foreach ( $matches[0] as $match ) {
		$original_counts[ $match ] = ( $original_counts[ $match ] ?? 0 ) + 1;
	}

	// No placeholders in the original — nothing to check.
	if ( empty( $original_counts ) ) {
		return true;
	}

	// Every translation form (singular + any plural forms) must contain all placeholders.
	foreach ( $entry->translations as $translation ) {
		if ( empty( $translation ) ) {
			continue;
		}

		$translation_counts = array();
		preg_match_all( "/$placeholders_re/", $translation, $matches );
		foreach ( $matches[0] as $match ) {
			$translation_counts[ $match ] = ( $translation_counts[ $match ] ?? 0 ) + 1;
		}

		foreach ( $original_counts as $placeholder => $original_count ) {
			$translation_count = $translation_counts[ $placeholder ] ?? 0;
			if ( $original_count > $translation_count ) {
				return false;
			}
		}
	}

	return true;
}



class GP_Format_MO extends GP_Format_PO {
	public $name           = 'Machine Object Message Catalog (.mo)';
	public $extension      = 'mo';
	public $alt_extensions = array();

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



GP::$formats['po'] = new GP_Format_PO();
GP::$formats['mo'] = new GP_Format_MO();
