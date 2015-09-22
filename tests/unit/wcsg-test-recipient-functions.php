<?php
class WCSG_Test_Recipient_Functions extends WC_Unit_Test_Case {

	/**
	 * Tests for WCSG_Recipient_Management::get_recipient_subscriptions
	 */
	public function test_get_recipient_subscriptions() {

		$recipient_user = wp_create_user( 'recipient_user', 'password', 'recipient@example.com' );
		$subscriptions  = array();

		//Gifted Subscription
		$subscription            = WCS_Helper_Subscription::create_subscription( array(), array( 'recipient_user' => $recipient_user ) );
		$user_subscriptions      = WCSG_Recipient_Management::add_recipient_subscriptions( $subscriptions, $recipient_user );
		$recipient_subscriptions = WCSG_Recipient_Management::get_recipient_subscriptions( $recipient_user );

		$this->assertTrue( array_key_exists( $subscription->id, $user_subscriptions ) );
		$this->assertEquals( 1 , count( $user_subscriptions ) );
		$this->assertEquals( 1 , count( $recipient_subscriptions ) );

		foreach( $user_subscriptions as $subscription ) {
			$this->assertTrue( $subscription instanceof WC_Subscription );
		}

		//Purchased Subscription
		$subscription_two        = WCS_Helper_Subscription::create_subscription( array( 'customer_id' => $recipient_user ) );
		$user_subscriptions      = WCSG_Recipient_Management::add_recipient_subscriptions( $subscriptions, $recipient_user );
		$recipient_subscriptions = WCSG_Recipient_Management::get_recipient_subscriptions( $recipient_user );

		$this->assertTrue( array_key_exists( $subscription->id, $user_subscriptions ) );
		$this->assertTrue( false == array_key_exists( $subscription_two->id, $user_subscriptions ) );

		$this->assertEquals( 1 , count( $user_subscriptions ) );
		$this->assertEquals( 1 , count( $recipient_subscriptions ) );

		foreach( $user_subscriptions as $subscription ) {
			$this->assertTrue( $subscription instanceof WC_Subscription );
		}

		//clean up
		wp_delete_post( $subscription->id, true );
		wp_delete_post( $subscription_two->id, true );

		wp_delete_user( $recipient_user );
	}

	/**
	 * Tests for WCSG_Checkout::subscription_created
	 */
	public function test_subscription_created() {

		$recipient_user_id            = wp_create_user( 'recipient_user', 'password', 'recipient@example.com' );
		$subscription                 = WCS_Helper_Subscription::create_subscription();
		$monthly_subscription_product = WCS_Helper_Product::create_simple_subscription_product( array( 'subscription_period' => 'month' ) );

		$test_shipping_address = array(
			'country'    => 'US',
			'first_name' => 'Jeroen',
			'last_name'  => 'Sormani',
			'company'    => 'Company',
			'address_1'  => 'Address',
			'address_2'  => '',
			'postcode'   => '123456',
			'city'       => 'City',
			'state'      => 'NY',
		);

		WCSG_Helper_Test_Cart::add_to_test_cart( $monthly_subscription_product , 1 );
		$cart_item_keys = array_keys( WC()->cart->cart_contents );

		WC()->cart->cart_contents[ end( $cart_item_keys ) ]['wcsg_gift_recipients_email'] = 'recipient@example.com';

		WC_Subscriptions_Cart::calculate_subscription_totals( 0 , WC()->cart );

		WCSG_Checkout::subscription_created( $subscription, null, reset( WC()->cart->recurring_carts ) );

		$this->assertEquals( $recipient_user_id, $subscription->recipient_user );

		//check subscriptions shipping address is equal to recipients shipping address
		foreach ( $test_shipping_address as $key => $value ) {
			$subscription_shipping = get_post_meta( $subscription->id, '_shipping_' . $key, true );
			$recipient_shipping = get_user_meta( $recipient_user_id, 'shipping_' . $key, true );
			$this->assertEquals( $subscription_shipping, $recipient_shipping );
		}

		//clean up
		WC()->cart->empty_cart();
		wp_delete_post( $subscription->id, true );

		/****************************************************************************/

		$subscription = WCS_Helper_Subscription::create_subscription();

		//set recipients shipping address
		foreach ( $test_shipping_address as $key => $value ) {
			update_user_meta( $recipient_user_id, 'shipping_' . $key, $value );
		}

		WCSG_Helper_Test_Cart::add_to_test_cart( $monthly_subscription_product , 1 );
		$cart_item_keys = array_keys( WC()->cart->cart_contents );

		WC()->cart->cart_contents[ end( $cart_item_keys ) ]['wcsg_gift_recipients_email'] = 'recipient@example.com';

		WC_Subscriptions_Cart::calculate_subscription_totals( 0 , WC()->cart );

		WCSG_Checkout::subscription_created( $subscription, null, reset( WC()->cart->recurring_carts ) );

		$this->assertEquals( $recipient_user_id, $subscription->recipient_user );

		//check subscriptions shipping address is equal to recipients shipping address
		foreach ( $test_shipping_address as $key => $value ) {
			$subscription_shipping = get_post_meta( $subscription->id, '_shipping_' . $key, true );
			$recipient_shipping = get_user_meta( $recipient_user_id, 'shipping_' . $key, true );

			$this->assertEquals( $subscription_shipping, $recipient_shipping );
		}

		//clean up
		WC()->cart->empty_cart();
		wp_delete_post( $subscription->id, true );

		/****************************************************************************/

		$subscription = WCS_Helper_Subscription::create_subscription();

		//set recipients shipping address
		foreach ( $test_shipping_address as $key => $value ) {
			update_user_meta( $recipient_user_id, 'shipping_' . $key, $value );
		}

		WCSG_Helper_Test_Cart::add_to_test_cart( $monthly_subscription_product , 1 );
		$cart_item_keys = array_keys( WC()->cart->cart_contents );

		WC_Subscriptions_Cart::calculate_subscription_totals( 0 , WC()->cart );

		WCSG_Checkout::subscription_created( $subscription, null, reset( WC()->cart->recurring_carts ) );

		$this->assertTrue( empty( $subscription->recipient_user ) );

		//clean up
		WC()->cart->empty_cart();
		wp_delete_post( $subscription->id, true );

		wp_delete_user( $recipient_user_id );
	}

