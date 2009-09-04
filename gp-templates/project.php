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
<?php foreach($sub_projects as $sub_project): ?>
	<li>
		<?php gp_link_project( $sub_project, gp_h( $sub_project->name )); ?>			
		<?php gp_link_project_edit( $sub_project ); ?>			
		<?php gp_link_project_delete( $sub_project ); ?>
	</li>
<?php endforeach; ?>
</ul>	
<?php endif; ?>
<?php if ( $translation_sets ): ?>
	<?php _e('Translations:'); ?>
	<ul>
	<?php foreach( $translation_sets as $translation_set ):
	    $locale = GP_Locales::by_slug( $translation_set->locale );
	?>    
		<li><?php gp_link( gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug ) ), $locale->combined_name().' &rarr; '.$translation_set->name ); ?></li>
	<?php endforeach; ?>
	</ul>
<?php else: ?>
	<p>There are no translations of this project.</p>
<?php endif; ?>
<?php if ( $can_write ): ?>
	<p>
		<?php gp_link( gp_url_project( $project, 'import-originals' ), __( 'Import originals' ) ); ?> |
		<?php gp_link( gp_url_project( '', '_new', array('parent_project_id' => $project->id) ), __('Create a New Sub-Project') ); ?> |
		<?php gp_link( gp_url( '/sets/_new', array( 'project_id' => $project->id ) ), __('Create a New Translation Set') ); ?>
	</p>
<?php endif; ?>
<?php gp_tmpl_footer(); ?>