<?php
/**
 * CLI: GP_CLI_Audit_Incomplete_Translations class
 *
 * @package GlotPress
 * @subpackage CLI
 * @since 4.2.0
 */

/**
 * Lists the translations which don't have a translation for every plural form of their locale.
 *
 * @since 4.2.0
 */
class GP_CLI_Audit_Incomplete_Translations extends WP_CLI_Command {

	/**
	 * Lists the translations which are missing one or more translations.
	 *
	 * A translation of an original with a plural form needs a translation for each plural form of its
	 * locale, and any other translation needs a single one. Translations which don't have them can't be
	 * saved in the editor, but they made it to the database through imports, through project branching,
	 * or because their original gained a plural form after they were translated.
	 *
	 * This command only reports them, unless it is asked to set them as fuzzy.
	 *
	 * ## OPTIONS
	 *
	 * [--project=<project>]
	 * : Audit the translation sets of a project path and of its sub-projects only.
	 *
	 * [--locale=<locale>]
	 * : Audit the translation sets of a locale slug only.
	 *
	 * [--set=<set>]
	 * : Audit a translation set ID only.
	 *
	 * [--status=<statuses>]
	 * : Comma separated statuses to audit, or "all" for every status; default is "current".
	 *
	 * [--format=<format>]
	 * : Render the output in a particular format (one of "table", "csv", "json", "yaml", "count");
	 * default is "table".
	 *
	 * [--set-fuzzy]
	 * : Set the current translations which were found as fuzzy, after asking for a confirmation.
	 * Translations of the other statuses are only reported, never changed.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message of `--set-fuzzy`.
	 *
	 * ## EXAMPLES
	 *
	 *     # List the current translations which are missing a plural form.
	 *     $ wp glotpress audit-incomplete-translations
	 *
	 *     # List them for a single project, whatever their status is.
	 *     $ wp glotpress audit-incomplete-translations --project=wp/dev --status=all
	 *
	 *     # Count them for a locale.
	 *     $ wp glotpress audit-incomplete-translations --locale=pt --format=count
	 *
	 *     # Set the ones of a locale as fuzzy, so that they are translated again.
	 *     $ wp glotpress audit-incomplete-translations --locale=pt --set-fuzzy
	 *
	 * @param array $args       Positional arguments, unused.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		$statuses = $this->statuses( $assoc_args );
		$format   = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		$rows     = array();
		$projects = array();

		foreach ( $this->translation_sets( $assoc_args ) as $translation_set ) {
			$locale = GP_Locales::by_slug( $translation_set->locale );

			if ( ! $locale ) {
				WP_CLI::warning(
					sprintf(
						/* translators: 1: Locale slug. 2: Translation set ID. */
						__( 'Skipped translation set #%2$s, its locale "%1$s" is unknown.', 'glotpress' ),
						$translation_set->locale,
						$translation_set->id
					)
				);

				continue;
			}

			if ( ! isset( $projects[ $translation_set->project_id ] ) ) {
				$project                                  = GP::$project->get( $translation_set->project_id );
				$projects[ $translation_set->project_id ] = $project ? $project->path : '';
			}

			foreach ( $this->incomplete_translations( $translation_set, $locale, $statuses ) as $translation ) {
				$rows[] = array(
					'translation'   => $translation->id,
					'original'      => $translation->original_id,
					'set'           => $translation_set->id,
					'project'       => $projects[ $translation_set->project_id ],
					'locale'        => $translation_set->locale,
					'slug'          => $translation_set->slug,
					'status'        => $translation->status,
					'missing'       => implode( ', ', $this->missing_translations( $translation, $locale ) ),
					'date_modified' => $translation->date_modified,
				);
			}
		}

		if ( ! $rows ) {
			WP_CLI::success( __( 'No incomplete translations were found.', 'glotpress' ) );

			return;
		}

		$fields = array( 'translation', 'original', 'set', 'project', 'locale', 'slug', 'status', 'missing', 'date_modified' );

		WP_CLI\Utils\format_items( $format, $rows, $fields );

		if ( 'table' === $format ) {
			WP_CLI::log(
				sprintf(
					/* translators: 1: Translations count. 2: Translation sets count. */
					_n(
						'%1$s incomplete translation was found in %2$s translation set.',
						'%1$s incomplete translations were found in %2$s translation sets.',
						count( $rows ),
						'glotpress'
					),
					count( $rows ),
					count( array_unique( wp_list_pluck( $rows, 'set' ) ) )
				)
			);
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'set-fuzzy', false ) ) {
			$this->set_as_fuzzy( $rows, $format, $assoc_args );
		}
	}

	/**
	 * Sets the current translations which were found as fuzzy.
	 *
	 * Only the current ones are touched, they are the ones which are used, and a fuzzy translation is
	 * kept in the translation set for a translator to complete it.
	 *
	 * @param array  $rows       The rows of the audit.
	 * @param string $format     The format the audit was rendered in.
	 * @param array  $assoc_args Associative arguments of the command.
	 */
	private function set_as_fuzzy( $rows, $format, $assoc_args ) {
		$current_rows = array_filter(
			$rows,
			function ( $row ) {
				return 'current' === $row['status'];
			}
		);

		if ( ! $current_rows ) {
			WP_CLI::warning( __( 'None of the incomplete translations is a current one, so there is nothing to set as fuzzy.', 'glotpress' ) );

			return;
		}

		$translation_ids = wp_list_pluck( $current_rows, 'translation' );

		WP_CLI::confirm(
			sprintf(
				/* translators: %s: Translations count. */
				_n(
					'Set %s current translation as fuzzy?',
					'Set %s current translations as fuzzy?',
					count( $translation_ids ),
					'glotpress'
				),
				count( $translation_ids )
			),
			$assoc_args
		);

		$progress = 'table' === $format ? WP_CLI\Utils\make_progress_bar( __( 'Setting translations as fuzzy', 'glotpress' ), count( $translation_ids ) ) : false;
		$fuzzied  = 0;
		$errors   = 0;

		foreach ( $translation_ids as $translation_id ) {
			$translation = GP::$translation->get( $translation_id );

			// The status is checked again, the audit could have taken a while on a big installation.
			if ( $translation && 'current' === $translation->status && $translation->set_status( 'fuzzy' ) ) {
				++$fuzzied;
			} else {
				++$errors;
			}

			if ( $progress ) {
				$progress->tick();
			}
		}

		if ( $progress ) {
			$progress->finish();
		}

		WP_CLI::success(
			sprintf(
				/* translators: %s: Translations count. */
				_n( '%s translation was set as fuzzy.', '%s translations were set as fuzzy.', $fuzzied, 'glotpress' ),
				$fuzzied
			)
		);

		if ( $errors ) {
			WP_CLI::warning(
				sprintf(
					/* translators: %s: Translations count. */
					_n(
						'%s translation could not be set as fuzzy.',
						'%s translations could not be set as fuzzy.',
						$errors,
						'glotpress'
					),
					$errors
				)
			);
		}
	}

	/**
	 * Returns the statuses to audit.
	 *
	 * @param array $assoc_args Associative arguments of the command.
	 * @return array Statuses to audit.
	 */
	private function statuses( $assoc_args ) {
		$status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'current';

		if ( 'all' === $status ) {
			return GP::$translation->get_static( 'statuses' );
		}

		$statuses = array_filter( array_map( 'trim', explode( ',', $status ) ) );
		$unknown  = array_diff( $statuses, GP::$translation->get_static( 'statuses' ) );

		if ( $unknown ) {
			WP_CLI::error(
				sprintf(
					/* translators: %s: Comma separated list of statuses. */
					__( 'Unknown translation status: %s.', 'glotpress' ),
					implode( ', ', $unknown )
				)
			);
		}

		return $statuses;
	}

	/**
	 * Returns the translation sets to audit.
	 *
	 * @param array $assoc_args Associative arguments of the command.
	 * @return array Translation sets to audit.
	 */
	private function translation_sets( $assoc_args ) {
		if ( isset( $assoc_args['set'] ) ) {
			$translation_set = GP::$translation_set->get( $assoc_args['set'] );

			if ( ! $translation_set ) {
				WP_CLI::error( __( 'Translation set not found!', 'glotpress' ) );
			}

			$translation_sets = array( $translation_set );
		} elseif ( isset( $assoc_args['project'] ) ) {
			$project = GP::$project->by_path( $assoc_args['project'] );

			if ( ! $project ) {
				WP_CLI::error( __( 'Project not found!', 'glotpress' ) );
			}

			$translation_sets = array();
			foreach ( array_merge( array( $project ), $project->inclusive_sub_projects() ) as $sub_project ) {
				$translation_sets = array_merge( $translation_sets, GP::$translation_set->by_project_id( $sub_project->id ) );
			}
		} else {
			$translation_sets = GP::$translation_set->all();
		}

		if ( isset( $assoc_args['locale'] ) ) {
			$translation_sets = array_filter(
				$translation_sets,
				function ( $translation_set ) use ( $assoc_args ) {
					return $translation_set->locale === $assoc_args['locale'];
				}
			);
		}

		return $translation_sets;
	}

	/**
	 * Retrieves the translations of a translation set which are missing one or more translations.
	 *
	 * The conditions mirror the ones of GP_Translation::has_all_translations(), but they run in a single
	 * query per translation set, so that a whole installation can be audited.
	 *
	 * @param GP_Translation_Set $translation_set The translation set to audit.
	 * @param GP_Locale          $locale          The locale of the translation set.
	 * @param array              $statuses        The statuses to audit.
	 * @return array The incomplete translations of the translation set.
	 */
	private function incomplete_translations( $translation_set, $locale, $statuses ) {
		global $wpdb;

		if ( ! $statuses ) {
			return array();
		}

		$missing = array();
		foreach ( range( 0, $locale->nplurals - 1 ) as $index ) {
			$missing[] = "( t.translation_$index IS NULL OR t.translation_$index = '' )";
		}

		$sql = "SELECT t.*, ( o.plural IS NOT NULL ) AS original_is_plural FROM {$wpdb->gp_translations} AS t
			INNER JOIN {$wpdb->gp_originals} AS o ON o.id = t.original_id
			WHERE t.translation_set_id = %d
				AND t.status IN ( " . implode( ', ', array_fill( 0, count( $statuses ), '%s' ) ) . ' )
				AND (
					( o.plural IS NULL AND ( t.translation_0 IS NULL OR t.translation_0 = \'\' ) )
					OR ( o.plural IS NOT NULL AND ( ' . implode( ' OR ', $missing ) . ' ) )
				)
			ORDER BY t.id ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare( $sql, array_merge( array( $translation_set->id ), $statuses ) ) );
	}

	/**
	 * Returns the indices of the plural forms a translation doesn't have a translation for.
	 *
	 * @param object    $translation A translation row of incomplete_translations().
	 * @param GP_Locale $locale      The locale of the translation.
	 * @return array The indices of the missing translations.
	 */
	private function missing_translations( $translation, $locale ) {
		$missing  = array();
		$nplurals = $translation->original_is_plural ? $locale->nplurals : 1;

		foreach ( range( 0, $nplurals - 1 ) as $index ) {
			$field = "translation_$index";

			if ( ! isset( $translation->$field ) || '' === $translation->$field ) {
				$missing[] = $index;
			}
		}

		return $missing;
	}
}
