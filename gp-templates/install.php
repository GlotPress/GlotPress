<?php
gp_title( __('Install &lt; GlotPress') );
gp_breadcrumb( array(
	gp_link_home_get(),
	'install' == $action? __('Install') : __('Upgrade'),
) );

gp_tmpl_header();
?>
<?php if ($errors): ?>
There were some errors:
<pre>
	<?php echo implode("\n", $errors); ?>
</pre>
<?php
	else:
		echo $success_message;
	endif;
	// TODO: deny access for scripts
	if ( $show_htaccess_instructions ): ?>
<p>
Please add this to your <code>.htacess</code> file:
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
<strong>The default username is <code>admin</code>, whose password is simply <code>a</code>.</strong>
</p>
<?php endif; ?>
<?php gp_tmpl_footer(); ?>