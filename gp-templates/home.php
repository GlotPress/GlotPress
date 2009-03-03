<h1>Welcome to GlotPress!</h1>
<h2>Projects</h2>
<?php foreach($projects as $project): ?>
	<li><?php gp_project_link( $project, gp_h( $project->name ) ); ?></li>
<?php endforeach; ?>
