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
	<?php foreach( $permissions as $permission ):
				$user_link = '<span class="user">' . sprintf( '<a href="%s">%s</a>', gp_url_profile( $permission->user->user_nicename ), esc_html( $permission->user->user_login ) ) . '</span>';
				$remove_link = '<a href="' . gp_url_join( gp_url_current(), '-delete/'.$permission->id ) . '" class="action delete">' . __('Remove', 'glotpress' ) . '</a>';
	?>
		<li>
			<span class="permission-action"><?php echo sprintf( __( 'user %s can %s strings with locale %s and slug %s %s', 'glotpress' ), $user_link, esc_html( $permission->action ), '<span class="user">' . esc_html( $permission->locale_slug ). '</span>', '<span class="user">' . esc_html( $permission->set_slug ) . '</span>', $remove_link ); ?></span>
		</li>
	<?php endforeach; ?>
</ul>
	<?php endif; ?>
	<?php  if ( $parent_permissions ): ?>
<h4 id="validators"><?php _e( 'Validators for parent projects', 'glotpress' ); ?></h4>
<ul class="permissions">
		<?php foreach( $parent_permissions as $permission ): 
				$user_link = '<span class="user">' . sprintf( '<a href="%s">%s</a>', gp_url_profile( $permission->user->user_nicename ), esc_html( $permission->user->user_login ) ) . '</span>';
		?>
			<li>
				<span class="permission-action"><?php echo sprintf( __( 'user %s can %s strings with locale %s and slug %s in the project %s', 'glotpress' ), $user_link, esc_html( $permission->action ), '<span class="user">' . esc_html( $permission->locale_slug ). '</span>', '<span class="user">' . esc_html( $permission->set_slug ) . '</span>', '<span class="user">' . gp_link_project( $permission->project, esc_html( $permission->project->name ) ) . '</span>'  ); ?></span>
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
</form>
<?php
gp_tmpl_footer();
