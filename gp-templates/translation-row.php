<?php
/**
 * Template for a single translation row in a translation set display
 *
 * @package GlotPress
 * @subpackage Templates
 */

$priority_char = array(
    '-2' => array('&times;', 'transparent', '#ccc'),
    '-1' => array('&darr;', 'transparent', 'blue'),
    '0' => array('', 'transparent', 'white'),
    '1' => array('&uarr;', 'transparent', 'green'),
);
$user = wp_get_current_user();
$can_reject_self = ( isset( $t->user->user_login ) && $user->user_login === $t->user->user_login && 'waiting' === $t->translation_status );

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

if ( is_object( $glossary ) ) {
	if ( ! isset( $glossary_entries_terms ) ) {
		$glossary_entries = $glossary->get_entries();
		$glossary_entries_terms = gp_sort_glossary_entries_terms( $glossary_entries );
	}

	$t = map_glossary_entries_to_translation_originals( $t, $glossary, $glossary_entries_terms );
}

?>

<tr class="preview <?php gp_translation_row_classes( $t ); ?>" id="preview-<?php echo esc_attr( $t->row_id ) ?>" row="<?php echo esc_attr( $t->row_id ); ?>">
	<?php if ( $can_approve_translation ) : ?>
		<th scope="row" class="checkbox"><input type="checkbox" name="selected-row[]" /></th>
	<?php elseif ( $can_approve ) : ?>
		<th scope="row"></th>
	<?php endif; ?>
	<td class="priority" title="<?php echo esc_attr( sprintf( __( 'Priority: %s', 'glotpress' ), gp_array_get( GP::$original->get_static( 'priorities' ), $t->priority ) ) ); ?>">
	   <?php echo $priority_char[$t->priority][0] ?>
	</td>
	<td class="original">
		<?php echo prepare_original( esc_translation( $t->singular ) ); ?>
		<?php if ( $t->context ): ?>
		<span class="context bubble" title="<?php printf( __( 'Context: %s', 'glotpress' ), esc_html($t->context) ); ?>"><?php echo esc_html($t->context); ?></span>
		<?php endif; ?>

	</td>
	<td class="translation foreign-text">
	<?php
		if ( $can_edit ) {
			$edit_text = __( 'Double-click to add', 'glotpress' );
		}
		elseif ( is_user_logged_in() ) {
			$edit_text = __( 'You are not allowed to add a translation.', 'glotpress' );
		}
		else {
			$edit_text = sprintf( __( 'You <a href="%s">have to log in</a> to add a translation.', 'glotpress' ), esc_url( wp_login_url( gp_url_current() ) ) );
		}

		$missing_text = "<span class='missing'>$edit_text</span>";
		if ( ! count( array_filter( $t->translations, 'gp_is_not_null' ) ) ) :
			echo $missing_text;
		elseif ( ! $t->plural ) :
			echo esc_translation( $t->translations[0] );
		else: ?>
		<ul>
			<?php
				foreach( $t->translations as $translation ):
			?>
				<li><?php echo gp_is_empty_string( $translation ) ? $missing_text : esc_translation( $translation ); ?></li>
			<?php
				endforeach;
			?>
		</ul>
	<?php
		endif;
	?>
	</td>
	<td class="actions">
		<a href="#" row="<?php echo $t->row_id; ?>" class="action edit"><?php _e( 'Details', 'glotpress' ); ?></a>
	</td>
