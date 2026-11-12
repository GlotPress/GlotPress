<?php
/**
 * The user settings block
 *
 * A single table that contains all of the user settings, which is included as part of gp-templates/settings.php.
 *
 * @link http://glotpress.org
 *
 * @package GlotPress
 * @since 2.0.0
 */

$gp_per_page = (int) get_user_option( 'gp_per_page' );
if ( 0 === $gp_per_page ) {
	$gp_per_page = 15;
}

$gp_default_sort = get_user_option( 'gp_default_sort' );
if ( ! is_array( $gp_default_sort ) ) {
	$gp_default_sort = array(
		'by'  => 'priority',
		'how' => 'desc',
	);
}

$gp_timezone         = gp_get_user_timezone_choice();
$gp_date_format      = gp_get_user_date_format_choice();
$gp_use_24_hour_time = gp_user_uses_24_hour_time();
?>

<table class="form-table">
	<tr>
		<th><label for="per_page"><?php _e( 'Number of items per page:', 'glotpress' ); ?></label></th>
		<td><input type="number" id="per_page" name="per_page" value="<?php echo esc_attr( $gp_per_page ); ?>"/></td>
	</tr>
	<tr>
		<th><label for="default_sort[by]"><?php _e( 'Default Sort By:', 'glotpress' ); ?></label></th>
		<td>
			<?php
			$sort_bys = wp_list_pluck( gp_get_sort_by_fields(), 'title' );

			echo gp_radio_buttons( 'default_sort[by]', $sort_bys, gp_array_get( $gp_default_sort, 'by', 'priority' ) );
			?>
		</td>
	</tr>
	<tr>
		<th><label for="default_sort[how]"><?php _e( 'Default Sort Order:', 'glotpress' ); ?></label></th>
		<td>
			<?php
			echo gp_radio_buttons(
				'default_sort[how]',
				array(
					'asc'  => __( 'Ascending', 'glotpress' ),
					'desc' => __( 'Descending', 'glotpress' ),
				),
				gp_array_get( $gp_default_sort, 'how', 'desc' )
			);
			?>
		</td>
	</tr>
	<tr>
		<th><label for="timezone"><?php _e( 'Timezone:', 'glotpress' ); ?></label></th>
		<td>
			<select id="timezone" name="timezone">
				<?php echo wp_timezone_choice( $gp_timezone, get_user_locale() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The options are escaped by WordPress. ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><label for="date_format"><?php _e( 'Date Format:', 'glotpress' ); ?></label></th>
		<td>
			<select id="date_format" name="date_format">
				<option value="">
					<?php
					/* translators: %s: The current date in the date format of the site. */
					printf( __( 'Site default (%s)', 'glotpress' ), esc_html( wp_date( get_option( 'date_format' ) ) ) );
					?>
				</option>
				<?php foreach ( gp_get_date_format_choices() as $gp_format ) : ?>
					<option value="<?php echo esc_attr( $gp_format ); ?>"<?php selected( $gp_date_format, $gp_format ); ?>><?php echo esc_html( wp_date( $gp_format ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php _e( 'Time Format:', 'glotpress' ); ?></th>
		<td>
			<label for="time_format_24h">
				<input type="checkbox" id="time_format_24h" name="time_format_24h" value="1"<?php checked( $gp_use_24_hour_time ); ?> />
				<?php _e( 'Use 24-hour time', 'glotpress' ); ?>
			</label>
		</td>
	</tr>
</table>
