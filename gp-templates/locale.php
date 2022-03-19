<?php
gp_title(
	sprintf(
		/* translators: %s: Locale english name. */
		__( 'Projects translated to %s &lt; GlotPress', 'glotpress' ),
		esc_html( $locale->english_name )
	)
);

$breadcrumb   = array();
$breadcrumb[] = gp_link_get( gp_url( '/languages' ), __( 'Locales', 'glotpress' ) );
if ( 'default' == $current_set_slug ) {
	$breadcrumb[] = esc_html( $locale->english_name );
} else {
	$breadcrumb[] = gp_link_get( gp_url_join( gp_url( '/languages' ), $locale->slug ), esc_html( $locale->english_name ) );
	$breadcrumb[] = $set_list[ $current_set_slug ];
}
gp_breadcrumb( $breadcrumb );
gp_tmpl_header();
?>

<div class="gp-heading">
	<h2>
		<?php
		printf(
			/* translators: %s: Locale english name. */
			esc_html__( 'Active Projects translated to %s', 'glotpress' ),
			esc_html( $locale->english_name )
		);
		?>
	</h2>
	<?php if ( $locale_glossary ) : ?>
		<a href="<?php echo esc_url( gp_url_join( gp_url( '/languages' ), $locale->slug, $current_set_slug, 'glossary' ) ); ?>" class="glossary-link"><?php _e( 'Locale Glossary', 'glotpress' ); ?></a>
	<?php elseif ( $can_create_locale_glossary ) : ?>
		<a href="<?php echo esc_url( gp_url_join( gp_url( '/languages' ), $locale->slug, $current_set_slug, 'glossary' ) ); ?>" class="glossary-link"><?php _e( 'Create Locale Glossary', 'glotpress' ); ?></a>
	<?php endif; ?>
</div>

<?php if ( count( $set_list ) > 1 ) : ?>
	<p class="actionlist">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in GP_Route_Locale::single().
		echo implode( ' &bull; ', $set_list );
		?>
	</p>
<?php endif; ?>

<?php
if ( empty( $projects_data ) ) {
	_e( 'No active projects found.', 'glotpress' );
}
?>

<?php
foreach ( $projects_data as $project_id => $sub_projects ) :
	$count_sub_projects = count( $sub_projects );
	$has_sub_projects   = $count_sub_projects > 1;
	?>
	<div class="locale-project">
		<h3><?php echo esc_html( $projects[ $project_id ]->name ); ?></h3>
		<table class="locale-sub-projects">
			<thead>
			<tr>
				<th rowspan="<?php echo esc_attr( $count_sub_projects ); ?>"><?php _e( 'Project / Stats', 'glotpress' ); ?></th>
				<th><?php _e( 'Set / Sub Project', 'glotpress' ); ?></th>
				<th><?php _e( 'Translated', 'glotpress' ); ?></th>
				<th><?php _e( 'Fuzzy', 'glotpress' ); ?></th>
				<th><?php _e( 'Untranslated', 'glotpress' ); ?></th>
				<th><?php _e( 'Waiting', 'glotpress' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $sub_projects as $sub_project_id => $data ) : ?>
				<tr>
				<th class="sub-project" rowspan="<?php echo count( $data['sets'] ); ?>">
					<?php
					if ( $has_sub_projects ) {
						echo esc_html( $projects[ $sub_project_id ]->name );
					}
					?>
					<div class="stats">
						<div class="total-strings">
							<?php
							printf(
								/* translators: %s: Count number. */
								__( '%s strings', 'glotpress' ),
								number_format_i18n( $data['totals']->all_count )
							);
							?>
						</div>
						<div class="percent-completed">
							<?php
							$percent_completed = 0;
							if ( $data['totals']->current_count ) {
								$percent_completed = floor( $data['totals']->current_count / $data['totals']->all_count * 100 );
							}
							printf(
								/* translators: %s: Percent completed. */
								__( '%s%% translated', 'glotpress' ),
								number_format_i18n( $percent_completed )
							);
							?>
						</div>
					</div>
				</th>
				<?php foreach ( $data['sets'] as $set_id => $set_data ) : ?>
					<?php
					reset( $data['sets'] );
					if ( key( $data['sets'] ) !== $set_id ) {
						echo '<tr>';
					}
					?>
					<td class="set-name">
						<strong><?php gp_link( gp_url_project( $set_data->project_path, gp_url_join( $locale->slug, $set_data->slug ) ), $set_data->name ); ?></strong>
						<?php
						if ( $set_data->current_count && $set_data->current_count >= $set_data->all_count * 0.9 ) :
							$percent = floor( $set_data->current_count / $set_data->all_count * 100 );
							?>
							<span class="bubble morethan90"><?php echo number_format_i18n( $percent ); ?>%</span>
						<?php endif; ?>
					</td>
					<td class="stats translated">
						<?php
						gp_link(
							gp_url_project(
								$set_data->project_path,
								gp_url_join( $locale->slug, $set_data->slug ),
								array(
									'filters[translated]' => 'yes',
									'filters[status]'     => 'current',
								)
							),
							number_format_i18n( $set_data->current_count )
						);
						?>
					</td>
					<td class="stats fuzzy">
						<?php
						gp_link(
							gp_url_project(
								$set_data->project_path,
								gp_url_join( $locale->slug, $set_data->slug ),
								array(
									'filters[status]' => 'fuzzy',
								)
							),
							number_format_i18n( $set_data->fuzzy_count )
						);
						?>
					</td>
					<td class="stats untranslated">
						<?php
						gp_link(
							gp_url_project(
								$set_data->project_path,
								gp_url_join( $locale->slug, $set_data->slug ),
								array(
									'filters[status]' => 'untranslated',
								)
							),
							number_format_i18n( $set_data->all_count - $set_data->current_count )
						);
						?>
					</td>
					<td class="stats waiting">
						<?php
						gp_link(
							gp_url_project(
								$set_data->project_path,
								gp_url_join( $locale->slug, $set_data->slug ),
								array(
									'filters[translated]' => 'yes',
									'filters[status]'     => 'waiting',
								)
							),
							number_format_i18n( $set_data->waiting_count )
						);
						?>
					</td>
					</tr>
				<?php endforeach; // Sub project slugs. ?>
				</tr>
			<?php endforeach;  // Sub projects. ?>
			</tbody>
		</table>
	</div>
<?php endforeach; // Top projects. ?>

	<p class="actionlist">
		<?php gp_link( gp_url( '/projects' ), __( 'All projects', 'glotpress' ) ); ?>
	</p>

<?php
gp_tmpl_footer();
