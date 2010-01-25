<?php
gp_title( sprintf( __('%s &lt; GlotPress'), esc_html( $project->name ) ) );
gp_breadcrumb( array(
	// TODO: add parent projects to breadcrumb
	gp_link_project_get( $project, $project->name ),
) );
wp_enqueue_script( 'common' );
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
			<span class="stats secondary">
				<!--
				<span class="translated" title="translated"><?php echo $set->current_count(); ?></span>
				<span class="untranslated" title="untranslated"><?php echo $set->untranslated_count(); ?></span>
				-->
			<?php if ( $can_write && $waiting = $set->waiting_count() ):  // TODO: check if the user can approve this locale ?>
				<?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ),
						array('filters[translated]' => 'yes', 'filters[status]' => 'waiting') ), $waiting, array('class' => 'waiting', 'title' => 'waiting') ); ?>
			<?php endif; ?>
			<?php if ( $warnings = $set->warnings_count() ): ?>
				<?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ),
						array('filters[translated]' => 'yes', 'filters[warnings]' => 'yes' ) ), $warnings, array('class' => 'warnings', 'title' => 'with warnings') ); ?>
			<?php endif; ?>
			
			<?php do_action( 'project_template_translation_set_extra', $set, $project ); ?>
			</span>
		</li>
	<?php endforeach; ?>
	</ul>
<?php else: ?>
	<p><?php _e('There are no translations of this project.'); ?></p>
<?php endif; ?>
<?php if ( $can_write ): ?>
	<div class="secondary actionlist">
	<a href="#" class="personal-options" id="personal-options-toggle"><?php _e('Personal project options &darr;'); ?></a>
	<div class="personal-options">
		<form action="<?php echo gp_url_project( $project, '_personal' ); ?>" method="post">
		<dl>
			<dt><label for="source-url-template"><?php _e('Source file URL');  ?></label></dt>
			<dd>
				<input type="text" value="<?php echo esc_html( $project->source_url_template() ); ?>" name="source-url-template" id="source-url-template" />
				<small><?php _e('URL to a source file in the project. You can use <code>%file%</code> and <code>%line%</code>. Ex. <code>http://trac.example.org/browser/%file%#L%line%</code>'); ?></small>
			</dd>
		</dl>
		<p>
			<input type="submit" name="submit" value="<?php esc_attr(__('Save &rarr;')); ?>" id="save" />
			<a class="ternary" href="#" onclick="jQuery('#personal-options-toggle').click();return false;"><?php _e('Cancel'); ?></a>
		</p>		
		</form>
	</div>
	</div>
<?php endif; ?>
<?php if ( $can_write ): ?>
	<p class="secondary actionlist">
		<?php gp_link( gp_url_project( $project, 'import-originals' ), __( 'Import originals' ) ); ?> &bull;
		<?php gp_link( gp_url_project( '', '_new', array('parent_project_id' => $project->id) ), __('Create a New Sub-Project') ); ?> &bull;
		<?php gp_link( gp_url( '/sets/_new', array( 'project_id' => $project->id ) ), __('Create a New Translation Set') ); ?>
	</p>
<?php endif; ?>
<script type="text/javascript" charset="utf-8">
	$gp.showhide('a.personal-options', 'Personal project options &darr;', 'Personal project options &uarr;', 'div.personal-options', '#source-url-template');
	$('div.personal-options').hide();
</script>
<?php gp_tmpl_footer();