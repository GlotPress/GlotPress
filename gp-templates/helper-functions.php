<?php
function prepare_original( $text ) {
	$text = str_replace( array("\r", "\n"), "<span class='invisibles' title='".esc_attr(__('New line'))."'>&crarr;</span>\n", $text);
	$text = str_replace( "\t", "<span class='invisibles' title='".esc_attr(__('Tab character'))."'>&rarr;</span>\t", $text);
	return $text;
}

function textareas( $entry, $permissions, $index = 0 ) {
	list( $can_edit, $can_approve ) = $permissions;
	$disabled = $can_edit? '' : 'disabled="disabled"';
	?>
	<div class="textareas">
		<?php
		if( isset( $entry->warnings[$index] ) ):
			$referenceable = $entry->warnings[$index];
			$warning = each( $referenceable );
			?>
			<div class="warning secondary">
				<?php printf( __('<strong>Warning:</strong> %s'), esc_html( $warning['value'] ) ); ?>

				<?php if( $can_approve ): ?>
					<a href="#" class="discard-warning" key="<?php echo $warning['key'] ?>" index="<?php echo $index; ?>"><?php _e('Discard'); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<blockquote><em><small><?php echo esc_translation( gp_array_get( $entry->translations, $index ) ); ?></small></em></blockquote>
		<textarea class="foreign-text" name="translation[<?php echo $entry->original_id; ?>][]" <?php echo $disabled; ?>><?php echo esc_translation(gp_array_get($entry->translations, $index)); ?></textarea>

		<?php if ( $can_edit ): ?>
			<p>
				<?php gp_entry_actions(); ?>
			</p>
		<?php else: ?>
			<p>
				<?php printf( __('You <a href="%s">have to log in</a> to edit this translation.'), gp_url_login() ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Similar to esc_html() but allows double-encoding.
 */
function esc_translation( $text ) {
	return wp_specialchars( $text, ENT_NOQUOTES, false, true );
}

function display_status( $status ) {
	$status = preg_replace( '/^[+-]/', '', $status);
	return $status ? $status : __('untranslated');
}

function references( $project, $entry ) {
	if ( ! $entry->references ) return;
	?>
	<dl><dt>
	<?php _e('References:'); ?>
	<ul class="refs">
		<?php
		foreach( $entry->references as $reference ):
			list( $file, $line ) = array_pad( explode( ':', $reference ), 2, 0 );
			// TODO: allow the user to override the project setting
			if ( $source_url = $project->source_url( $file, $line ) ):
				?>
				<li><a target="_blank" tabindex="-1" href="<?php echo $source_url; ?>"><?php echo $file.':'.$line ?></a></li>
				<?php
			else :
				echo "<li>$file:$line</li>";
			endif;
		endforeach;
		?>
	</ul></dt></dl>
<?php
}
