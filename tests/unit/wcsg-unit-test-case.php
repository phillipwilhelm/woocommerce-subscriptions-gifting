<?php
/**
 *
 * @see WCG_Unit_Test_Case::setUp()
 * @since 2.0
 */
class WCSG_Unit_Test_Case extends WC_Unit_Test_Case {

	public function test_update_cart_item_key() {

		/*********************************SETUP CART*********************************/

		add_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );

		$product = WCS_Helper_Product::create_simple_subscription_product();
		WC()->cart->add_to_cart( $product->id, 1 );

		remove_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );

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

		WC()->cart->add_to_cart( $product->id, 1 );

		$cart_item = reset( WC()->cart->cart_contents );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );
		$this->assertTrue( 2 == $cart_item['quantity'] );

		WC()->cart->add_to_cart( $product->id, 3 );

		$cart_item = reset( WC()->cart->cart_contents );

		$this->assertTrue( 1 == count( WC()->cart->cart_contents ) );
		$this->assertTrue( 5 == $cart_item['quantity'] );

		/****************************************************************************/

		$cart_item_keys = array_keys( WC()->cart->cart_contents );
		$cart_item_key  = reset( $cart_item_keys );
		$cart_item      = WC()->cart->cart_contents[ $cart_item_key ];

		WCS_Gifting::update_cart_item_key( $cart_item, $cart_item_key, 'email@example.com' );

		WC()->cart->add_to_cart( $product->id, 1 );

		$this->assertTrue( 2 == count( WC()->cart->cart_contents ) );

		/****************************************************************************/

		//clear the cart
		WC()->cart->empty_cart();

		$product_two = WCS_Helper_Product::create_simple_subscription_product();

		WC()->cart->add_to_cart( $product->id, 1 );
		WC()->cart->add_to_cart( $product_two->id, 1 );

		$this->assertTrue( 2 == count( WC()->cart->cart_contents ) );

		$cart_item_keys     = array_keys( WC()->cart->cart_contents );

		$cart_item_one_key  = $cart_item_keys[0];
		$cart_item_one      = WC()->cart->cart_contents[ $cart_item_one_key ];

		$cart_item_two_key  = $cart_item_keys[1];
		$cart_item_two      = WC()->cart->cart_contents[ $cart_item_two_key ];

		WCS_Gifting::update_cart_item_key( $cart_item_one, $cart_item_one_key, 'email@example.com' );
		WCS_Gifting::update_cart_item_key( $cart_item_two, $cart_item_two_key, 'email@example.com' );

		$this->assertTrue( 2 == count( WC()->cart->cart_contents ) );
	}

	public function test() {

	}
}
