<?php
gp_title( __( 'Projects &lt; GlotPress', 'glotpress' ) );
gp_breadcrumb( array( __( 'Projects', 'glotpress' ) ) );
gp_tmpl_header();
?>

	<h2><?php _e( 'Projects', 'glotpress' ); ?></h2>

	<table class="projects tablesorter">
		<thead>
			<tr>
				<th><?php _e( 'Name', 'glotpress' ); ?></th>
				<th><?php _e( 'Description', 'glotpress' ); ?></th>
				<th><?php _e( 'Active', 'glotpress' ); ?></th>
				<th>&mdash;</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $projects as $project ): ?>
			<tr>
				<td><?php gp_link_project( $project, esc_html( $project->name ) ); ?></td>
				<td><?php echo $project->description; ?></td>
				<td><?php if ( $project->active ) { _e( 'Yes', 'glotpress' ); }?></td>
				<td><?php gp_link_project_edit( $project ); ?></td>
		<?php endforeach; ?>
	</table>	

	<p class="actionlist secondary">
		<?php if ( GP::$permission->current_user_can( 'admin' ) ): ?>
			<?php gp_link( gp_url_project( '-new' ), __( 'Create a New Project', 'glotpress' ) ); ?>  &bull;&nbsp;
		<?php endif; ?>

		<?php gp_link( gp_url( '/languages' ), __( 'Projects by language', 'glotpress' ) ); ?>
	</p>

<?php gp_tmpl_footer();
