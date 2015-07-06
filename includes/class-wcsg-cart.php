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
		if ( is_page( wc_get_page_id( 'cart' ) ) && WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) ) {
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
		$html = '';
		if ( ! empty( $cart_item['wcsg_gift_recipients_email'] ) ) {
			$html = self::generate_minicart_gifting_html( $cart_item_key, $cart_item['wcsg_gift_recipients_email'] );
		}
		return $html . $quantity;
	}

	/**
	 * Updates the cart items for changes made to recipient infomation on the cart page.
	 */
	public static function cart_update( $cart_updated ) {

		foreach ( WC()->cart->cart_contents as $key => $item ) {

			if ( ! empty( $_POST['recipient_email'][ $key ] ) ) {
				if ( ! isset( $item['wcsg_gift_recipients_email'] ) || $item['wcsg_gift_recipients_email'] != $_POST['recipient_email'][ $key ] ) {
					$cart_item_data = WC()->cart->get_item_data( $item );
					$cart_item_data['wcsg_gift_recipients_email'] = $_POST['recipient_email'][ $key ];
					$new_key = WC()->cart->generate_cart_id( $item['product_id'], $item['variation_id'], $item['variation'], $cart_item_data );

					if ( ! empty( WC()->cart->get_cart_item( $new_key ) ) ){
						$combined_quantity = $item['quantity'] + WC()->cart->get_cart_item( $new_key )['quantity'];
						WC()->cart->cart_contents[ $new_key ]['quantity'] = $combined_quantity;
						unset( WC()->cart->cart_contents[ $key ] );

					} else {// there is no item in the cart with the same new key
						WC()->cart->cart_contents[ $new_key ] = WC()->cart->cart_contents[ $key ];
						WC()->cart->cart_contents[ $new_key ]['wcsg_gift_recipients_email'] = $_POST['recipient_email'][ $key ];
						unset( WC()->cart->cart_contents[ $key ] );
					}
				}
			}
		}
		return $cart_updated;
	}

	/**
	 * Returns gifting ui html elements displaying the email of the recipient
	 */
	public static function generate_minicart_gifting_html( $cart_item_key, $email ) {

		return '<fieldset id="woocommerce_subscriptions_gifting_field">
				<label class="woocommerce_subscriptions_gifting_recipient_email">' . "Recipient: " . '</label>' . esc_html( $email ) .
				'</fieldset>';
	}
}
WCSG_Cart::init();
