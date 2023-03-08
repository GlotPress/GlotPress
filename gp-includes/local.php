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
		add_filter( 'gp_remote_project_path', array( $this, 'get_remote_project_path' ) );
		// phpcs:ignore add_filter( 'gp_local_sync_url', array( $this, 'get_local_sync_url' ), 10, 2 );
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
			esc_html__( 'Translation Interface', 'glotpress' ),
			esc_html__( 'Translation Interface', 'glotpress' ),
			'read',
			'glotpress-ui',
			array( $this, 'show_welcome_page' )
		);
		add_action( 'load-glotpress_page_glotpress-ui', array( $this, 'redirect_to_glotpress' ) );

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
			add_submenu_page(
				'glotpress',
				esc_html__( 'Contribute back', 'glotpress' ),
				esc_html__( 'Contribute back', 'glotpress' ),
				'read',
				'glotpress-sync',
				array( $this, 'sync_to_wordpress_org_overview' ),
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
	 * Redirect to the GlotPress UI
	 */
	public function redirect_to_glotpress() {
		wp_safe_redirect( gp_url( '/' ) );
		exit;
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
	 * Gets the remote project path.
	 *
	 * @param      string $project_path  The project path.
	 *
	 * @return     string  The remote project path.
	 */
	public function get_remote_project_path( $project_path ) {
		switch ( strtok( $project_path, '/' ) ) {
			case 'local-wp':
				return substr( $project_path, 6 );
			case 'local-plugins':
			case 'local-themes':
				return 'wp-' . substr( $project_path, 6 );
		}

		return $project_path;
	}

	/**
	 * Gets the URL to which we should sync.
	 *
	 * @param      string $url           The url.
	 * @param      string $project_path  The project path.
	 *
	 * @return     string  The local synchronize url.
	 */
	public function get_local_sync_url( $url, $project_path ) {
		return home_url( gp_url( 'projects/' . $project_path ) );
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

	/**
	 * Shows a page with a list of translations that could be synced.
	 *
	 * @return void
	 */
	public function sync_to_wordpress_org_overview() {
		include __DIR__ . '/../gp-templates/helper-functions.php';
		global $wpdb;

		$locale_code = get_user_locale();
		$locale_slug = 'default';
		$gp_locale   = GP_Locales::by_field( 'wp_locale', $locale_code );
		if ( ! $gp_locale ) {
			$gp_locale               = new GP_Locale();
			$gp_locale->english_name = 'Unknown (' . $locale_code . ')';
			$gp_locale->native_name  = $gp_locale->english_name;
			$gp_locale->wp_locale    = $locale_code;
		}

		$syncable_translations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					p.id AS project_id,
					p.name as project_name,
					ts.slug AS translation_set_slug,
					o.id AS original_id,
					o.singular,
					o.plural,
					o.status,
					o.comment,
					o.priority,
					t.*
				FROM
					{$wpdb->gp_translations} t,
					{$wpdb->gp_originals} o,
					{$wpdb->gp_translation_sets} ts,
					{$wpdb->gp_projects} p
				WHERE
					t.original_id = o.id AND
					t.translation_set_id = ts.id AND
					ts.project_id = p.id AND
					p.id IS NOT NULL AND
					ts.locale = %s AND
					t.user_id != 0 AND
					t.status NOT IN ('old','rejected') AND
					o.status NOT IN ('-obsolete')
				ORDER BY p.id, t.id",
				$gp_locale->slug
			)
		);

		if ( ! empty( $_REQUEST['_wpnonce'] ) && ! empty( $_REQUEST['translation'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'sync_translations' ) ) {
			$translations_by_project_translation_set = array();
			foreach ( array_keys( $_REQUEST['translation'] ) as $id ) {
				$translations_by_project_translation_set[ $_REQUEST['project'][ $id ] ][ $_REQUEST['translation_set'][ $id ] ][] = $id;
			}
			echo '<h2>We will send these PO files</h2>';

			$syncable_translations = array_column( $syncable_translations, null, 'id' );

			foreach ( $translations_by_project_translation_set as $project_id => $translation_sets ) {
				$project            = GP::$project->get( $project_id );
				$entries_for_export = array();
				foreach ( $translation_sets as $translation_set_id => $translations ) {
					$translation_set = GP::$translation_set->get( $translation_set_id );
					foreach ( $translations as $translation_id ) {
						$translation = $syncable_translations[ $translation_id ];

						$entries_for_export[] = new Translation_Entry(
							array(
								'singular'           => $translation->singular,
								'plural'             => $translation->plural,
								'context'            => $translation->context,
								'extracted_comments' => $translation->comment,
								'translations'       => array_filter( array( $translation->translation_0, $translation->translation_1, $translation->translation_2, $translation->translation_3, $translation->translation_4 ) ),

							)
						);
					}
					$remote_path = apply_filters( 'gp_remote_project_path', $project->path );
					$url         = apply_filters( 'gp_local_sync_url', 'https://translate.wordpress.org/projects/' . $remote_path, $remote_path );
					echo wp_kses_post( 'Path: <a href="' . $url . '">' . $url . '</a> File: ' . $project->slug . '-' . $translation_set->locale . '.po' );
					echo '<br/><textarea cols=80 rows=10 style="font-family: monospace">';
					echo esc_html( GP::$formats['po']->print_exported_file( $project, $gp_locale, $translation_set, $entries_for_export ) );
					echo '</textarea><br/>';
				}
			}
			exit;
		}

		$current_project = false;
		$table_end       = function() use ( $current_project ) {
			if ( ! $current_project ) {
				return;
			}
			?>
			</tbody></table>
			<?php
		};

		$table_start = function( $translation ) use ( &$current_project ) {
			if ( $current_project && intval( $translation->project_id ) === $current_project->id ) {
				return;
			}
			if ( $current_project ) {
				?>
				</tbody></table>
				<?php
			}
			$current_project = GP::$project->get( $translation->project_id );
			?>
			<h2>
			<?php
			switch ( strtok( $current_project->path, '/' ) ) {
				case 'local-wp':
					echo esc_html(
						sprintf(
							// translators: %s is the name of the WordPress translation project, such as Administration.
							__( 'WordPress %s' ),
							$current_project->name
						)
					);
					break;
				case 'local-plugins':
					echo esc_html(
						sprintf(
							// translators: %s is a plugin name.
							__( 'Plugin: %s' ),
							$current_project->name
						)
					);
					break;
				case 'local-themes':
					echo esc_html(
						sprintf(
							// translators: %s is a theme name.
							__( 'Theme: %s' ),
							$current_project->name
						)
					);
					break;
				default:
					echo esc_html( $current_project->name );
			}

			echo ' ';
			echo gp_link_project_get( $current_project, '<span class="dashicons dashicons-external"></span>', array( 'target' => '_blank' ) );
			?>
			</h2>
			<p>
			<?php
			echo esc_html( $current_project->description );
			?>
			</p>
			<table class="translations widefat">
				<thead>
					<tr>
						<th style="width: 5%">
							<label>
								<input type="checkbox" checked="checked" onclick="this.parentNode.parentNode.parentNode.parentNode.parentNode.querySelectorAll( 'input[type=checkbox]').forEach((el)=>el.checked = this.checked); return true;"/>
							Sync
							</label>
						</th>
						<th style="width: 30%">Original</th>
						<th style="width: 30%">Translation</th>
						<th style="width: 15%">Created</th>
						<th style="width: 5%">Actions</th>
					</tr>
				</thead>
				<tbody>
			<?php
		};
		?>
		<style>
			:root {
			--gp-color-status-fuzzy-subtle: #fc6;
			--gp-color-status-current-subtle: #e9ffd8;
			--gp-color-status-old-subtle: #fee4f8;
			--gp-color-status-waiting-subtle: #ffffc2;
			--gp-color-status-rejected-subtle: #ff8e8e;
			--gp-color-status-changesrequested-subtle: #87ceeb;
			}
			tr.status-fuzzy td {
				background-color: var( --gp-color-status-fuzzy-subtle );
			}
			tr.status-current td {
				background-color: var( --gp-color-status-current-subtle );
			}
			tr.status-old td {
				background-color: var( --gp-color-status-old-subtle );
			}
			tr.status-waiting td {
				background-color: var( --gp-color-status-waiting-subtle );
			}
			tr.status-rejected td {
				background-color: var( --gp-color-status-rejected-subtle );
			}
			tr.status-changesrequested td {
				background-color: var( --gp-color-status-changesrequested-subtle );
			}
		</style>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Contribute back', 'glotpress' ); ?>
			</h1>
			<p>
				<span>
					<?php esc_html_e( 'Thank you for contributing back to WordPress.org!', 'glotpress' ); ?>
				</span>
				<span>
					<?php
					echo esc_html(
						sprintf(
						// translators: %s is the user's language.
							__( 'These are all %s translations that you have created in your Local GlotPress and are now ready to be sent back to WordPress.org.', 'glotpress' ),
							$gp_locale->native_name
						)
					);
					?>
				</span>
			</p>
			<form action="" method="get"><!-- TODO: change to POST. GET is easier during debugging. -->
				<input type="hidden" name="page" value="glotpress-sync" />
				<?php wp_nonce_field( 'sync_translations' ); ?>
		<?php foreach ( $syncable_translations as $translation ) : ?>
			<?php
			$table_start( $translation );
			$original_permalink = gp_url_project_locale( $current_project, $gp_locale->slug, $translation->translation_set_slug, array( 'filters[original_id]' => $translation->original_id ) );
			$user               = get_userdata( $translation->user_id );
			?>
			<tr class="status-<?php echo esc_attr( $translation->status ); ?> priority-<?php echo esc_attr( $translation->priority ); ?> has-translations">
				<td class="sync">
					<input type="checkbox" name="translation[<?php echo esc_attr( $translation->id ); ?>]" value="1" checked="checked" />
					<input type="hidden" name="project[<?php echo esc_attr( $translation->id ); ?>]" value="<?php echo esc_attr( $translation->project_id ); ?>" />
					<input type="hidden" name="translation_set[<?php echo esc_attr( $translation->id ); ?>]" value="<?php echo esc_attr( $translation->translation_set_id ); ?>" />
				</td>
				<td class="original">
					<span class="original-text"><?php echo prepare_original( esc_translation( $translation->singular ) ); ?></span>
				</td>
				<td class="translation foreign-text">
					<span class="translation-text"><?php echo prepare_original( esc_translation( $translation->translation_0 ) ); ?></span>
				</td>
				<td class="meta">
					<?php
					echo esc_html(
						sprintf(
							// translators: %s is a relative human time.
							__( '%s ago' ),
							human_time_diff( strtotime( $translation->date_modified ) )
						)
					);
					?>
					by <?php echo esc_html( $user->display_name ); ?>
				</td>
				<td class="actions">
					<a href="<?php echo esc_url( $original_permalink ); ?>" target="_blank"><span class="dashicons dashicons-external"></span></a>
				</td>
			</tr>
			<?php
		endforeach;
		$table_end();

		?>
		</tbody></table>
		<p>
			<button class="button button-primary">Sync to WordPress.org</button>
		</p>
		</form>
		</div>
		<?php
	}
}
