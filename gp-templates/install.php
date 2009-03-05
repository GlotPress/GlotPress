<?php
gp_title( __('Install < GlotPress') );
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
</p>
<?php gp_tmpl_footer(); ?>