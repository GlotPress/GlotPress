<?php
gp_title( sprintf( __( 'Projects translated to %s &lt; GlotPress', 'glotpress' ),  esc_html( $locale->english_name ) ) );

$breadcrumb = array();
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

	<h2><?php printf( __( 'Active Projects translated to %s', 'glotpress' ), esc_html( $locale->english_name ) ); ?>
		<?php if ( $locale_glossary ) : ?>
			<a href="<?php echo esc_url( gp_url_join( gp_url( '/languages' ), $locale->slug, $current_set_slug, 'glossary' ) ); ?>" class="glossary-link"><?php _e( 'Locale Glossary', 'glotpress' ); ?></a>
		<?php elseif ( $can_create_locale_glossary ) : ?>
			<a href="<?php echo esc_url( gp_url_join( gp_url( '/languages' ), $locale->slug, $current_set_slug, 'glossary' ) ); ?>" class="glossary-link"><?php _e( 'Create Locale Glossary', 'glotpress' ); ?></a>
		<?php endif; ?>
	</h2>

<?php if ( count( $set_list ) > 1 ) : ?>
	<p class="actionlist secondary">
		<?php echo implode( ' &bull;&nbsp;', $set_list ); ?>
	</p>
<?php endif; ?>

<?php
if ( empty( $projects_data ) ) {
	_e( 'No active projects found.', 'glotpress' );
}
?>

<?php foreach ( $projects_data as $project_id => $sub_projects ) : ?>
	<div class="locale-project">
		<h3><?php echo ( $projects[$project_id]->name );?></h3>
		<table class="locale-sub-projects">
			<thead>
			<tr>
				<th class="header" <?php if (count($sub_projects)>1 ) echo 'rowspan="'. count($sub_projects) . '"';?>><?php if (count($sub_projects)>1 ) _e( 'Project', 'glotpress' ); ?></th>
				<th class="header"><?php _e( 'Set / Sub Project', 'glotpress' ); ?></th>
				<th><?php _e( 'Translated', 'glotpress' ); ?></th>
				<th><?php _e( 'Fuzzy', 'glotpress' ); ?></th>
				<th><?php _e( 'Untranslated', 'glotpress' ); ?></th>
				<th><?php _e( 'Waiting', 'glotpress' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $sub_projects as $sub_project_id => $data ) : ?>
				<tr>
				<th class="sub-project" rowspan="<?php echo count( $data['sets'] );  ?>">
					<?php if (count($sub_projects)>1  ) echo esc_html( $projects[$sub_project_id]->name ); ?>
					<div class="stats">
						<div class="total-strings"><?php printf( __( '%d strings', 'glotpress' ), $data['totals']->all_count ); ?></div>
						<div class="percent-completed"><?php printf( __( '%d%% translated', 'glotpress' ), $data['totals']->current_count ? floor( absint($data['totals']->current_count ) / absint( $data['totals']->all_count ) * 100 ) : 0 ); ?></div>
					</div>
				</th>
				<?php foreach ( $data['sets'] as $set_id => $set_data ) : ?>
					<?php  reset( $data['sets'] );	if ( $set_id !== key($data['sets']) ) echo '<tr>'; ?>
					<td class="set-name">
						<strong><?php gp_link( gp_url_project( $set_data->project_path, gp_url_join( $locale->slug, $set_data->slug ) ), $set_data->name ); ?></strong>
						<?php if ( $set_data->current_count && $set_data->current_count >= $set_data->all_count * 0.9 ):
							$percent = floor( $set_data->current_count / $set_data->all_count * 100 );
							?>
							<span class="bubble morethan90"><?php echo $percent; ?>%</span>
						<?php endif;?>
					</td>
					<td class="stats translated"><?php gp_link( gp_url_project( $set_data->project_path, gp_url_join( $locale->slug, $set_data->slug ), array('filters[translated]' => 'yes', 'filters[status]' => 'current') ), absint( $set_data->current_count ) ); ?></td>
					<td class="stats fuzzy"><?php gp_link( gp_url_project( $set_data->project_path, gp_url_join( $locale->slug, $set_data->slug ), array('filters[status]' => 'fuzzy' ) ), absint( $set_data->fuzzy_count ) ); ?></td>
					<td class="stats untranslated"><?php gp_link( gp_url_project( $set_data->project_path, gp_url_join( $locale->slug, $set_data->slug ), array('filters[status]' => 'untranslated' ) ), absint( $set_data->all_count ) -  absint( $set_data->current_count ) ); ?></td>
					<td class="stats waiting"><?php gp_link( gp_url_project( $set_data->project_path, gp_url_join( $locale->slug, $set_data->slug ), array('filters[translated]' => 'yes', 'filters[status]' => 'waiting') ), absint( $set_data->waiting_count ) ); ?></td>
					</tr>
				<?php endforeach; //sub project slugs ?>
				</tr>
			<?php endforeach;  //sub projects ?>
			</tbody>
		</table>
	</div>
<?php endforeach; //top projects ?>

	<p class="actionlist secondary">
		<?php gp_link( gp_url( '/projects' ), __( 'All projects', 'glotpress' ) ); ?>
	</p>

<?php gp_tmpl_footer();
