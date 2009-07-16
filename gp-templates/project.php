<?php
gp_title( sprintf( __('%s &lt; GlotPress'), gp_h( $project->name ) ) );
gp_breadcrumb( array(
	gp_link_home_get(),
	// TODO: add parent projects to breadcrumb
	gp_link_project_get( $project, $project->name ),
) );
gp_tmpl_header();
?>
<?php if ($sub_projects): ?>
<ul>
<?php foreach($sub_projects as $sub_project) ?>
	<li><?php gp_link_project( $sub_project, gp_h( $sub_project->name )); ?> <?php gp_link_project_edit( $sub_project ); ?></li>
</ul>	
<?php endif; ?>
<?php _e('Translations:'); ?>
<ul>
<?php foreach( $translation_sets as $translation_set ):
    $locale = GP_Locales::by_slug( $translation_set->locale );
?>    
	<li><?php gp_link( gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug ) ), $locale->combined_name().' > '.$translation_set->name ); ?></li>
<?php endforeach; ?>
</ul>
<p><?php gp_link( gp_url_project( $project, 'import-originals' ), __( 'Import originals' ) ); ?></p>
<?php gp_tmpl_footer(); ?>