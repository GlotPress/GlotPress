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
					<?php gp_link_project( $project, esc_html( $project->name ) ); ?>
					<?php gp_link_project_edit( $project, null, array( 'class' => 'button is-small' ) ); ?>
					<?php gp_link_project_delete( $project, null, array( 'class' => 'button is-small' ) ); ?>
					<?php
					if ( ! $project->active ) {
						echo "<span class='inactive bubble'>" . __( 'Inactive', 'glotpress' ) . '</span>';
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
