<?php
gp_title( sprintf( __( 'Translations &lt; %s &lt; GlotPress' ), $project->name ) );
gp_breadcrumb( array(
	gp_link_home_get(),
	gp_link_project_get( $project, $project->name ),
	$locale->combined_name(),
) );
wp_enqueue_script( 'editor' );
$parity = gp_parity_factory();
gp_tmpl_header();
?>
<table id="translations" class="translations">
	<tr>
		<th class="original"><?php _e('Original string'); ?></th>
		<th class="translation"><?php _e('Translation'); ?></th>
		<th><?php _e('Actions'); ?></th>
	</tr>
<?php foreach( $translations as $t ): ?>
	<tr class="preview <?php echo $parity(); ?>" id="preview-<?php echo $t->original_id ?>" original="<?php echo $t->original_id; ?>">
		<td class="original">			
			<?php echo gp_h( $t->singular ); ?>
			<?php if ( $t->context ): ?>
			<span class="context" title="<?php printf( __('Context: %s'), gp_h($t->context) ); ?>"><?php echo gp_h($t->context); ?></span>
			<?php endif; ?>

		</td>
		<td class="translation"><?php echo gp_h( $t->translation_0 ); ?></td>
		<td class="actions">
			<a href="#" original="<?php echo $t->original_id; ?>" class="edit"><?php _e('Edit'); ?></a>
		</td>
	</tr>
	<tr class="editor" id="editor-<?php echo $t->original_id; ?>" original="<?php echo $t->original_id; ?>">
		<td colspan="3">
			<?php if ( $t->plural ): ?>
			<p><?php printf(__('Singular: %s'), '<span class="original">'.gp_h($t->singular).'</span>'); ?></p>
			<p><?php printf(__('Plural: %s'), '<span class="original">'.gp_h($t->plural).'</span>'); ?></p>
			<?php else: ?>
			<p class="original"><?php echo gp_h($t->singular); ?></p>
			<?php endif; ?>
			<?php if ($t->plural): ?>
				<div class="textareas" id="tabs-<?php echo $t->original_id ?>">
					<ul>
						<!--
							TODO: use the correct number of plurals
							TODO: dynamically set the number of rows
						-->					
						<li><a href="#tabs-<?php echo $t->original_id; ?>-1">Singular</a></li>
						<li><a href="#tabs-<?php echo $t->original_id; ?>-2">Plural</a></li>
					</ul>
					<div id="tabs-<?php echo $t->original_id; ?>-1">
						<textarea name="translation[<?php echo $t->original_id; ?>][]" rows="8" cols="80"><?php echo $t->translation_0 ?></textarea>
					</div>
					<div id="tabs-<?php echo $t->original_id; ?>-2">
						<textarea name="translation[<?php echo $t->original_id; ?>][]" rows="8" cols="80"><?php echo $t->translation_1 ?></textarea>
					</div>

				</div>
			<?php else: ?>
				<div class="textareas">
					<textarea name="translation[<?php echo $t->original_id; ?>][]" rows="8" cols="80"><?php echo $t->translation_0 ?></textarea>				
				</div>
			<?php endif; ?>
			<div class="meta">
				<?php if ( $t->context ): ?>
				<p class="context"><?php printf( __('Context: %s'), '<span class="context">'.gp_h($t->context).'</span>' ); ?></p>
				<?php endif; ?>
				<?php if ( $t->comment ): ?>
				<p class="comment"><?php printf( __('Comment: %s'), gp_h($t->comment) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="actions">
				<button class="ok">Add translation</button>
				<a href="#" class="close"><?php _e('Close'); ?></a>
			</div>
		</td>
	</tr>
<?php endforeach; ?>
</table>
<?php gp_tmpl_footer(); ?>