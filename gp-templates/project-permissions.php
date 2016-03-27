<?php
gp_title( sprintf( __( 'Permissions &lt; %s &lt; GlotPress', 'glotpress' ), $project->name ) );
gp_breadcrumb_project( $project );
gp_tmpl_header();
?>
<h2><?php _e( 'Permissions', 'glotpress' ); ?></h2>
<h3 id="validators">
	<?php _e( 'Validators', 'glotpress' ); ?>
	<?php if ( count( $permissions ) + count( $parent_permissions ) > 10 ): ?>
	<a href="#add" onclick="jQuery('#user_login').focus(); return false;" class="secondary"><?php _e( 'Add', 'glotpress' ); ?> &rarr;</a>
	<?php endif; ?>
</h3>
	<?php if ( $permissions ): ?>
	<?php if ( $parent_permissions ): ?>
<h4 id="validators"><?php _e( 'Validators for this project', 'glotpress' ); ?></h4>
	<?php endif; ?>
<ul class="permissions">
	<?php foreach( $permissions as $permission ): ?>
		<li>
			<span class="permission-action"><?php _e( 'user', 'glotpress' ); ?></span>
			<span class="user"><?php printf( '<a href="%s">%s</a>', gp_url_profile( $permission->user->user_nicename ), esc_html( $permission->user->user_login ) ); ?></span>
			<span class="permission-action"><?php printf( __( 'can %s strings with locale', 'glotpress' ), esc_html( $permission->action ) ); ?></span>
			<span class="user"><?php echo esc_html( $permission->locale_slug ); ?></span>
			<span class="permission-action"><?php _e( 'and slug', 'glotpress' ); ?></span>
			<span class="user"><?php echo esc_html( $permission->set_slug ); ?></span>
			<?php
			$delete_url = gp_url_join( gp_url_current(), '-delete', $permission->id );
			$delete_url = gp_route_nonce_url( $delete_url, 'delete-project-permission_' . $permission->id );
			?>
			<a href="<?php echo esc_url( $delete_url ); ?>" class="action delete"><?php _e( 'Remove', 'glotpress' ); ?></a>
		</li>
	<?php endforeach; ?>
</ul>
	<?php endif; ?>
	<?php  if ( $parent_permissions ): ?>
<h4 id="validators"><?php _e( 'Validators for parent projects', 'glotpress' ); ?></h4>
<ul class="permissions">
		<?php foreach( $parent_permissions as $permission ): ?>
			<li>
				<span class="permission-action"><?php _e( 'user', 'glotpress' ); ?></span>
				<span class="user"><?php printf( '<a href="%s">%s</a>', gp_url_profile( $permission->user->user_nicename ), esc_html( $permission->user->user_login ) ); ?></span>
				<span class="permission-action"><?php printf(__( 'can %s strings with locale', 'glotpress' ), esc_html( $permission->action )); ?></span>
				<span class="user"><?php echo esc_html( $permission->locale_slug ); ?></span>
				<span class="permission-action"><?php _e( 'and slug', 'glotpress' ); ?></span>
				<span class="user"><?php echo esc_html( $permission->set_slug ); ?></span>
				<span class="permission-action"><?php _e( 'in the project', 'glotpress' ); ?> </span>
				<span class="user"><?php gp_link_project( $permission->project, esc_html( $permission->project->name ) ); ?></span>
			</li>
		<?php endforeach; ?>
</ul>
	<?php endif; ?>
	<?php if ( ! $permissions && !$parent_permissions ): ?>
		<strong><?php _e( 'No validators defined for this project.', 'glotpress' ); ?></strong>
	<?php endif; ?>
<form action="" method="post" class="secondary">
	<h3 id="add"><?php _e( 'Add a validator for this project', 'glotpress' ); ?></h3>
	<dl>
		<dt><label for="user_login"><?php _e( 'Username:', 'glotpress' ); ?></label></dt>
		<dd><input type="text" name="user_login" value="" id="user_login" /></dd>
		<dt><label for="locale"><?php _e( 'Locale:', 'glotpress' ); ?></label></dt>
		<dd><?php echo gp_locales_by_project_dropdown( $project->id, 'locale' ); ?></dd>
		<dt><label for="set-slug"><?php _e( 'Translation set slug:', 'glotpress' ); ?></label></dt>
		<dd><input type="text" name="set-slug" value="default" id="set-slug" /></dd>

		<dt>
			<input type="submit" name="submit" value="<?php esc_attr_e( 'Add', 'glotpress' ); ?>" id="submit" />
			<input type="hidden" name="action" value="add-validator" />
		</dt>
		<?php gp_route_nonce_field( 'add-project-permissions_' . $project->id ); ?>
</form>
<?php
gp_tmpl_footer();
