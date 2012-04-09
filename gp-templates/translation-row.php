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
?>
<tr class="preview <?php echo $parity().' '.$status_class.' '.$warning_class.' '.$priority_class ?>" id="preview-<?php echo $t->row_id ?>" row="<?php echo $t->row_id; ?>">
	<?php if ( $can_approve ) : ?><th scope="row" class="checkbox"><input type="checkbox" name="selected-row[]" /></th><?php endif; ?>
	<?php /*
	<td class="priority" style="background-color: <?php echo $priority_char[$t->priority][1] ?>; color: <?php echo $priority_char[$t->priority][2] ?>; text-align: center; font-size: 1.2em;" title="<?php echo esc_attr('Priority: '.gp_array_get( GP::$original->get_static( 'priorities' ), $t->priority )); ?>">
	*/ ?>
	<td class="priority" title="<?php echo esc_attr('Priority: '.gp_array_get( GP::$original->get_static( 'priorities' ), $t->priority )); ?>">
	   <?php echo $priority_char[$t->priority][0] ?>
	</td>
	<td class="original">
		<?php echo prepare_original( esc_translation( $t->singular ) ); ?>
		<?php if ( $t->context ): ?>
		<span class="context bubble" title="<?php printf( __('Context: %s'), esc_html($t->context) ); ?>"><?php echo esc_html($t->context); ?></span>
		<?php endif; ?>
	
	</td>
	<td class="translation foreign-text">
	<?php
		$edit_text = $can_edit? __('Double-click to add') : sprintf(__('You <a href="%s">have to login</a> to add a translation.'), gp_url_login());
		$missing_text = "<span class='missing'>$edit_text</span>";
		if ( !count( array_filter( $t->translations ) ) ):
			echo $missing_text;
		elseif ( !$t->plural ):
			echo esc_translation( $t->translations[0] );
		else: ?>
		<ul>
			<?php
				foreach( $t->translations as $translation ):
			?>
				<li><?php echo $translation? esc_translation( $translation ) : $missing_text; ?></li>
			<?php
				endforeach;
			?>
		</ul>
	<?php
		endif;
	?>
	</td>
	<td class="actions">
		<a href="#" row="<?php echo $t->row_id; ?>" class="action edit"><?php _e('Details'); ?></a>
	</td>
