<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Class GP_CLI_Remove_Multiple_Currents
 *
 * Provides WP-CLI commands for removing duplicate current translations
 * from GlotPress translation sets.
 *
 * @package GlotPress
 */
class GP_CLI_Remove_Multiple_Currents extends WP_CLI_Command {
	/**
	 * Counter for the number of duplicate translations found (and removed unless dry-run).
	 *
	 * @var int
	 */
	private $duplicates_found = 0;

	/**
	 * Remove duplicate current translations from translation sets.
	 *
	 * Scans translation sets for duplicate current translations with the same original_id
	 * and reports or removes them. Can process all translation sets or filter by project
	 * and locale. Automatically includes all subprojects when a project path is specified.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run without actually deleting duplicate translations. Only report what would be deleted.
	 * ---
	 * default: false
	 * ---
	 *
	 * [--verbose]
	 * : Output detailed logging information during processing.
	 * ---
	 * default: false
	 * ---
	 *
	 * [--project-path=<path>]
	 * : Process only translation sets for a specific project and its subprojects.
	 * Use the project path (e.g., 'wp-plugins/woocommerce' or 'wp/dev').
	 *
	 * [--locale=<locale>]
	 * : Process only translation sets for a specific locale.
	 * Use the `slug` property from the GP_Locale object. E.g., 'gl', 'es', 'nl-be'.
	 * Requires --project-path to be specified.
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview duplicates across all translation sets (dry run)
	 *     $ wp glotpress remove-multiple-currents --dry-run --verbose
	 *
	 *     # Remove duplicates from all translation sets
	 *     $ wp glotpress remove-multiple-currents
	 *
	 *     # Preview duplicates for a specific project and all its subprojects
	 *     $ wp glotpress remove-multiple-currents --project-path=wp-plugins/woocommerce --dry-run --verbose
	 *
	 *     # Remove duplicates for a specific project and locale
	 *     $ wp glotpress remove-multiple-currents --project-path=wp-plugins/woocommerce --locale=gl
	 *
	 *     # Preview duplicates for WordPress core in Spanish
	 *     $ wp glotpress remove-multiple-currents --project-path=wp/dev --locale=es --dry-run --verbose
	 *
	 * @param array $args       Positional arguments (not used).
	 * @param array $assoc_args Associative arguments including dry-run, verbose, project-path, and locale.
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		$dry_run                = filter_var( \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false ), FILTER_VALIDATE_BOOLEAN );
		$verbose                = filter_var( \WP_CLI\Utils\get_flag_value( $assoc_args, 'verbose', false ), FILTER_VALIDATE_BOOLEAN );
		$project_path           = isset( $assoc_args['project-path'] ) ? $assoc_args['project-path'] : null;
		$locale                 = isset( $assoc_args['locale'] ) ? $assoc_args['locale'] : null;
		$this->duplicates_found = 0;

		if ( $locale && ! $project_path ) {
			WP_CLI::error( __( 'The --locale parameter requires --project-path to be specified.', 'glotpress' ) );
			return;
		}

		if ( $verbose ) {
			WP_CLI::log( __( 'Verbose: enabled', 'glotpress' ) );
			WP_CLI::log(
				sprintf(
				/* translators: %s: enabled or disabled */
					__( 'Dry run: %s', 'glotpress' ),
					$dry_run ? __( 'enabled', 'glotpress' ) : __( 'disabled', 'glotpress' )
				)
			);
			if ( $project_path ) {
				WP_CLI::log(
					sprintf(
					/* translators: %s: project path */
						__( 'Project path: %s', 'glotpress' ),
						$project_path
					)
				);
			}
			if ( $locale ) {
				WP_CLI::log(
					sprintf(
					/* translators: %s: locale code */
						__( 'Locale: %s', 'glotpress' ),
						$locale
					)
				);
			}
		}

		$conditions = $this->get_translation_set_conditions( $project_path, $locale, $verbose );
		if ( $conditions ) {
			$this->process_specific_sets( $conditions, $dry_run, $verbose );
		} elseif ( ! $project_path ) {
			// Only process everything when no filtering was requested: an invalid
			// --project-path must never degrade into a full-database run.
			$this->process_all_sets( $dry_run, $verbose );
		}
	}

	/**
	 * Get translation set conditions based on project path and locale.
	 *
	 * @param string|null $project_path The project path to filter by.
	 * @param string|null $locale       The locale to filter by.
	 * @param bool        $verbose       Whether to output verbose logging.
	 * @return array|null Array of conditions or null if no filtering needed.
	 */
	private function get_translation_set_conditions( $project_path, $locale, $verbose ) {
		if ( ! $project_path ) {
			return null;
		}

		$project = GP::$project->by_path( $project_path );
		if ( ! $project ) {
			WP_CLI::error(
				sprintf(
				/* translators: %s: project path */
					__( 'Project not found: %s', 'glotpress' ),
					$project_path
				)
			);
			return null;
		}

		if ( $verbose ) {
			WP_CLI::log(
				sprintf(
				/* translators: %s: project path */
					__( 'Processing the project %s and all its subprojects', 'glotpress' ),
					$project->path
				)
			);
		}

		$conditions = array(
			'project_path' => $project->path,
		);

		if ( $locale ) {
			$conditions['locale'] = $locale;
		}

		return $conditions;
	}

	/**
	 * Process specific translation sets based on conditions.
	 *
	 * Filters the translation sets by joining on the projects table and matching the
	 * project path (and its subprojects) instead of enumerating project IDs, so the
	 * query size stays constant regardless of the number of subprojects.
	 *
	 * @param array $conditions Conditions to filter translation sets (project_path, locale).
	 * @param bool  $dry_run    Whether to perform a dry run without deleting duplicates.
	 * @param bool  $verbose    Whether to output verbose logging.
	 */
	private function process_specific_sets( $conditions, $dry_run, $verbose ) {
		global $wpdb;

		$where_clauses = array( '( p.path = %s OR p.path LIKE %s )' );
		$query_args    = array( $conditions['project_path'], $wpdb->esc_like( $conditions['project_path'] ) . '/%' );

		if ( isset( $conditions['locale'] ) ) {
			$where_clauses[] = 'ts.locale = %s';
			$query_args[]    = $conditions['locale'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$query     = "SELECT ts.* FROM {$wpdb->gp_translation_sets} ts JOIN {$wpdb->gp_projects} p ON p.id = ts.project_id WHERE {$where_sql} AND ts.id > %d ORDER BY ts.id ASC LIMIT %d";

		$this->process_sets( $query, $query_args, $dry_run, $verbose );
	}

	/**
	 * Process all translation sets to remove multiple currents.
	 *
	 * @param bool $dry_run Whether to perform a dry run without deleting duplicates.
	 * @param bool $verbose Whether to output verbose logging.
	 */
	private function process_all_sets( $dry_run, $verbose ) {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->gp_translation_sets} WHERE id > %d ORDER BY id ASC LIMIT %d";
		$this->process_sets( $query, array(), $dry_run, $verbose );
	}

	/**
	 * Process translation sets using a given query.
	 *
	 * Pages through the sets with keyset pagination (id > last processed ID) so
	 * each batch query costs the same regardless of how far the run has advanced.
	 *
	 * @param string $query      SQL query template ending in id > %d ORDER BY id ASC LIMIT %d placeholders.
	 * @param array  $query_args Values for the query placeholders before the id and LIMIT ones.
	 * @param bool   $dry_run    Whether to perform a dry run without deleting duplicates.
	 * @param bool   $verbose    Whether to output verbose logging.
	 */
	private function process_sets( $query, $query_args, $dry_run, $verbose ) {
		$batch_size      = 1000;
		$last_id         = 0;
		$total_processed = 0;

		while ( true ) {
			$sets = GP::$translation_set->many( $query, ...array_merge( $query_args, array( $last_id, $batch_size ) ) );

			if ( $verbose && ! empty( $sets ) ) {
				WP_CLI::log(
					sprintf(
					/* translators: 1: number of sets loaded, 2: translation set ID */
						__( 'Loaded %1$d sets after set #%2$d', 'glotpress' ),
						count( $sets ),
						$last_id
					)
				);
			}

			if ( empty( $sets ) ) {
				break;
			}

			foreach ( $sets as $set ) {
				++$total_processed;

				if ( $verbose ) {
					/* translators: %d: Set ID */
					WP_CLI::log( sprintf( __( 'Processing set #%d..', 'glotpress' ), $set->id ) );
				}

				$this->process_set( $set, $dry_run, $verbose );
			}

			$last_id = end( $sets )->id;

			if ( $verbose ) {
				WP_CLI::log(
					sprintf(
					/* translators: %d: number of sets processed */
						__( 'Processed %d sets so far...', 'glotpress' ),
						$total_processed
					)
				);
			}

			// Free memory.
			unset( $sets );
		}

		if ( $dry_run ) {
			WP_CLI::success(
				sprintf(
				/* translators: 1: total number of sets processed, 2: number of duplicates found */
					__( 'Dry run finished, nothing was deleted. Total sets processed: %1$d. Duplicates found: %2$d', 'glotpress' ),
					$total_processed,
					$this->duplicates_found
				)
			);
		} else {
			WP_CLI::success(
				sprintf(
				/* translators: 1: total number of sets processed, 2: number of duplicates removed */
					__( 'Multiple currents are cleaned up. Total sets processed: %1$d. Duplicates removed: %2$d', 'glotpress' ),
					$total_processed,
					$this->duplicates_found
				)
			);
		}
	}

	/**
	 * Process a single translation set to remove duplicate current translations.
	 *
	 * Finds the originals with more than one current translation with a grouping
	 * query, then loads only the translations for those originals, keeps the oldest
	 * one (lowest ID) and removes the rest, so memory usage is proportional to the
	 * number of duplicates instead of the size of the set.
	 *
	 * @param GP_Translation_Set $set     The translation set to process.
	 * @param bool               $dry_run Whether to perform a dry run without deleting duplicates.
	 * @param bool               $verbose Whether to output verbose logging.
	 */
	private function process_set( $set, $dry_run, $verbose ) {
		global $wpdb;

		$duplicate_rows = GP::$translation->many_no_map(
			"SELECT original_id FROM {$wpdb->gp_translations} WHERE translation_set_id = %d AND status = 'current' GROUP BY original_id HAVING COUNT(*) > 1",
			$set->id
		);

		foreach ( $duplicate_rows as $row ) {
			$original_id  = (int) $row->original_id;
			$translations = GP::$translation->find(
				array(
					'translation_set_id' => $set->id,
					'status'             => 'current',
					'original_id'        => $original_id,
				),
				'id ASC'
			);

			// Keep the oldest current translation and remove the rest.
			array_shift( $translations );

			$original = $verbose ? GP::$original->get( $original_id ) : null;

			foreach ( $translations as $translation ) {
				if ( $verbose ) {
					WP_CLI::log(
						sprintf(
							/* translators: 1: original ID, 2: translation ID, 3: translation string */
							__( '- Duplicate for original_id #%1$d. Translation_id #%2$d. Translation string: %3$s', 'glotpress' ),
							$original_id,
							$translation->id,
							$translation->translation_0
						)
					);
					if ( $original ) {
						WP_CLI::log(
							sprintf(
							/* translators: 1: original ID, 2: original string */
								__( '    Original id: %1$d. Original string: %2$s', 'glotpress' ),
								$original->id,
								$original->singular
							)
						);
					}
				} else {
					WP_CLI::log(
						sprintf(
							/* translators: 1: original ID, 2: translation ID */
							__( '- Duplicate for original_id #%1$d. Translation_id #%2$d.', 'glotpress' ),
							$original_id,
							$translation->id
						)
					);
				}
				++$this->duplicates_found;
				if ( ! $dry_run ) {
					$translation->delete();
				}
			}
		}
	}
}
