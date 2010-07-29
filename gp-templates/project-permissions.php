<?php
gp_title( sprintf( __( 'Permissions &lt; %s &lt; GlotPress' ), $project->name ) );
gp_breadcrumb_project( $project );
gp_tmpl_header();
?>
<h2><?php _e('Permissions'); ?></h2>
<h3 id="validators">
	<?php _e('Validators'); ?>
	<?php if ( count( $permissions ) + count( $parent_permissions ) > 10 ): ?>
	<a href="#add" onclick="jQuery('#user_login').focus(); return false;" class="secondary">Add &rarr;</a>
	<?php endif; ?>
</h3>
	<?php if ( $permissions ): ?>
	<?php if ( $parent_permissions ): ?>
<h4 id="validators"><?php _e('Validators for this project'); ?></h4>
	<?php endif; ?>
<ul class="permissions">
	<?php foreach( $permissions as $permission ): ?>
		<li>
			<span class="permission-action"><?php _e('user'); ?></span>
			<span class="user"><?php echo esc_html( $permission->user->user_login ); ?></span>
			<span class="permission-action">can <?php echo esc_html( $permission->action ); ?> strings with locale</span>
			<span class="user"><?php echo esc_html( $permission->locale_slug ); ?></span>
			<span class="permission-action">and slug</span>
			<span class="user"><?php echo esc_html( $permission->set_slug ); ?></span>
			<a href="<?php echo gp_url_join( gp_url_current(), '-delete/'.$permission->id ); ?>" class="action delete"><?php _e('Remove'); ?></a>
		</li>
	<?php endforeach; ?>
</ul>	
	<?php endif; ?>
	<?php  if ( $parent_permissions ): ?>
<h4 id="validators"><?php _e('Validators for parent projects'); ?></h4>
<ul class="permissions">		
		<?php foreach( $parent_permissions as $permission ): ?>
			<li>
				<span class="permission-action"><?php _e('user'); ?></span>
				<span class="user"><?php echo esc_html( $permission->user->user_login ); ?></span>
				<span class="permission-action">can <?php echo esc_html( $permission->action ); ?> strings with locale</span>
				<span class="user"><?php echo esc_html( $permission->locale_slug ); ?></span>
				<span class="permission-action">and slug</span>
				<span class="user"><?php echo esc_html( $permission->set_slug ); ?></span>
				<span class="permission-action">in the project </span>
				<span class="user"><?php gp_link_project( $permission->project, esc_html( $permission->project->name ) ); ?></span>
			</li>
		<?php endforeach; ?>
</ul>				
	<?php endif; ?>
	<?php if ( !$permissions && !$parent_permissions ): ?>
		<strong><?php _e('No validators defined for this project.'); ?></strong>
	<?php endif; ?>
<form action="" method="post" class="secondary">
	<h3 id="add"><?php _e('Add a validator for this project'); ?></h3>
	<dl>
		<dt><label for="user_login"><?php _e('Username:'); ?></label></dt>
		<dd><input type="text" name="user_login" value="" id="user_login" /></dd>
		<dt><label for="locale"><?php _e('Locale:'); ?></label></dt>
		<dd><?php echo gp_locales_dropdown( 'locale' ); ?></dd>
		<dt><label for="set-slug"><?php _e('Translation set slug:'); ?></label></dt>
		<dd><input type="text" name="set-slug" value="default" id="set-slug" /></dd>
				
		<dt>
			<input type="submit" name="submit" value="<?php echo esc_attr(__('Add')); ?>" id="submit" />
			<input type="hidden" name="action" value="add-validator" />
		</dt>
</form>
<?php
gp_tmpl_footer();