</tr>
<tr class="editor <?php echo $warning_class; ?>" id="editor-<?php echo $t->row_id; ?>" row="<?php echo $t->row_id; ?>">
	<td colspan="<?php echo $can_approve ? 5 : 4 ?>">
		<div class="strings">
		<?php if ( !$t->plural ): ?>
		<p class="original"><?php echo prepare_original( esc_translation($t->singular) ); ?></p>
		<?php textareas( $t, array( $can_edit, $can_approve ) ); ?>
		<?php else: ?>
			<?php if ( $locale->nplurals == 2 && $locale->plural_expression == 'n != 1'): ?>
				<p><?php printf(__('Singular: %s'), '<span class="original">'.esc_translation($t->singular).'</span>'); ?></p>
				<?php textareas( $t, array( $can_edit, $can_approve ), 0 ); ?>
				<p class="clear">
					<?php printf(__('Plural: %s'), '<span class="original">'.esc_translation($t->plural).'</span>'); ?>
				</p>
				<?php textareas( $t, array( $can_edit, $can_approve ), 1 ); ?>
			<?php else: ?>
				<!--
				TODO: labels for each plural textarea and a sample number
				-->
				<p><?php printf(__('Singular: %s'), '<span class="original">'.esc_translation($t->singular).'</span>'); ?></p>
				<p class="clear">
					<?php printf(__('Plural: %s'), '<span class="original">'.esc_translation($t->plural).'</span>'); ?>
				</p>
				<?php foreach( range( 0, $locale->nplurals - 1 ) as $plural_index ): ?>
					<?php if ( $locale->nplurals > 1 ): ?>
					<p class="plural-numbers"><?php printf(__('This plural form is used for numbers like: %s'),
							'<span class="numbers">'.implode(', ', $locale->numbers_for_index( $plural_index ) ).'</span>' ); ?></p>
					<?php endif; ?>
					<?php textareas( $t, array( $can_edit, $can_approve ), $plural_index ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
		</div>
		
		<div class="meta">
			<h3><?php _e('Meta'); ?></h3>
			<dl>
				<dt><?php _e('Status:'); ?></dt>
				<dd>
					<?php echo display_status( $t->translation_status ); ?>
					<?php if ( $can_approve && $t->translation_status ): ?>
					
						<?php if ( $t->translation_status != 'current' ): ?>
						<button class="approve" tabindex="-1"><strong>+</strong> Approve</button>
						<?php endif; ?>
						<?php if ( $t->translation_status != 'rejected' ): ?>
						<button class="reject" tabindex="-1"><strong>&minus;</strong> Reject</button>
						<?php endif; ?>
					<?php endif; ?>
				</dd>
			</dl>
			<!--
			<dl>
				<dt><?php _e('Priority:'); ?></dt>
				<dd><?php echo esc_html($t->priority); ?></dd>
			</dl>
			-->
			
			<?php if ( $t->context ): ?>
			<dl>
				<dt><?php _e('Context:'); ?></dt>
				<dd><span class="context bubble"><?php echo esc_translation($t->context); ?></span></dd>
			</dl>
			<?php endif; ?>
			<?php if ( $t->extracted_comment ): ?>
			<dl>
				<dt><?php _e('Comment:'); ?></dt>
				<dd><?php echo make_clickable( esc_translation($t->extracted_comment) ); ?></dd>
			</dl>
			<?php endif; ?>
			<?php if ( $t->translation_added && $t->translation_added != '0000-00-00 00:00:00' ): ?>
			<dl>
				<dt><?php _e('Date added:'); ?></dt>
				<dd><?php echo $t->translation_added; ?> GMT</dd>
			</dl>
			<?php endif; ?>
			<?php if ( $t->user_login ): ?>
			<dl>
				<dt><?php _e('Translated by:'); ?></dt>
				<dd><?php echo $t->user_login; ?></dd>
			</dl>
			<?php endif; ?>
			
			<?php references( $project, $t ); ?>
			
			<dl>
			    <dt><?php _e('Priority of the original:'); ?></dt>
			<?php if ( $can_write ): ?>
			    <dd><?php echo gp_select( 'priority-'.$t->original_id, GP::$original->get_static( 'priorities' ), $t->priority, array('class' => 'priority', 'tabindex' => '-1') ); ?></dd>
			<?php else: ?>
			    <dd><?php echo gp_array_get( GP::$original->get_static( 'priorities' ), $t->priority, 'unknown' ); ?></dd>
			<?php endif; ?>
			</dl>
			
			<?php $extra_args = $t->translation_status? array( 'filters[translation_id]' => $t->id ) : array(); ?>
			<dl>
<?php
		$permalink = gp_url_project_locale( $project, $locale->slug, $translation_set->slug,
        	array_merge( array('filters[status]' => 'either', 'filters[original_id]' => $t->original_id ), $extra_args ) );
		$original_history = gp_url_project_locale( $project, $locale->slug, $translation_set->slug,
        	array_merge( array('filters[status]' => 'either', 'filters[original_id]' => $t->original_id, 'sort[by]' => 'translation_date_added', 'sort[how]' => 'asc' ) ) );

?>
			    <dt>More links:
				<ul>
					<li><a tabindex="-1" href="<?php echo $permalink; ?>" title="Permanent link to this translation">Permalink to this translation</a></li>
					<li><a tabindex="-1" href="<?php echo $original_history; ?>" title="Link to the history of translations of this original">All translations of this original</a></li>
				</ul>
				</dt>
			</dl>
		</div>
		<div class="actions">
		<?php if ( $can_edit ): ?>
			<button class="ok">
				<?php echo $can_approve? __('Add translation &rarr;') : __('Suggest new translation &rarr;'); ?>
			</button>
		<?php endif; ?>
			or <a href="#" class="close"><?php _e('Cancel'); ?></a>
		</div>
	</td>
</tr>