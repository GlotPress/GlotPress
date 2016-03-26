
<tr class='view' data-id="<?php echo esc_attr( $entry->id ); ?>">
	<td><?php echo esc_html( $entry->term ); ?></td>
	<td><?php echo esc_html( $entry->part_of_speech ); ?></td>
	<td><?php echo esc_html( $entry->translation ); ?></td>
	<td><?php echo make_clickable( nl2br( esc_html( $entry->comment ) ) ); ?></td>

	<?php if ( $can_edit) : ?>
	<td class="actions">
		<ul>
			<li><a href="#" class="action edit"><?php _e( 'Details', 'glotpress' ); ?></a></li>
		</ul>
	</td>
	<?php endif; ?>
</tr>
<tr id="editor-<?php echo esc_attr( $entry->id ); ?>" class="hide-if-js editor">
	<td colspan="5">
		<div class="strings">
			<dl>
				<dt><label for="glossary_entry_term_<?php echo esc_attr( $entry->id ); ?>"><?php _ex( 'Original term:', 'glossary entry', 'glotpress' ); ?></label></dt>
				<dd><input type="text" name="glossary_entry[<?php echo esc_attr( $entry->id );?>][term]" id="glossary_entry_term_<?php echo esc_attr( $entry->id ); ?>" value="<?php echo esc_attr( $entry->term ); ?>"></dd>
				<dt><label for="glossary_entry_post_<?php echo esc_attr( $entry->id ); ?>"><?php _ex( 'Part of speech', 'glossary entry', 'glotpress' ); ?></label></dt>
				<dd><select name="glossary_entry[<?php echo esc_attr( $entry->id );?>][part_of_speech]" id="glossary_entry_pos_<?php echo esc_attr( $entry->id ); ?>">
				<?php
					foreach ( GP::$glossary_entry->parts_of_speech as $pos => $name ) {
						$selected = $pos == $entry->part_of_speech ? " selected='selected'" : '';
						echo "\t<option value='".esc_attr( $pos )."' $selected>" . esc_html( $name ) . "</option>\n";
					}
				?>
				</select></dd>
				<dt><label for="glossary_entry_comments_<?php echo esc_attr( $entry->id ); ?><?php echo esc_attr( $entry->id ); ?>"><?php _ex( 'Comments', 'glossary entry', 'glotpress' ); ?></label></dt>
				<dd><textarea type="text" name="glossary_entry[<?php echo esc_attr( $entry->id );?>][comment]" id="glossary_entry_comments_<?php echo esc_attr( $entry->id ); ?>"><?php echo esc_textarea( $entry->comment );?></textarea></dd>
				<dt><label for="glossary_entry_translation_<?php echo esc_attr( $entry->id ); ?>"><?php _ex( 'Translation', 'glossary entry', 'glotpress' ); ?></label></dt>
				<dd><input type="text" name="glossary_entry[<?php echo esc_attr( $entry->id );?>][translation]" id="glossary_entry_translation_<?php echo esc_attr( $entry->id ); ?>" value="<?php echo esc_attr( $entry->translation ); ?>"></dd>
			</dl>
			<p>
				<input type="hidden" name="glossary_entry[<?php echo esc_attr( $entry->id );?>][glossary_id]" value="<?php echo esc_attr( $entry->glossary_id );?>">
				<input type="hidden" name="glossary_entry[<?php echo esc_attr( $entry->id );?>][glossary_entry_id]" value="<?php echo esc_attr( $entry->id );?>">
				<button class="action save" data-nonce="<?php echo esc_attr( wp_create_nonce( 'edit-glossary-entry_' . $entry->id ) ); ?>"><?php _e( 'Save', 'glotpress' ); ?></button><span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="#" class="action cancel"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
			</p>
		</div>

		<div class="meta">
			<h3><?php _e( 'Meta', 'glotpress' ); ?></h3>
			<dl>
				<dt><?php _e( 'Last Modified:', 'glotpress' ); ?></dt>
				<dd><?php echo $entry->date_modified; ?></dd>
			</dl>
			<?php if ( $entry->user_login ): ?>
			<dl>
				<dt><?php _x( 'By:','by author', 'glotpress' ); ?></dt>
				<dd><?php
				if ( $entry->user_display_name && $entry->user_display_name != $entry->user_login ) {
					printf( '%s (%s)', $entry->user_display_name, $entry->user_login );
				} else {
					echo $entry->user_login;
				}
				?></dd>
			</dl>
			<dl>
				<dt><?php _e( 'Actions:', 'glotpress' ); ?></dt>
				<dd>
					<button class="delete" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'delete-glossary-entry_' . $entry->id ) ); ?>"><?php _ex( 'Delete', 'delete glossary entry', 'glotpress' ); ?></button>
				</dd>
			</dl>
			<?php endif; ?>
		</div>
	</td>
</tr>
<?php //TODO: last modified, by who ?>
