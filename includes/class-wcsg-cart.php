<?php
class WCSG_Cart {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {
		add_filter( 'woocommerce_cart_item_name', __CLASS__ . '::add_gifting_option_cart', 1, 3 );

		add_filter( 'woocommerce_widget_cart_item_quantity', __CLASS__ . '::add_gifting_option_minicart', 1, 3 );

		add_filter( 'woocommerce_update_cart_action_cart_updated', __CLASS__ . '::cart_update', 1, 1 );
	}

	/**
	 * Adds gifting ui elements to subscription cart items.
	 */
	public static function add_gifting_option_cart( $title, $cart_item, $cart_item_key ) {
		if ( is_page( wc_get_page_id( 'cart' ) ) && WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) && ! isset( $cart_item['subscription_renewal'] ) && ! isset( $cart_item['subscription_switch'] ) ) {
			if ( empty( $cart_item['wcsg_gift_recipients_email'] ) ) {
				$title .= WCS_Gifting::generate_gifting_html( $cart_item_key, '' );
			} else {
				$title .= WCS_Gifting::generate_gifting_html( $cart_item_key, $cart_item['wcsg_gift_recipients_email'] );
			}
		}
		return $title;
	}

	/**
	 * Adds gifting ui elements to subscription items in the mini cart.
	 */
	public static function add_gifting_option_minicart( $quantity, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item['wcsg_gift_recipients_email'] ) ) {
			$quantity .= self::generate_minicart_gifting_html( $cart_item_key, $cart_item['wcsg_gift_recipients_email'] );
		}
		return $quantity;
	}

	/**
	 * Updates the cart items for changes made to recipient infomation on the cart page.
	 */
	public static function cart_update( $cart_updated ) {
		foreach( WC()->cart->cart_contents as $key => $item ) {
			WCS_Gifting::update_cart_item_key( $item, $key, $_POST['recipient_email'][ $key ] );
		}
		return $cart_updated;
	}

	/**
	 * Returns gifting ui html elements displaying the email of the recipient
	 */
	public static function generate_minicart_gifting_html( $cart_item_key, $email ) {

		return '<fieldset id="woocommerce_subscriptions_gifting_field">'
		     . '<label class="woocommerce_subscriptions_gifting_recipient_email">' . esc_html__( 'Recipient: ', 'woocommerce-subscriptions-gifting' ) . '</label>' . esc_html( $email )
		     . '</fieldset>';
	}
}
WCSG_Cart::init();
