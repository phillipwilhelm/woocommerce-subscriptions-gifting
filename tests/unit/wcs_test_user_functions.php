<?php
/**
 *
 * @since 2.0
 */
class WCS_User_Functions_Unit_Tests extends WCS_Unit_Test_Case {

	public $admin_user_id;

	public function setUp() {
		parent::setUp();

		// setup a shop_manager and admin for testing
		$admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Function: wcs_make_user_active
	 *
	 *
	 * @since 2.0
	 */
	public function test_wcs_make_user_active() {
		// Set default subscriber role
		update_option( WC_Subscriptions_Admin::$option_prefix . '_subscriber_role', 'subscriber' );

		// Test Default subscriber role for non-admins
		$user = new WP_User( $this->user_id );
		$this->assertFalse( in_array( 'subscriber', $user->roles ) ); // test the user is not a subscriber
		
		//ignore for admins as wcs_make_user_active currently doesn't return an object if an admin
		if ( ! in_array( 'administrator', $user->roles ) ) {
			$this->assertTrue( in_array( 'subscriber', wcs_make_user_active( $this->user_id )->roles ) ); // test the user is now a subscriber
		}

	}

}