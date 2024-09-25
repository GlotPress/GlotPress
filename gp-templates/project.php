<?php
gp_title(
	sprintf(
		/* translators: %s: Project name. */
		__( '%s &lt; GlotPress', 'glotpress' ),
		esc_html( $project->name )
	)
);
gp_breadcrumb_project( $project );
gp_enqueue_scripts( array( 'gp-editor', 'tablesorter' ) );
$edit_link   = gp_link_project_edit_get( $project, null, array( 'class' => 'button is-small' ) );
$delete_link = gp_link_project_delete_get( $project, null, array( 'class' => 'button is-small' ) );

if ( ! $project->active ) {
	add_filter(
		'gp_breadcrumb_items',
		function( $items ) {
			$items[ count( $items ) - 1 ] .= ' <span class="inactive bubble">' . __( 'Inactive', 'glotpress' ) . '</span>';

			return $items;
		}
	);
}

gp_tmpl_header();
?>

<div class="gp-heading">
	<h2><?php echo esc_html( $project->name ); ?></h2>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $edit_link;
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $delete_link;
	?>
</div>

<?php
/**
 * Filter a project description.
 *
 * @since 1.0.0
 *
 * @param string     $description Project description.
 * @param GP_Project $project     The current project.
 */
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
$project_description = apply_filters( 'gp_project_description', $project->description, $project );

if ( $project_description ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized via filters.
	echo '<div class="project-description">' . $project_description . '</div>';
}
?>

<?php if ( $can_write ) : ?>

<div>
	<a href="#" class="project-actions" id="project-actions-toggle"><?php echo __( 'Project actions', 'glotpress' ) . ' &darr;'; ?></a>
	<div class="project-actions hide-if-js">
		<?php gp_project_actions( $project, $translation_sets ); ?>
	</div>
</div>
<?php endif; ?>

<?php
$project_class = $sub_projects ? 'with-sub-projects' : '';
?>
<div id="project" class="<?php echo esc_attr( $project_class ); ?>">

<?php if ( $translation_sets ) : ?>
<div id="translation-sets">
	<h3><?php _e( 'Translations', 'glotpress' ); ?></h3>
	<table class="gp-table translation-sets">
		<thead>
			<tr>
				<?php

				$translation_sets_columns = array(
					'gp-column-locale'       => __( 'Locale', 'glotpress' ),
					'gp-column-percent'      => _x( '%', 'locale translation percent header', 'glotpress' ),
					'gp-column-translated'   => __( 'Translated', 'glotpress' ),
					'gp-column-fuzzy'        => __( 'Fuzzy', 'glotpress' ),
					'gp-column-untranslated' => __( 'Untranslated', 'glotpress' ),
					'gp-column-waiting'      => __( 'Waiting', 'glotpress' ),
				);
				if ( has_action( 'gp_project_template_translation_set_extra' ) ) {
					$translation_sets_columns['gp-column-extra'] = __( 'Extra', 'glotpress' );
				}

				/**
				 * Fires after the last header column of a translation set.
				 *
				 * @since 4.0.2
				 *
				 * @param GP_Translation_Set $set     The translation set.
				 * @param GP_Project         $project The current project.
				 */
				$translation_sets_columns = apply_filters( 'gp_project_template_translation_set_manage_columns', $translation_sets_columns, $project );

				foreach ( $translation_sets_columns as $class => $label ) {
					?>
					<th class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></th>
					<?php
				}

				?>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $translation_sets as $set ) {

				?>
				<tr>
					<?php

					foreach ( $translation_sets_columns as $column_name => $column ) {

						switch ( $column_name ) {

							case 'gp-column-locale':
								?>
								<td>
									<strong><?php gp_link( gp_url_project( $project, gp_url_join( $set->locale, $set->slug ) ), $set->name_with_locale() ); ?></strong>
									<?php
									if ( $set->current_count && $set->current_count >= $set->all_count * 0.9 ) :
											$percent = floor( $set->current_count / $set->all_count * 100 );
									?>
										<span class="bubble morethan90"><?php echo number_format_i18n( $percent ); ?>%</span>
									<?php endif; ?>
								</td>
								<?php
								break;

							case 'gp-column-percent':
								?>
								<td class="stats percent"><?php echo number_format_i18n( $set->percent_translated ); ?>%</td>
								<?php
								break;

							case 'gp-column-translated':
								?>
								<td class="stats translated" title="translated">
									<?php
									gp_link(
										gp_url_project(
											$project,
											gp_url_join( $set->locale, $set->slug ),
											array(
												'filters[status]' => 'current',
											)
										),
										number_format_i18n( $set->current_count )
									);
									?>
								</td>
								<?php
								break;

							case 'gp-column-fuzzy':
								?>
								<td class="stats fuzzy" title="fuzzy">
									<?php
									gp_link(
										gp_url_project(
											$project,
											gp_url_join( $set->locale, $set->slug ),
											array(
												'filters[status]' => 'fuzzy',
											)
										),
										number_format_i18n( $set->fuzzy_count )
									);
									?>
								</td>
								<?php
								break;

							case 'gp-column-untranslated':
								?>
								<td class="stats untranslated" title="untranslated">
									<?php
									gp_link(
										gp_url_project(
											$project,
											gp_url_join( $set->locale, $set->slug ),
											array(
												'filters[status]' => 'untranslated',
											)
										),
										number_format_i18n( $set->untranslated_count )
									);
									?>
								</td>
								<?php
								break;

							case 'gp-column-waiting':
								?>
								<td class="stats waiting">
									<?php
									gp_link(
										gp_url_project(
											$project,
											gp_url_join( $set->locale, $set->slug ),
											array(
												'filters[status]' => 'waiting',
											)
										),
										number_format_i18n( $set->waiting_count )
									);
									?>
								</td>
								<?php
								break;

							case 'gp-column-extra':
								?>
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
									do_action( 'gp_project_template_translation_set_extra', $set, $project );
									?>
								</td>
								<?php
								break;

							default:
								?>
								<td class="<?php echo esc_attr( $column_name ); ?>">
									<?php

									/**
									 * Fires inside each custom column of the Translation Sets list table.
									 *
									 * @since 4.0.2
									 *
									 * @param string             $column_name   Name of the column.
									 * @param GP_Translation_Set $set           The translation set.
									 * @param GP_Project         $project       The current project.
									 */
									do_action( 'gp_project_template_translation_set_custom_column', $column_name, $set, $project );

									?>
								</td>
								<?php
								break;

						}
					}

					?>
				</tr>
			<?php

			}
			?>
		</tbody>
	</table>
