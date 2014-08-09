<?php
gp_title( sprintf( __( 'Projects translated to %s &lt; GlotPress' ),  esc_html( $locale->english_name ) ) );
gp_breadcrumb( array(
	gp_link_get( '/languages', __( 'Locales' ) ),
	esc_html( $locale->english_name )
) );
gp_tmpl_header();
?>

	<h2><?php printf( __( 'Active Projects translated to %s' ), esc_html( $locale->english_name ) ); ?></h2>
	<?php
	if ( empty( $projects_data ) ) {
		_e( 'No active projects found.' );
	}
	?>

	<?php foreach ( $projects_data as $project_id => $sub_projects ) : ?>
	<h3><?php echo esc_html( $projects[ $project_id ]->name ); ?></h3>
	<div class="language-project">
		<div class="project-description">
			<?php echo esc_html( gp_html_excerpt( $projects[ $project_id ]->description, 150 ) ); ?>
		</div>
		<table class="language-sub-projects">
			<thead>
				<tr>
					<th class="header"><?php _e( 'Sub Project' ); ?></th>
					<th class="header"><?php _e( 'Set' ); ?></th>
					<th><?php echo _x( '%', 'language translation percent header' ); ?></th>
					<th><?php _e( 'Translated' ); ?></th>
					<th><?php _e( 'Fuzzy' ); ?></th>
					<th><?php _e( 'Untranslated' ); ?></th>
					<th><?php _e( 'Waiting' ); ?></th>
				</tr>
			</thead>
		<?php foreach ( $sub_projects as $sub_project_id => $sets ) : ?>
			<tbody>
				<tr>
					<th class="sub-project" rowspan="<?php echo count( $sets ) + 1; ?>">
						<?php echo esc_html( $projects[$sub_project_id]->name ); ?>
					</th>
				</tr>
			<?php foreach ( $sets['sets'] as $set_slug => $set_data ) : ?>
				<tr>
					<td class="set-name">
						<strong><?php gp_link( gp_url_project( $sets['project'], gp_url_join( $locale->slug, $set_slug ) ), $set_data->name ); ?></strong>
						<?php if ( $set_data->current_count && $set_data->current_count >= $set_data->all_count * 0.9 ):
							$percent = floor( $set_data->current_count / $set_data->all_count * 100 );
						?>
						<span class="bubble morethan90"><?php echo $percent; ?>%</span>
						<?php endif;?>
					</td>
					<td class="stats percent">
						<?php
							if ( $set_data->current_count ) {
								echo( floor( absint( $set_data->current_count ) / absint( $set_data->all_count ) * 100 ) );
							} else {
								echo '0';
							}
						?>%
						</td>
					<td class="stats translated"><?php gp_link( gp_url_project( $sets['project'], gp_url_join( $locale->slug, $set_slug ), array('filters[translated]' => 'yes', 'filters[status]' => 'current') ), absint( $set_data->current_count ) ); ?></td>
					<td class="stats fuzzy"><?php gp_link( gp_url_project( $sets['project'], gp_url_join( $locale->slug, $set_slug ), array('filters[status]' => 'fuzzy' ) ), absint( $set_data->fuzzy_count ) ); ?></td>
					<td class="stats untranslated"><?php gp_link( gp_url_project( $sets['project'], gp_url_join( $locale->slug, $set_slug ), array('filters[status]' => 'untranslated' ) ), absint( $set_data->all_count ) -  absint( $set_data->current_count ) ); ?></td>
					<td class="stats waiting"><?php gp_link( gp_url_project( $sets['project'], gp_url_join( $locale->slug, $set_slug ), array('filters[translated]' => 'yes', 'filters[status]' => 'waiting') ), absint( $set_data->waiting_count ) ); ?></td>
				</tr>
			<?php endforeach; //sub project slugs ?>
		<?php endforeach;  //sub projects ?>
			</tbody>
		</table>
	</div>
	<?php endforeach; //top projects ?>

	<p class="actionlist secondary">
		<?php gp_link( '/projects', __('All projects') ); ?>
	</p>

<?php gp_tmpl_footer();