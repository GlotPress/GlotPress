<?php
gp_title( __('Install &lt; GlotPress') );
gp_breadcrumb( array(
	'install' == $action? __('Install') : __('Upgrade'),
) );

gp_tmpl_header();
?>

<?php if ($errors): ?>
	<?php _e('There were some errors:'); ?>
	<pre>
		<?php echo implode("\n", $errors); ?>
	</pre>
<?php
	else:
		echo $success_message;
	endif;
?>

<?php
// TODO: deny access to scripts folder
if ( $show_htaccess_instructions ): ?>
	<p>
		<?php _e( 'If your <code>.htaccess</code> file were writable, we could do this automatically, but it isn&#8217;t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file.' ); ?>

		<pre><?php echo esc_html( gp_mod_rewrite_rules() ); ?></pre>

		<?php _e( '<strong>The default username is <code>admin</code>, whose password is simply <code>a</code>.</strong>' ); ?>
	</p>
<?php endif; ?>

<?php gp_tmpl_footer();