<?php
gp_title( sprintf( __('%s &lt; GlotPress'), __('Login') ) );
gp_breadcrumb( array(
	gp_link_home_get(),
	gp_link_login_get(),
) );
gp_tmpl_header();
?>
<form action="" method="post">
	<dl>
		<dt><label for="user_login"><?php _e('Username'); ?></label></dt>
		<dd><input type="text" value="" id="user_login" name="user_login" /></dd>
		
		<dt><label for="user_pass"><?php _e('Password'); ?></label></dt>
		<dd><input type="password" value="" id="user_pass" name="user_pass" /></dd>
		
		<dd><input type="submit" name="submit" value="<?php _e('Login'); ?>" id="submit"></dd>
	</dl>
	<input type="hidden" value="<?php echo esc_attr( gp_get( 'redirect_to' ) ); ?>" id="redirect_to" name="redirect_to" />	
</form>
<script type="text/javascript" charset="utf-8">
	document.getElementById('user_login').focus();
</script>
<?php gp_tmpl_footer(); ?>

