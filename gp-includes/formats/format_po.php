<?php

class GP_Format_PO {
	
	var $extension = 'po';
	
	function print_exported_file( $project, $locale, $translation_set, $entries ) {

		// TODO: rename locale column to locale_slug and use freely $this->locale as the locale object
		$locale = GP_Locales::by_slug( $translation_set->locale );

		$po = new PO();
		// TODO: add more meta data in the project: language team, report URL
		// TODO: last updated for a translation set
		$po->set_header( 'PO-Revision-Date', gmdate('Y-m-d H:i:s+0000') );
		$po->set_header( 'MIME-Version', '1.0' );
		$po->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
		$po->set_header( 'Content-Transfer-Encoding', '8bit' );
		$po->set_header( 'Plural-Forms', "nplurals=$locale->nplurals; plural=$locale->plural_expression;" );
		$po->set_header( 'X-Generator', 'GlotPress/' . gp_get_option('version') );

		$filters['status'] = 'current';

		foreach( $entries as $entry ) {
			$po->add_entry( $entry );
		}
		$po->set_header( 'Project-Id-Version', $project->name );

		// TODO: include parent project's names in the comment
		echo "# Translation of {$project->name} in {$locale->english_name}\n";
		echo "# This file is distributed under the same license as the {$project->name} package.\n";

		echo $po->export();
	}	
}

GP::$formats['po'] = new GP_Format_PO;