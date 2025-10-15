<?php
gp_title( __( 'Projects &lt; GlotPress', 'glotpress' ) );
gp_breadcrumb( array( __( 'Projects', 'glotpress' ) ) );
gp_tmpl_header();
?>

	<div id="projects">
		<h2><?php _e( 'Projects', 'glotpress' ); ?></h2>
		<dl>
			<?php
			foreach ( $projects as $project ) {
				$project_class = $project->active ? 'project-active' : 'project-inactive';
				?>
				<dt class="<?php echo esc_attr( $project_class ); ?>">
					<?php

					/**
					 * Filter a project row items, like title, action buttons and active bubble in the root projects template.
					 *
					 * @since 4.0.2
					 *
					 * @param array $project_row_items   The array of project items to render on the projects list.
					 * @param GP_Project $project        GP_Project object.
					 */
					$project_row_items = apply_filters(
						'gp_projects_template_project_items',
						array(
							'link-name'     => gp_link_project_get( $project, esc_html( $project->name ) ),
							'button-edit'   => gp_link_project_edit_get( $project, null, array( 'class' => 'button is-small' ) ),
							'button-delete' => gp_link_project_delete_get( $project, null, array( 'class' => 'button is-small' ) ),
							'bubble-status' => $project->active ? null : "<span class='inactive bubble'>" . __( 'Inactive', 'glotpress' ) . '</span>',
						),
						$project
					);

					// Render project row items.
					foreach ( $project_row_items as $project_row_item ) {
						if ( ! is_null( $project_row_item ) ) {
							echo wp_kses_post( $project_row_item );
						}
					}
					?>
				</dt>
				<dd class="<?php echo esc_attr( $project_class ); ?>">
					<?php
					/**
					 * Filter a project description.
					 *
					 * @since 4.0.0
					 *
					 * @param string     $description Project description.
					 * @param GP_Project $project     The project.
					 */
					echo esc_html( gp_html_excerpt( apply_filters( 'gp_project_description', $project->description, $project ), 111 ) );
					?>
				</dd>
				<?php
			}
			?>
		</dl>
	</div>
	<div class="clear"></div>

	<p class="actionlist">
		<?php if ( GP::$permission->current_user_can( 'admin' ) ) : ?>
			<?php gp_link( gp_url_project( '-new' ), __( 'Create a New Project', 'glotpress' ) ); ?>  &bull;
		<?php endif; ?>

		<?php gp_link( gp_url( '/languages' ), __( 'Projects by language', 'glotpress' ) ); ?>
	</p>

<?php
gp_tmpl_footer();
