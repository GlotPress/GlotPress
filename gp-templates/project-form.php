<dl>
	<dt><label for="project[name]"><?php _e( 'Name', 'glotpress' ); ?></label></dt>
	<dd><input type="text" name="project[name]" value="<?php echo esc_html( $project->name ); ?>" id="project[name]"></dd>

	<!-- TODO: make slug edit WordPress style -->
	<dt><label for="project[slug]"><?php _e( 'Slug', 'glotpress' ); ?></label></dt>
	<dd>
		<input type="text" name="project[slug]" value="<?php echo esc_html( $project->slug ); ?>" id="project[slug]">
		<small><?php _e( 'If you leave the slug empty, it will be derived from the name.', 'glotpress' ); ?></small>
	</dd>

	<dt><label for="project[description]"><?php _e( 'Description', 'glotpress' ); ?></label> <span class="ternary"><?php _e( 'can include HTML', 'glotpress' ); ?></span></dt>
	<dd><textarea name="project[description]" rows="4" cols="40" id="project[description]"><?php echo esc_html( $project->description ); ?></textarea></dd>

	<dt><label for="project[plurals_type]"><?php _e( 'Plurals Type', 'glotpress' ); ?></label></dt>
	<dd><?php echo gp_plurals_dropdown( 'project[plurals_type]', $project->plurals_type, array() ); // WPCS: XSS ok. ?></dd>

	<dt><label for="project[source_url_template]"><?php _e( 'Source file URL', 'glotpress' ); ?></label></dt>
	<dd>
		<input type="text" value="<?php echo esc_html( $project->source_url_template ); ?>" name="project[source_url_template]" id="project[source_url_template]" style="width: 30em;" />
		<span class="ternary"><?php printf(
			/* translators: 1: %file%, 2: %line%, 3: https://trac.example.org/browser/%file%#L%line% */
			__( 'Public URL to a source file in the project. You can use %1$s and %2$s. Ex. %3$s', 'glotpress' ),
			'<code>%file%</code>',
			'<code>%line%</code>',
			'<code>https://trac.example.org/browser/%file%#L%line%</code>'
		); ?></span>
	</dd>

	<dt><label for="project[parent_project_id]"><?php _e( 'Parent Project', 'glotpress' ); ?></label></dt>
	<dd><?php echo gp_projects_dropdown( 'project[parent_project_id]', $project->parent_project_id, array(), $project->id ); ?></dd>

	<dt><label for="project[active]"><?php _e( 'Active', 'glotpress' ); ?></label> <input type="checkbox" id="project[active]" name="project[active]" <?php gp_checked( $project->active ); ?> /></dt>
</dl>

<?php echo gp_js_focus_on( 'project[name]' ); ?>
