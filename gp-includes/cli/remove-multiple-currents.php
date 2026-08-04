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
		} else {
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

		$project_ids = $this->get_project_and_subproject_ids( $project );

		if ( $verbose ) {
			WP_CLI::log(
				sprintf(
				/* translators: %d: number of projects */
					__( 'Found %d projects (including subprojects) to process', 'glotpress' ),
					count( $project_ids )
				)
			);
		}

		$conditions = array(
			'project_ids' => $project_ids,
		);

		if ( $locale ) {
			$conditions['locale'] = $locale;
		}

		return $conditions;
	}

	/**
	 * Get project ID and all subproject IDs recursively.
	 *
	 * @param GP_Project $project The parent project.
	 * @return array Array of project IDs.
	 */
	private function get_project_and_subproject_ids( $project ) {
		$project_ids = array( $project->id );

		// Find all subprojects.
		$subprojects = GP::$project->find_many(
			array(
				'parent_project_id' => $project->id,
			)
		);

		// Recursively get IDs from subprojects.
		foreach ( $subprojects as $subproject ) {
			$subproject_ids = $this->get_project_and_subproject_ids( $subproject );
			$project_ids    = array_merge( $project_ids, $subproject_ids );
		}

		return $project_ids;
	}

	/**
	 * Process specific translation sets based on conditions.
	 *
	 * @param array $conditions Conditions to filter translation sets.
	 * @param bool  $dry_run    Whether to perform a dry run without deleting duplicates.
	 * @param bool  $verbose    Whether to output verbose logging.
	 */
	private function process_specific_sets( $conditions, $dry_run, $verbose ) {
		global $wpdb;

		$where_clauses = array();
		$query_args    = array();

		if ( ! empty( $conditions['project_ids'] ) ) {
			$placeholders    = implode( ',', array_fill( 0, count( $conditions['project_ids'] ), '%d' ) );
			$where_clauses[] = "project_id IN ({$placeholders})";
			$query_args      = array_merge( $query_args, array_map( 'intval', $conditions['project_ids'] ) );
		}

		if ( isset( $conditions['locale'] ) ) {
			$where_clauses[] = 'locale = %s';
			$query_args[]    = $conditions['locale'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$query     = "SELECT * FROM {$wpdb->gp_translation_sets} WHERE {$where_sql} ORDER BY id ASC LIMIT %d OFFSET %d";

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

		$query = "SELECT * FROM {$wpdb->gp_translation_sets} ORDER BY id ASC LIMIT %d OFFSET %d";
		$this->process_sets( $query, array(), $dry_run, $verbose );
	}

	/**
	 * Process translation sets using a given query.
	 *
	 * @param string $query      SQL query template ending in LIMIT %d OFFSET %d placeholders.
	 * @param array  $query_args Values for the query placeholders before LIMIT and OFFSET.
	 * @param bool   $dry_run    Whether to perform a dry run without deleting duplicates.
	 * @param bool   $verbose    Whether to output verbose logging.
	 */
	private function process_sets( $query, $query_args, $dry_run, $verbose ) {
		$batch_size      = 1000;
		$offset          = 0;
		$total_processed = 0;

		while ( true ) {
			$sets = GP::$translation_set->many( $query, ...array_merge( $query_args, array( $batch_size, $offset ) ) );

			if ( $verbose && ! empty( $sets ) ) {
				WP_CLI::log(
					sprintf(
					/* translators: 1: number of sets loaded, 2: offset value */
						__( 'Loaded %1$d sets with offset %2$d', 'glotpress' ),
						count( $sets ),
						$offset
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

			$offset += $batch_size;

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
	 * @param GP_Translation_Set $set     The translation set to process.
	 * @param bool               $dry_run Whether to perform a dry run without deleting duplicates.
	 * @param bool               $verbose Whether to output verbose logging.
	 */
	private function process_set( $set, $dry_run, $verbose ) {
		$translations     = GP::$translation->find(
			array(
				'translation_set_id' => $set->id,
				'status'             => 'current',
			),
			'original_id ASC'
		);
		$prev_original_id = null;
		foreach ( $translations as $translation ) {
			if ( $translation->original_id === $prev_original_id ) {
				if ( $verbose ) {
					WP_CLI::log(
						sprintf(
							/* translators: 1: original ID, 2: translation ID, 3: translation string */
							__( '- Duplicate for original_id #%1$d. Translation_id #%2$d. Translation string: %3$s', 'glotpress' ),
							$prev_original_id,
							$translation->id,
							$translation->translation_0
						)
					);
					$original = GP::$original->get( $translation->original_id );
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
							$prev_original_id,
							$translation->id
						)
					);
				}
				++$this->duplicates_found;
				if ( ! $dry_run ) {
					$translation->delete();
				}
			}
			$prev_original_id = $translation->original_id;
		}
	}
}
