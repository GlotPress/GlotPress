<?php
gp_title(
	sprintf(
		/* translators: %s: Project name. */
		__( 'Permissions &lt; %s &lt; GlotPress', 'glotpress' ),
		$project->name
	)
);
gp_breadcrumb_project( $project );
gp_tmpl_header();
?>
<h2><?php _e( 'Permissions', 'glotpress' ); ?></h2>
<h3 id="validators">
	<?php _e( 'Validators', 'glotpress' ); ?>
	<?php if ( count( $permissions ) + count( $parent_permissions ) > 1 ) : ?>
	<a href="#add" onclick="jQuery('#user_login').focus(); return false;"><?php _e( 'Add', 'glotpress' ); ?> &rarr;</a>
	<?php endif; ?>
</h3>
	<?php if ( $permissions ) : ?>
	<?php if ( $parent_permissions ) : ?>
<h4 id="validators"><?php _e( 'Validators for this project', 'glotpress' ); ?></h4>
	<?php endif; ?>

	<table class="gp-table permissions">
		<thead>
			<tr>
				<th class="gp-column-user"><?php _e( 'User', 'glotpress' ); ?></th>
				<th class="gp-column-permission"><?php _e( 'Permission', 'glotpress' ); ?></th>
				<th class="gp-column-locale"><?php _e( 'Locale', 'glotpress' ); ?></th>
				<th class="gp-column-slug"><?php _e( 'Slug', 'glotpress' ); ?></th>
				<th class="gp-column-actions">&mdash;</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $permissions as $permission ) : ?>
				<tr>
					<td class="user"><?php printf( '<a href="%s">%s</a>', esc_url( gp_url_profile( $permission->user->user_nicename ) ), esc_html( $permission->user->user_login ) ); ?></td>
					<td><?php echo esc_html( $permission->action ); ?></td>
					<td><?php echo esc_html( $permission->locale_slug ); ?></td>
					<td><?php echo esc_html( $permission->set_slug ); ?></td>
					<td><a href="<?php echo esc_url( gp_route_nonce_url( gp_url_join( gp_url_current(), '-delete/' . $permission->id ), 'delete-project-permission_' . $permission->id ) ); ?>" class="action delete"><?php _e( 'Delete', 'glotpress' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
	<?php if ( $parent_permissions ) : ?>
<h4 id="validators"><?php _e( 'Validators for parent projects', 'glotpress' ); ?></h4>
	<table class="gp-table permissions">
		<thead>
			<tr>
				<th class="gp-column-user"><?php _e( 'User', 'glotpress' ); ?></th>
				<th class="gp-column-permission"><?php _e( 'Permission', 'glotpress' ); ?></th>
				<th class="gp-column-locale"><?php _e( 'Locale', 'glotpress' ); ?></th>
				<th class="gp-column-slug"><?php _e( 'Slug', 'glotpress' ); ?></th>
				<th class="gp-column-parent"><?php _e( 'Parent', 'glotpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $parent_permissions as $permission ) : ?>
				<tr>
					<td><?php printf( '<a href="%s">%s</a>', esc_url( gp_url_profile( $permission->user->user_nicename ) ), esc_html( $permission->user->user_login ) ); ?></td>
					<td><?php echo esc_html( $permission->action ); ?></td>
					<td><?php echo esc_html( $permission->locale_slug ); ?></td>
					<td><?php echo esc_html( $permission->set_slug ); ?></td>
					<td><?php gp_link_project( $permission->project, esc_html( $permission->project->name ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
	<?php if ( ! $permissions && ! $parent_permissions ) : ?>
		<strong><?php _e( 'No validators defined for this project.', 'glotpress' ); ?></strong>
	<?php endif; ?>
<form action="" method="post">
	<h3 id="add"><?php _e( 'Add a validator for this project', 'glotpress' ); ?></h3>
	<dl>
		<dt><label for="user_login"><?php _e( 'Username:', 'glotpress' ); ?></label></dt>
		<dd><input type="text" name="user_login" value="" id="user_login" /></dd>
		<dt><label for="locale"><?php _e( 'Locale:', 'glotpress' ); ?></label></dt>
		<dd><?php echo gp_locales_by_project_dropdown( $project->id, 'locale' ); ?></dd>
		<dt><label for="set-slug"><?php _e( 'Translation set slug:', 'glotpress' ); ?></label></dt>
		<dd><input type="text" name="set-slug" value="default" id="set-slug" /></dd>
	</dl>

	<div class="button-group">
		<input class="button is-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Add', 'glotpress' ); ?>" id="submit" />
		<a class="button is-link" href="<?php echo esc_url( gp_url_project( $project ) ); ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a>
	</div>

	<input type="hidden" name="action" value="add-validator" />
	<?php gp_route_nonce_field( 'add-project-permissions_' . $project->id ); ?>
</form>
<?php
gp_tmpl_footer();
