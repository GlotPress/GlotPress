<?php

class GP_CLI_Remove_Multiple_Currents extends WP_CLI_Command {
	public function __invoke() {
		$sets = GP::$translation_set->all();
		foreach( $sets as $set ) {
			echo "Processing set#$set->id...\n";
			$translations = GP::$translation->find( array( 'translation_set_id' => $set->id, 'status' => 'current' ), 'original_id ASC' );
			$prev_original_id = null;
			foreach( $translations as $translation ) {
				if ( $translation->original_id == $prev_original_id ) {
					WP_CLI::line( sprintf(
						/* translators: 1: original ID, 2: new ID */
						__( "Duplicate with original_id#%s. Translation#%s\n", 'glotpress' )
						$prev_original_id,
						$translation->id
					) );
					$translation->delete();
				}
				$prev_original_id = $translation->original_id;
			}
		}
	}
}
