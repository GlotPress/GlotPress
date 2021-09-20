<?php
/**
 * All code related to the GlotPress admin functions in the WordPress backend.
 *
 * @package GlotPress
 * @since 3.0.0
 */

/**
 * Class to add a settings page to the WordPress admin settings menu and other admin related functions.
 *
 * @since 3.0.0
 */
class GP_Admin {

	/**
	 * GP_Admin constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'glotpress_settings_menu' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'glotpress_settings_init' ) );
	}

	/**
	 * Adds the GlotPress menu and registers the settings.
	 *
	 * @since 3.0.0
	 */
	public function glotpress_settings_menu() {
		add_options_page(
			__( 'GlotPress', 'glotpress' ),
			__( 'GlotPress', 'glotpress' ),
			'manage_options',
			'glotpress',
			array( $this, 'glotpress_option_page' )
		);
	}

	/**
	 * Displays the GlotPress settings page.
	 *
	 * @since 3.0.0
	 */
	public function glotpress_option_page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'GlotPress', 'glotpress' ); ?></h2>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'gp_options' );
				do_settings_sections( 'glotpress' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Initializes the GlotPress settings page.
	 *
	 * @since 3.0.0
	 */
	function glotpress_settings_init() {
		$args = array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'glotpress_validate_options' ),
			'default'           => array(
				'delete_options' => true,
				'delete_data'    => false,
			),
		);
		register_setting(
			'gp_options',
			'gp_options',
			$args
		);
		add_settings_section(
			'glotpress_delete',
			__( 'GlotPress uninstall settings' ),
			array( $this, 'glotpress_delete_section_text' ),
			'glotpress'
		);
		add_settings_field(
			'delete_options',
			__( 'Delete options' ),
			array( $this, 'checkbox' ),
			'glotpress',
			'glotpress_delete',
			$args = array(
				'id'    => 'delete_options',
				'type'  => 'checkbox',
				'label' => __( 'The GlotPress options will be deleted on uninstall.' ),
			)
		);
		add_settings_field(
			'delete_data',
			__( 'Delete data' ),
			array( $this, 'checkbox' ),
			'glotpress',
			'glotpress_delete',
			$args = array(
				'id'    => 'delete_data',
				'type'  => 'checkbox',
				'label' => __( 'The GlotPress data will be deleted on uninstall.' ),
			)
		);
	}

	/**
	 * Displays the text in the delete section.
	 *
	 * @since 3.0.0
	 */
	function glotpress_delete_section_text() {
		_e( 'Select this options to delete the GlotPress options and/or data on uninstall.' );
	}

	/**
	 * Displays the HTML code for the checkboxes.
	 *
	 * @since 3.0.0
	 */
	function checkbox( $args ) {
		$options = (array) get_option( 'gp_options' );
		$value   = ( true === isset( $options[ $args['id'] ] ) ) ? $options[ $args['id'] ] : false;
		$html    = '<input type="checkbox" id="' . $args['id'] . '" ';
		$html   .= 'name="gp_options[' . $args['id'] . ']" value="1" ' . checked( 1, $value, false ) . ' />';
		$html   .= '<label for="' . $args['id'] . '">' . $args['label'] . '</label>';
		echo $html;
	}

	/**
	 * Validates the GlotPress options.
	 *
	 * @since 3.0.0
	 */
	function glotpress_validate_options( $input ) {
		$valid                   = array();
		$valid['delete_options'] = isset( $input['delete_options'] ) && true == $input['delete_options'];
		$valid['delete_data']    = isset( $input['delete_data'] ) && true == $input['delete_data'];
		return $valid;
	}

}

if ( is_admin() ) {
	GP::$admin = new GP_Admin();
}
