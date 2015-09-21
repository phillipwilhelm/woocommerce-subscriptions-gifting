<?php
class WCSG_Test_Cart_Functions extends WC_Unit_Test_Case {

	public $wcsg_test_product_one;
	public $wcsg_test_product_two;

	public function setUp() {

		$this->wcsg_test_product_one = WCS_Helper_Product::create_simple_subscription_product();
		$this->wcsg_test_product_two = WCS_Helper_Product::create_simple_subscription_product();
	}

	/**
	 * Tests for WCS_Gifting::update_cart_item_key
	 */
	public function test_update_cart_item_key() {

		/*********************************SETUP CART*********************************/

		WCSG_Helper_Test_Cart::add_to_test_cart( $this->wcsg_test_product_one, 1 );

		/*************************One Product, One Recipient*************************/

		$cart_item_keys = array_keys( WC()->cart->cart_contents );
		$cart_item_key  = reset( $cart_item_keys );
		$cart_item      = WC()->cart->cart_contents[ $cart_item_key ];

		WCS_Gifting::update_cart_item_key( $cart_item, $cart_item_key, 'email@example.com' );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );

		$new_cart_item_keys = array_keys( WC()->cart->cart_contents );
		$new_cart_item_key  = reset( $new_cart_item_keys );

		$this->assertNotEquals( $new_cart_item_key, $cart_item_key );
		$this->assertTrue( WC()->cart->cart_contents[ $new_cart_item_key ]['wcsg_gift_recipients_email'] == 'email@example.com' );

		/****************One Product, Remove Recipient From Last Test****************/

		$cart_item_keys = array_keys( WC()->cart->cart_contents );
		$cart_item_key  = reset( $cart_item_keys );
		$cart_item      = WC()->cart->cart_contents[ $cart_item_key ];

