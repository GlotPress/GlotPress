<?php
/**
 * Template for the meta section of the editor row in a translation set display
 *
 * @package    GlotPress
 * @subpackage Templates
 */

$more_links = array();
if ( $t->translation_status ) {
	$translation_permalink = gp_url_project_locale( $project, $locale->slug, $translation_set->slug, array( 'filters[status]' => 'either', 'filters[original_id]' => $t->original_id, 'filters[translation_id]' => $t->id ) );
	$more_links['translation-permalink'] = '<a tabindex="-1" href="' . esc_url( $translation_permalink ) . '">' . __( 'Permalink to this translation', 'glotpress' ) . '</a>';
} else {
	$original_permalink = gp_url_project_locale( $project, $locale->slug, $translation_set->slug, array( 'filters[original_id]' => $t->original_id ) );
	$more_links['original-permalink'] = '<a tabindex="-1" href="' . esc_url( $original_permalink ) . '">' . __( 'Permalink to this original', 'glotpress' ) . '</a>';
}

$original_history = gp_url_project_locale( $project, $locale->slug, $translation_set->slug, array( 'filters[status]' => 'either', 'filters[original_id]' => $t->original_id, 'sort[by]' => 'translation_date_added', 'sort[how]' => 'asc' ) );
$more_links['history'] = '<a tabindex="-1" href="' . esc_url( $original_history ) . '">' . __( 'All translations of this original', 'glotpress' ) . '</a>';

/**
 * Allows to modify the more links in the translation editor.
 *
 * @since 2.3.0
 *
 * @param array              $more_links      The links to be output.
 * @param GP_Project         $project         Project object.
 * @param GP_Locale          $locale          Locale object.
 * @param GP_Translation_Set $translation_set Translation Set object.
 * @param GP_Translation     $t               Translation object.
 */
$more_links = apply_filters( 'gp_translation_row_template_more_links', $more_links, $project, $locale, $translation_set, $t );

?>
<div class="meta">
	<h3><?php _e( 'Meta', 'glotpress' ); ?></h3>
	<dl>
		<dt><?php _e( 'Status:', 'glotpress' ); ?></dt>
		<dd>
	<?php echo display_status( $t->translation_status ); ?>
	<?php if ( $t->translation_status ) : ?>
		<?php if ( $can_approve_translation ) : ?>
		<?php if ( 'current' !== $t->translation_status ) : ?>
						<button class="approve" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-current_' . $t->id ) ); ?>"><strong>+</strong> <?php _e( 'Approve', 'glotpress' ); ?></button>
		<?php endif; ?>
		<?php if ( 'rejected' !== $t->translation_status ) : ?>
						<button class="reject" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $t->id ) ); ?>"><strong>&minus;</strong> <?php _e( 'Reject', 'glotpress' ); ?></button>
		<?php endif; ?>
		<?php if ( 'fuzzy' !== $t->translation_status ) : ?>
						<button class="fuzzy" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-fuzzy_' . $t->id ) ); ?>"><strong>~</strong> <?php _e( 'Fuzzy', 'glotpress' ); ?></button>
		<?php endif; ?>
				<?php elseif ( $can_reject_self ) : ?>
					<button class="reject" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $t->id ) ); ?>"><strong>&minus;</strong> <?php _e( 'Reject Suggestion', 'glotpress' ); ?></button>
					<button class="fuzzy" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-fuzzy_' . $t->id ) ); ?>"><strong>~</strong> <?php _e( 'Fuzzy', 'glotpress' ); ?></button>
				<?php endif; ?>
	<?php endif; ?>
		</dd>
	</dl>
	<!--
			<dl>
				<dt><?php _e( 'Priority:', 'glotpress' ); ?></dt>
				<dd><?php echo esc_html( $t->priority ); ?></dd>
			</dl>
			-->

	<?php if ( $t->context ) : ?>
		<dl>
			<dt><?php _e( 'Context:', 'glotpress' ); ?></dt>
			<dd><span class="context bubble"><?php echo esc_translation( $t->context ); ?></span></dd>
		</dl>
	<?php endif; ?>
	<?php if ( $t->extracted_comments ) : ?>
		<dl>
			<dt><?php _e( 'Comment:', 'glotpress' ); ?></dt>
			<dd><?php echo make_clickable( esc_translation( $t->extracted_comments ) ); ?></dd>
		</dl>
	<?php endif; ?>
	<?php if ( $t->translation_added && '0000-00-00 00:00:00' !== $t->translation_added ) : ?>
		<dl>
			<dt><?php _e( 'Date added:', 'glotpress' ); ?></dt>
			<dd><?php echo esc_html( $t->translation_added ); ?> GMT</dd>
		</dl>
	<?php endif; ?>
	<?php if ( $t->user ) : ?>
		<dl>
			<dt><?php _e( 'Translated by:', 'glotpress' ); ?></dt>
			<dd><?php gp_link_user( $t->user ); ?></dd>
		</dl>
	<?php endif; ?>
	<?php if ( $t->user_last_modified && ( ! $t->user || $t->user->ID !== $t->user_last_modified->ID ) ) : ?>
		<dl>
			<dt><?php
			if ( 'current' === $t->translation_status ) {
				_e( 'Approved by:', 'glotpress' );
			} elseif ( 'rejected' === $t->translation_status ) {
				_e( 'Rejected by:', 'glotpress' );
			} else {
				_e( 'Last updated by:', 'glotpress' );
			}
				?>
			</dt>
			<dd><?php gp_link_user( $t->user_last_modified ); ?></dd>
		</dl>
	<?php endif; ?>
	<?php references( $project, $t ); ?>

	<dl>
		<dt><?php _e( 'Priority of the original:', 'glotpress' ); ?></dt>
	<?php if ( $can_write ) : ?>
			<dd><?php
				echo gp_select(
					'priority-' . $t->original_id,
					GP::$original->get_static( 'priorities' ),
					$t->priority,
					array(
						'class'      => 'priority',
						'tabindex'   => '-1',
						'data-nonce' => wp_create_nonce( 'set-priority_' . $t->original_id ),
					)
				);
				?></dd>
	<?php else : ?>
			<dd><?php echo gp_array_get( GP::$original->get_static( 'priorities' ), $t->priority, 'unknown' ); // WPCS: XSS ok. ?></dd>
	<?php endif; ?>
	</dl>

	<dl>
		<dt><?php _e( 'More links:', 'glotpress' ); ?>
		<ul>
	<?php foreach ( $more_links as $link ) : ?>
				<li><?php echo $link; // WPCS: XSS ok. ?></li>
	<?php endforeach; ?>
		</ul>
		</dt>
	</dl>
</div>
