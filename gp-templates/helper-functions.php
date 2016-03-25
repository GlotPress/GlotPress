<?php
gp_enqueue_style( 'base' );
gp_enqueue_script( 'jquery' );


function prepare_original( $text ) {
	$text = str_replace( array("\r", "\n"), "<span class='invisibles' title='" . esc_attr__( 'New line', 'glotpress' ) . "'>&crarr;</span>\n", $text);
	$text = str_replace( "\t", "<span class='invisibles' title='" . esc_attr__( 'Tab character', 'glotpress' ) . "'>&rarr;</span>\t", $text);

	// Glossaries are injected into the translations prior to escaping and prepare_original() being run.
	$glossary_entries = array();
	$text = preg_replace_callback( '!(<span class="glossary-word"[^>]+>)!i', function( $m ) use ( &$glossary_entries ) {
		$item_number = count( $glossary_entries );
		$glossary_entries[ $item_number ] = $m[0];
		return "<span GLOSSARY={$item_number}>";
	}, $text );

	// Wrap full HTML tags with a notranslate class
	$text = preg_replace( '/(&lt;.+?&gt;)/', '<span class="notranslate">\\1</span>', $text );
	// Break out & back into notranslate for translatable attributes
	$text = preg_replace( '/(title|aria-label)=([\'"])([^\\2]+?)\\2/', '\\1=\\2</span>\\3<span class="notranslate">\\2', $text );
	// Wrap placeholders with notranslate
	$text = preg_replace( '/(%(\d+\$(?:\d+)?)?[bcdefgosuxEFGX])/', '<span class="notranslate">\\1</span>', $text );

	// Put the glossaries back!
	$text = preg_replace_callback( '!(<span GLOSSARY=(\d+)>)!', function( $m ) use ( $glossary_entries ) {
		return $glossary_entries[ $m[2] ];
	}, $text );

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

	uasort( $glossary_entries_terms, function( $a, $b ) { return gp_strlen($a) < gp_strlen($b); } );

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
			$replacement = '<span class="glossary-word" data-translations="' . htmlspecialchars( wp_json_encode( $glossary_data ), ENT_QUOTES, 'UTF-8') . '">$1</span>';
			$translations[$key]->singular_glossary_markup = preg_replace( '/\b(' . preg_quote( $term, '/' ) . '[es|s]?)(?![^<]*<\/span>)\b/iu', $replacement, $translations[$key]->singular_glossary_markup );

			if ( $t->plural ) {
				$translations[$key]->plural_glossary_markup = preg_replace( '/\b(' . preg_quote( $term, '/' ) . '[es|s]?)(?![^<]*<\/span>)\b/iu', $replacement, $translations[$key]->plural_glossary_markup );
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
				<strong><?php _e( 'Warning:', 'glotpress' ); ?></strong> <?php echo esc_html( $warning['value'] ); ?>

				<?php if( $can_approve ): ?>
					<a href="#" class="discard-warning" data-nonce="<?php echo esc_attr( wp_create_nonce( 'discard-warning_' . $index . $warning['key'] ) ); ?>" data-key="<?php echo esc_attr( $warning['key'] ); ?>" data-index="<?php echo esc_attr( $index ); ?>"><?php _e( 'Discard', 'glotpress' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<blockquote><em><small><?php echo esc_translation( gp_array_get( $entry->translations, $index ) ); ?></small></em></blockquote>
		<textarea class="foreign-text" name="translation[<?php echo $entry->original_id; ?>][]" <?php echo $disabled; ?>><?php echo esc_translation(gp_array_get($entry->translations, $index)); ?></textarea>

		<p>
			<?php
			if ( $can_edit ) {
				gp_entry_actions();
			}
			elseif ( is_user_logged_in() ) {
				_e( 'You are not allowed to edit this translation.', 'glotpress' );
			}
			else {
				printf( __( 'You <a href="%s">have to log in</a> to edit this translation.', 'glotpress' ), esc_url( wp_login_url() ) );
			}
			?>
		</p>
	</div>
	<?php
}

function display_status( $status ) {
	$status = preg_replace( '/^[+-]/', '', $status);
	return $status ? $status : __( 'untranslated', 'glotpress' );
}

function references( $project, $entry ) {

	/**
	 * Filter whether to show references of a translation string on a translation row.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean           $references Whether to show references.
	 * @param GP_Project        $project    The current project.
	 * @param Translation_Entry $entry      Translation entry object.
	 */
	$show_references = apply_filters( 'gp_show_references', (bool) $entry->references, $project, $entry );

	if ( ! $show_references ) return;
	?>
	<dl><dt>
	<?php _e( 'References:', 'glotpress' ); ?>
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
