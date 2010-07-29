<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script extends GP_CLI {
	
	function run() {
		$sets = GP::$translation_set->all();
		foreach( $sets as $set ) {
			echo "Processing set#$set->id...\n";
			$translations = GP::$translation->find( array( 'translation_set_id' => $set->id, 'status' => 'current' ), 'original_id ASC' );
			$prev_original_id = null;
			foreach( $translations as $translation ) {
				if ( $translation->original_id == $prev_original_id ) {
					echo "Duplicate with original_id#$prev_original_id. Translation#$translation->id\n";
					$translation->delete();
				} 
				$prev_original_id = $translation->original_id;
			}
		}
	}
}

$gp_script = new GP_Script;
$gp_script->run();
