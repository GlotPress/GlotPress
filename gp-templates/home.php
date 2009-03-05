<?php
gp_title( __('Home < GlotPress') );
gp_tmpl_header();
?>
<h1>Welcome to GlotPress!</h1>
<h2>Projects</h2>
<?php foreach($projects as $project): ?>
	<li><?php gp_link_project( $project, gp_h( $project->name ) ); ?></li>
<?php endforeach; ?>
<?php gp_tmpl_footer(); ?>