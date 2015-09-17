<?php
/**
 *
 * @see WCG_Unit_Test_Case::setUp()
 * @since 2.0
 */
class WCSG_Unit_Test_Case extends WC_Unit_Test_Case {

	public $wcsg_test_product_one;
	public $wcsg_test_product_two;

	public function setUp() {

		$this->wcsg_test_product_one = WCS_Helper_Product::create_simple_subscription_product();
		$this->wcsg_test_product_two = WCS_Helper_Product::create_simple_subscription_product();
	}


	public function test_update_cart_item_key() {

		/*********************************SETUP CART*********************************/

		self::add_to_test_cart( $this->wcsg_test_product_one, 1 );

		/****************************************************************************/

		$cart_item_keys = array_keys( WC()->cart->cart_contents );
		$cart_item_key  = reset( $cart_item_keys );
		$cart_item      = WC()->cart->cart_contents[ $cart_item_key ];

		WCS_Gifting::update_cart_item_key( $cart_item, $cart_item_key, 'email@example.com' );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );

		$new_cart_item_keys = array_keys( WC()->cart->cart_contents );
		$new_cart_item_key  = reset( $new_cart_item_keys );

		$this->assertNotEquals( $new_cart_item_key, $cart_item_key );
		$this->assertTrue( WC()->cart->cart_contents[ $new_cart_item_key ]['wcsg_gift_recipients_email'] == 'email@example.com' );

		/****************************************************************************/

		$cart_item_keys = array_keys( WC()->cart->cart_contents );
		$cart_item_key  = reset( $cart_item_keys );
		$cart_item      = WC()->cart->cart_contents[ $cart_item_key ];

		WCS_Gifting::update_cart_item_key( $cart_item, $cart_item_key, '' );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );

		$new_cart_item_keys = array_keys( WC()->cart->cart_contents );
		$new_cart_item_key  = reset( $new_cart_item_keys );

		$this->assertNotEquals( $new_cart_item_key, $cart_item_key );
		$this->assertTrue( empty( WC()->cart->cart_contents[ $new_cart_item_key ]['wcsg_gift_recipients_email'] ) );

		/****************************************************************************/

		self::add_to_test_cart( $this->wcsg_test_product_one, 1 );

		$cart_item = reset( WC()->cart->cart_contents );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );
		$this->assertTrue( 2 == $cart_item['quantity'] );

		self::add_to_test_cart( $this->wcsg_test_product_one, 3 );

		$cart_item = reset( WC()->cart->cart_contents );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );
		$this->assertTrue( 5 == $cart_item['quantity'] );

		/****************************************************************************/

		$cart_item_keys = array_keys( WC()->cart->cart_contents );
		$cart_item_key  = reset( $cart_item_keys );
		$cart_item      = WC()->cart->cart_contents[ $cart_item_key ];

		WCS_Gifting::update_cart_item_key( $cart_item, $cart_item_key, 'email@example.com' );

		self::add_to_test_cart( $this->wcsg_test_product_one, 1 );

		$this->assertTrue( 2 == count( WC()->cart->cart_contents ) );

		/****************************************************************************/

		//clear the cart
		WC()->cart->empty_cart();

		self::add_to_test_cart( $this->wcsg_test_product_one, 1 );
		self::add_to_test_cart( $this->wcsg_test_product_two, 1 );

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

	public function test_email_belongs_to_current_user() {

		$user_id = wp_create_user( 'user', 'password', 'email@example.com' );

		wp_set_current_user( $user_id );

		$this->assertTrue( WCS_Gifting::email_belongs_to_current_user( 'email@example.com' ) );
		$this->assertTrue( false == WCS_Gifting::email_belongs_to_current_user( '' ) );
		$this->assertTrue( false == WCS_Gifting::email_belongs_to_current_user( 'email1@example.com' ) );
	}

	/**
	 * Basic tests for WCSG_Checkout::add_recipient_email_recurring_cart_key
	 *
	 * @dataProvider recipient_recurring_cart_keys_setup
	 */
	public function test_recipient_recurring_cart_keys( $products, $expected ) {

		foreach ( $products as $product ) {
			self::add_to_test_cart( $product['product'] , 1 );
			$cart_item_keys = array_keys( WC()->cart->cart_contents );
			WC()->cart->cart_contents[ end( $cart_item_keys ) ]['wcsg_gift_recipients_email'] = $product['recipient_email'];
		}

		//setup recurring carts
		WC_Subscriptions_Cart::calculate_subscription_totals( 0 , WC()->cart );

		$this->assertEquals( $expected, count( WC()->cart->recurring_carts ) );

		//clean up
		WC()->cart->empty_cart();
	}

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
		);
	}

	public function test_user_display_name() {

		$user_id = wp_create_user( 'user_one', 'testuser', 'user_one@example.com' );

		//Just email
		$this->assertEquals( 'user_one@example.com', WCS_Gifting::get_user_display_name( $user_id ) );

		//First name and Email
		update_user_meta( $user_id, 'first_name', 'first_name');
		$this->assertEquals( 'first_name (user_one@example.com)', WCS_Gifting::get_user_display_name( $user_id ) );

		//First name, last name and Email
		update_user_meta( $user_id, 'last_name', 'last_name');
		$this->assertEquals( 'first_name last_name (user_one@example.com)', WCS_Gifting::get_user_display_name( $user_id ) );

		//cleanup
		wp_delete_user( $user_id );
	}

	public function test_add_recipient_subscriptions() {

		$recipient_user     = wp_create_user( 'recipient_user', 'password', 'recipient@example.com' );

		//Gifted Subscription
		$subscription       = WCS_Helper_Subscription::create_subscription( array(), array( 'recipient_user' => $recipient_user ) );
		$user_subscriptions = wcs_get_users_subscriptions( $recipient_user );

		$this->assertTrue( array_key_exists( $subscription->id, $user_subscriptions ) );
		$this->assertEquals( 1 , count( $user_subscriptions ) );

		//Purchased Subscription
		$subscription_two   = WCS_Helper_Subscription::create_subscription( array( 'customer_id' => $recipient_user ) );
		$user_subscriptions = wcs_get_users_subscriptions( $recipient_user );

		$this->assertTrue( array_key_exists( $subscription->id, $user_subscriptions ) );
		$this->assertTrue( array_key_exists( $subscription_two->id, $user_subscriptions ) );

		$this->assertEquals( 2 , count( $user_subscriptions ) );

		//clean up
		wp_delete_post( $subscription->id, true );
		wp_delete_post( $subscription_two->id, true );

		wp_delete_user( $recipient_user );
	}


	public static function add_to_test_cart( $product, $quantity ) {

		add_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );

		WC()->cart->add_to_cart( $product->id, $quantity );

		remove_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );

	}
}
