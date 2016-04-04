<dl>
	<dt><label for="set[locale]"><?php _e( 'Locale', 'glotpress' ); ?></label></dt>
	<dd>
		<?php echo gp_locales_dropdown( 'set[locale]', $set->locale ); ?>
		<a href="#" id="copy"><?php _e( 'Use as name', 'glotpress' ); ?></a>
	</dd>

	<dt><label for="set[name]"><?php _e( 'Name', 'glotpress' ); ?></label></dt>
	<dd><input type="text" name="set[name]" value="<?php echo esc_html( $set->name ); ?>" id="set[name]"></dd>

	<!-- TODO: make slug edit WordPress style -->
	<dt><label for="set[slug]"><?php _e( 'Slug', 'glotpress' ); ?></label></dt>
	<dd><input type="text" name="set[slug]" value="<?php echo esc_html( $set->slug? $set->slug : 'default' ); ?>" id="set[slug]"></dd>

	<dt><label for="set[project_id]"><?php _e( 'Project', 'glotpress' ); ?></label></dt>
	<dd><?php echo gp_projects_dropdown( 'set[project_id]', $set->project_id, null, null, true ); ?></dd>
</dl>
<?php echo gp_js_focus_on( 'set[locale]' ) . "\n"; ?>
<script type="text/javascript">
	jQuery('#copy').click(function() {
		var text = jQuery('#set\\[locale\\] option:selected').html().replace(/^\S+\s+\S+\s+/, '').replace(/&mdash|—/, '');
		jQuery('#set\\[name\\]').val(text);
		return false;
	});
</script>