</tr>
<tr class="editor <?php echo gp_translation_row_classes( $t ); ?>" id="editor-<?php echo esc_attr( $t->row_id ); ?>" row="<?php echo esc_attr( $t->row_id ); ?>">
	<td colspan="<?php echo $can_approve ? 5 : 4 ?>">
		<div class="strings">
		<?php
			$singular = isset( $t->singular_glossary_markup ) ? $t->singular_glossary_markup : esc_translation( $t->singular );
			$plural   = isset( $t->plural_glossary_markup ) ? $t->plural_glossary_markup : esc_translation( $t->plural );
		?>

		<?php if ( ! $t->plural ): ?>
		<p class="original"><?php echo prepare_original( $singular ); ?></p>
		<?php textareas( $t, array( $can_edit, $can_approve_translation ) ); ?>
		<?php else: ?>
			<?php if ( $locale->nplurals == 2 && $locale->plural_expression == 'n != 1'): ?>
				<p><?php printf(__( 'Singular: %s', 'glotpress' ), '<span class="original">'. $singular .'</span>'); ?></p>
				<?php textareas( $t, array( $can_edit, $can_approve ), 0 ); ?>
				<p class="clear">
					<?php printf(__( 'Plural: %s', 'glotpress' ), '<span class="original">'. $plural .'</span>'); ?>
				</p>
				<?php textareas( $t, array( $can_edit, $can_approve ), 1 ); ?>
			<?php else: ?>
				<!--
				TODO: labels for each plural textarea and a sample number
				-->
				<p><?php printf(__( 'Singular: %s', 'glotpress' ), '<span class="original">'. $singular .'</span>'); ?></p>
				<p class="clear">
					<?php printf(__( 'Plural: %s', 'glotpress' ), '<span class="original">'. $plural .'</span>'); ?>
				</p>
				<?php foreach( range( 0, $locale->nplurals - 1 ) as $plural_index ): ?>
					<?php if ( $locale->nplurals > 1 ): ?>
					<p class="plural-numbers"><?php printf(__( 'This plural form is used for numbers like: %s', 'glotpress' ),
							'<span class="numbers">'.implode(', ', $locale->numbers_for_index( $plural_index ) ).'</span>' ); ?></p>
					<?php endif; ?>
					<?php textareas( $t, array( $can_edit, $can_approve ), $plural_index ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
			<div class="actions">
				<?php if ( $can_edit ) : ?>
					<button class="ok" data-nonce="<?php echo esc_attr( wp_create_nonce( 'add-translation_' . $t->original_id ) ); ?>">
						<?php echo $can_approve_translation ? __( 'Add translation &rarr;', 'glotpress' ) : __( 'Suggest new translation &rarr;', 'glotpress' ); ?>
					</button>
				<?php endif; ?>
				<?php _e( 'or', 'glotpress' ); ?> <a href="#" class="close"><?php _e( 'Cancel', 'glotpress' ); ?></a>
			</div>
		</div>
		<div class="meta">
			<h3><?php _e( 'Meta', 'glotpress' ); ?></h3>
			<dl>
				<dt><?php _e( 'Status:', 'glotpress' ); ?></dt>
				<dd>
					<?php echo display_status( $t->translation_status ); ?>
					<?php if ( $t->translation_status ): ?>
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
						<?php elseif ( $can_reject_self ): ?>
							<button class="reject" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $t->id ) ); ?>"><strong>&minus;</strong> <?php _e( 'Reject Suggestion', 'glotpress' ); ?></button>
							<button class="fuzzy" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-fuzzy_' . $t->id ) ); ?>"><strong>~</strong> <?php _e( 'Fuzzy', 'glotpress' ); ?></button>
						<?php endif; ?>
					<?php endif; ?>
				</dd>
			</dl>
			<!--
			<dl>
				<dt><?php _e( 'Priority:', 'glotpress' ); ?></dt>
				<dd><?php echo esc_html($t->priority); ?></dd>
			</dl>
			-->

			<?php if ( $t->context ): ?>
			<dl>
				<dt><?php _e( 'Context:', 'glotpress' ); ?></dt>
				<dd><span class="context bubble"><?php echo esc_translation($t->context); ?></span></dd>
			</dl>
			<?php endif; ?>
			<?php if ( $t->extracted_comments ): ?>
			<dl>
				<dt><?php _e( 'Comment:', 'glotpress' ); ?></dt>
				<dd><?php echo make_clickable( esc_translation($t->extracted_comments) ); ?></dd>
			</dl>
			<?php endif; ?>
			<?php if ( $t->translation_added && $t->translation_added != '0000-00-00 00:00:00' ): ?>
			<dl>
				<dt><?php _e( 'Date added:', 'glotpress' ); ?></dt>
				<dd><?php echo $t->translation_added; ?> GMT</dd>
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
			<?php if ( $can_write ): ?>
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
	</td>
</tr>
