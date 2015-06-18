<?php
gp_title( __('Install &lt; GlotPress') );
gp_breadcrumb( array(
	'upgrade' == $action? __('Upgrade') : __('Install'),
) );
wp_enqueue_style( 'install' );
gp_tmpl_header();
?>

<?php
if ( isset( $errors ) ) {
	_e('There were some errors:');

	echo '<pre class="message">';
	echo implode( "\n", $errors );
	echo '</pre>';
}
else if ( isset( $success_message ) ) {
	echo '<p>' . $success_message . '</p>';
}
?>

<?php
// TODO: deny access to scripts folder
if ( $show_htaccess_instructions ): ?>
	<p>
		<?php _e( 'If your <code>.htaccess</code> file were writable, we could do this automatically, but it isn&#8217;t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file.' ); ?>

		<pre><?php echo esc_html( gp_mod_rewrite_rules() ); ?></pre>
	</p>
<?php endif; ?>

<?php if ( $action == 'install' ): ?>
	<form id="setup" method="post" action="install.php">
		<table class="form-table">
			<tr>
				<th scope="row"><label for="user_login"><?php _e('Username'); ?></label></th>
				<td>
					<input name="user_name" type="text" id="user_login" size="25" value="<?php echo esc_attr( sanitize_user( $user_name, true ) ); ?>" />
					<p><?php _e( 'Usernames can have only alphanumeric characters, spaces, underscores, hyphens, periods and the @ symbol.' ); ?></p>
				</td>
			</tr>
				<tr>
					<th scope="row">
						<label for="admin_password"><?php _e('Password, twice'); ?></label>
						<p><?php _e('A password will be automatically generated for you if you leave this blank.'); ?></p>
					</th>
					<td>
						<input name="admin_password" type="password" id="pass1" size="25" value="" />
						<input name="admin_password2" type="password" id="pass2" size="25" value="" />
						<p><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp; ).'); ?></p>
					</td>
				</tr>
			<tr>
				<th scope="row"><label for="admin_email"><?php _e( 'Email Address' ); ?></label></th>
				<td><input name="admin_email" type="text" id="admin_email" size="25" value="<?php echo esc_attr( $admin_email ); ?>" />
					<p><?php _e( 'Double-check your email address before continuing.' ); ?></p></td>
			</tr>
		</table>
		<p class="step"><input type="submit" name="Submit" value="<?php esc_attr_e( 'Install GlotPress' ); ?>" /></p>
	</form>
<?php endif; ?>

<?php gp_tmpl_footer();
