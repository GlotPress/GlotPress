<?php

class GP_Local {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_glotpress_admin_menu' ) );
	}

	/**
	 * Adds the GlotPress menu to the admin menu.
	 *
	 * @return void
	 */
	public function add_glotpress_admin_menu() {
		add_menu_page(
			esc_html__( 'Local GlotPress', 'glotpress' ),
			'GlotPress',
			'read',
			'glotpress',
			array( $this, 'show_settings_page' ),
			'dashicons-translation'
		);
		add_submenu_page(
			'glotpress',
			esc_html__( 'Settings', 'glotpress' ),
			esc_html__( 'Settings', 'glotpress' ),
			'read',
			'glotpress',
			array( $this, 'show_settings_page' )
		);
		add_submenu_page(
			'glotpress',
			esc_html__( 'Local Projects', 'glotpress' ),
			esc_html__( 'Local Projects', 'glotpress' ),
			'read',
			'glotpress-local-projects',
			array( $this, 'show_local_projects' ),
		);
	}

	/**
	 * Shows the settings page.
	 *
	 * @return void
	 */
	public function show_settings_page() {
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Settings', 'glotpress' ); ?>
			</h1>
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Main Path', 'glotpress' ); ?>
					</th>
					<td>
						<p>
							<?php echo gp_link_get( gp_url( '/' ), esc_html__( 'GlotPress Main Path', 'glotpress' ), array( 'target' => '_blank' ) ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Local Translations', 'glotpress' ); ?>
					</th>
					<td>
						<p>
							<label>
								<input type="checkbox" name="gp_enable_local_translation" value="true">
								<?php esc_html_e( 'Enable Local Translations', 'glotpress' ); ?>
						   </label>
						</p>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Shows a page with a list with the core, the plugins and themes installed locally.
	 *
	 * @return void
	 */
	public function show_local_projects() {
		global $wp_version;
		$plugins = apply_filters( 'local_glotpress_local_plugins', get_plugins() );
		$themes  = apply_filters( 'local_glotpress_local_themes', wp_get_themes() );
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$languages = wp_get_available_translations();
		$language  = 'Unknown';
		if ( 'en_US' === get_user_locale() ) {
			$language = 'English (US)';
		} elseif ( isset( $languages[ get_user_locale() ] ) ) {
			$language = $languages[ get_user_locale() ]['native_name'];
		}
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Local GlotPress', 'glotpress' ); ?>
			</h1>
			<p>
				<?php esc_html_e( 'These are the plugins and themes that you have installed locally. With GlotPress you can change the translations of these.', 'glotpress' ); ?>
			</p>

			<!-- Start core -->
			<div class="tablenav">
				<span class="displaying-num alignright">
					<?php
						esc_html_e( '1 item', 'glotpress' );
					?>
				</span>
			</div>
			<table id="core-table" class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th scope="col" id="plugins-name" style="width: 15%;">
							<span><?php esc_html_e( 'WordPress Core', 'glotpress' ); ?></span>
						</th>
						<th scope="col" id="plugins-description" style="width: 70%;">
							<span><?php esc_html_e( 'Description', 'glotpress' ); ?></span>
						</th>
						<th scope="col" id="plugins-actions" style="width: 15%;">
							<span><?php esc_html_e( 'Actions', 'glotpress' ); ?></span>
						</th>
					</tr>
				</thead>
				<tbody id="core-list">
					<tr>
						<td><?php esc_html_e( 'WordPress core', 'glotpress' ); ?></td>
						<td><?php echo esc_html( $wp_version ); ?></td>
						<td>
							<a href="#">
								<?php
								echo esc_html(
									sprintf(
									/* Translators: %s is the language into which we will translate . */
										__( 'Translate to %s', 'glotpress' ),
										$language
									)
								);
								?>
							</a>
						</td>
					</tr>
				</tbody>
			</table>
			<!-- End core -->

			<!-- Start plugins -->
			<div class="tablenav">
				<span class="displaying-num alignright">
					<?php
						/* Translators: %s is the number of items to translate. */
						printf( _nx( '%s item', '%s items', count( $plugins ), 'Number of plugins', 'glotpress' ), number_format_i18n( count( $plugins ) ) );
					?>
				</span>
			</div>
			<table id="plugins-table" class="widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" id="plugins-name" style="width: 15%;">
							<span><?php esc_html_e( 'Plugin', 'glotpress' ); ?></span>
						</th>
						<th scope="col" id="plugins-description" style="width: 70%;">
							<span><?php esc_html_e( 'Description', 'glotpress' ); ?></span>
						</th>
						<th scope="col" id="plugins-actions" style="width: 15%;">
							<span><?php esc_html_e( 'Actions', 'glotpress' ); ?></span>
						</th>
					</tr>
				</thead>
				<tbody id="plugins-list">
					<?php foreach ( $plugins as $slug => $plugin ) : ?>
					<tr style="box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.1);">
						<td>
							<?php echo esc_html( $plugin['Name'] ); ?>
						</td>
						<td>
							<p>
								<?php echo esc_html( wp_trim_words( $plugin['Description'], 50 ) ); ?>
							</p>
							<p>
								<?php

								echo esc_html(
									sprintf(
									/* Translators: %s is the plugin version. */
										__( 'Version %s', 'glotpress' ),
										$plugin['Version']
									)
								);
								?>
							</p>
						</td>
						<td>
							<a href="#">
								<?php
								echo esc_html(
									sprintf(
									/* Translators: %s is the language into which we will translate . */
										__( 'Translate to %s', 'glotpress' ),
										$language
									)
								);
								?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<!-- End plugins -->

			<!-- Start themes -->
			<div class="tablenav">
				<span class="displaying-num alignright">
					<?php
						/* Translators: %s is the number of items to translate. */
						printf( _nx( '%s item', '%s items', count( $themes ), 'Number of plugins', 'glotpress' ), number_format_i18n( count( $themes ) ) );
					?>
				</span>
			</div>
			<table id="themes-table" class="wp-list-table widefat fixed striped table-view-list profiles">
				<thead>
					<tr>
						<th scope="col" id="plugins-name" style="width: 15%;">
							<span><?php esc_html_e( 'Theme', 'glotpress' ); ?></span>
						</th>
						<th scope="col" id="plugins-description" style="width: 70%;">
							<span><?php esc_html_e( 'Description', 'glotpress' ); ?></span>
						</th>
						<th scope="col" id="plugins-actions" style="width: 15%;">
							<span><?php esc_html_e( 'Actions', 'glotpress' ); ?></span>
						</th>
					</tr>
				</thead>
				<tbody id="plugins-list">
					<?php foreach ( $themes as $slug => $theme ) : ?>
					<tr style="box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.1);">
						<td>
							<?php echo esc_html( $theme['Name'] ); ?>
						</td>
						<td>
							<p>
								<?php echo esc_html( wp_trim_words( $theme['Description'], 50 ) ); ?>
							</p>
							<p>
								<?php

									echo esc_html(
										sprintf(
											/* Translators: %s is the theme version. */
											__( 'Version %s', 'glotpress' ),
											$theme['Version']
										)
									);
								?>
							</p>
						</td>
						<td>
							<a href="#">
								<?php
									echo esc_html(
										sprintf(
											/* Translators: %s is the language into which we will translate . */
											__( 'Translate to %s', 'glotpress' ),
											$language
										)
									);
								?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<!-- Finish themes -->

			<div style="padding-top: 40px;">
				<button class="button-primary">
					<?php esc_html_e( 'Share your translations with WordPress.org', 'glotpress' ); ?>
				</button>
			</div>

		</div>
		<?php
	}
}
