<?php

class WCSG_Renewal_Order_Tests extends WC_Unit_Test_Case {

	protected $recipient_user;
	protected $subscription_product;
	protected $subscription;
	protected $gifted_subscription;

	/**
	 * Sets users, products and subscriptions up to be used in tests.
	 */
	public function setUp() {

		parent::setUp();

		$this->recipient_user       = wp_create_user( 'recipient_user', 'testuser', 'recipient_user@example.com' );
		$this->subscription_product = WCS_Helper_Product::create_simple_subscription_product();

		$this->subscription         = WCS_Helper_Subscription::create_subscription();
		$this->gifted_subscription  = WCS_Helper_Subscription::create_subscription( array(), array( 'recipient_user' => $this->recipient_user ) );

	}

	/**
	 * Tests for WCSG_Checkout::maybe_ship_to_recipient
	 */
	public function test_maybe_ship_to_recipient() {

		//Gifted Subscription - expects true
		self::setup_renewal_cart( $this->subscription_product, $this->gifted_subscription );

		$ship_to_different_address = false;
		$result                    = WCSG_Checkout::maybe_ship_to_recipient( $ship_to_different_address );

		$this->assertTrue( $result );

		//Non gifted Subscription - expects false
		self::setup_renewal_cart( $this->subscription_product, $this->subscription );

		$ship_to_different_address = false;
		$result                    = WCSG_Checkout::maybe_ship_to_recipient( $ship_to_different_address );

		$this->assertFalse( $result );

		//clean-up
		WC()->cart->empty_cart();
	}

	/**
	 * Tests for WCSG_Checkout::maybe_get_recipient_shipping
	 */
	public function test_maybe_get_recipient_shipping() {

		$recipient_shipping_address = array(
			'shipping_first_name' => 'Flynn',
			'shipping_last_name' =>'Wilton',
			'shipping_company' => 'apartment 24',
			'shipping_country' =>'AU',
			'shipping_address_1' => '18 Reynolds Road',
			'shipping_address_2' => 'shipping_address_2',
			'shipping_city' => 'KANDANGA CREEK',
			'shipping_state' => 'QLD',
			'shipping_postcode' => '4570',
		);

		$empty_recipient_shipping_address = array(
			'shipping_first_name' => '',
			'shipping_last_name' =>'',
			'shipping_company' => '',
			'shipping_country' =>'',
			'shipping_address_1' => '',
			'shipping_address_2' => '',
			'shipping_city' => '',
			'shipping_state' => '',
			'shipping_postcode' => '',
		);

		//gifted subscription - Expects to return recipient information
		self::setup_renewal_cart( $this->subscription_product, $this->gifted_subscription );

		foreach ( $recipient_shipping_address as $key => $field ) {
			update_user_meta( $this->recipient_user, $key, $field );
			$result = WCSG_Checkout::maybe_get_recipient_shipping( '', $key );
			$this->assertEquals( $recipient_shipping_address[ $key ], $result );
		}

		//non gifted subscription - Expects to return unchanged result ('')
		self::setup_renewal_cart( $this->subscription_product, $this->subscription );

		foreach ( $recipient_shipping_address as $key => $field ) {
			$result = WCSG_Checkout::maybe_get_recipient_shipping( '', $key );
			$this->assertEquals( '', $result );
		}

		//gifted subscription with empty shipping data - Expects to return empty
		self::setup_renewal_cart( $this->subscription_product, $this->gifted_subscription );

		foreach ( $empty_recipient_shipping_address as $key => $field ) {
			update_user_meta( $this->recipient_user, $key, $field );
			$result = WCSG_Checkout::maybe_get_recipient_shipping( 'Original Field', $key );
			$this->assertEquals( $empty_recipient_shipping_address[ $key ], $result );
		}
	}

	/**
	 * Sets up a renewal cart to contain a subscription renewal.
	 *
	 * @param $product The product to add into the cart.
	 * @param $subscription The subscription the rewnewal belongs to.
	 */
	public static function setup_renewal_cart( $product, $subscription ) {

		WC()->cart->empty_cart();

		add_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );

		WC()->cart->add_to_cart( $product->id, 1, '', array(), array( 'subscription_renewal' => array( 'subscription_id' => $subscription->id ) ) );

		remove_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );
	}

	/**
	 * Destroys all users, products and subscriptions in preparation for the next test.
	 */
	public function tearDown() {

		parent::tearDown();

		wp_delete_post( $this->subscription->id, true );
		wp_delete_post( $this->gifted_subscription->id, true );
		wp_delete_post( $this->subscription_product->id, true );

		wp_delete_user( $this->recipient_user );
	}
}

// mock methods
function wc_next_scheduled_action( $hook, $args = null, $group = '' ) {
	return true;
}
function wc_unschedule_action( $hook, $args = array(), $group = '' ) {
	return;
}
