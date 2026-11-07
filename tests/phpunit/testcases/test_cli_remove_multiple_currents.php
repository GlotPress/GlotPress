<?php

// WP-CLI is not loaded in the test suite, so provide the parent class the command extends.
if ( ! class_exists( 'WP_CLI_Command' ) ) {
	abstract class WP_CLI_Command {}
}

// And a WP_CLI class that records the messages.
if ( ! class_exists( 'WP_CLI' ) ) {
	class WP_CLI {
		public static $messages = array();

		public static function log( $message ) {
			self::$messages[] = array( 'log', $message );
		}

		public static function success( $message ) {
			self::$messages[] = array( 'success', $message );
		}

		public static function error( $message ) {
			self::$messages[] = array( 'error', $message );
		}
	}
}

require_once GP_PATH . GP_INC . 'cli/remove-multiple-currents.php';

class GP_Test_CLI_Remove_Multiple_Currents extends GP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		WP_CLI::$messages = array();
	}

	/**
	 * Creates an original with two current translations in a set.
	 *
	 * @param GP_Translation_Set $set The translation set.
	 * @return GP_Translation[] The older and the newer current translations.
	 */
	private function create_two_currents( $set ) {
		global $wpdb;

		$original = $this->factory->original->create( array( 'project_id' => $set->project_id ) );

		$translations = array();
		foreach ( array( '2020-01-01 00:00:00', '2021-01-01 00:00:00' ) as $date_modified ) {
			$translation = $this->factory->translation->create(
				array(
					'translation_set_id' => $set->id,
					'original_id'        => $original->id,
					'status'             => 'current',
				)
			);
			$wpdb->update( $wpdb->gp_translations, array( 'date_modified' => $date_modified ), array( 'id' => $translation->id ) );
			$translations[] = $translation;
		}

		return $translations;
	}

	public function test_sets_the_older_current_translations_as_old() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		list( $older, $newer ) = $this->create_two_currents( $set );

		( new GP_CLI_Remove_Multiple_Currents() )->__invoke( array(), array() );

		$this->assertSame( 'old', GP::$translation->get( $older->id )->status, 'Set as old, not deleted.' );
		$this->assertSame( 'current', GP::$translation->get( $newer->id )->status );
		$this->assertContains( array( 'log', "Set #{$set->id}, original #{$older->original_id}: kept translation #{$newer->id} and set #{$older->id} as old." ), WP_CLI::$messages );
		$this->assertContains( array( 'success', 'Multiple currents are cleaned up: 1 translation was set as old.' ), WP_CLI::$messages );
	}

	public function test_dry_run_changes_nothing() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		list( $older, $newer ) = $this->create_two_currents( $set );

		( new GP_CLI_Remove_Multiple_Currents() )->__invoke( array(), array( 'dry-run' => true ) );

		$this->assertSame( 'current', GP::$translation->get( $older->id )->status );
		$this->assertContains( array( 'log', "Set #{$set->id}, original #{$older->original_id}: would keep translation #{$newer->id} and set #{$older->id} as old." ), WP_CLI::$messages );
		$this->assertContains( array( 'success', '1 current translation would be set as old.' ), WP_CLI::$messages );
	}

	public function test_only_cleans_the_matching_translation_sets() {
		$set           = $this->factory->translation_set->create_with_project_and_locale();
		$other_locale  = $this->factory->translation_set->create( array( 'project_id' => $set->project_id, 'locale' => 'de', 'slug' => $set->slug ) );
		$other_slug    = $this->factory->translation_set->create( array( 'project_id' => $set->project_id, 'locale' => $set->locale, 'slug' => 'formal' ) );
		$other_project = $this->factory->translation_set->create( array( 'project_id' => $this->factory->project->create()->id, 'locale' => $set->locale, 'slug' => $set->slug ) );

		$cleaned   = $this->create_two_currents( $set );
		$untouched = array_merge(
			$this->create_two_currents( $other_locale ),
			$this->create_two_currents( $other_slug ),
			$this->create_two_currents( $other_project )
		);

		$project = GP::$project->get( $set->project_id );
		( new GP_CLI_Remove_Multiple_Currents() )->__invoke(
			array(),
			array(
				'project' => $project->path,
				'locale'  => $set->locale,
				'set'     => $set->slug,
			)
		);

		$this->assertSame( 'old', GP::$translation->get( $cleaned[0]->id )->status );
		foreach ( $untouched as $translation ) {
			$this->assertSame( 'current', GP::$translation->get( $translation->id )->status );
		}
	}

	public function test_unknown_project() {
		( new GP_CLI_Remove_Multiple_Currents() )->__invoke( array(), array( 'project' => 'does/not/exist' ) );

		$this->assertSame( array( array( 'error', 'Project not found!' ) ), WP_CLI::$messages );
	}
}
