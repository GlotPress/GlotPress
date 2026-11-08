<?php
/**
 * GlotPress CLI command to recheck current translations for errors
 *
 * @package GlotPress
 */

/**
 * Rechecks current translations against the registered translation errors.
 *
 * Since translations with errors can no longer be approved, this finds the
 * current translations that were approved earlier, or before an error was
 * added, and sets them to fuzzy, so that translators see and fix them.
 *
 * @since 4.2.0
 */
class GP_CLI_Recheck_Errors extends WP_CLI_Command {

	/**
	 * Number of translation sets, and of translations within a set, loaded per query.
	 *
	 * @var int
	 */
	private $batch_size = 1000;

	/**
	 * Translation sets with at least one translation that fails, keyed by set ID.
	 *
	 * @var array
	 */
	private $affected_sets = array();

	/**
	 * Translations that could not be set to fuzzy, as messages.
	 *
	 * @var string[]
	 */
	private $failures = array();

	/**
	 * Translations and sets that were skipped, as messages.
	 *
	 * @var string[]
	 */
	private $skipped = array();

	/**
	 * Number of translation sets checked.
	 *
	 * @var int
	 */
	private $sets_checked = 0;

	/**
	 * Number of current translations checked.
	 *
	 * @var int
	 */
	private $translations_checked = 0;

	/**
	 * Number of translations that fail a check.
	 *
	 * @var int
	 */
	private $translations_failing = 0;

	/**
	 * Number of failing translations that were no longer current when they were about to be changed.
	 *
	 * @var int
	 */
	private $translations_changed_meanwhile = 0;

