<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Routes: GP_Route_Main class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 1.0.0
 */

/**
 * Core class used to implement the main route.
 *
 * @since 1.0.0
 */
class GP_Route_Main extends GP_Route {

	/**
	 * Finds the ID of a project chosen in a form, and adds an error if no project matches.
	 *
	 * @since 4.2.0
	 *
	 * @param mixed $value The path or the ID of a project.
	 * @return int|null|false The ID of the project, null for no project, or false if no project matches.
	 */
	protected function project_id_from_request( $value ) {
		$project_id = GP::$project->id_by_path_or_id( $value );

		if ( false === $project_id ) {
			$this->errors[] = sprintf(
				/* translators: %s: Path of a project. */
				__( 'The project %s wasn&#8217;t found.', 'glotpress' ),
				'<code>' . esc_html( is_scalar( $value ) ? $value : '' ) . '</code>'
			);
		}

		return $project_id;
	}
}
