<?php
class WCSG_Test_Recipient_Functions extends WC_Unit_Test_Case {

	/**
	 * Tests for WCSG_Recipient_Management::get_recipient_subscriptions
	 */
	public function test_get_recipient_subscriptions() {

		$recipient_user     = wp_create_user( 'recipient_user', 'password', 'recipient@example.com' );

		//Gifted Subscription
		$subscription            = WCS_Helper_Subscription::create_subscription( array(), array( 'recipient_user' => $recipient_user ) );
		$user_subscriptions      = wcs_get_users_subscriptions( $recipient_user );
		$recipient_subscriptions = WCSG_Recipient_Management::get_recipient_subscriptions( $recipient_user );

		$this->assertTrue( array_key_exists( $subscription->id, $user_subscriptions ) );
		$this->assertEquals( 1 , count( $user_subscriptions ) );
		$this->assertEquals( 1 , count( $recipient_subscriptions ) );

		//Purchased Subscription
		$subscription_two        = WCS_Helper_Subscription::create_subscription( array( 'customer_id' => $recipient_user ) );
		$user_subscriptions      = wcs_get_users_subscriptions( $recipient_user );
		$recipient_subscriptions = WCSG_Recipient_Management::get_recipient_subscriptions( $recipient_user );

		$this->assertTrue( array_key_exists( $subscription->id, $user_subscriptions ) );
		$this->assertTrue( array_key_exists( $subscription_two->id, $user_subscriptions ) );

		$this->assertEquals( 2 , count( $user_subscriptions ) );
		$this->assertEquals( 1 , count( $recipient_subscriptions ) );

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

}
