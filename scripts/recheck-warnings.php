<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Recheck_Warnings extends GP_Translation_Set_Script {
	
	function action_on_translation_set( $translation_set ) {
		$project = GP::$project->get( $translation_set->project_id );
		$locale = GP_Locales::by_slug( $translation_set->locale );
		foreach( GP::$translation->for_translation( $project, $translation_set, 'no-limit' ) as $entry ) {
			$warnings = GP::$translation_warnings->check( $entry->singular, $entry->plural, $entry->translations, $locale );
			if ( $warnings != $entry->warnings ) {
				$translation = new GP_Translation( array('id' => $entry->id) );
				echo sprintf( __("Updating warnings for %s"), $entry->id ) . "\n";
				$translation->update( array('warnings' => $warnings) );
			}
		}
	}
	
}
$gp_script_recheck_warnings = new GP_Script_Recheck_Warnings;
$gp_script_recheck_warnings->run();