<?php
/**
 * GlotPress CLI Command to Remove Multiple Currents
 *
 * @package GlotPress
 */

/**
 * WP-CLI command class to remove multiple current translations in GlotPress.
 */
class GP_CLI_Remove_Multiple_Currents extends WP_CLI_Command {
	/**
	 * Remove multiple current translations.
	 *
	 * Keeps the most recently modified current translation of each original, and sets the others as old.
	 *
	 * ## OPTIONS
	 *
	 * [--project=<project>]
	 * : Only the translation sets of the project with this path.
	 *
	 * [--locale=<locale>]
	 * : Only the translation sets of the locale with this slug.
	 *
	 * [--set=<set>]
	 * : Only the translation sets with this slug.
	 *
	 * [--dry-run]
	 * : List the current translations that would be set as old, without changing them.
	 *
	 * ## EXAMPLES
	 *
	 *     wp glotpress remove-multiple-currents --project=wp/dev --locale=pt --dry-run
	 *
	 * @since 4.2.0 Sets the duplicate current translations as old instead of deleting them, keeps the most
	 *              recently modified one, and added the `--project`, `--locale`, `--set` and `--dry-run` options.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args = array(), $assoc_args = array() ) {
		$dry_run = ! empty( $assoc_args['dry-run'] );

		if ( isset( $assoc_args['project'] ) ) {
			$project = GP::$project->by_path( $assoc_args['project'] );
			if ( ! $project ) {
				WP_CLI::error( __( 'Project not found!', 'glotpress' ) );
				return;
			}
			$sets = GP::$translation_set->by_project_id( $project->id );
		} else {
			$sets = GP::$translation_set->all();
		}

		$superseded = 0;
		foreach ( $sets as $set ) {
			if ( isset( $assoc_args['locale'] ) && $assoc_args['locale'] !== $set->locale ) {
				continue;
			}
			if ( isset( $assoc_args['set'] ) && $assoc_args['set'] !== $set->slug ) {
				continue;
			}

			foreach ( $set->supersede_multiple_currents( $dry_run ) as $original_id => $duplicate ) {
				WP_CLI::log(
					sprintf(
						/* translators: 1: Translation set ID, 2: Original ID, 3: Kept translation ID, 4: Comma-separated translation IDs. */
						$dry_run ? __( 'Set #%1$d, original #%2$d: would keep translation #%3$d and set #%4$s as old.', 'glotpress' ) : __( 'Set #%1$d, original #%2$d: kept translation #%3$d and set #%4$s as old.', 'glotpress' ),
						$set->id,
						$original_id,
						$duplicate['kept'],
						implode( ', #', $duplicate['superseded'] )
					)
				);
				$superseded += count( $duplicate['superseded'] );
			}
		}

		if ( $dry_run ) {
			/* translators: %d: Number of translations. */
			WP_CLI::success( sprintf( _n( '%d current translation would be set as old.', '%d current translations would be set as old.', $superseded, 'glotpress' ), $superseded ) );
		} else {
			/* translators: %d: Number of translations. */
			WP_CLI::success( sprintf( _n( 'Multiple currents are cleaned up: %d translation was set as old.', 'Multiple currents are cleaned up: %d translations were set as old.', $superseded, 'glotpress' ), $superseded ) );
		}
	}
}
