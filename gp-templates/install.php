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
<?php else: ?>
<?php echo $success_message; ?>
<?php endif; ?>
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
<?php gp_tmpl_footer(); ?>