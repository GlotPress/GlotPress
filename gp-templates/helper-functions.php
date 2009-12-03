<?php

function prepare_original( $text ) {
	$text = str_replace( array("\r", "\n"), "<span class='invisibles' title='New line'>&crarr;</span>\n", $text);
	$text = str_replace( "\t", "<span class='invisibles' title='Tab character'>&rarr;</span>\t", $text);
	return $text;
}

function textareas( $entry, $can_edit, $index = 0 ) {
	$disabled = $can_edit? '' : 'disabled="disabled"';
?>
<div class="textareas">
	<?php if( isset( $entry->warnings[$index] ) ):
			$referenceable = $entry->warnings[$index];
			$warning = each( $referenceable );
	?>
		<div class="warning secondary">
			<strong>Warning:</strong> <?php  echo esc_html( $warning['value'] ); ?>
			<?php if( GP::$user->current()->admin() ): // TODO: allow users with write permissions, too ?>
			<a href="#" class="discard-warning" key="<?php echo $warning['key'] ?>" index="<?php echo $index; ?>">Discard</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<textarea name="translation[<?php echo $entry->original_id; ?>][]" <?php echo $disabled; ?>><?php echo esc_translation(gp_array_get($entry->translations, $index)); ?></textarea>
<?php if ( $can_edit ): ?>
	<p>
		<a href="#" class="copy" tabindex="-1">Copy from original</a>
	</p>
<?php else: ?>
	<p>
		You <a href="<?php echo gp_url_login(); ?>">have to log in</a> to edit this translation.
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
	return $status? $status : 'untranslated';
}

function references( $project, $entry ) {
	if ( !$project->source_url_template() ) return;
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