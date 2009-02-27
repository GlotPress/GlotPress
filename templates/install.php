<?php if ($errors): ?>
There were some errors:
<pre>
	<?php echo implode("\n", $errors); ?>
</pre>
<?php else: ?>
GlotPress database was updated successfully!
<?php endif; ?>
<p>
Please add this to your <code>.htacess</code> file:
<pre>
# BEGIN GlotPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase <?php echo $path . "\n"; ?>
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . <?php echo $path; ?>index.php [L]
</IfModule>
# END GlotPress
	
</pre>
</p>