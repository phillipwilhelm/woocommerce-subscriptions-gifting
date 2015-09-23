<?php
class WCSG_Test_Display_Functions extends WC_Unit_Test_Case {

	/**
	 * Tests for WCS_Gifting::get_user_display_name
	 */
	public function test_user_display_name() {

		$user_id = wp_create_user( 'user_one', 'testuser', 'user_one@example.com' );

		//Just email
		$this->assertEquals( 'user_one@example.com', WCS_Gifting::get_user_display_name( $user_id ) );

		//First name and Email
		update_user_meta( $user_id, 'first_name', 'first_name');
		$this->assertEquals( 'first_name (user_one@example.com)', WCS_Gifting::get_user_display_name( $user_id ) );

		//last name and Email
		delete_user_meta( $user_id, 'first_name', 'first_name');
		update_user_meta( $user_id, 'last_name', 'last_name');
		$this->assertEquals( 'user_one@example.com', WCS_Gifting::get_user_display_name( $user_id ) );

		//First name, last name and Email
		update_user_meta( $user_id, 'first_name', 'first_name');
		$this->assertEquals( 'first_name last_name (user_one@example.com)', WCS_Gifting::get_user_display_name( $user_id ) );

		//cleanup
		wp_delete_user( $user_id );
	}

	/**
	 * Basic tests for WCS_Gifting::get_recipient_email_field_args.
	 *
	 * @dataProvider recipient_email_field_args_setup
	 * @param string $current_user_email The email address of the current user to be created.
	 * @param string $email The entered email address being tested.
	 * @param bool $expected_invalid Whether the field is expected to be flagged to display invalid.
	 * @param bool $expected_is_display_none Whether the field is expected to be flagged to display none.
	 */
	public function test_get_recipient_email_field_args( $current_user_email, $email, $expected_invalid, $expected_is_display_none ) {
		$user_id = 0;
		if ( ! empty( $current_user_email ) ) {
			$user_id = wp_create_user( 'user', 'password', $current_user_email );
			wp_set_current_user( $user_id );
		}

		$result                 = WCS_Gifting::get_recipient_email_field_args( $email );
		$invalid_result         = in_array( 'woocommerce-invalid', $result['class'] );
		$result_is_display_none = in_array( 'display: none', $result['style_attributes'] );

		$this->assertEquals( $invalid_result, $expected_invalid );
		$this->assertEquals( $result_is_display_none, $expected_is_display_none );

		if ( 0 !== $user_id ) {
			//clean up
			wp_delete_user( $user_id );
		}
	}

	/**
	 * DataProvider for @see $this->test_get_recipient_email_field_args
	 *
	 * @return array Returns inputs and the expected values in the format:
	 * array(
	 * 		'current_user_email',
	 * 		'email',
	 * 		'expected_invalid',
	 * 		'expected_is_display_none' )
	 */
	public static function recipient_email_field_args_setup() {
		return array(
			//self gifting, expects invalid
			array( 'current_user_email' => 'sages1940@cuvox.de', 'email' => 'sages1940@cuvox.de', 'invalid' => true, 'is_display_none' => false ),
			// 2 different emails expects valid
			array( 'current_user_email' => 'satingrame1961@gustr.com', 'email' => 'sages1940@cuvox.de', 'invalid' => false, 'is_display_none' => false ),
			// blank email expects valid and display none
			array( 'current_user_email' => '', 'email' => '', 'invalid' => false, 'is_display_none' => true ),
			// non email (sages1940) expects invalid
			array( 'current_user_email' => '', 'email' => 'sages1940', 'invalid' => true, 'is_display_none' => false ),
		);
	}

	/**
	 * Tests for WCS_Gifting::email_belongs_to_current_user
	 */
	public function test_email_belongs_to_current_user() {

		$user_id = wp_create_user( 'user', 'password', 'user_email@example.com' );

		wp_set_current_user( $user_id );

		$this->assertTrue( WCS_Gifting::email_belongs_to_current_user( 'user_email@example.com' ) );
		$this->assertTrue( false == WCS_Gifting::email_belongs_to_current_user( '' ) );
		$this->assertTrue( false == WCS_Gifting::email_belongs_to_current_user( 'email1@example.com' ) );

		wp_delete_user( $user_id );
	}

}
