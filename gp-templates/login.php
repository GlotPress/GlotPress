<?php
gp_title( sprintf( __('%s &lt; GlotPress'), __('Login') ) );
gp_breadcrumb( array(
	__('Login'),
) );
gp_tmpl_header();
?>
	<h2>Login</h2>
	<?php do_action( 'before_login_form' ); ?>
	<form action="<?php echo gp_url_ssl( gp_url_current() ); ?>" method="post">
	<dl>
		<dt><label for="user_login"><?php _e('Username'); ?></label></dt>
		<dd><input type="text" value="" id="user_login" name="user_login" /></dd>
		
		<dt><label for="user_pass"><?php _e('Password'); ?></label></dt>
		<dd><input type="password" value="" id="user_pass" name="user_pass" /></dd>
	</dl>
	<p><input type="submit" name="submit" value="<?php _e('Login'); ?>" id="submit"></p>
	<input type="hidden" value="<?php echo esc_attr( gp_get( 'redirect_to' ) ); ?>" id="redirect_to" name="redirect_to" />
</form>
<?php do_action( 'after_login_form' ); ?>
<script type="text/javascript" charset="utf-8">
	document.getElementById('user_login').focus();
</script>
<?php gp_tmpl_footer();
