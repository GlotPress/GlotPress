<?php
gp_title( sprintf( __( 'Translations &lt; %s &lt; %s &lt; GlotPress' ), $translation_set->name, $project->name ) );
gp_breadcrumb( array(
	gp_link_project_get( $project, $project->name ),
	$locale->combined_name(),
	$translation_set->name,
) );
wp_enqueue_script( 'editor' );
$parity = gp_parity_factory();
gp_tmpl_header();
$i = 0;
function textareas( $entry, $index = 0 ) {
?>
<div class="textareas">
	<textarea name="translation[<?php echo $entry->original_id; ?>][]" rows="8" cols="80"><?php echo esc_html($entry->translations[$index]); ?></textarea>
	<p>
		<a href="#" class="copy" tabindex="-1">Copy from original</a>
	</p>
</div>
<?php
}

function references( $project, $entry ) {
	if ( !$project->source_url_template ) return;
?>
	References:
			<ul class="refs">
	<?php
		foreach( $entry->references as $reference ):
			list( $file, $line ) = array_pad( explode( ':', $reference ), 2, 0 );
			// TODO: allow the user to override the project setting
			if ( $source_url = $project->source_url( $file, $line ) ):
	?>
				<li><a target="_blank" tabindex="-1" href="<?php echo $source_url; ?>"><?php echo $file.':'.$line ?></a></li>

	<?php
			endif;
		endforeach;
	?>
			</ul>
<?php
}

echo gp_pagination( $page, $per_page, $total_translations_count );
?>
<table id="translations" class="translations clear">
	<tr>
		<th>#</th>
		<th class="original"><?php _e('Original string'); ?></th>
		<th class="translation"><?php _e('Translation'); ?></th>
		<th><?php _e('Actions'); ?></th>
	</tr>
<?php foreach( $translations->entries as $t ):
		$class = str_replace( array( '+', '-' ), '', $t->translation_status );
		if ( !$class )  $class = 'untranslated';
?>
	<tr class="preview <?php echo $parity().' status-'.$class ?>" id="preview-<?php echo $t->original_id ?>" original="<?php echo $t->original_id; ?>">
		<td><?php echo $i++; ?></td>
		<td class="original">			
			<?php echo esc_html( $t->singular ); ?>
			<?php if ( $t->context ): ?>
			<span class="context" title="<?php printf( __('Context: %s'), esc_html($t->context) ); ?>"><?php echo esc_html($t->context); ?></span>
			<?php endif; ?>

		</td>
		<td class="translation"><?php echo esc_html( $t->translations[0] ); ?></td>
		<td class="actions">
			<a href="#" original="<?php echo $t->original_id; ?>" class="action edit"><?php _e('Edit'); ?></a>
		</td>
	</tr>
	<tr class="editor" id="editor-<?php echo $t->original_id; ?>" original="<?php echo $t->original_id; ?>">
		<td colspan="3">
			<?php if ( !$t->plural ): ?>
			<p class="original"><?php echo esc_html($t->singular); ?></p>
			<?php textareas( $t ); ?>
			<?php else: ?>
				<!--
					TODO: use the correct number of plurals
					TODO: dynamically set the number of rows
				-->				
				<p><?php printf(__('Singular: %s'), '<span class="original">'.esc_html($t->singular).'</span>'); ?></p>
				<?php textareas( $t, 0 ); ?>
				<p class="clear"><?php printf(__('Plural: %s'), '<span class="original">'.esc_html($t->plural).'</span>'); ?></p>
				<?php textareas( $t, 1 ); ?>				
			<?php endif; ?>
			<div class="meta">
				<?php if ( $t->context ): ?>
					<p class="context"><?php printf( __('Context: %s'), '<span class="context">'.esc_html($t->context).'</span>' ); ?></p>
				<?php endif; ?>
				<?php if ( $t->extracted_comment ): ?>
					<p class="comment"><?php printf( __('Comment: %s'), make_clickable( esc_html($t->extracted_comment) ) ); ?></p>
				<?php endif; ?>
				<?php references( $project, $t ); ?>				
			</div>
			<div class="actions">
				<button class="ok">Add translation</button>
				<a href="#" class="close"><?php _e('Close'); ?></a>
			</div>
		</td>
	</tr>
<?php endforeach; ?>
</table>
<?php echo gp_pagination( $page, $per_page, $total_translations_count ); ?>
<p class="clear">
	<?php gp_link( gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'import-translations' ) ), __('Import translations') ); ?> |
	<?php gp_link( gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'export-translations' ) ), __('Export translations') ); ?>
</p>

<?php gp_tmpl_footer(); ?>