</div>
<?php elseif ( ! $sub_projects ) : ?>
	<p><?php _e( 'There are no translations of this project.', 'glotpress' ); ?></p>
<?php endif; ?>


<?php if ( $sub_projects ) : ?>
<div id="sub-projects">
	<h3><?php _e( 'Sub-projects', 'glotpress' ); ?></h3>
	<dl>
	<?php
		foreach ( $sub_projects as $sub_project ) {
			$sub_project_class = $sub_project->active ? 'project-active' : 'project-inactive';
			?>
			<dt class="<?php echo esc_attr( $sub_project_class ); ?>">
				<?php gp_link_project( $sub_project, esc_html( $sub_project->name ) ); ?>
				<?php gp_link_project_edit( $sub_project, null, array( 'class' => 'button is-small' ) ); ?>
				<?php gp_link_project_delete( $sub_project, null, array( 'class' => 'button is-small' ) ); ?>
				<?php
				if ( ! $sub_project->active ) {
					echo "<span class='inactive bubble'>" . __( 'Inactive', 'glotpress' ) . '</span>';
				}
				?>
			</dt>
			<dd class="<?php echo esc_attr( $sub_project_class ); ?>">
				<?php
				/**
				 * Filter a sub-project description.
				 *
				 * @since 1.0.0
				 *
				 * @param string     $description Sub-project description.
				 * @param GP_Project $project     The sub-project.
				 */
				echo esc_html( gp_html_excerpt( apply_filters( 'gp_sub_project_description', $sub_project->description, $sub_project ), 111 ) );
				?>
			</dd>
			<?php
		}
		?>
	</dl>
</div>
<?php endif; ?>

</div>

<div class="clear"></div>


<script type="text/javascript" charset="utf-8">
	$gp.showhide('a.personal-options', 'div.personal-options', {
		show_text: '<?php echo __( 'Personal project options', 'glotpress' ) . ' &darr;'; ?>',
		hide_text: '<?php echo __( 'Personal project options', 'glotpress' ) . ' &uarr;'; ?>',
		focus: '#source-url-template',
		group: 'personal'
	});
	jQuery('div.personal-options').hide();
	$gp.showhide('a.project-actions', 'div.project-actions', {
		show_text: '<?php echo __( 'Project actions', 'glotpress' ) . ' &darr;'; ?>',
		hide_text: '<?php echo __( 'Project actions', 'glotpress' ) . ' &uarr;'; ?>',
		focus: '#source-url-template',
		group: 'project'
	});
	jQuery(document).ready(function($) {
		$(".translation-sets").tablesorter({
			theme: 'glotpress',
			sortList: [[2,1]],
			headers: {
				0: {
					sorter: 'text'
				}
			}
		});
	});
</script>
<?php
gp_tmpl_footer();
