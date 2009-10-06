<?php
gp_title( sprintf( __('%s &lt; GlotPress'), esc_html( $project->name ) ) );
gp_breadcrumb( array(
	// TODO: add parent projects to breadcrumb
	gp_link_project_get( $project, $project->name ),
) );
gp_tmpl_header();
?>
<?php if ($sub_projects): ?>
<ul>
<?php foreach($sub_projects as $sub_project): ?>
	<li>
		<?php gp_link_project( $sub_project, esc_html( $sub_project->name )); ?>			
		<?php gp_link_project_edit( $sub_project ); ?>			
		<?php gp_link_project_delete( $sub_project ); ?>
	</li>
<?php endforeach; ?>
</ul>	
<?php endif; ?>
<?php if ( $translation_sets ): ?>
	<?php _e('Translations:'); ?>
	<ul class="translation-sets">
	<?php foreach( $translation_sets as $set ): ?>    
		<li>
			<?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ) ), $set->name_with_locale() ); ?>
			<span class="stats">
				<!--
				<span class="translated" title="translated"><?php echo $set->current_count(); ?></span>
				<span class="untranslated" title="untranslated"><?php echo $set->untranslated_count(); ?></span>
				-->
			<?php if ( $can_approve && $waiting = $set->waiting_count() ): ?>
				<?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ), array('filters[translated]' => 'yes', 'filters[status]' => 'waiting') ), "$waiting waiting", array('class' => 'waiting') ); ?>
			<?php endif; ?>
			</span>
		</li>
	<?php endforeach; ?>
	</ul>
<?php else: ?>
	<p>There are no translations of this project.</p>
<?php endif; ?>
<?php if ( $can_write ): ?>
	<p>
		<?php gp_link( gp_url_project( $project, 'import-originals' ), __( 'Import originals' ) ); ?> &bull;
		<?php gp_link( gp_url_project( '', '_new', array('parent_project_id' => $project->id) ), __('Create a New Sub-Project') ); ?> &bull;
		<?php gp_link( gp_url( '/sets/_new', array( 'project_id' => $project->id ) ), __('Create a New Translation Set') ); ?>
	</p>
<?php endif; ?>
<?php gp_tmpl_footer(); ?>