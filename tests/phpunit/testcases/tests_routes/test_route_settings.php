<?php

class GP_Test_Route_Settings extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Settings';

	private $user_id;

	public function set_up() {
		parent::set_up();

		update_option( 'timezone_string', 'Europe/Berlin' );
		update_option( 'time_format', 'g:i a' );

		$this->user_id = $this->set_normal_user_as_current();
	}

	private function save_settings( $fields ) {
		$_POST = array_merge(
			array(
				'submit'       => 'Save',
				'per_page'     => 15,
				'default_sort' => array(),
			),
			$fields
		);

		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'update-settings_' . $this->user_id );

		$this->do_route_request(
			function () {
				$this->route->settings_post();
			}
		);
	}

	public function test_saves_the_date_settings() {
		$this->save_settings(
			array(
				'timezone'        => 'UTC+5.5',
				'date_format'     => 'Y-m-d',
				'time_format_24h' => '1',
			)
		);

		$this->assertSame( 'UTC+5.5', get_user_option( 'gp_timezone', $this->user_id ) );
		$this->assertSame( 'Y-m-d', get_user_option( 'gp_date_format', $this->user_id ) );
		$this->assertSame( '1', get_user_option( 'gp_time_format_24h', $this->user_id ) );
	}

	public function test_does_not_save_an_invalid_timezone_or_date_format() {
		$this->save_settings(
			array(
				'timezone'    => 'Mars/Olympus_Mons',
				'date_format' => '<b>Y</b>',
			)
		);

		$this->assertFalse( get_user_option( 'gp_timezone', $this->user_id ) );
		$this->assertFalse( get_user_option( 'gp_date_format', $this->user_id ) );
	}

	public function test_does_not_save_choices_that_match_the_site() {
		update_user_option( $this->user_id, 'gp_timezone', 'Asia/Tokyo' );
		update_user_option( $this->user_id, 'gp_time_format_24h', '1' );

		$this->save_settings(
			array(
				'timezone'    => 'Europe/Berlin',
				'date_format' => '',
			)
		);

		$this->assertFalse( get_user_option( 'gp_timezone', $this->user_id ) );
		$this->assertFalse( get_user_option( 'gp_time_format_24h', $this->user_id ) );
	}
}
