<?php
/**
 * Functionality for the Local GlotPress.
 *
 * @package GlotPress
 * @subpackage Local
 */

/**
 * This class contains the functionality for the Local GlotPress.
 */
class GP_Local {
	const CORE_PROJECTS = array(
		'wp/dev',
		'wp/dev/cc',
		'wp/dev/admin',
		'wp/dev/admin/network',
	);

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_glotpress_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'save_glotpress_settings' ) );
		add_filter( 'gp_local_project_path', array( $this, 'get_local_project_path' ) );
		add_filter( 'gp_local_project_po', array( $this, 'get_local_project_po' ), 10, 5 );
	}

	/**
	 * Determines if local is active.
	 *
	 * @return     bool  True if active, False otherwise.
	 */
	public static function is_active() {
		static $is_active;
		if ( ! isset( $is_active ) ) {
			if ( '1' === gp_post( 'gp_local_translation_enabled' ) && ! gp_post( 'gp_enable_local_translation' ) ) {
				// Deactivate local translation even before the option is saved.
				$is_active = false;
			} elseif ( '0' === gp_post( 'gp_local_translation_enabled' ) && gp_post( 'gp_enable_local_translation' ) ) {
				// Activate local translation even before the option is saved.
				$is_active = true;
			} else {
				$is_active = get_option( 'gp_enable_local_translation' );
			}
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
			'edit_posts',
			'glotpress',
			array( $this, 'show_welcome_page' ),
			'dashicons-translation'
		);
		add_submenu_page(
			'glotpress',
			esc_html__( 'Welcome', 'glotpress' ),
			esc_html__( 'Welcome', 'glotpress' ),
			'edit_posts',
			'glotpress',
			array( $this, 'show_welcome_page' )
		);
		add_submenu_page(
			'glotpress',
			esc_html__( 'Settings', 'glotpress' ),
			esc_html__( 'Settings', 'glotpress' ),
			'manage_options',
			'glotpress-settings',
			array( $this, 'show_settings_page' )
		);
		if ( self::is_active() ) {
			add_submenu_page(
				'glotpress',
				esc_html__( 'Local GlotPress', 'glotpress' ),
				esc_html__( 'Local GlotPress', 'glotpress' ),
				'edit_posts',
				'glotpress-local-glotpress',
				array( $this, 'show_local_projects' ),
			);
		}
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
		if ( isset( $_POST['gp_enable_inline_translation'] ) ) {
			update_option( 'gp_enable_inline_translation', 1 );
		} else {
			delete_option( 'gp_enable_inline_translation' );
		}
	}

	/**
	 * Shows the welcome page.
	 *
	 * @return void
	 */
	public function show_welcome_page() {
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Welcome to GlotPress', 'glotpress' ); ?>
			</h1>
			<p>
				<?php esc_html_e( 'With GlotPress you can translate WordPress.', 'glotpress' ); ?>
			</p>
			<h2><?php esc_html_e( 'Local GlotPress', 'glotpress' ); ?></h2>
			<p>
				<?php esc_html_e( 'Since version 5, GlotPress also has a local mode that allows you to translate your current WordPress install, including plugins and themes.', 'glotpress' ); ?>
			</p>

			<?php if ( ! self::is_active() ) : ?>
				<p>
					<span><?php esc_html_e( 'Local GlotPress mode is not active.' ); ?></span>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=glotpress-settings' ) ); ?>"><?php esc_html_e( 'Activate Local GlotPress mode here.' ); ?></a>
						<?php else : ?>
							<span><?php esc_html_e( 'Please ask your administrator to activate Local GlotPress mode.' ); ?></span>
						<?php endif; ?>
				</p>
			<?php else : ?>
				<h2><?php esc_html_e( 'Inline Translation', 'glotpress' ); ?></h2>
				<p>
					<span><?php esc_html_e( 'To make translating easier, Local GlotPress provides inline translation so that you can enter translations where you see them.', 'glotpress' ); ?></span>
					<span><?php esc_html_e( 'Clicking the globe icon will activate inline translation.', 'glotpress' ); ?></span>
					<span><?php esc_html_e( 'Translatable text will glow in red if it is untranslated, yellow if it has a waiting translation, and green when it is already translated.', 'glotpress' ); ?></span>
					<span><?php esc_html_e( 'Right-click glowing text to add or change its translation.', 'glotpress' ); ?></span>
				</p>
				<?php if ( ! GP_Inline_Translation::is_active() ) : ?>
				<p>
					<span><?php esc_html_e( 'Inline translation is not active.' ); ?></span>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=glotpress-settings' ) ); ?>"><?php esc_html_e( 'Activate inline translations here.' ); ?></a>
						<?php else : ?>
						<span><?php esc_html_e( 'Please ask your administrator to activate inline translations.' ); ?></span>
						<?php endif; ?>
				<?php endif; ?>
				</p>

			<?php endif; ?>
			<p>
				<?php echo gp_link_get( gp_url( '/' ), esc_html__( 'Go to the GlotPress interface', 'glotpress' ), array( 'target' => '_blank' ) ); ?>
			</p>
		</div>
		<?php
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
							<?php esc_html_e( 'Local Translations', 'glotpress' ); ?>
						</th>
						<td>
							<p>
								<label>
									<input type="hidden" name="gp_local_translation_enabled" value="<?php echo esc_attr( self::is_active() ? '1' : '0' ); ?>" />
									<input type="checkbox" name="gp_enable_local_translation" <?php checked( self::is_active() ); ?> />
									<span><?php esc_html_e( 'Enable Local Translations', 'glotpress' ); ?></span>
								</label>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Inline Translation', 'glotpress' ); ?>
						</th>
						<td>
							<?php if ( class_exists( 'GP_Inline_Translation' ) ) : ?>
								<p>
									<label>
										<input type="hidden" name="gp_inline_translation_enabled" value="<?php echo esc_attr( GP_Inline_Translation::is_active() ? '1' : '0' ); ?>" />
										<input type="checkbox" name="gp_enable_inline_translation" <?php checked( GP_Inline_Translation::is_active() ); ?> />
										<span><?php esc_html_e( 'Enable Inline Translations', 'glotpress' ); ?></span>
									</label>
								</p>
							<?php else : ?>
								<p>
									<?php esc_html_e( 'Local GlotPress must be activae to enable local translation.', 'glotpress' ); ?>
								</p>
							<?php endif; ?>
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
	 * Gets the local project po path.
	 *
	 * @param      string $file_path     The file path.
	 * @param      string $project_path  The project path.
	 * @param      string $slug          The slug.
	 * @param      string $locale        The locale.
	 * @param      string $directory     The directory.
	 *
	 * @return     string  The local project po.
	 */
	public function get_local_project_po( $file_path, $project_path, $slug, $locale, $directory ) {
		switch ( $project_path ) {
			case 'wp/dev':
				return trailingslashit( $directory ) . $locale->wp_locale . '.po';
			case 'wp/dev/admin':
				return trailingslashit( $directory ) . 'admin-' . $locale->wp_locale . '.po';
			case 'wp/dev/admin/network':
				return trailingslashit( $directory ) . 'admin-network-' . $locale->wp_locale . '.po';
			case 'wp/dev/cc':
				return trailingslashit( $directory ) . 'continents-cities-' . $locale->wp_locale . '.po';
		}

		return $file_path;
	}

	/**
	 * Gets the local project path.
	 *
	 * @param      string $project_path  The project path.
	 *
	 * @return     string  The local project path.
	 */
	public function get_local_project_path( $project_path ) {
		switch ( strtok( $project_path, '/' ) ) {
			case 'wp':
				return 'local-' . $project_path;
			case 'wp-plugins':
			case 'wp-themes':
				return 'local-' . substr( $project_path, 3 );
		}

		return $project_path;
	}

	/**
	 * Gets the project name.
	 *
	 * @param      string $project_path  The project path.
	 *
	 * @return     string   The project name.
	 */
	public function get_project_name( $project_path ) {
		if ( 'local-' === substr( $project_path, 0, 6 ) ) {
			$project_path = substr( $project_path, 6 );
		}
		$names = array(
			'wp/dev'               => __( 'Development', 'glotpress' ),
			'wp/dev/cc'            => __( 'Continents & Cities', 'glotpress' ),
			'wp/dev/admin'         => __( 'Administration', 'glotpress' ),
			'wp/dev/admin/network' => __( 'Network Administration', 'glotpress' ),
			'wp'                   => __( 'WordPress', 'glotpress' ),
			'plugins'              => __( 'Plugins', 'glotpress' ),
			'themes'               => __( 'Themes', 'glotpress' ),
			'wp-plugins'           => __( 'Plugins', 'glotpress' ),
			'wp-themes'            => __( 'Themes', 'glotpress' ),
		);
		if ( isset( $names[ $project_path ] ) ) {
			return $names[ $project_path ];
		}
		return ucwords( strtr( $project_path, '-', ' ' ) );
	}

	/**
	 * Gets the project description.
	 *
	 * @param      string $project_path  The project path.
	 *
	 * @return     array|string  The project description.
	 */
	public function get_project_description( $project_path ) {
		if ( 'local-' === substr( $project_path, 0, 6 ) ) {
			$project_path = substr( $project_path, 6 );
		}
		$descriptions = array(
			'wp/dev'               => __( 'Covers the core WordPress project.', 'glotpress' ),
			'wp/dev/cc'            => __( 'Contains the continents and main cities around the world.', 'glotpress' ),
			'wp/dev/admin'         => __( 'Covers the WordPress administration.', 'glotpress' ),
			'wp/dev/admin/network' => __( 'Covers the WordPress network administration.', 'glotpress' ),
		);
		if ( isset( $descriptions[ $project_path ] ) ) {
			return $descriptions[ $project_path ];
		}
		return '';
	}


	/**
	 * Shows a page with a list with the core, the plugins and themes installed locally.
	 *
	 * @return void
	 */
	public function show_local_projects() {
		$locale_code = get_user_locale();
		$locale_slug = 'default';
		$gp_locale   = GP_Locales::by_field( 'wp_locale', $locale_code );
		if ( ! $gp_locale ) {
			$gp_locale               = new GP_Locale();
			$gp_locale->english_name = 'Unknown (' . $locale_code . ')';
			$gp_locale->native_name  = $gp_locale->english_name;
			$gp_locale->wp_locale    = $locale_code;
		}

		$projects = array(
			'wp'         => array_map(
				function( $path ) {
					global $wp_version;
					return array(
						'TextDomain'  => $path,
						'Name'        => GP::$local->get_project_name( $path ),
						'Description' => GP::$local->get_project_description( $path ),
						'Version'     => $wp_version,
					);
				},
				self::CORE_PROJECTS
			),
			'wp-plugins' => apply_filters( 'local_glotpress_local_plugins', get_plugins() ),
			'wp-themes'  => array_map(
				function( $theme ) {
					$theme = array(
						'TextDomain'  => $theme->get( 'TextDomain' ),
						'Name'        => $theme['Name'],
						'Description' => $theme['Description'],
						'Version'     => $theme['Version'],
					);
					return $theme;
				},
				apply_filters( 'local_glotpress_local_themes', wp_get_themes() )
			),
		);

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
									__( 'Please <a href="%s">enable the local translations in the GlotPress settings</a>.', 'glotpress' ),
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
			if ( 'en_US' === $gp_locale->wp_locale ) {
				?>
					<div class="notice notice-error">
						<p>
						<?php
							echo wp_kses(
								sprintf(
									/* Translators: %1$s is the WordPress general settings URL, %2$s is the WordPress profile settings URL. */
									__( 'You are running your WordPress in English (US) which is the default language. Please <a href="%1$s">change your WordPress site language</a> or <a href="%2$s">change your user language</a>.', 'glotpress' ),
									admin_url( 'options-general.php' ),
									admin_url( 'profile.php' )
								),
								array( 'a' => array( 'href' => array() ) )
							);
						?>
						</p>
					</div>
					<?php
					return;
			}

			$can_create_projects = GP::$permission->current_user_can( 'write', 'project', null );
			$show_actions_column = $can_create_projects;
			?>
			<p>
				<?php esc_html_e( 'These are the plugins and themes that you have installed locally. With GlotPress you can change the translations of these.', 'glotpress' ); ?>
			</p>

			<?php foreach ( $projects as $type => $items ) : ?>
			<div class="tablenav">
				<span class="displaying-num alignright">
					<?php
						/* Translators: %s is the number of items to translate. */
						printf( _n( '%s item', '%s items', count( $items ), 'glotpress' ), number_format_i18n( count( $items ) ) );
					?>
				</span>
			</div>
			<table id="<?php echo esc_html( $type ); ?>-table" class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th scope="col" id="<?php echo esc_html( $type ); ?>-name" style="width: 3%;">
							<span><?php esc_html_e( 'Active', 'glotpress' ); ?></span>
						</th>
						<th scope="col" id="<?php echo esc_html( $type ); ?>-name" style="width: 15%;">
							<span><?php echo esc_html( $this->get_project_name( $type ) ); ?></span>
						</th>
						<th scope="col" id="<?php echo esc_html( $type ); ?>-description" style="width: 70%;">
							<span><?php esc_html_e( 'Description' ); ?></span>
						</th>
						<?php if ( $show_actions_column ) : ?>
							<th scope="col" id="<?php echo esc_html( $type ); ?>-actions" style="width: 15%;">
								<span><?php esc_html_e( 'Actions' ); ?></span>
							</th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody id="<?php echo esc_html( $type ); ?>-list">
					<?php foreach ( $items as $item ) : ?>
						<?php
						if ( empty( $item['TextDomain'] ) ) {
							continue;
						}
						$path            = str_replace( 'wp/wp/', 'wp/', $type . '/' . $item['TextDomain'] );
						$project         = GP::$project->by_path( apply_filters( 'gp_local_project_path', $path ) );
						$translation_set = false;
						if ( $project ) {
							$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $locale_slug, $gp_locale->slug );
						}
						?>
						<tr>
							<td>
								<?php echo esc_html( $translation_set ? '✅' : '' ); ?>
							</td>
							<td>
								<?php echo esc_html( $item['Name'] ); ?>
							</td>
							<td>
							<p>
								<?php
								echo wp_kses(
									$item['Description'],
									array(
										'a'      => array(
											'href' => array(),
										),
										'strong' => array(),
									)
								);
								?>
							</p>
							<p>
								<span>
								<?php
								echo esc_html(
									sprintf(
										// translators: %s is a version number.
										__( 'Version %s' ),
										$item['Version']
									)
								);
								?>
								</span>
							</td>
						<?php if ( $show_actions_column ) : ?>
								<td>
									<?php if ( $can_create_projects ) : ?>
										<form action="<?php echo esc_url( gp_url( '/local/' . $path ) ); ?>" method="post" target="_blank">
											<?php wp_nonce_field( 'gp-local-' . $path ); ?>
											<input type="hidden" name='name' value="<?php echo esc_attr( $item['Name'] ); ?>" />
											<input type="hidden" name='description' value="<?php echo esc_attr( $item['Description'] ); ?>" />
											<input type="hidden" name='locale' value="<?php echo esc_attr( $locale_code ); ?>" />
											<input type="hidden" name='locale_slug' value="<?php echo esc_attr( $locale_slug ); ?>" />
											<button class="button">
											<?php
												$name = $item['Name'];
											if ( 'wp' === $type ) {
												$name = 'WordPress ' . $name;
											}
												echo esc_html(
													sprintf(
														/* Translators: %1$s is a project like WordPress or plugin name, %2$s is the language into which we will translate. */
														__( 'Translate %1$s to %2$s', 'glotpress' ),
														$name,
														$gp_locale->native_name
													)
												)
											?>
											</button>
										</form>
									<?php endif; ?>
								</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endforeach; ?>

			<div style="padding-top: 40px;">
				<button class="button-primary">
					<?php esc_html_e( 'Share your translations with WordPress.org', 'glotpress' ); ?>
				</button>
			</div>

		</div>
		<?php
	}
}
