<?php
gp_title( sprintf( __( '%s &lt; GlotPress', 'glotpress' ), esc_html( $project->name ) ) );
gp_breadcrumb_project( $project );
gp_enqueue_script( array( 'gp-editor', 'tablesorter' ) );
$edit_link = gp_link_project_edit_get( $project, __( '(edit)', 'glotpress' ) );

if ( $project->active ) {
	add_filter( 'gp_breadcrumb_items', function( $items ) {
		$items[ count($items) - 1 ] .= ' <span class="active bubble">' . __( 'Active', 'glotpress' ) . '</span>';

		return $items;
	} );
}

gp_tmpl_header();
?>
<h2><?php echo esc_html( $project->name ); ?> <?php echo $edit_link; ?></h2>
<p class="description">
	<?php
	/**
	 * Filter a project description.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $description Project description.
	 * @param GP_Project $project     The current project.
	 */
	echo apply_filters( 'gp_project_description', $project->description, $project );?>
</p>

<?php if ( $can_write ): ?>

<div class="actionlist">
	<a href="#" class="project-actions" id="project-actions-toggle"><?php _e( 'Project actions &darr;', 'glotpress' ); ?></a>
	<div class="project-actions hide-if-js">
		<?php gp_project_actions( $project, $translation_sets ); ?>
	</div>
</div>
<?php endif; ?>

<div id="project" <?php if ( $sub_projects ) { echo ' class="with-sub-projects"'; } ?>>

<?php if ( $translation_sets ): ?>
<div id="translation-sets">
	<h3><?php _e( 'Translations', 'glotpress' );?></h3>
	<table class="translation-sets tablesorter">
		<thead>
			<tr>
				<th><?php _e( 'Locale', 'glotpress' ); ?></th>
				<th><?php _ex( '%', 'locale translation percent header', 'glotpress' ); ?></th>
				<th><?php _e( 'Translated', 'glotpress' ); ?></th>
				<th><?php _e( 'Fuzzy', 'glotpress' ); ?></th>
				<th><?php _e( 'Untranslated', 'glotpress' ); ?></th>
				<th><?php _e( 'Waiting', 'glotpress' ); ?></th>
				<?php if ( has_action( 'gp_project_template_translation_set_extra' ) ) : ?>
				<th class="extra"><?php _e( 'Extra', 'glotpress' ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>
		<?php foreach( $translation_sets as $set ): ?>
			<tr>
				<td>
					<strong><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ) ), $set->name_with_locale() ); ?></strong>
					<?php if ( $set->current_count && $set->current_count >= $set->all_count * 0.9 ):
							$percent = floor( $set->current_count / $set->all_count * 100 );
					?>
						<span class="bubble morethan90"><?php echo $percent; ?>%</span>
					<?php endif; ?>
				</td>
				<td class="stats percent"><?php echo $set->percent_translated; ?>%</td>
				<td class="stats translated" title="translated"><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ),
							array('filters[translated]' => 'yes', 'filters[status]' => 'current') ), $set->current_count ); ?></td>
				<td class="stats fuzzy" title="fuzzy"><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ),
						array('filters[status]' => 'fuzzy') ), $set->fuzzy_count ); ?></td>
				<td class="stats untranslated" title="untranslated"><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ),
							array('filters[status]' => 'untranslated' ) ), $set->untranslated_count ); ?></td>
				<td class="stats waiting"><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ),
							array('filters[translated]' => 'yes', 'filters[status]' => 'waiting') ), $set->waiting_count ); ?></td>
				<?php if ( has_action( 'gp_project_template_translation_set_extra' ) ) : ?>
				<td class="extra">
					<?php
					/**
					 * Fires in an extra information column of a translation set.
					 *
					 * @since 1.0.0
					 *
					 * @param GP_Translation_Set $set     The translation set.
					 * @param GP_Project         $project The current project.
					 */
					do_action( 'gp_project_template_translation_set_extra', $set, $project ); ?>
				</td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php elseif ( !$sub_projects ): ?>
	<p><?php _e( 'There are no translations of this project.', 'glotpress' ); ?></p>
<?php endif; ?>


<?php if ($sub_projects): ?>
<div id="sub-projects">
<h3><?php _e( 'Sub-projects', 'glotpress' ); ?></h3>
<dl>
<?php foreach($sub_projects as $sub_project): ?>
	<dt>
		<?php gp_link_project( $sub_project, esc_html( $sub_project->name ) ); ?>
		<?php gp_link_project_edit( $sub_project, null, array( 'class' => 'bubble' ) ); ?>
		<?php if ( $sub_project->active ) echo "<span class='active bubble'>" . __( 'Active', 'glotpress' ) . "</span>"; ?>
	</dt>
	<dd>
		<?php
		/**
		 * Filter a sub-project description.
		 *
		 * @since 1.0.0
		 *
		 * @param string     $description Sub-project description.
		 * @param GP_Project $project     The sub-project.
		 */
		echo esc_html( gp_html_excerpt( apply_filters( 'gp_sub_project_description', $sub_project->description, $sub_project ), 111 ) ); ?>
	</dd>
<?php endforeach; ?>
</dl>
</div>
<?php endif; ?>

</div>

<div class="clear"></div>


<script type="text/javascript" charset="utf-8">
	$gp.showhide('a.personal-options', 'div.personal-options', {
		show_text: '<?php _e( 'Personal project options &darr;', 'glotpress' ); ?>',
		hide_text: '<?php _e( 'Personal project options &uarr;', 'glotpress' ); ?>',
		focus: '#source-url-template',
		group: 'personal'
	});
	jQuery('div.personal-options').hide();
	$gp.showhide('a.project-actions', 'div.project-actions', {
		show_text: '<?php _e( 'Project actions &darr;', 'glotpress' ); ?>',
		hide_text: '<?php _e( 'Project actions &uarr;', 'glotpress' ); ?>',
		focus: '#source-url-template',
		group: 'project'
	});
	jQuery(document).ready(function($) {
		$(".translation-sets").tablesorter({
			headers: {
				0: {
					sorter: 'text'
				}
			},
			widgets: ['zebra']
		});
	});
</script>
<?php gp_tmpl_footer();