	/**
	 * Tests for WCSG_Recipient_Management::recipient_can_suspend
	 *
	 * @dataProvider recipient_can_suspend_test_setup
	 */
	public function test_recipient_can_suspend( $recipient_email, $current_user, $subscription_suspension_count, $max_suspensions, $expected ) {

		$subscription_meta = array( 'suspension_count' => $subscription_suspension_count );

		if ( ! empty( $recipient_email ) ) {
			$recipient_user = wp_create_user( 'recipient_user', 'password', $recipient_email );
			$subscription_meta[ 'recipient_user' ] = $recipient_user;
		}

		$purchaser_user = wp_create_user( 'purchaser_user', 'password', 'email@example.com' );
		$post_meta      = array( 'customer_id' => $purchaser_user );

		if ( 'recipient' == $current_user ) {
			wp_set_current_user( $recipient_user );
		} else if ( 'purchaser' == $current_user ) {
			wp_set_current_user( $purchaser_user );
		} else {
			wp_set_current_user( 0 );
		}

		$subscription     = WCS_Helper_Subscription::create_subscription( $post_meta, $subscription_meta );
		$user_can_suspend = false;

		update_option( WC_Subscriptions_Admin::$option_prefix . '_max_customer_suspensions', $max_suspensions );

		$actual_result = WCSG_Recipient_Management::recipient_can_suspend( $user_can_suspend, $subscription );

		$this->assertEquals( $expected, $actual_result );

		//clean-up
		wp_delete_post( $subscription->id, true );
		wp_delete_user( $purchaser_user );

		if ( ! empty( $recipient_email ) ) {
			wp_delete_user( $recipient_user );
		}

	}

	/**
	 * DataProvider for @see $this->test_recipient_can_suspend
	 *
	 * @return array Returns inputs and the expected values in the format:
	 * array(
	 * 		recipient_user_email,
	 * 		current_user,
	 * 		subscription_suspension_count,
	 * 		max_suspensions,
	 * 		expected_result );
	 */
	public static function recipient_can_suspend_test_setup() {
		return array(
			//Gifted subscription, recipient user. Expects true
			array(
				'recipient' => 'recipient@example.com',
				'current_user' => 'recipient',
				'subscription_suspension_count' => 0,
				'max_suspensions' => 'unlimited',
				'expected_result' => true ),

			//Gifted subscription, purchaser user. Expects unchanged - null
			array(
				'recipient' => 'recipient@example.com',
				'current_user' => 'purchaser',
				'subscription_suspension_count' => 0,
				'max_suspensions' => 'unlimited',
				'expected_result' => null ),

			//Gifted subscription, recipient user, reached maximum suspensions. Expects false
			array(
				'recipient' => 'recipient@example.com',
				'current_user' => 'recipient',
				'subscription_suspension_count' => 0,
				'max_suspensions' => 0,
				'expected_result' => false ),

			//Gifted subscription, recipient user, 1 suspension left. Expects true
			array(
				'recipient' => 'recipient@example.com',
				'current_user' => 'recipient',
				'subscription_suspension_count' => 0,
				'max_suspensions' => 1,
				'expected_result' => true ),

			//Gifted subscription, recipient user, suspension_count equals max_suspensions. Expects false
			array(
				'recipient' => 'recipient@example.com',
				'current_user' => 'recipient',
				'subscription_suspension_count' => 1,
				'max_suspensions' => 1,
				'expected_result' => false ),

			//not gifted subscription, purchaser user. Expects unchanged - null
			array(
				'recipient' => '',
				'current_user' => 'purchaser',
				'subscription_suspension_count' => 0,
				'max_suspensions' => 'unlimited',
				'expected_result' => null ),

		);
	}

