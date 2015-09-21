<?php
class WCSG_Helper_Test_Cart {

	/**
	 * Helper function for adding a product to cart
	 *
	 * @param object $product the product to add to cart.
	 * @param int $quantity.
	 */
	public static function add_to_test_cart( $product, $quantity ) {

		add_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );

		WC()->cart->add_to_cart( $product->id, $quantity );

		remove_filter( 'woocommerce_subscription_is_purchasable', '__return_true' );
	}
}
