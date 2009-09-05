<?php
gp_title( __('Home &lt; GlotPress') );
gp_breadcrumb(array( gp_link_home_get() ));
gp_tmpl_header();
?>
<h2>Projects</h2>
<ul>
<?php foreach($projects as $project): ?>
	<li><?php gp_link_project( $project, esc_html( $project->name ) ); ?> <?php gp_link_project_edit( $project ); ?> <?php gp_link_project_delete( $project ); ?></li>
<?php endforeach; ?>
</ul>
<?php if ( GP::$user->current()->can( 'write', 'project' ) ): ?>
	<p><?php gp_link( gp_url_project( '_new' ), __('Create a New Project') ); ?></p>
<?php endif; ?>
<?php gp_tmpl_footer(); ?>