	public function test_grant_recipient_capabilities() {

		$recipient_user = wp_create_user( 'recipient_user', 'password', 'recipient@example.com' );
		$purchaser_user = wp_create_user( 'purchaser_user', 'password', 'purchaser@example.com' );
		$user           = wp_create_user( 'random_user', 'password', 'user@example.com' );

		$parent_order   = wc_create_order();
		$subscription   = WCS_Helper_Subscription::create_subscription( array( 'customer_id' => $purchaser_user ), array( 'recipient_user' => $recipient_user ) );
		error_log( 'creating renewal order for ' . $subscription->id );
		$renewal_order  = wcs_create_renewal_order( $subscription );


		$order                     = wc_create_order();
		$non_gifted_subscription   = WCS_Helper_Subscription::create_subscription();

		$subscription->update_parent( $parent_order->id );

		$tests = array(
			/*recipient tests*/
			//parent order of gifted subscription
			//array( 'current_user' => $recipient_user, 'capability' => 'view_order', 'post_id' => $parent_order->id, 'expected' => true ),
			//gifted subscription
			array( 'current_user' => $recipient_user, 'capability' => 'view_order', 'post_id' => $subscription->id, 'expected' => true ),
			//renewal order for gifted subscription
			//array( 'current_user' => $recipient_user, 'capability' => 'view_order', 'post_id' => $renewal_order->id, 'expected' => true ),
			//unrelated order
			array( 'current_user' => $recipient_user, 'capability' => 'view_order', 'post_id' => $order->id, 'expected' => false ),
			//non gifted subscription
			array( 'current_user' => $recipient_user, 'capability' => 'view_order', 'post_id' => $non_gifted_subscription->id, 'expected' => false ),

			/*purchaser tests - all tests should expect unchanged result (false)*/
			//parent order of gifted subscription
			array( 'current_user' => $purchaser_user, 'capability' => 'view_order', 'post_id' => $parent_order->id, 'expected' => false ),
			//gifted subscription
			array( 'current_user' => $purchaser_user, 'capability' => 'view_order', 'post_id' => $subscription->id, 'expected' => false ),
			//unrelated order
			array( 'current_user' => $purchaser_user, 'capability' => 'view_order', 'post_id' => $order->id, 'expected' => false ),
			//non gifted subscription
			array( 'current_user' => $purchaser_user, 'capability' => 'view_order', 'post_id' => $non_gifted_subscription->id, 'expected' => false ),

			/*unrelated user tests - all tests should expect false, this user has no ties to the orders or subscriptions*/
			//parent order of gifted subscription
			array( 'current_user' => $user, 'capability' => 'view_order', 'post_id' => $parent_order->id, 'expected' => false ),
			//gifted subscription
			array( 'current_user' => $user, 'capability' => 'view_order', 'post_id' => $subscription->id, 'expected' => false ),
			//unrelated order
			array( 'current_user' => $user, 'capability' => 'view_order', 'post_id' => $order->id, 'expected' => false ),
			//non gifted subscription
			array( 'current_user' => $user, 'capability' => 'view_order', 'post_id' => $non_gifted_subscription->id, 'expected' => false ),
		);

		foreach( $tests as $key => $test ) {
			//reset to default
			$user_caps          = array( $test['capability'] => false );
			$args               = array( $test['capability'], $test['current_user'], $test['post_id'] );
			$capability_request = array( $test['capability'] );

			$user_caps = WCSG_Recipient_Management::grant_recipient_capabilities( $user_caps, $capability_request, $args );

			$this->assertEquals( $test['expected'], $user_caps[ $test['capability'] ] , 'Test ' . $key . ' Failed with data: ' . http_build_query( $test, '' , ', ') );
		}

		//clean-up
		wp_delete_post( $subscription->id, true );
		wp_delete_post( $parent_order->id, true );
		wp_delete_post( $order->id, true );
		wp_delete_post( $non_gifted_subscription->id, true );
		wp_delete_post( $renewal_order->id, true );

		wp_delete_user( $recipient_user );
		wp_delete_user( $purchaser_user );
		wp_delete_user( $user );

	}

}
