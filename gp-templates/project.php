<?php
gp_title( sprintf( __('%s &lt; GlotPress'), esc_html( $project->name ) ) );
gp_breadcrumb_project( $project );
wp_enqueue_script( 'common' );
$edit_link = gp_link_project_edit_get( $project, '(edit)' );
$parity = gp_parity_factory();
if ( $project->active ) add_filter( 'gp_breadcrumb', lambda( '$s', '$s . "<span class=\\"active bubble\\">Active</span>"' ) );
gp_tmpl_header();
?>
<h2><?php echo esc_html( $project->name ); ?> <?php echo $edit_link; ?></h2>
<p class="description">
	<?php echo $project->description; ?>
</p>

<?php if ( $can_write ): ?>

<div class="actionlist">
	<a href="#" class="project-actions" id="project-actions-toggle"><?php _e('Project actions &darr;'); ?></a>
	<div class="project-actions hide-if-js">
		<ul>
			<li><?php gp_link( gp_url_project( $project, 'import-originals' ), __( 'Import originals' ) ); ?></li>
			<li><?php gp_link( gp_url_project( $project, array( '-permissions' ) ), __('Permissions') ); ?></li>
			<li><?php gp_link( gp_url_project( '', '-new', array('parent_project_id' => $project->id) ), __('New Sub-Project') ); ?></li>
			<li><?php gp_link( gp_url( '/sets/-new', array( 'project_id' => $project->id ) ), __('New Translation Set') ); ?></li>
			<li><?php gp_link( gp_url_project( $project, array( '-mass-create-sets' ) ), __('Mass-create Translation Sets') ); ?></li>
			<?php if ( $translation_sets ): ?>
			<li>
				<a href="#" class="personal-options" id="personal-options-toggle"><?php _e('Personal project options &darr;'); ?></a>
				<div class="personal-options">
					<form action="<?php echo gp_url_project( $project, '-personal' ); ?>" method="post">
					<dl>
						<dt><label for="source-url-template"><?php _e('Source file URL');  ?></label></dt>
						<dd>
							<input type="text" value="<?php echo esc_html( $project->source_url_template() ); ?>" name="source-url-template" id="source-url-template" />
							<small><?php _e('URL to a source file in the project. You can use <code>%file%</code> and <code>%line%</code>. Ex. <code>http://trac.example.org/browser/%file%#L%line%</code>'); ?></small>
						</dd>
					</dl>
					<p>
						<input type="submit" name="submit" value="<?php echo esc_attr(__('Save &rarr;')); ?>" id="save" />
						<a class="ternary" href="#" onclick="jQuery('#personal-options-toggle').click();return false;"><?php _e('Cancel'); ?></a>
					</p>		
					</form>
				</div>
			</li>
		<?php endif; ?>
		</ul>
	</div>
</div>
<?php endif; ?>


<?php if ($sub_projects): ?>
<div id="sub-projects">
<h3><?php _e('Sub-projects'); ?></h3>
<dl>
<?php foreach($sub_projects as $sub_project): ?>
	<dt>
		<?php gp_link_project( $sub_project, esc_html( $sub_project->name ) ); ?>
		<?php gp_link_project_edit( $sub_project, null, array( 'class' => 'bubble' ) ); ?>
		<?php if ( $sub_project->active ) echo "<span class='active bubble'>Active</span>"; ?>
	</dt>
	<dd>
		<?php echo esc_html( gp_html_excerpt( $sub_project->description, 111 ) ); ?>
	</dd>
<?php endforeach; ?>
</dl>
</div>
<?php endif; ?>

<?php if ( $translation_sets ): ?>
<div id="translation-sets">
	<h3>Translations</h3>
	<table class="translation-sets">
		<thead>
			<tr>
				<th><?php _e( 'Language' ); ?></th>
				<th><?php echo _x( '%', 'language translation percent header' ); ?></th>
				<th><?php _e( 'Translated' ); ?></th>
				<th><?php _e( 'Untranslated' ); ?></th>
				<th><?php _e( 'Waiting' ); ?></th>
				<th><?php _e( 'Extra' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach( $translation_sets as $set ): ?>
			<tr class="<?php echo $parity(); ?>">
				<td>
					<strong><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ) ), $set->name_with_locale() ); ?></strong>
					<?php if ($set->current_count >= $set->all_count * 0.9 ): ?>
						<span class="bubble morethan90">90%+</span>
					<?php endif; ?>
				</td>
				<td class="stats percent"><?php echo $set->percent_translated; ?></td>
				<td class="stats translated" title="translated"><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ),
							array('filters[translated]' => 'yes', 'filters[status]' => 'current') ), $set->current_count );; ?></td>
				<td class="stats untranslated" title="untranslated"><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ),
							array('filters[status]' => 'untranslated' ) ), $set->untranslated_count ); ?></td>
				<td class="stats waiting"><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ),
							array('filters[translated]' => 'yes', 'filters[status]' => 'waiting') ), $set->waiting_count ); ?></td>
				<td>
					<?php do_action( 'project_template_translation_set_extra', $set, $project ); ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php elseif ( !$sub_projects ): ?>
	<p><?php _e('There are no translations of this project.'); ?></p>
<?php endif; ?>
<div class="clear"></div>


<script type="text/javascript" charset="utf-8">
	$gp.showhide('a.personal-options', 'div.personal-options', {
		show_text: 'Personal project options &darr;',
		hide_text: 'Personal project options &uarr;',
		focus: '#source-url-template',
		group: 'personal'
	});
	$('div.personal-options').hide();
	$gp.showhide('a.project-actions', 'div.project-actions', {
		show_text: 'Project actions &darr;',
		hide_text: 'Project actions &uarr;',
		focus: '#source-url-template',
		group: 'project'
	});
</script>
<?php gp_tmpl_footer();
