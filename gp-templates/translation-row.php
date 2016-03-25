<?php
$status_class = $t->translation_status? 'status-'.$t->translation_status : 'untranslated';
$warning_class = $t->warnings? 'has-warnings' : 'no-warnings';
$priority_class = 'priority-'.gp_array_get( GP::$original->get_static( 'priorities' ), $t->priority );
$priority_char = array(
    '-2' => array('&times;', 'transparent', '#ccc'),
    '-1' => array('&darr;', 'transparent', 'blue'),
    '0' => array('', 'transparent', 'white'),
    '1' => array('&uarr;', 'transparent', 'green'),
);
$user = wp_get_current_user();
$can_reject_self = ($user->user_login == $t->user_login && $t->translation_status == "waiting");
?>

<tr class="preview <?php echo $status_class.' '.$warning_class.' '.$priority_class ?>" id="preview-<?php echo $t->row_id ?>" row="<?php echo $t->row_id; ?>">
	<?php if ( $can_approve ) : ?><th scope="row" class="checkbox"><input type="checkbox" name="selected-row[]" /></th><?php endif; ?>
	<?php /*
	<td class="priority" style="background-color: <?php echo $priority_char[$t->priority][1] ?>; color: <?php echo $priority_char[$t->priority][2] ?>; text-align: center; font-size: 1.2em;" title="<?php echo esc_attr('Priority: '.gp_array_get( GP::$original->get_static( 'priorities' ), $t->priority )); ?>">
	*/ ?>
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
			$edit_text = sprintf( __( 'You <a href="%s">have to login</a> to add a translation.', 'glotpress' ), esc_url( wp_login_url() ) );
		}

		$missing_text = "<span class='missing'>$edit_text</span>";
		if ( ! count( array_filter( $t->translations, 'gp_is_not_empty_string' ) ) ):
			echo $missing_text;
		elseif ( !$t->plural ):
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
<tr class="editor <?php echo $warning_class; ?>" id="editor-<?php echo $t->row_id; ?>" row="<?php echo $t->row_id; ?>">
	<td colspan="<?php echo $can_approve ? 5 : 4 ?>">
		<div class="strings">
		<?php
			$singular = isset( $t->singular_glossary_markup ) ? $t->singular_glossary_markup : esc_translation( $t->singular );
			$plural   = isset( $t->plural_glossary_markup ) ? $t->plural_glossary_markup : esc_translation( $t->plural );
		?>

		<?php if ( ! $t->plural ): ?>
		<p class="original"><?php echo prepare_original( $singular ); ?></p>
		<?php textareas( $t, array( $can_edit, $can_approve ) ); ?>
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
		</div>

		<div class="meta">
			<h3><?php _e( 'Meta', 'glotpress' ); ?></h3>
			<dl>
				<dt><?php _e( 'Status:', 'glotpress' ); ?></dt>
				<dd>
					<?php echo display_status( $t->translation_status ); ?>
					<?php if ( $t->translation_status ): ?>
						<?php if ( $can_approve ): ?>
							<?php if ( $t->translation_status != 'current' ): ?>
							<button class="approve" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-current_' . $t->id ) ); ?>"><strong>+</strong> <?php _e( 'Approve', 'glotpress' ); ?></button>
							<?php endif; ?>
							<?php if ( $t->translation_status != 'rejected' ): ?>
							<button class="reject" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $t->id ) ); ?>"><strong>&minus;</strong> <?php _e( 'Reject', 'glotpress' ); ?></button>
							<?php endif; ?>
						<?php elseif ( $can_reject_self ): ?>
							<button class="reject" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $t->id ) ); ?>"><strong>&minus;</strong> <?php _e( 'Reject Suggestion', 'glotpress' ); ?></button>
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
			<?php if ( $t->user_login ): ?>
			<dl>
				<dt><?php _e( 'Translated by:', 'glotpress' ); ?></dt>
				<dd><?php
				if ( $t->user_display_name && $t->user_display_name != $t->user_login ) {
					printf( '<a href="%s" tabindex="-1">%s (%s)</a>',
						gp_url_profile( $t->user_nicename ),
						$t->user_display_name,
						$t->user_login
					);
				} else {
					printf( '<a href="%s" tabindex="-1">%s</a>',
						gp_url_profile( $t->user_nicename ),
						$t->user_login
					);
				}
				?></dd>
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

			<?php $extra_args = $t->translation_status? array( 'filters[translation_id]' => $t->id ) : array(); ?>
			<dl>
			<?php
					$permalink_filters = $t->translation_status ? array( 'filters[status]' => 'either', 'filters[original_id]' => $t->original_id ) : array( 'filters[original_id]' => $t->original_id );
					$permalink = gp_url_project_locale( $project, $locale->slug, $translation_set->slug,
						array_merge( $permalink_filters, $extra_args ) );
					$original_history = gp_url_project_locale( $project, $locale->slug, $translation_set->slug,
						array_merge( array('filters[status]' => 'either', 'filters[original_id]' => $t->original_id, 'sort[by]' => 'translation_date_added', 'sort[how]' => 'asc' ) ) );
			?>
			    <dt><?php _e( 'More links:', 'glotpress' ); ?>
				<ul>
				<?php if ( $t->translation_status ) : ?>
					<li><a tabindex="-1" href="<?php echo $permalink; ?>" title="<?php esc_attr_e( 'Permanent link to this translation', 'glotpress' ); ?>"><?php _e( 'Permalink to this translation', 'glotpress' ); ?></a></li>
				<?php else : ?>
					<li><a tabindex="-1" href="<?php echo $permalink; ?>" title="<?php esc_attr_e( 'Permanent link to this original', 'glotpress' ); ?>"><?php _e( 'Permalink to this original', 'glotpress' ); ?></a></li>
				<?php endif; ?>
					<li><a tabindex="-1" href="<?php echo $original_history; ?>" title="<?php esc_attr_e( 'Link to the history of translations of this original', 'glotpress' ); ?>"><?php _e( 'All translations of this original', 'glotpress' ); ?></a></li>
				</ul>
				</dt>
			</dl>
		</div>
		<div class="actions">
		<?php if ( $can_edit ): ?>
			<button class="ok" data-nonce="<?php echo esc_attr( wp_create_nonce( 'add-translation_' . $t->original_id ) ); ?>">
				<?php echo $can_approve? __( 'Add translation &rarr;', 'glotpress' ) : __( 'Suggest new translation &rarr;', 'glotpress' ); ?>
			</button>
		<?php endif; ?>
			or <a href="#" class="close"><?php _e( 'Cancel', 'glotpress' ); ?></a>
		</div>
	</td>
</tr>