	/**
	 * Checks current translations against the translation errors and sets the ones that fail to fuzzy.
	 *
	 * Every registered translation error is checked, the same as when a translation
	 * is approved or imported. Current translations of obsolete originals are
	 * included, as an originals import that brings the string back reactivates
	 * the original, and its current translation is used again without being
	 * checked. Other statuses are not changed.
	 *
	 * ## OPTIONS
	 *
	 * [--project-path=<path>]
	 * : Only check the translation sets of this project and its subprojects.
	 *
	 * [--locale=<locale>]
	 * : Only check the translation sets of this locale, for example `de` or `pt-br`.
	 * Works with or without --project-path.
	 *
	 * [--dry-run]
	 * : Report the translations that fail without changing them.
	 *
	 * [--verbose]
	 * : List every translation that fails, with the errors it fails.
	 *
	 * ## EXAMPLES
	 *
	 *     # See which current translations would be set to fuzzy
	 *     $ wp glotpress recheck-errors --dry-run --verbose
	 *
	 *     # Recheck one project and its subprojects
	 *     $ wp glotpress recheck-errors --project-path=wp-plugins/akismet
	 *
	 *     # Recheck one locale across all projects, recording the change for a user
	 *     $ wp glotpress recheck-errors --locale=de --user=admin
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments, not used.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		$dry_run      = isset( $assoc_args['dry-run'] ) && filter_var( $assoc_args['dry-run'], FILTER_VALIDATE_BOOLEAN );
		$verbose      = isset( $assoc_args['verbose'] ) && filter_var( $assoc_args['verbose'], FILTER_VALIDATE_BOOLEAN );
		$project_path = isset( $assoc_args['project-path'] ) ? (string) $assoc_args['project-path'] : '';
		$locale_slug  = isset( $assoc_args['locale'] ) ? (string) $assoc_args['locale'] : '';

		$this->affected_sets        = array();
		$this->failures             = array();
		$this->skipped              = array();
		$this->sets_checked         = 0;
		$this->translations_checked = 0;
		$this->translations_failing = 0;

		$this->translations_changed_meanwhile = 0;

		global $wpdb;

		$join       = '';
		$where      = array();
		$query_args = array();

		if ( '' !== $project_path ) {
			$project = GP::$project->by_path( $project_path );
			if ( ! $project ) {
				/* translators: %s: Project path. */
				WP_CLI::error( sprintf( __( 'Project not found: %s', 'glotpress' ), $project_path ) );
				return;
			}

			$join         = "INNER JOIN {$wpdb->gp_projects} p ON p.id = ts.project_id";
			$where[]      = '( p.path = %s OR p.path LIKE %s )';
			$query_args[] = $project->path;
			$query_args[] = $wpdb->esc_like( $project->path ) . '/%';
		}

		if ( '' !== $locale_slug ) {
			if ( ! GP_Locales::by_slug( $locale_slug ) ) {
				/* translators: %s: Locale slug. */
				WP_CLI::error( sprintf( __( 'Locale not found: %s', 'glotpress' ), $locale_slug ) );
				return;
			}

			$where[]      = 'ts.locale = %s';
			$query_args[] = $locale_slug;
		}

		$where[] = 'ts.id > %d';
		$query   = "SELECT ts.* FROM {$wpdb->gp_translation_sets} ts {$join} WHERE " . implode( ' AND ', $where ) . ' ORDER BY ts.id ASC LIMIT %d';

		$last_set_id = 0;
		while ( true ) {
			$sets = GP::$translation_set->many( $query, ...array_merge( $query_args, array( $last_set_id, $this->batch_size ) ) );
			if ( ! $sets ) {
				break;
			}

			foreach ( $sets as $set ) {
				$this->check_set( $set, $dry_run, $verbose );
			}

			$last_set_id = end( $sets )->id;
		}

		$this->report( $dry_run );
	}

	/**
	 * Checks the current translations of one translation set.
	 *
	 * @param GP_Translation_Set $set     The translation set.
	 * @param bool               $dry_run Whether to leave the translations unchanged.
	 * @param bool               $verbose Whether to list every translation that fails.
	 */
	private function check_set( $set, $dry_run, $verbose ) {
		global $wpdb;

		$locale = GP_Locales::by_slug( $set->locale );
		if ( ! $locale ) {
			$this->skipped[] = sprintf(
				/* translators: 1: Translation set ID, 2: Locale slug. */
				__( 'Translation set #%1$d: the locale %2$s is not registered.', 'glotpress' ),
				$set->id,
				$set->locale
			);
			return;
		}

		++$this->sets_checked;

		/*
		 * Original columns get a prefix so that they don't overwrite the translation
		 * columns. Obsolete originals are included on purpose.
		 */
		$query = "SELECT t.*, o.id AS o_id, o.project_id AS o_project_id, o.context AS o_context, o.singular AS o_singular, o.plural AS o_plural, o.references AS o_references, o.comment AS o_comment, o.status AS o_status, o.priority AS o_priority, o.date_added AS o_date_added
			FROM {$wpdb->gp_translations} t
			LEFT JOIN {$wpdb->gp_originals} o ON o.id = t.original_id
			WHERE t.translation_set_id = %d AND t.status = 'current' AND t.id > %d
			ORDER BY t.id ASC
			LIMIT %d";

		$last_translation_id = 0;
		while ( true ) {
			$rows = GP::$translation->many_no_map( $query, $set->id, $last_translation_id, $this->batch_size );
			if ( ! $rows ) {
				break;
			}

			foreach ( $rows as $row ) {
				$this->check_translation( $row, $set, $locale, $dry_run, $verbose );
			}

			$last_translation_id = (int) end( $rows )->id;
		}
	}

	/**
	 * Checks one current translation and sets it to fuzzy if it fails.
	 *
	 * @param object             $row     The translation row, with the original columns prefixed with `o_`.
	 * @param GP_Translation_Set $set     The translation set.
	 * @param GP_Locale          $locale  The locale of the set.
	 * @param bool               $dry_run Whether to leave the translation unchanged.
	 * @param bool               $verbose Whether to list the translation if it fails.
	 */
	private function check_translation( $row, $set, $locale, $dry_run, $verbose ) {
		if ( null === $row->o_id ) {
			$this->skipped[] = sprintf(
				/* translators: 1: Translation ID, 2: Original ID. */
				__( 'Translation #%1$d: its original #%2$d does not exist.', 'glotpress' ),
				$row->id,
				$row->original_id
			);
			return;
		}

		++$this->translations_checked;

		$original = new GP_Original(
			array(
				'id'         => $row->o_id,
				'project_id' => $row->o_project_id,
				'context'    => $row->o_context,
				'singular'   => $row->o_singular,
				'plural'     => $row->o_plural,
				'references' => $row->o_references,
				'comment'    => $row->o_comment,
				'status'     => $row->o_status,
				'priority'   => $row->o_priority,
				'date_added' => $row->o_date_added,
			)
		);

		$translations = array();
		foreach ( range( 0, GP::$translation->get_static( 'number_of_plural_translations' ) - 1 ) as $index ) {
			$translations[ $index ] = $row->{"translation_$index"};
		}

		$error_ids = $this->get_error_ids( $original, $translations, $locale );
		if ( ! $error_ids ) {
			return;
		}

		++$this->translations_failing;

		if ( ! isset( $this->affected_sets[ $set->id ] ) ) {
			$this->affected_sets[ $set->id ] = array(
				'set'   => $set,
				'count' => 0,
			);
		}
		++$this->affected_sets[ $set->id ]['count'];

		if ( $verbose ) {
			WP_CLI::log(
				sprintf(
					/* translators: 1: Translation ID, 2: Original ID, 3: Translation set ID, 4: Comma-separated error IDs. */
					__( 'Translation #%1$d of original #%2$d in set #%3$d: %4$s', 'glotpress' ),
					$row->id,
					$row->original_id,
					$set->id,
					implode( ', ', $error_ids )
				)
			);
		}

		if ( $dry_run ) {
			return;
		}

		// Load the row again, so that a translation changed since the batch was read is not overwritten.
		$translation = GP::$translation->get( $row->id );
		if ( ! $translation || 'current' !== $translation->status ) {
			++$this->translations_changed_meanwhile;
			$this->skipped[] = sprintf(
				/* translators: %d: Translation ID. */
				__( 'Translation #%d: no longer current.', 'glotpress' ),
				$row->id
			);
			return;
		}

		if ( ! $translation->set_status( 'fuzzy' ) ) {
			$this->failures[] = sprintf(
				/* translators: %d: Translation ID. */
				__( 'Translation #%d could not be set to fuzzy.', 'glotpress' ),
				$row->id
			);
		}
	}

	/**
	 * Returns the IDs of the translation errors a translation fails.
	 *
	 * @param GP_Original $original     The original.
	 * @param array       $translations The translations, one per plural form.
	 * @param GP_Locale   $locale       The locale.
	 * @return string[] The error IDs, without duplicates across plural forms.
	 */
	private function get_error_ids( $original, $translations, $locale ) {
		$problems = GP::$translation_errors->check( $original, $translations, $locale );
		if ( ! $problems ) {
			return array();
		}

		$error_ids = array();
		foreach ( $problems as $form_problems ) {
			$error_ids = array_merge( $error_ids, array_keys( $form_problems ) );
		}

		return array_values( array_unique( $error_ids ) );
	}

	/**
	 * Prints the affected projects and locales and the totals.
	 *
	 * @param bool $dry_run Whether the translations were left unchanged.
	 */
	private function report( $dry_run ) {
		$lines = array();
		foreach ( $this->affected_sets as $affected ) {
			$set     = $affected['set'];
			$project = GP::$project->get( $set->project_id );
			$lines[] = sprintf(
				/* translators: 1: Project path, 2: Locale slug, 3: Translation set slug, 4: Number of translations. */
				_n( '%1$s, %2$s/%3$s: %4$d translation', '%1$s, %2$s/%3$s: %4$d translations', $affected['count'], 'glotpress' ),
				$project ? $project->path : '#' . $set->project_id,
				$set->locale,
				$set->slug,
				$affected['count']
			);
		}
		sort( $lines );

		if ( $lines ) {
			WP_CLI::log( __( 'Translations that fail a translation error, by project and translation set:', 'glotpress' ) );
			foreach ( $lines as $line ) {
				WP_CLI::log( $line );
			}
		}

		foreach ( $this->skipped as $message ) {
			/* translators: %s: Why an item was skipped. */
			WP_CLI::log( sprintf( __( 'Skipped: %s', 'glotpress' ), $message ) );
		}

		foreach ( $this->failures as $message ) {
			WP_CLI::log( $message );
		}

		$checked = sprintf(
			/* translators: 1: Number of translation sets, 2: Number of translations. */
			__( 'Translation sets checked: %1$d. Current translations checked: %2$d.', 'glotpress' ),
			$this->sets_checked,
			$this->translations_checked
		);

		if ( $dry_run ) {
			WP_CLI::success(
				__( 'Dry run, nothing was changed.', 'glotpress' ) . ' ' . $checked . ' ' . sprintf(
					/* translators: %d: Number of translations. */
					__( 'Would be set to fuzzy: %d.', 'glotpress' ),
					$this->translations_failing
				)
			);
			return;
		}

		$set_to_fuzzy = $this->translations_failing - count( $this->failures ) - $this->translations_changed_meanwhile;
		$result       = $checked . ' ' . sprintf(
			/* translators: %d: Number of translations. */
			__( 'Set to fuzzy: %d.', 'glotpress' ),
			$set_to_fuzzy
		);

		if ( $this->failures ) {
			WP_CLI::error(
				$result . ' ' . sprintf(
					/* translators: %d: Number of translations. */
					__( 'Could not be changed: %d.', 'glotpress' ),
					count( $this->failures )
				)
			);
			return;
		}

		WP_CLI::success( $result );
	}
}
