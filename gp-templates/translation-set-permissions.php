<?php
gp_title( sprintf( __( 'Permissions &lt; %s &lt; %s &lt; GlotPress' ), $translation_set->name, $project->name ) );
gp_breadcrumb( array(
	gp_link_project_get( $project, $project->name ),
	$locale->english_name,
	'default' != $translation_set->slug? $translation_set->name : '',
	__('Permissions')
) );
gp_tmpl_header();
?>
<h2><?php _e('Permissions'); ?></h2>
<ul id="translation-set-permissions">
	<?php foreach( $permissions as $permission ): ?>
		<li>
			<span class="permission-action"><?php _e('user'); ?></span>
			<span class="user"><?php echo esc_html( $permission->user->user_login ); ?></span>
			<span class="permission-action">can <?php echo esc_html( $permission->action ); ?></span>
			<a href="<?php echo gp_url_join( gp_url_current(), '_delete/'.$permission->id ); ?>" class="action delete"><?php _e('Remove'); ?></a>
		</li>
	<? endforeach; ?>

	<?php if ( !$permissions ): ?>
		<strong><?php _e('No validators defined for this translation set.'); ?></strong>
	<?php endif; ?>
</ul>

<form action="" method="post" class="secondary">
	<h3><?php _e('Add a validator for this translation set'); ?></h3>
	<p>
		<label for="user_login"><?php _e('Username:'); ?></label>
		<input type="text" name="user_login" value="" id="user_login" />
	</p>
	<p>
		<input type="submit" name="submit" value="<?php esc_attr(__('Add &rarr;')); ?>" id="submit" />
	</p>
	
	<input type="hidden" name="action" value="add-approver" />
</form>
<?php
echo gp_js_focus_on('user_login');
gp_tmpl_footer();