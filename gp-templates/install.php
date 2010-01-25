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
		<?php _e('Please add this to your <code>.htaccess</code> file:'); ?>
		<pre>
		# BEGIN GlotPress
		&lt;IfModule mod_rewrite.c&gt;
		RewriteEngine On
		RewriteBase <?php echo $path . "\n"; ?>
		RewriteCond %{REQUEST_FILENAME} !-f
		RewriteCond %{REQUEST_FILENAME} !-d
		RewriteRule . <?php echo $path; ?>index.php [L]
		&lt;/IfModule&gt;
		# END GlotPress
		</pre>
		<?php _e('<strong>The default username is <code>admin</code>, whose password is simply <code>a</code>.</strong>'); ?>
	</p>
<?php endif; ?>
	
<?php gp_tmpl_footer(); ?>