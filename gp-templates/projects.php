<?php
gp_title( __('Projects &lt; GlotPress') );
gp_tmpl_header();
?>

	<h2><?php _e('Projects'); ?></h2>

	<ul>
		<?php foreach ( $projects as $project ): ?>
			<li><?php gp_link_project( $project, esc_html( $project->name ) ); ?> <?php gp_link_project_edit( $project, null, array( 'class' => 'bubble' ) ); ?></li>
		<?php endforeach; ?>
	</ul>

	<p class="actionlist secondary">
		<?php if ( GP::$user->current()->can( 'write', 'project' ) ): ?>
			<?php gp_link( gp_url_project( '-new' ), __('Create a New Project') ); ?>  &bull;&nbsp;
		<?php endif; ?>

		<?php gp_link( gp_url( '/languages' ), __('Projects by language') ); ?>
	</p>

<?php gp_tmpl_footer();