<?php
function prepare_original( $text ) {
	$text = str_replace( array("\r", "\n"), "<span class='invisibles' title='".esc_attr(__('New line'))."'>&crarr;</span>\n", $text);
	$text = str_replace( "\t", "<span class='invisibles' title='".esc_attr(__('Tab character'))."'>&rarr;</span>\t", $text);

	return $text;
}

function map_glossary_entries_to_translations_originals( $translations, $glossary ) {
	$glossary_entries = GP::$glossary_entry->by_glossary_id( $glossary->id );

	if ( empty ( $glossary_entries ) ) {
		return $translations;
	}

	$glossary_entries_terms = array();

	//Create array of glossary terms, longest first
	foreach ( $glossary_entries as $key => $value ) {
		$glossary_entries_terms[ $key ] = $value->term;
	}

	uasort( $glossary_entries_terms, lambda('$a, $b', 'gp_strlen($a) < gp_strlen($b)' ) );

	foreach ( $translations as $key => $t ) {
		//Save our current singular/plural strings before attempting any markup change. Also escape now, since we're going to add some html.
		$translations[$key]->singular_glossary_markup = esc_translation( $t->singular );
		$translations[$key]->plural_glossary_markup   = esc_translation( $t->plural );

		//Search for glossary terms in our strings
		$matching_entries = array();

		foreach( $glossary_entries_terms as $i => $term ) {
			$glossary_entry = $glossary_entries[ $i ];

			if ( gp_stripos( $t->singular . ' ' . $t->plural, $term ) !== false ) {
				$matching_entries[$term][] = array( 'translation' => $glossary_entry->translation, 'pos' => $glossary_entry->part_of_speech, 'comment' => $glossary_entry->comment );
			}
		}

		//Replace terms in strings with markup
		foreach( $matching_entries as $term => $glossary_data ) {
			$replacement = '<span class="glossary-word" data-translations="' . htmlspecialchars( json_encode( $glossary_data ), ENT_QUOTES, 'UTF-8') . '">$1</span>';
			$translations[$key]->singular_glossary_markup = preg_replace( '/\b(' . preg_quote( $term, '/' ) . '[es|s]?)(?![^<]*<\/span>)\b/iu', $replacement, $translations[$key]->singular_glossary_markup, 1 );

			if ( $t->plural ) {
				$translations[$key]->plural_glossary_markup = preg_replace( '/\b(' . preg_quote( $term, '/' ) . '[es|s]?)(?![^<]*<\/span>)\b/iu', $replacement, $translations[$key]->plural_glossary_markup, 1 );
			}
		}
	}

	return $translations;
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
	return _wp_specialchars( $text, ENT_NOQUOTES, false, true );
}

function display_status( $status ) {
	$status = preg_replace( '/^[+-]/', '', $status);
	return $status ? $status : __('untranslated');
}

function references( $project, $entry ) {
	$show_references = apply_filters( 'show_references', (bool) $entry->references, $project, $entry );

	if ( ! $show_references ) return;
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
