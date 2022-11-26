<?php
/**
 * Template for the meta section of the editor row in a translation set display
 *
 * @package    GlotPress
 * @subpackage Templates
 */

$more_links = array();
if ( $translation->translation_status ) {
	$translation_permalink = gp_url_project_locale(
		$project,
		$locale->slug,
		$translation_set->slug,
		array(
			'filters[status]'         => 'either',
			'filters[original_id]'    => $translation->original_id,
			'filters[translation_id]' => $translation->id,
		)
	);

	$more_links['translation-permalink'] = '<a tabindex="-1" href="' . esc_url( $translation_permalink ) . '">' . __( 'Permalink to this translation', 'glotpress' ) . '</a>';
} else {
	$original_permalink = gp_url_project_locale( $project, $locale->slug, $translation_set->slug, array( 'filters[original_id]' => $translation->original_id ) );

	$more_links['original-permalink'] = '<a tabindex="-1" href="' . esc_url( $original_permalink ) . '">' . __( 'Permalink to this original', 'glotpress' ) . '</a>';
}

$original_history = gp_url_project_locale(
	$project,
	$locale->slug,
	$translation_set->slug,
	array(
		'filters[status]'      => 'either',
		'filters[original_id]' => $translation->original_id,
		'sort[by]'             => 'translation_date_added',
		'sort[how]'            => 'asc',
	)
);

$more_links['history'] = '<a tabindex="-1" href="' . esc_url( $original_history ) . '">' . __( 'All translations of this original', 'glotpress' ) . '</a>';

/**
 * Allows to modify the more links in the translation editor.
 *
 * @since 2.3.0
 *
 * @param array $more_links The links to be output.
 * @param GP_Project $project Project object.
 * @param GP_Locale $locale Locale object.
 * @param GP_Translation_Set $translation_set Translation Set object.
 * @param GP_Translation $translation Translation object.
 */
$more_links = apply_filters( 'gp_translation_row_template_more_links', $more_links, $project, $locale, $translation_set, $translation );

$meta_sidebar  = '<div class="meta" id="sidebar-div-meta-' . $translation->original_id . '">';
$meta_sidebar .= '<h3>' . __( 'Meta', 'glotpress' ) . '</h3>';
$meta_sidebar .= gp_tmpl_get_output( 'translation-row-editor-meta-status', get_defined_vars() );
if ( $translation->context ) {
	$meta_sidebar .= '		<dl>';
	$meta_sidebar .= '			<dt>' . __( 'Context:', 'glotpress' ) . '</dt>';
	$meta_sidebar .= '			<dd>' . esc_translation( $translation->context ) . '</dd>';
	$meta_sidebar .= '		</dl>';
}

if ( $translation->extracted_comments ) {
	$meta_sidebar .= '		<dl>';
	$meta_sidebar .= '			<dt>' . __( 'Comment:', 'glotpress' ) . '</dt>';
	$meta_sidebar .= '			<dd>';
				/**
				 * Filters the extracted comments of an original.
				 *
				 * @param string         $extracted_comments Extracted comments of an original.
				 * @param GP_Translation $translation        Translation object.
				 */
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$meta_sidebar .= apply_filters( 'gp_original_extracted_comments', $translation->extracted_comments, $translation );
	$meta_sidebar .= '			</dd>';
	$meta_sidebar .= '		</dl>';
}
if ( $translation->translation_added && '0000-00-00 00:00:00' !== $translation->translation_added ) {
	$meta_sidebar .= '		<dl>';
	$meta_sidebar .= '			<dt>' . __( 'Date added (GMT):', 'glotpress' ) . '</dt>';
	$meta_sidebar .= '			<dd id="gmt-date-added-' . esc_attr( $translation->row_id ) . '">' . esc_html( $translation->translation_added ) . '</dd>';
	$meta_sidebar .= '		</dl>';
	$meta_sidebar .= '		<dl>';
	$meta_sidebar .= '			<dt>' . __( 'Date added (local):', 'glotpress' ) . '</dt>';
	$meta_sidebar .= '			<dd id="local-date-added-' . esc_attr( $translation->row_id ) . '">' . __( 'Calculating...', 'glotpress' ) . '</dd>';
	$meta_sidebar .= '		</dl>';
}
if ( $translation->user ) {
	$meta_sidebar .= '		<dl>';
	$meta_sidebar .= '			<dt>' . __( 'Translated by:', 'glotpress' ) . '</dt>';
	$meta_sidebar .= '			<dd>' . gp_link_user_get( $translation->user ) . '</dd>';
	$meta_sidebar .= '		</dl>';
}

if ( $translation->user_last_modified && ( ! $translation->user || $translation->user->ID !== $translation->user_last_modified->ID ) ) {
	$meta_sidebar .= '		<dl>';
	$meta_sidebar .= '			<dt>';
			if ( 'current' === $translation->translation_status ) {
				$meta_sidebar .= __( 'Approved by:', 'glotpress' );
			} elseif ( 'rejected' === $translation->translation_status ) {
				$meta_sidebar .= __( 'Rejected by:', 'glotpress' );
			} else {
				$meta_sidebar .= __( 'Last updated by:', 'glotpress' );
			}
	$meta_sidebar .= '			</dt>';
	$meta_sidebar .= '			<dd>' . gp_link_user_get( $translation->user_last_modified ) . '</dd>';
	$meta_sidebar .= '		</dl>';
}
ob_start();
references( $project, $translation );
$meta_sidebar .= ob_get_clean();

$meta_sidebar .= '	<dl>';
$meta_sidebar .= '		<dt>' . __( 'Priority:', 'glotpress' ) . '</dt>';
if ( $can_write ) {
	$meta_sidebar .= '			<dd>';
	$meta_sidebar .= gp_select(
		'priority-' . $translation->original_id,
		GP::$original->get_static( 'priorities' ),
		$translation->priority,
		array(
			'class'      => 'priority',
			'tabindex'   => '-1',
			'data-nonce' => wp_create_nonce( 'set-priority_' . $translation->original_id ),
		)
	);
	$meta_sidebar .= '			</dd>';
	} else {
	$meta_sidebar .= '			<dd>';
	$meta_sidebar .= esc_html(
		gp_array_get(
			GP::$original->get_static( 'priorities' ),
			$translation->priority,
			_x( 'Unknown', 'priority', 'glotpress' )
		)
	);
	$meta_sidebar .= '			</dd>';
}
$meta_sidebar .= '	</dl>';

$meta_sidebar .= '	<dl>';
$meta_sidebar .= '		<dt>' . __( 'More links:', 'glotpress' );
$meta_sidebar .= '			<ul>';
				foreach ( $more_links as $more_link ) {
					$meta_sidebar .= '					<li>';
					$meta_sidebar .= $more_link;
					$meta_sidebar .= '					</li>';
				}
$meta_sidebar .= '			</ul>';
$meta_sidebar .= '		</dt>';
$meta_sidebar .= '	</dl>';
$meta_sidebar .= '</div>';

$defined_vars = get_defined_vars();

/**
 * Filter the content in the sidebar.
 *
 * @since 4.0.0
 *
 * @param string $meta_sidebar Default content for the sidebar.
 * @param array  $defined_vars The defined vars.
 */
$meta_sidebar = apply_filters( 'gp_right_sidebar', $meta_sidebar, $defined_vars );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $meta_sidebar;
