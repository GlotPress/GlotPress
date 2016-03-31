<?php

class GP_Import extends GP_UnitTestCase {

	/**
	 * @ticket gh-377
	 */
	private function _verify_multiple_imports( $originals, $runs ) {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		if ( isset( $originals['singular'] ) ) {
			$originals = array( $originals );
		}

		foreach ( $originals as $original ) {
			$o = $this->factory->original->create( array_merge( array(
				'project_id' => $set->project_id,
				'status'     => '+active',
			), $original ) );
		}

		$this->assertEquals( count( $originals ), $set->all_count() );
		$this->assertEquals( 0, $set->current_count() );
		$this->assertEquals( count( $originals ), $set->untranslated_count() );

		$status_sequence = '';
		foreach ( $runs as $run ) {
			$status_sequence .= $run['status'] . '|';
			$set->import( $run['translations'], $run['status'] );

			wp_cache_flush();
			$set->update_status_breakdown();

			foreach ( $run['counts'] as $function => $count ) {
				$this->assertEquals( $count, $set->$function(), $status_sequence . $function . '()' );
			}
		}
	}

	function test_multiple_imports_singular() {
		$original = array(
			'singular'   => 'Good morning',
		);

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular' => $original['singular'],
			'translations' => array( 'Guten Morgen' ),
		)));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 1,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 1,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 1,
					'waiting_count' => 1,
				),
			),
		));
	}

	function test_multiple_imports_plural() {
		$original = array(
			'singular'   => '%d apple',
			'plural'   => '%d apples',
		);

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular' => $original['singular'],
			'plural' => $original['plural'],
			'translations' => array( '%d Apfel', '%d Äpfel' ),
		)));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 1,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 1,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 1,
					'waiting_count' => 1,
				),
			),
		));
	}


	function test_multiple_imports_multiple_singulars() {
		$originals = array(
			array(
				'singular'   => 'Good morning',
			),
			array(
				'singular'   => 'Good evening',
			),
		);

		$translations1 = new Translations();
		$translations1->add_entry( new Translation_Entry( array(
			'singular' => $originals[0]['singular'],
			'translations' => array( 'Guten Morgen' ),
		)));

		$translations2 = new Translations();
		$translations2->add_entry( new Translation_Entry( array(
			'singular' => $originals[1]['singular'],
			'translations' => array( 'Guten Abend' ),
		)));

		$this->_verify_multiple_imports( $originals, array(
			array(
				'status' => 'current',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 1,
					'untranslated_count' => 1,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations2,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 2,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $originals, array(
			array(
				'status' => 'current',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 1,
					'untranslated_count' => 1,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations2,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 1,
					'untranslated_count' => 1,
					'waiting_count' => 1,
				),
			),
		));

		$this->_verify_multiple_imports( $originals, array(
			array(
				'status' => 'waiting',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 0,
					'untranslated_count' => 2,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations2,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 0,
					'untranslated_count' => 2,
					'waiting_count' => 2,
				),
			),
		));

		$this->_verify_multiple_imports( $originals, array(
			array(
				'status' => 'waiting',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 0,
					'untranslated_count' => 2,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 1,
					'untranslated_count' => 1,
					'waiting_count' => 0,
				),
			),
		));
	}

}
