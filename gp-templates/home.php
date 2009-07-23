<?php
gp_title( __('Home &lt; GlotPress') );
gp_breadcrumb(array( gp_link_home_get() ));
gp_tmpl_header();
?>
<h2>Projects</h2>
<ul>
<?php foreach($projects as $project): ?>
	<li><?php gp_link_project( $project, gp_h( $project->name ) ); ?> <?php gp_link_project_edit( $project ); ?></li>
<?php endforeach; ?>
</ul>
<?php if ( GP_User::current()->can( 'write', 'project' ) ): ?>
	<p><?php gp_link( gp_url( '/project/_new' ), __('Create a New Project') ); ?></p>
<?php endif; ?>
<?php gp_tmpl_footer(); ?>