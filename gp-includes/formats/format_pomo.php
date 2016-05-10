<?php

class GP_Format_PO extends GP_Format {

	public $name = 'Portable Object Message Catalog (.po/.pot)';
	public $extension = 'po';
	public $alt_extensions = array( 'pot' );

	public $class = 'PO';

	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$po = new $this->class;

		// TODO: add more meta data in the project: language team, report URL
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
		$this->set_header( $po, 'Project-Id-Version', $project->name );

		// TODO: include parent project's names in the comment
		$this->add_comments_before_headers( $po, "Translation of {$project->name} in {$locale->english_name}\n" );
		$this->add_comments_before_headers( $po, "This file is distributed under the same license as the {$project->name} package.\n" );

		return $po->export();
	}

	public function read_translations_from_file( $file_name, $project = null ) {
		$po     = new $this->class;
		$result = $po->import_from_file( $file_name );

		return $result ? $po : $result;
	}

	public function read_originals_from_file( $file_name ) {
		return $this->read_translations_from_file( $file_name );
	}

	/**
	 * Create a string that represents the value for the "Language:" header for a po file.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Locale $locale The locale object.
	 *
	 * @return string|false Returns false if the locale object does not have any iso_639 language code, otherwise returns the shortest possible language code string.
	 */
	protected function get_language_code( $locale ) {
		$ret = '';

		if ( $locale->lang_code_iso_639_1 ) {
			$ret = $locale->lang_code_iso_639_1;
		} elseif ( $locale->lang_code_iso_639_2 ) {
			$ret = $locale->lang_code_iso_639_2;
		} elseif ( $locale->lang_code_iso_639_3 ) {
			$ret = $locale->lang_code_iso_639_3;
		}

		if ( '' === $ret ) {
			return false;
		}

		$ret = strtolower( $ret );

		if ( null !== $locale->country_code && 0 !== strcasecmp( $ret, $locale->country_code ) ) {
			$ret .= '_' . strtoupper( $locale->country_code );
		}

		return $ret;
	}

	/**
	 * Add a header to the selected format, overrideable by child classes.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Format $format The format object to set the header for.
	 * @param string    $header The header name to set.
	 * @param string    $text   The text to set the header to.
	 *
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
	 *
	 */
	protected function add_comments_before_headers( $format, $text ) {
		$format->comments_before_headers .= $text;
	}
}

class GP_Format_MO extends GP_Format_PO {
	public $name = 'Machine Object Message Catalog (.mo)';
	public $extension = 'mo';
	public $alt_extensions = array();

	public $class = 'MO';

	/**
	 * Override the set header function as PO files do not use it.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Format $format The format object to set the header for.
	 * @param string    $header The header name to set.
	 * @param string    $text   The text to set the header to.
	 *
	 */
	protected function set_header( $format, $header, $text ) {
		return;
	}

	/**
	 * Override the comments function as PO files do not use it.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Format $format The format object to set the header for.
	 * @param string    $text   The text to add to the comment.
	 *
	 */
	protected function add_comments_before_headers( $format, $text ) {
		return;
	}
}

GP::$formats['po'] = new GP_Format_PO;
GP::$formats['mo'] = new GP_Format_MO;