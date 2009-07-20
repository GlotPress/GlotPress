<dl>
	<dt><label for="project[name]"><?php _e('Name');  ?></label></dt>
	<dd><input type="text" name="project[name]" value="<?php echo gp_h( $project->name ); ?>" id="project[name]"></dd>
	
	<!-- TODO: make slug edit WordPress style -->
	<dt><label for="project[slug]"><?php _e('Slug');  ?></label></dt>
	<dd><input type="text" name="project[slug]" value="<?php echo gp_h( $project->slug ); ?>" id="project[slug]"></dd>

	<dt><label for="project[description]"><?php _e('Description');  ?></label></dt>
	<dd><textarea name="project[description]" rows="4" cols="40" id="project[slug]"><?php echo gp_h( $project->description ); ?></textarea></dd>

	<dt><label for="project[parent_project_id]"><?php _e('Parent Project');  ?></label></dt>
	<dd><?php echo gp_select( 'project[parent_project_id]', $all_project_options, $project->parent_project_id); ?></dd>
</dl>
<?php echo gp_js_focus_on( 'project[name]' ); ?>