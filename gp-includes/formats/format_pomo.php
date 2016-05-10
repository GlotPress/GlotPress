<?php

class GP_Format_PO extends GP_Format {

	public $name = 'Portable Object Message Catalog (.po/.pot)';
	public $extension = 'po';
	public $alt_extensions = array( 'pot' );

	public $class = 'PO';

	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$po = new $this->class;

		// TODO: add more meta data in the project: language team, report URL
		$po->set_header( 'PO-Revision-Date', GP::$translation->last_modified( $translation_set ) . '+0000' );
		$po->set_header( 'MIME-Version', '1.0' );
		$po->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
		$po->set_header( 'Content-Transfer-Encoding', '8bit' );
		$po->set_header( 'Plural-Forms', "nplurals=$locale->nplurals; plural=$locale->plural_expression;" );
		$po->set_header( 'X-Generator', 'GlotPress/' . GP_VERSION );

		$language_code = $this->get_language_code( $locale );
		if ( false !== $language_code ) {
			$po->set_header( 'Language', $language_code );
		}

		// Force export only current translations.
		$filters = array();
		$filters['status'] = 'current';

		foreach( $entries as $entry ) {
			$po->add_entry( $entry );
		}
		$po->set_header( 'Project-Id-Version', $project->name );

		// TODO: include parent project's names in the comment
		$po->comments_before_headers .= "Translation of {$project->name} in {$locale->english_name}\n";
		$po->comments_before_headers .= "This file is distributed under the same license as the {$project->name} package.\n";

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
	private function get_language_code( $locale ) {
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

		if ( null !== $locale->country_code && 0 !== strcasecmp( $ret, $locale->country_code ) ) {
			$ret .= '_' . strtoupper( $locale->country_code );
		}

		return $ret;
	}
}

class GP_Format_MO extends GP_Format_PO {
	public $name = 'Machine Object Message Catalog (.mo)';
	public $extension = 'mo';
	public $alt_extensions = array();

	public $class = 'MO';
}

GP::$formats['po'] = new GP_Format_PO;
GP::$formats['mo'] = new GP_Format_MO;