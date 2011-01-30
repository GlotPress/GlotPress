<?php

class GP_Format_PO {
	
	var $extension = 'po';
	var $class = 'PO';
	
	function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$po = new $this->class;
		// TODO: add more meta data in the project: language team, report URL
		// TODO: last updated for a translation set
		$po->set_header( 'PO-Revision-Date', gmdate( 'Y-m-d H:i:s+0000' ) );
		$po->set_header( 'MIME-Version', '1.0' );
		$po->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
		$po->set_header( 'Content-Transfer-Encoding', '8bit' );
		$po->set_header( 'Plural-Forms', "nplurals=$locale->nplurals; plural=$locale->plural_expression;" );
		$po->set_header( 'X-Generator', 'GlotPress/' . gp_get_option('version') );

		// force export only current translations
		$filters['status'] = 'current';

		foreach( $entries as $entry ) {
			$po->add_entry( $entry );
		}
		$po->set_header( 'Project-Id-Version', $project->name );

		// TODO: include parent project's names in the comment
		$po->comments_before_headers .= "Translation of {$project->name} in {$locale->english_name}\n";
		$po->comments_before_headers .= "This file is distributed under the same license as the {$project->name} package.\n";

		echo $po->export();
	}
	
	function read_translations_from_file( $file_name, $project = null ) {
		$po = new $this->class;
		$result = $po->import_from_file( $file_name );
		return $result? $po : $result;
	}
	
	function read_originals_from_file( $file_name ) {
		return $this->read_translations_from_file( $file_name );
	}

}

class GP_Format_MO extends GP_Format_PO {
	var $extension = 'mo';
	var $class = 'MO';
}

GP::$formats['po'] = new GP_Format_PO;
GP::$formats['mo'] = new GP_Format_MO;