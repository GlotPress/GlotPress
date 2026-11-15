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

require_once GP_PATH . GP_INC . 'cli/recheck-errors.php';

class GP_Test_CLI_Recheck_Errors extends GP_UnitTestCase {

	/**
	 * An original and a translation that fail the extra_sprintf_placeholders error.
	 */
	const EXTRA_PLACEHOLDER = array( 'Regenerate <a href="%s">thumbnails</a>.', 'Regenerieren <a href="%2$s">Miniaturen</a>.' );

	/**
	 * An original and a translation that fail the control_characters error.
	 */
	const CONTROL_CHARACTERS = array( 'Command suggestions', "Befehlsvorschl\u{00E4}ge\x7f\x7f" );

	/**
	 * An original and a translation without errors.
	 */
	const VALID = array( 'Hello %s', 'Hallo %s' );

	public function set_up() {
		parent::set_up();
		WP_CLI::$messages = array();
	}

	/**
	 * Creates an original and a translation for it.
	 *
	 * @param GP_Translation_Set $set           The translation set.
	 * @param array              $strings       The singular original and its translation.
	 * @param string             $status        The status of the translation.
	 * @param string             $original_status The status of the original.
	 * @return GP_Translation The translation.
	 */
	private function create_translation( $set, $strings, $status = 'current', $original_status = '+active' ) {
		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project_id,
				'singular'   => $strings[0],
				'status'     => $original_status,
			)
		);

		return $this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'translation_0'      => $strings[1],
				'status'             => $status,
			)
		);
	}

	/**
	 * Runs the command.
	 *
	 * @param array $assoc_args The options.
	 * @param int   $batch_size Number of sets and translations per query.
	 */
	private function run_command( $assoc_args = array(), $batch_size = 1000 ) {
		$command  = new GP_CLI_Recheck_Errors();
		$property = new ReflectionProperty( $command, 'batch_size' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}
		$property->setValue( $command, $batch_size );

		$command->__invoke( array(), $assoc_args );
	}

	private function status_of( $translation ) {
		return GP::$translation->get( $translation->id )->status;
	}

	public function test_sets_current_translations_with_errors_to_fuzzy() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$placeholder = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );
		$control     = $this->create_translation( $set, self::CONTROL_CHARACTERS );
		$valid       = $this->create_translation( $set, self::VALID );

		$this->run_command();

		$this->assertSame( 'fuzzy', $this->status_of( $placeholder ) );
		$this->assertSame( 'fuzzy', $this->status_of( $control ) );
		$this->assertSame( 'current', $this->status_of( $valid ) );

		$project = GP::$project->get( $set->project_id );
		$this->assertContains( array( 'log', "{$project->path}, {$set->locale}/{$set->slug}: 2 translations" ), WP_CLI::$messages );
		$this->assertContains( array( 'success', 'Translation sets checked: 1. Current translations checked: 3. Set to fuzzy: 2.' ), WP_CLI::$messages );
	}

	public function test_dry_run_changes_nothing() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$placeholder = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );

		$this->run_command( array( 'dry-run' => true ) );

		$this->assertSame( 'current', $this->status_of( $placeholder ) );
		$this->assertContains( array( 'success', 'Dry run, nothing was changed. Translation sets checked: 1. Current translations checked: 1. Would be set to fuzzy: 1.' ), WP_CLI::$messages );
	}

	public function test_dry_run_false_changes_the_translations() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$placeholder = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );

		$this->run_command( array( 'dry-run' => 'false' ) );

		$this->assertSame( 'fuzzy', $this->status_of( $placeholder ) );
	}

	public function test_lists_the_affected_projects_and_translation_sets() {
		$set     = $this->factory->translation_set->create_with_project_and_locale();
		$other   = $this->factory->translation_set->create_with_project_and_locale();
		$project = GP::$project->get( $set->project_id );
		$this->create_translation( $set, self::EXTRA_PLACEHOLDER );
		$this->create_translation( $other, self::VALID );

		$this->run_command( array( 'dry-run' => true ) );

		$this->assertContains( array( 'log', 'Translations that fail a translation error, by project and translation set:' ), WP_CLI::$messages );
		$this->assertContains( array( 'log', "{$project->path}, {$set->locale}/{$set->slug}: 1 translation" ), WP_CLI::$messages );
		$this->assertCount( 2, array_filter( WP_CLI::$messages, fn( $message ) => 'log' === $message[0] ) );
	}

	public function test_checks_a_set_whose_project_was_deleted() {
		global $wpdb;

		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );
		$wpdb->delete( $wpdb->gp_projects, array( 'id' => $set->project_id ) );

		$this->run_command();

		$this->assertSame( 'fuzzy', $this->status_of( $translation ) );
		$this->assertContains( array( 'log', "#{$set->project_id}, {$set->locale}/{$set->slug}: 1 translation" ), WP_CLI::$messages );
	}

	public function test_verbose_lists_every_failing_translation_with_its_errors() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$placeholder = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );
		$this->create_translation( $set, self::VALID );

		$this->run_command(
			array(
				'dry-run' => true,
				'verbose' => true,
			)
		);

		$this->assertContains( array( 'log', "Translation #{$placeholder->id} of original #{$placeholder->original_id} in set #{$set->id}: extra_sprintf_placeholders" ), WP_CLI::$messages );
		$this->assertCount( 1, array_filter( WP_CLI::$messages, fn( $message ) => 0 === strpos( $message[1], 'Translation #' ) ) );
	}

	public function test_checks_every_registered_error() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->create_translation( $set, array( 'Save', 'Speichern' ) );

		GP::$translation_errors->add(
			'test_no_speichern',
			function ( $original, $translation ) {
				return 'Speichern' === $translation ? 'Speichern is not allowed.' : true;
			}
		);

		try {
			$this->run_command();
		} finally {
			GP::$translation_errors->remove( 'test_no_speichern' );
		}

		$this->assertSame( 'fuzzy', $this->status_of( $translation ) );
	}

	public function test_includes_translations_of_obsolete_originals() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->create_translation( $set, self::EXTRA_PLACEHOLDER, 'current', '-obsolete' );

		$this->run_command();

		$this->assertSame( 'fuzzy', $this->status_of( $translation ) );
	}

	public function test_leaves_other_statuses_unchanged() {
		$set   = $this->factory->translation_set->create_with_project_and_locale();
		$other = array();
		foreach ( array( 'waiting', 'fuzzy', 'old', 'rejected', 'changesrequested' ) as $status ) {
			$other[ $status ] = $this->create_translation( $set, self::EXTRA_PLACEHOLDER, $status );
		}

		$this->run_command();

		foreach ( $other as $status => $translation ) {
			$this->assertSame( $status, $this->status_of( $translation ) );
		}
		$this->assertContains( array( 'success', 'Translation sets checked: 1. Current translations checked: 0. Set to fuzzy: 0.' ), WP_CLI::$messages );
	}

	public function test_project_path_includes_subprojects_only() {
		$parent     = $this->factory->project->create( array( 'slug' => 'parent' ) );
		$child      = $this->factory->project->create(
			array(
				'slug'              => 'child',
				'parent_project_id' => $parent->id,
			)
		);
		$lookalike  = $this->factory->project->create( array( 'slug' => 'parent-other' ) );
		$locale     = $this->factory->locale->create();
		$set_parent = $this->factory->translation_set->create( array( 'project_id' => $parent->id, 'locale' => $locale->slug ) );
		$set_child  = $this->factory->translation_set->create( array( 'project_id' => $child->id, 'locale' => $locale->slug ) );
		$set_other  = $this->factory->translation_set->create( array( 'project_id' => $lookalike->id, 'locale' => $locale->slug ) );

		$in_parent = $this->create_translation( $set_parent, self::EXTRA_PLACEHOLDER );
		$in_child  = $this->create_translation( $set_child, self::EXTRA_PLACEHOLDER );
		$elsewhere = $this->create_translation( $set_other, self::EXTRA_PLACEHOLDER );

		$this->run_command( array( 'project-path' => 'parent' ) );

		$this->assertSame( 'parent/child', GP::$project->get( $child->id )->path );
		$this->assertSame( 'fuzzy', $this->status_of( $in_parent ) );
		$this->assertSame( 'fuzzy', $this->status_of( $in_child ) );
		$this->assertSame( 'current', $this->status_of( $elsewhere ) );
	}

	public function test_locale_works_without_a_project() {
		$locale  = $this->factory->locale->create();
		$other   = $this->factory->locale->create();
		$set_one = $this->factory->translation_set->create( array( 'project_id' => $this->factory->project->create()->id, 'locale' => $locale->slug ) );
		$set_two = $this->factory->translation_set->create( array( 'project_id' => $this->factory->project->create()->id, 'locale' => $locale->slug ) );
		$set_off = $this->factory->translation_set->create( array( 'project_id' => $set_one->project_id, 'locale' => $other->slug ) );

		$one       = $this->create_translation( $set_one, self::EXTRA_PLACEHOLDER );
		$two       = $this->create_translation( $set_two, self::EXTRA_PLACEHOLDER );
		$elsewhere = $this->create_translation( $set_off, self::EXTRA_PLACEHOLDER );

		$this->run_command( array( 'locale' => $locale->slug ) );

		$this->assertSame( 'fuzzy', $this->status_of( $one ) );
		$this->assertSame( 'fuzzy', $this->status_of( $two ) );
		$this->assertSame( 'current', $this->status_of( $elsewhere ) );
	}

	public function test_project_and_locale_together() {
		$set       = $this->factory->translation_set->create_with_project_and_locale();
		$other     = $this->factory->locale->create();
		$set_other = $this->factory->translation_set->create( array( 'project_id' => $set->project_id, 'locale' => $other->slug ) );

		$matching  = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );
		$elsewhere = $this->create_translation( $set_other, self::EXTRA_PLACEHOLDER );

		$project = GP::$project->get( $set->project_id );
		$this->run_command(
			array(
				'project-path' => $project->path,
				'locale'       => $set->locale,
			)
		);

		$this->assertSame( 'fuzzy', $this->status_of( $matching ) );
		$this->assertSame( 'current', $this->status_of( $elsewhere ) );
	}

	public function test_unknown_project_changes_nothing() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );

		$this->run_command( array( 'project-path' => 'does/not/exist' ) );

		$this->assertSame( array( array( 'error', 'Project not found: does/not/exist' ) ), WP_CLI::$messages );
		$this->assertSame( 'current', $this->status_of( $translation ) );
	}

	public function test_unknown_locale_changes_nothing() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );

		$this->run_command( array( 'locale' => 'xx-unknown' ) );

		$this->assertSame( array( array( 'error', 'Locale not found: xx-unknown' ) ), WP_CLI::$messages );
		$this->assertSame( 'current', $this->status_of( $translation ) );
	}

	public function test_pages_through_sets_and_translations_in_batches() {
		$locale       = $this->factory->locale->create();
		$translations = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$set = $this->factory->translation_set->create( array( 'project_id' => $this->factory->project->create()->id, 'locale' => $locale->slug ) );
			for ( $j = 0; $j < 3; $j++ ) {
				$translations[] = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );
				$translations[] = $this->create_translation( $set, self::VALID );
			}
		}

		$this->run_command( array( 'locale' => $locale->slug ), 2 );

		foreach ( $translations as $index => $translation ) {
			$this->assertSame( 0 === $index % 2 ? 'fuzzy' : 'current', $this->status_of( $translation ) );
		}
		$this->assertContains( array( 'success', 'Translation sets checked: 3. Current translations checked: 18. Set to fuzzy: 9.' ), WP_CLI::$messages );
	}

	public function test_skips_and_reports_a_translation_without_original() {
		global $wpdb;

		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );
		$wpdb->delete( $wpdb->gp_originals, array( 'id' => $translation->original_id ) );

		$this->run_command();

		$this->assertSame( 'current', $this->status_of( $translation ) );
		$this->assertContains( array( 'log', "Skipped: Translation #{$translation->id}: its original #{$translation->original_id} does not exist." ), WP_CLI::$messages );
		$this->assertContains( array( 'success', 'Translation sets checked: 1. Current translations checked: 0. Set to fuzzy: 0.' ), WP_CLI::$messages );
	}

	public function test_skips_and_reports_a_set_with_an_unregistered_locale() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );

		// The locale was registered when the set was created and is removed since.
		$locales = &GP_Locales::instance();
		unset( $locales->locales[ $set->locale ] );

		$this->run_command();

		$this->assertSame( 'current', $this->status_of( $translation ) );
		$this->assertContains( array( 'log', "Skipped: Translation set #{$set->id}: the locale {$set->locale} is not registered." ), WP_CLI::$messages );
		$this->assertContains( array( 'success', 'Translation sets checked: 0. Current translations checked: 0. Set to fuzzy: 0.' ), WP_CLI::$messages );
	}

	public function test_skips_a_translation_that_is_no_longer_current_when_it_is_changed() {
		global $wpdb;

		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );

		// Another process sets the translation as old after the batch was read.
		GP::$translation_errors->add(
			'test_superseded_meanwhile',
			function () use ( $wpdb, $translation ) {
				$wpdb->update( $wpdb->gp_translations, array( 'status' => 'old' ), array( 'id' => $translation->id ) );
				return true;
			}
		);

		try {
			$this->run_command();
		} finally {
			GP::$translation_errors->remove( 'test_superseded_meanwhile' );
		}

		$this->assertSame( 'old', $this->status_of( $translation ) );
		$this->assertContains( array( 'log', "Skipped: Translation #{$translation->id}: no longer current." ), WP_CLI::$messages );
		$this->assertContains( array( 'success', 'Translation sets checked: 1. Current translations checked: 1. Set to fuzzy: 0.' ), WP_CLI::$messages );
	}

	public function test_reports_translations_that_cannot_be_set_to_fuzzy() {
		$set     = $this->factory->translation_set->create_with_project_and_locale();
		$blocked = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );
		$fixed   = $this->create_translation( $set, self::CONTROL_CHARACTERS );

		$refuse = function ( $verdict, $translation ) use ( $blocked ) {
			return (int) $translation->id === (int) $blocked->id ? false : $verdict;
		};
		add_filter( 'gp_pre_can_set_translation_status', $refuse, 10, 2 );
		$this->run_command();
		remove_filter( 'gp_pre_can_set_translation_status', $refuse, 10 );

		$this->assertSame( 'current', $this->status_of( $blocked ) );
		$this->assertSame( 'fuzzy', $this->status_of( $fixed ) );
		$this->assertContains( array( 'log', "Translation #{$blocked->id} could not be set to fuzzy." ), WP_CLI::$messages );
		$this->assertContains( array( 'error', 'Translation sets checked: 1. Current translations checked: 2. Set to fuzzy: 1. Could not be changed: 1.' ), WP_CLI::$messages );
	}

	public function test_fuzzy_from_the_cli_records_the_current_user() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->create_translation( $set, self::EXTRA_PLACEHOLDER );
		$user        = $this->factory->user->create();
		wp_set_current_user( $user );

		$this->run_command();

		$this->assertSame( $user, GP::$translation->get( $translation->id )->user_id_last_modified );
	}
}