		WCS_Gifting::update_cart_item_key( $cart_item, $cart_item_key, '' );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );

		$new_cart_item_keys = array_keys( WC()->cart->cart_contents );
		$new_cart_item_key  = reset( $new_cart_item_keys );

		$this->assertNotEquals( $new_cart_item_key, $cart_item_key );
		$this->assertTrue( empty( WC()->cart->cart_contents[ $new_cart_item_key ]['wcsg_gift_recipients_email'] ) );

		/***************Add Additional Products - No Recipients in Cart***************/

		WCSG_Helper_Test_Cart::add_to_test_cart( $this->wcsg_test_product_one, 1 );

		$cart_item = reset( WC()->cart->cart_contents );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );
		$this->assertTrue( 2 == $cart_item['quantity'] );

		WCSG_Helper_Test_Cart::add_to_test_cart( $this->wcsg_test_product_one, 3 );

		$cart_item = reset( WC()->cart->cart_contents );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );
		$this->assertTrue( 5 == $cart_item['quantity'] );

		/**************Add Additional Products - One Recipient in Cart**************/

		$cart_item_keys = array_keys( WC()->cart->cart_contents );
		$cart_item_key  = reset( $cart_item_keys );
		$cart_item      = WC()->cart->cart_contents[ $cart_item_key ];

		WCS_Gifting::update_cart_item_key( $cart_item, $cart_item_key, 'email@example.com' );

		WCSG_Helper_Test_Cart::add_to_test_cart( $this->wcsg_test_product_one, 1 );

		$this->assertTrue( 2 == count( WC()->cart->cart_contents ) );

		/*************************Two Products, One Recipient*************************/

		//clear the cart
		WC()->cart->empty_cart();

		WCSG_Helper_Test_Cart::add_to_test_cart( $this->wcsg_test_product_one, 1 );
		WCSG_Helper_Test_Cart::add_to_test_cart( $this->wcsg_test_product_two, 1 );

		$this->assertTrue( 2 == count( WC()->cart->cart_contents ) );

		$cart_item_keys     = array_keys( WC()->cart->cart_contents );

		$cart_item_one_key  = $cart_item_keys[0];
		$cart_item_one      = WC()->cart->cart_contents[ $cart_item_one_key ];

		$cart_item_two_key  = $cart_item_keys[1];
		$cart_item_two      = WC()->cart->cart_contents[ $cart_item_two_key ];

		WCS_Gifting::update_cart_item_key( $cart_item_one, $cart_item_one_key, 'email@example.com' );
		WCS_Gifting::update_cart_item_key( $cart_item_two, $cart_item_two_key, 'email@example.com' );

		$this->assertTrue( 2 == count( WC()->cart->cart_contents ) );

		/****************************************************************************/

		//clean up
		WC()->cart->empty_cart();
	}

	/**
	 * Basic tests for WCSG_Checkout::add_recipient_email_recurring_cart_key.
	 *
	 * @dataProvider recipient_recurring_cart_keys_setup
	 * @param array $products An array of subscription products and recipient.
	 * @param int $expected the number of recurring carts expected.
	 */
	public function test_recipient_recurring_cart_keys( $products, $expected ) {

		foreach ( $products as $product ) {
			WCSG_Helper_Test_Cart::add_to_test_cart( $product['product'] , 1 );
			$cart_item_keys = array_keys( WC()->cart->cart_contents );
			if ( ! empty( $product['recipient_email'] ) ) {
				WC()->cart->cart_contents[ end( $cart_item_keys ) ]['wcsg_gift_recipients_email'] = $product['recipient_email'];
			}
		}

		//setup recurring carts
		WC_Subscriptions_Cart::calculate_subscription_totals( 0 , WC()->cart );

		$this->assertEquals( $expected, count( WC()->cart->recurring_carts ) );

		//clean up
		WC()->cart->empty_cart();
	}

	/**
	 * DataProvider for @see $this->test_recipient_recurring_cart_keys.
	 *
	 * @return array Returns inputs and the expected values in the format:
	 *	array(
	 *		'products' => array(
	 *		 	array( 'product', 'recipient_email' ),
	 *		 	)
	 *		, expected_number_of_recurring_carts )
	 */
	public static function recipient_recurring_cart_keys_setup() {

		$daily_subscription_product = WCS_Helper_Product::create_simple_subscription_product( array( 'subscription_period' => 'day' ) );

		$monthly_subscription_product = WCS_Helper_Product::create_simple_subscription_product( array( 'subscription_period' => 'month' ) );
		$monthly_subscription_product_two = WCS_Helper_Product::create_simple_subscription_product( array( 'subscription_period' => 'month' ) );

		return array(
			//1 Monthly Product, 1 recipient. Expects 1 recurring cart.
			array(
				'products' => array(
					array( 'product' => $monthly_subscription_product, 'recipient_email' => 'email@example.com' ),
					)
			, 1 ),
			//2 Monthly Products, 1 recipient. Expects 1 recurring cart.
			array(
				'products' => array(
					array( 'product' => $monthly_subscription_product, 'recipient_email' => 'email@example.com' ),
					array( 'product' => $monthly_subscription_product_two, 'recipient_email' => 'email@example.com' ),
					)
			, 1 ),
			//2 Monthly Products, 2 recipients. Expects 2 recurring carts.
			array(
				'products' => array(
					array( 'product' => $monthly_subscription_product, 'recipient_email' => 'email_two@example.com' ),
					array( 'product' => $monthly_subscription_product_two, 'recipient_email' => 'email@example.com' ),
					)
			, 2 ),
			//1 Monthly and 1 daily, 1 recipient. Expects 2 recurring carts.
			array(
				'products' => array(
					array( 'product' => $daily_subscription_product, 'recipient_email' => 'email@example.com' ),
					array( 'product' => $monthly_subscription_product_two, 'recipient_email' => 'email@example.com' ),
					)
			, 2 ),
			//1 Monthly and 1 daily, 2 recipient. Expects 2 recurring carts.
			array(
				'products' => array(
					array( 'product' => $daily_subscription_product, 'recipient_email' => 'email_two@example.com' ),
					array( 'product' => $monthly_subscription_product_two, 'recipient_email' => 'email@example.com' ),
					)
			, 2 ),
			//2 Monthly products, 1 gifted 1 not gifted. Expects 2 recurring carts.
			array(
				'products' => array(
					array( 'product' => $monthly_subscription_product, 'recipient_email' => '' ),
					array( 'product' => $monthly_subscription_product_two, 'recipient_email' => 'email@example.com' ),
					)
			, 2 ),
			//2 Monthly products, 0 recipients Expects 1 recurring carts.
			array(
				'products' => array(
					array( 'product' => $monthly_subscription_product, 'recipient_email' => '' ),
					array( 'product' => $monthly_subscription_product_two, 'recipient_email' => '' ),
					)
			, 1 ),
		);
	}
}
