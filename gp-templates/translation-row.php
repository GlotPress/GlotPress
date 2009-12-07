<?php
$status_class = $t->translation_status;
if ( !$status_class ) $class = 'untranslated';
$warning_class = $t->warnings? 'has-warnings' : 'no-warnings';
?>
<tr class="preview <?php echo $parity().' status-'.$status_class.' '.$warning_class ?>" id="preview-<?php echo $t->row_id ?>" row="<?php echo $t->row_id; ?>">
	<td class="checkbox"><input type="checkbox" name="selected-row[]" /></td>
	<td class="original">			
		<?php echo prepare_original( esc_translation( $t->singular ) ); ?>
		<?php if ( $t->context ): ?>
		<span class="context" title="<?php printf( __('Context: %s'), esc_html($t->context) ); ?>"><?php echo esc_html($t->context); ?></span>
		<?php endif; ?>

	</td>
	<td class="translation">
	<?php
		$edit_text = $can_edit? 'Double-click to add' : 'You <a href="'.gp_url_login().'">have to login</a> to add a translation.';
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
	<td colspan="4">
		<div class="strings">
		<?php if ( !$t->plural ): ?>
		<p class="original"><?php echo prepare_original( esc_translation($t->singular) ); ?></p>
		<?php textareas( $t, $can_edit ); ?>
		<?php else: ?>
			<?php if ( $locale->nplurals == 2 && $locale->plural_expression == 'n != 1'): ?>
				<p><?php printf(__('Singular: %s'), '<span class="original">'.esc_translation($t->singular).'</span>'); ?></p>
				<?php textareas( $t, $can_edit, 0 ); ?>
				<p class="clear">
					<?php printf(__('Plural: %s'), '<span class="original">'.esc_translation($t->plural).'</span>'); ?>
				</p>
				<?php textareas( $t, $can_edit, 1 ); ?>
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
					<p class="plural-numbers">This plural form is used for numbers like: <span class="numbers"><?php  echo implode(', ', $locale->numbers_for_index( $plural_index) ); ?></span></p>										
					<?php endif; ?>
					<?php textareas( $t, $can_edit, $plural_index ); ?>					
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
		</div>
		<div class="meta">
			<h3>Meta</h3>
			<dl>
				<dt>Status:</dt>
				<dd><?php echo display_status( $t->translation_status ); ?></dd>
				<!--
				TODO: ajaxy actions for approve/set as current/reject
			<?php if ( $can_approve ): ?>
				<?php if ( gp_startswith( $t->translation_status, '-' ) ): ?>
				<dd><a href="#" tabindex="-1">Set as current</a></dd>
				<?php endif; ?>
				<?php if ( $t->translation_status ): ?>
				<dd><a href="#" tabindex="-1">Reject</a></dd>
				<?php endif; ?>
			<?php endif; ?>
				-->
			</dl>
			<!--
			<dl>					
				<dt>Priority:</dt>
				<dd><?php echo esc_html($t->priority); ?></dd>
			</dl>
			-->	
			
			<?php if ( $t->context ): ?>
			<dl>					
				<dt>Context:</dt>
				<dd><span class="context"><?php echo esc_translation($t->context); ?></span></dd>
			</dl>	
			<?php endif; ?>				
			<?php if ( $t->extracted_comment ): ?>
			<dl>					
				<dt>Comment:</dt>
				<dd><?php echo make_clickable( esc_translation($t->extracted_comment) ); ?></dd>
			</dl>
			<?php endif; ?>
			<?php if ( $t->translation_added && $t->translation_added != '0000-00-00 00:00:00' ): ?>
			<dl>
				<dt>Date added:</dt>
				<dd><?php echo $t->translation_added; ?> GMT</dd>
			</dl>								
			<?php endif; ?>
			<?php if ( $t->user_login ): ?>
			<dl>
				<dt>Translated by:</dt>
				<dd><?php echo $t->user_login; ?></dd>				
			</dl>								
			<?php endif; ?>
			
			<?php references( $project, $t ); ?>
		</div>
		<div class="actions">
		<?php if ( $can_edit ): ?>
			<button class="ok">
				<?php echo $can_approve? 'Add translation &rarr;' : 'Suggest new translation &rarr;'; ?>
			</button>
		<?php endif; ?>				
			<a href="#" class="close"><?php _e('Cancel'); ?></a>
		</div>
	</td>
</tr>