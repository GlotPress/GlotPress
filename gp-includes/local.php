<?php

class GP_Local {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_glotpress_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'save_glotpress_settings' ) );
	}

	public static function is_active() {
		static $is_active;
		if ( ! isset( $is_active ) ) {
			$is_active = get_option( 'gp_enable_local_translation' );
		}
		return $is_active;
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
			'manage_options',
			'glotpress',
			array( $this, 'show_settings_page' ),
			'dashicons-translation'
		);
		add_submenu_page(
			'glotpress',
			esc_html__( 'Settings', 'glotpress' ),
			esc_html__( 'Settings', 'glotpress' ),
			'manage_options',
			'glotpress',
			array( $this, 'show_settings_page' )
		);
		add_submenu_page(
			'glotpress',
			esc_html__( 'Local Projects', 'glotpress' ),
			esc_html__( 'Local Projects', 'glotpress' ),
			'manage_options',
			'glotpress-local-projects',
			array( $this, 'show_local_projects' ),
		);
	}

	/**
	 * Saves the settings.
	 *
	 * @return void
	 */
	public function save_glotpress_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['gp_save_settings_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['gp_save_settings_nonce'], 'gp_save_settings' ) ) {
			wp_die( esc_html__( 'Your nonce could not be verified.', 'glotpress' ) );
		}
		if ( isset( $_POST['gp_enable_local_translation'] ) ) {
			update_option( 'gp_enable_local_translation', 1 );
		} else {
			delete_option( 'gp_enable_local_translation' );
		}
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
			<form method="post">
				<?php wp_nonce_field( 'gp_save_settings', 'gp_save_settings_nonce' ); ?>
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
									<?php $checked = self::is_active() ? 'checked' : ''; ?>
									<input type="checkbox" name="gp_enable_local_translation" <?php echo esc_html( $checked ); ?>>
									<span><?php esc_html_e( 'Enable Local Translations', 'glotpress' ); ?></span>
							   </label>
							</p>
						</td>
					</tr>
					</tbody>
				</table>
				<?php submit_button( esc_attr__( 'Save Settings', 'glotpress' ), 'primary' ); ?>
			</form>

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
			<?php
			if ( ! self::is_active() ) {
				?>
					<div class="notice notice-error">
						<p>
						<?php
							echo wp_kses(
								sprintf(
									/* Translators: %s is GlotPress settings URL. */
									__( 'You have to enable the local translations. You can do it <a href="%s">here</a>.', 'glotpress' ),
									admin_url( 'admin.php?page=glotpress' )
								),
								array( 'a' => array( 'href' => array() ) )
							);
						?>
						</p>
					</div>
					<?php
					return;
			}
			?>
			<p>
				<?php esc_html_e( 'These are the plugins and themes that you have installed locally. With GlotPress you can change the translations of these.', 'glotpress' ); ?>
			</p>

			<!-- Start core -->
			<div class="tablenav">
				<span class="displaying-num alignright">
					<?php
						esc_html_e( '4 items', 'glotpress' );
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
							<span><?php esc_html_e( 'Description' ); ?></span>
						</th>
						<th scope="col" id="plugins-actions" style="width: 15%;">
							<span><?php esc_html_e( 'Actions' ); ?></span>
						</th>
					</tr>
				</thead>
				<tbody id="core-list">
					<tr>
						<td><?php esc_html_e( 'WordPress development', 'glotpress' ); ?></td>
						<td>
							<p>
								<?php esc_html_e( 'Strings from the main project.', 'glotpress' ); ?>
							</p>
							<p>
								<?php esc_html_e( 'Version', 'glotpress' ); ?>
								<?php echo esc_html( $wp_version ); ?>
							</p>
						<td>
							<?php
								echo gp_link_get(
									wp_nonce_url(
										gp_url( '/local/core/development' ),
										'gp-local-core-development'
									),
									esc_html(
										sprintf(
											/* Translators: %s is the language into which we will translate . */
											__( 'Translate to %s', 'glotpress' ),
											$language
										)
									),
									array( 'target' => '_blank' )
								);
							?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WordPress Continents & Cities', 'glotpress' ); ?></td>
						<td>
							<p>
								<?php esc_html_e( 'List with the continents and main cities around the world.', 'glotpress' ); ?>
							</p>
							<p>
								<?php esc_html_e( 'Version', 'glotpress' ); ?>
								<?php echo esc_html( $wp_version ); ?>
							</p>
						</td>
						<td>
							<?php
								echo gp_link_get(
									wp_nonce_url(
										gp_url( '/local/core/continents-cities' ),
										'gp-local-core-continents-cities'
									),
									esc_html(
										sprintf(
											/* Translators: %s is the language into which we will translate . */
											__( 'Translate to %s', 'glotpress' ),
											$language
										)
									),
									array( 'target' => '_blank' )
								);
							?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WordPress Administration', 'glotpress' ); ?></td>
						<td>
							<p>
								<?php esc_html_e( 'Strings from the WordPress administration.', 'glotpress' ); ?>
							</p>
							<p>
								<?php esc_html_e( 'Version', 'glotpress' ); ?>
								<?php echo esc_html( $wp_version ); ?>
							</p>
						</td>
						<td>
							<?php
								echo gp_link_get(
									wp_nonce_url(
										gp_url( '/local/core/administration' ),
										'gp-local-core-administration'
									),
									esc_html(
										sprintf(
											/* Translators: %s is the language into which we will translate . */
											__( 'Translate to %s', 'glotpress' ),
											$language
										)
									),
									array( 'target' => '_blank' )
								);
							?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WordPress Network Admin', 'glotpress' ); ?></td>
						<td>
							<p>
								<?php esc_html_e( 'Strings from the WordPress network administration.', 'glotpress' ); ?>
							</p>
							<p>
								<?php esc_html_e( 'Version', 'glotpress' ); ?>
								<?php echo esc_html( $wp_version ); ?>
							</p>
						</td>
						<td>
							<?php
								echo gp_link_get(
									wp_nonce_url(
										gp_url( '/local/core/network-admin' ),
										'gp-local-core-network-admin'
									),
									esc_html(
										sprintf(
											/* Translators: %s is the language into which we will translate . */
											__( 'Translate to %s', 'glotpress' ),
											$language
										)
									),
									array( 'target' => '_blank' )
								);
							?>
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
							<span><?php esc_html_e( 'Description' ); ?></span>
						</th>
						<th scope="col" id="plugins-actions" style="width: 15%;">
							<span><?php esc_html_e( 'Actions' ); ?></span>
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
							<?php
							if ( $plugin['TextDomain'] ) {
								echo gp_link_get(
									wp_nonce_url(
										gp_url( '/local/plugin/' . $plugin['TextDomain'] ),
										'gp-local-plugin-' . $plugin['TextDomain']
									),
									esc_html(
										sprintf(
										/* Translators: %s is the language into which we will translate . */
											__( 'Translate to %s', 'glotpress' ),
											$language
										)
									),
									array( 'target' => '_blank' )
								);
							}
							?>
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
							<span><?php esc_html_e( 'Description' ); ?></span>
						</th>
						<th scope="col" id="plugins-actions" style="width: 15%;">
							<span><?php esc_html_e( 'Actions' ); ?></span>
						</th>
					</tr>
				</thead>
				<tbody id="themes-list">
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
						<?php

						if ( $theme->get( 'TextDomain' ) ) {
							echo gp_link_get(
								wp_nonce_url(
									gp_url( '/local/theme/' . $theme->get( 'TextDomain' ) ),
									'gp-local-theme-' . $theme->get( 'TextDomain' )
								),
								esc_html(
									sprintf(
									/* Translators: %s is the language into which we will translate . */
										__( 'Translate to %s', 'glotpress' ),
										$language
									)
								),
								array( 'target' => '_blank' )
							);
						}
						?>
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
