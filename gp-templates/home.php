<?php
gp_title( __('Home &lt; GlotPress') );
gp_breadcrumb(array( gp_link_home_get() ));
gp_tmpl_header();
?>
<h2>Projects</h2>
<?php foreach($projects as $project): ?>
	<li><?php gp_link_project( $project, gp_h( $project->name ) ); ?></li>
<?php endforeach; ?>
<?php gp_tmpl_footer(); ?>