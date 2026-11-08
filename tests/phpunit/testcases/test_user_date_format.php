<?php

class GP_Test_User_Date_Format extends GP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		update_option( 'timezone_string', 'Europe/Berlin' );
		update_option( 'date_format', 'F j, Y' );
		update_option( 'time_format', 'g:i a' );

		wp_set_current_user( $this->factory->user->create() );
	}

	public function test_timezone_defaults_to_the_one_of_the_site() {
		$this->assertSame( 'Europe/Berlin', gp_get_user_timezone()->getName() );

		update_user_option( get_current_user_id(), 'gp_timezone', 'UTC+2 or so' );
		$this->assertSame( 'Europe/Berlin', gp_get_user_timezone()->getName() );
	}

	public function test_timezone_of_the_user() {
		update_user_option( get_current_user_id(), 'gp_timezone', 'America/New_York' );

		$this->assertSame( 'America/New_York', gp_get_user_timezone()->getName() );
	}

	public function test_timezone_as_an_offset_from_utc() {
		update_user_option( get_current_user_id(), 'gp_timezone', 'UTC+5.5' );
		$this->assertSame( '+05:30', gp_get_user_timezone()->getName() );

		update_user_option( get_current_user_id(), 'gp_timezone', 'UTC-3' );
		$this->assertSame( '-03:00', gp_get_user_timezone()->getName() );
	}

	public function test_sanitize_timezone() {
		$this->assertSame( 'UTC-9.5', gp_sanitize_timezone( 'UTC-9.5' ) );
		$this->assertSame( 'Asia/Tokyo', gp_sanitize_timezone( 'Asia/Tokyo' ) );
		$this->assertSame( '', gp_sanitize_timezone( 'Mars/Olympus_Mons' ) );
		$this->assertSame( '', gp_sanitize_timezone( array( 'UTC' ) ) );
	}

	public function test_format_user_date_uses_the_formats_of_the_site_by_default() {
		$this->assertSame( 'November 8, 2026 at 2:30 pm (Europe/Berlin)', gp_format_user_date( '2026-11-08 13:30:00' ) );
	}

	public function test_format_user_date_with_the_date_format_and_24_hour_time_of_the_user() {
		update_user_option( get_current_user_id(), 'gp_date_format', 'Y-m-d' );
		update_user_option( get_current_user_id(), 'gp_time_format_24h', '1' );

		$this->assertSame( '2026-11-08 at 14:30 (Europe/Berlin)', gp_format_user_date( '2026-11-08 13:30:00' ) );
	}

	public function test_format_user_date_with_12_hour_time_when_the_site_uses_24_hour_time() {
		update_option( 'time_format', 'H:i' );
		update_user_option( get_current_user_id(), 'gp_time_format_24h', '0' );

		$this->assertSame( 'November 8, 2026 at 2:30 pm (Europe/Berlin)', gp_format_user_date( '2026-11-08 13:30:00' ) );
	}

	public function test_format_user_date_ignores_a_date_format_that_is_not_offered() {
		update_user_option( get_current_user_id(), 'gp_date_format', '<b>Y</b>' );

		$this->assertSame( 'November 8, 2026 at 2:30 pm (Europe/Berlin)', gp_format_user_date( '2026-11-08 13:30:00' ) );
	}

	public function test_format_user_date_names_an_offset_timezone() {
		update_user_option( get_current_user_id(), 'gp_timezone', 'UTC+2' );

		$this->assertSame( 'November 8, 2026 at 3:30 pm (UTC+2)', gp_format_user_date( '2026-11-08 13:30:00' ) );
	}

	public function test_format_user_date_names_the_offset_of_a_site_without_a_timezone() {
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 2 );

		$this->assertSame( 'November 8, 2026 at 3:30 pm (UTC+2)', gp_format_user_date( '2026-11-08 13:30:00' ) );
	}
}
