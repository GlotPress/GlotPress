<table class="glotpress form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row"><label for="project[name]"><?php _e( 'Name', 'glotpress' ); ?></label></th>
			<td>
				<input type="text" name="project[name]" value="<?php echo esc_html( $project->name ); ?>" id="project[name]">
			</td>
		</tr>

		<tr>
			<th scope="row"><label for="project[slug]"><?php _e( 'Slug', 'glotpress' ); ?></label></th>
			<td>
				<input type="text" name="project[slug]" value="<?php echo esc_attr( urldecode( $project->slug ) ); ?>" id="project[slug]">
				<div><small><?php _e( 'If you leave the slug empty, it will be derived from the name.', 'glotpress' ); ?></small></div>
			</td>
		</tr>

		<tr >
			<th scope="row"><label for="project[description]"><?php _e( 'Description', 'glotpress' ); ?></label> </th>
			<td>
				<textarea name="project[description]" rows="4" cols="40" id="project[description]"><?php echo esc_html( $project->description ); ?></textarea>
				<div><small><span class="ternary"><?php _e( 'can include HTML', 'glotpress' ); ?></span></small></div>
			</td>
		</tr>

		<tr>
			<th scope="row"><label for="project[source_url_template]"><?php _e( 'Source file URL', 'glotpress' ); ?></label></th>
			<td>
				<input type="text" value="<?php echo esc_html( $project->source_url_template ); ?>" name="project[source_url_template]" id="project[source_url_template]" style="width: 30em;" />
		
				<div>
					<span class="ternary"><small>
					<?php
					printf(
						/* translators: 1: %file%, 2: %line%, 3: https://trac.example.org/browser/%file%#L%line% */
						__( 'Public URL to a source file in the project. You can use %1$s and %2$s. Ex. %3$s', 'glotpress' ),
						'<code>%file%</code>',
						'<code>%line%</code>',
						'<code>https://trac.example.org/browser/%file%#L%line%</code>'
					);
					?>
				</small></span>
			<small><?php _e( 'If you leave the slug empty, it will be derived from the name.', 'glotpress' ); ?></small></<div>
			</td>
		</tr>
				
	
			
		
		<tr>
			<th scope="row"><label for="project[active]"><?php _e( 'Active', 'glotpress' ); ?></label> </th>
			<td>
				<input type="checkbox" id="project[active]" name="project[active]" <?php gp_checked( $project->active ); ?> />	
			</td>
		</tr>
			
		<tr>
			<th scope="row">
				<label for="project[parent_project_id]"><?php _e( 'Parent Project', 'glotpress' ); ?>
			</th>
			<td>
				<?php echo gp_projects_dropdown( 'project[parent_project_id]', $project->parent_project_id, array(), $project->id ); ?>
			</td>
		</tr>
				
		
</tbody></table>

<?php echo gp_js_focus_on( 'project[name]' ); ?>
