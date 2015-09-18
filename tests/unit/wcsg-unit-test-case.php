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

		wp_delete_user( $user_id );
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

		self::add_to_test_cart( $monthly_subscription_product , 1 );
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

		self::add_to_test_cart( $monthly_subscription_product , 1 );
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

		self::add_to_test_cart( $monthly_subscription_product , 1 );
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
	 * Basic tests for WCS_Gifting::get_recipient_email_field_args
	 *
	 * @dataProvider recipient_email_field_args_setup
	 */
	public function test_get_recipient_email_field_args( $current_user_email, $email, $expected_invalid, $expected_is_display_none ) {

		if ( ! empty( $current_user_email ) ) {
			$user_id = wp_create_user( 'user', 'password', $current_user_email );
			wp_set_current_user( $user_id );
		}

		$result                 = WCS_Gifting::get_recipient_email_field_args( $email );
		$invalid_result         = in_array( 'woocommerce-invalid', $result['class'] );
		$result_is_display_none = in_array( 'display: none', $result['style_attributes'] );

		$this->assertEquals( $invalid_result, $expected_invalid );
		$this->assertEquals( $result_is_display_none, $expected_is_display_none );

		if ( ! empty( $current_user_email ) ) {
			//clean up
			wp_delete_user( $user_id );
		}
	}

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

	public static function add_to_test_cart( $product, $quantity ) {

		add_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );

		WC()->cart->add_to_cart( $product->id, $quantity );

		remove_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );
	}
}
