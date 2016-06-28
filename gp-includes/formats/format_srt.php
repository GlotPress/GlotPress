<?php
/**
 * GP Format SRT class
 *
 * @package GlotPress
 * @since 2.1.0
 */

/**
 * Format class used to support SRT file format.
 *
 * @since 2.1.0
 */
class GP_Format_SRT extends GP_Format {

	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	public $name = 'SubRip Text File (.srt)';

	/**
	 * File Extension of file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	public $extension = 'srt';

	/**
	 * Generates a string the contains the $entries to export in the Properties file format.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Project         $project         The project the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Locale          $locale          The locale object the strings are being exported for. not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Translation_Set $translation_set The locale object the strings are being
	 *                                            exported for. not used in this format but part
	 *                                            of the scaffold of the parent object.
	 * @param GP_Translation     $entries         The entries to export.
	 *
	 * @return string
	 */
	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$result = '';

		// Sort the entries based on the context timestamp string.
		$sorted_entries = $entries;
		usort( $sorted_entries, array( 'GP_Format_SRT', 'sort_entries' ) );

		// Since SRT files use an increase, 1 based, indicator for each translation block, set it up now.
		$i = 1;

		// Loop through all the entries to export.
		foreach ( $sorted_entries as $entry ) {
			// Internally GP uses just \n, but SRT files always use the windows standard \r\n so convert them now.
			$translation = str_replace( "\n", "\r\n", $entry->translations[0] );
			$result .= "{$i}\r\n{$entry->context}\r\n{$translation}\r\n\r\n";

			$i++;
		}

		// Make sure we only have one \r\n at the end of the file.
		$result = trim( $result, "\r\n" );
		$result .= "\r\n";

		return $result;
	}

	/**
	 * Reads a set of original strings from an SRT file.
	 *
	 * @since 2.1.0
	 *
	 * @param string $file_name The filename of the uploaded SRT file.
	 *
	 * @return Translations|bool
	 */
	public function read_originals_from_file( $file_name ) {
		$entries = new Translations;
		$file = file_get_contents( $file_name );

		if ( false === $file ) {
			return false;
		}

		$context = $comment = $text = null;
		$this_entry = 0;

		// Note, SRT files use the Windows line ending standard.
		$lines = explode( "\r\n", $file );

		// Loop through each line, translation "blocks" are separated by blank lines.
		foreach ( $lines as $line ) {
			switch ( $this_entry ) {
				case 0:	// This is the first line of a new SRT block, just discard it.
					$this_entry++;

					break;
				case 1: // This is the timestamp line, save it to the context.
					$context = trim( $line, " \r\n" );
					$this_entry++;

					break;
				default: // This is the text.
					// A blank line means we're done with the block and we should save it.
					if ( '' === $line ) {
						$entry = new Translation_Entry();
						$entry->context = $context;
						$entry->singular = trim( $text, "\r\n" );

						$entry->translations = array();
						$entries->add_entry( $entry );

						$this_entry = 0;
						$text = '';
						$context = '';
					} else {
						$text .= trim( $line, " \r\n" ) . "\n";
						$this_entry++;
					}

					break;
			}
		}

		return $entries;
	}

	/**
	 * Reads a set of translations from an SRT file.
	 *
	 * @since 2.1.0
	 *
	 * @param string     $file_name The filename of the uploaded properties file.
	 * @param GP_Project $project   The project object to read the translations in to.
	 *
	 * @return Translations|bool
	 */
	public function read_translations_from_file( $file_name, $project = null ) {
		// Use the read_oroginals_from_file code to read the file.
		$translations = $this->read_originals_from_file( $file_name );

		// Get the originals for this project since they are not contained in the SRT file.
		$originals        = GP::$original->by_project_id( $project->id );
		$new_translations = new Translations;

		// Loop through each of the translations read from the SRT file.
		foreach ( $translations->entries as $key => $entry ) {
			// Since we used the read_originals_from_file, we need to swap the singular to the translation.
			$entry->translations = array( $entry->singular );
			$entry->singular = null;

			// Now find the original string based on the context timestamp line.
			foreach ( $originals as $original ) {
				if ( $original->context === $entry->context ) {
					$entry->singular = $original->singular;

					$new_translations->add_entry( $entry );

					break;
				}
			}
		}

		return $new_translations;
	}

	/**
	 * Splits a time info line (stored in the context property ) in two.
	 *
	 * @since 2.1.0
	 *
	 * @param string $context  The line to split.
	 *
	 * @return array Returns an array containing the start and stop timestamp in strings.
	 */
	private function split_context_time_info( $context ) {
		// Since the millisecond field uses the french separator, convert it to the north american standard.
		$context = str_replace( ',', '.', $context );
		// Since the timestamps are zero padded we can just replace the colons with blanks and get a valid number to compare against.
		$context = str_replace( ':', '', $context );

		// Split the start/end sections.
		$parts = explode( '-->', $context );

		$start = floatval( $parts[0] );

		// If for some reason we didn't parse an end time, set the end to be the same as the start.
		if ( count( $parts ) > 1 ) {
			$end = floatval( $parts[1] );
		} else {
			$end = $start;
		}

		return array( $start, $end );
	}

	/**
	 * The callback to sort the entries by, used above in print_exported_file().
	 *
	 * @since 2.1.0
	 *
	 * @param Translations $a The first translation to compare.
	 * @param Translations $b The second translation to compare.
	 *
	 * @return int
	 */
	private function sort_entries( $a, $b ) {
		/*
		 * Since SRT files are ordered by their time stamp, split the two context
		 * strings (which contain the start/stop timestamps).
		 *
		 * Convert them to floating point numbers so we can compare against the
		 * millisecond.
		 */
		list( $start_a, $end_a ) = $this->split_context_time_info( $a->context );
		list( $start_b, $end_b ) = $this->split_context_time_info( $b->context );

		// Check if we're starting at the same time.
		if ( $start_a === $start_b ) {
			return 0;
		}

		// Otherwise return if they're greater or less than each other.
		return ( $start_a > $start_b ) ? 1 : -1;
	}
}

GP::$formats['srt'] = new GP_Format_SRT;
