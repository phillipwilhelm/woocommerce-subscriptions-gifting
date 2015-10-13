<?php
class WCSG_Cart {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {
		add_filter( 'woocommerce_cart_item_name', __CLASS__ . '::add_gifting_option_cart', 1, 3 );

		add_filter( 'woocommerce_widget_cart_item_quantity', __CLASS__ . '::add_gifting_option_minicart', 1, 3 );

		add_filter( 'woocommerce_update_cart_action_cart_updated', __CLASS__ . '::cart_update', 1, 1 );

		add_filter( 'woocommerce_add_to_cart_validation', __CLASS__ . '::prevent_products_in_gifted_renewal_orders', 10 );

	}

	/**
	 * Adds gifting ui elements to subscription cart items.
	 *
	 * @param string $title The product title displayed in the cart table.
	 * @param array $cart_item Details of an item in WC_Cart
	 * @param string $cart_item_key The key of the cart item being displayed in the cart table.
	 */
	public static function add_gifting_option_cart( $title, $cart_item, $cart_item_key ) {
		if ( is_cart() && ! in_array( 'get_refreshed_fragments', $_GET ) && self::is_giftable_item( $cart_item ) ) {
			ob_start();
			$email = ( empty( $cart_item['wcsg_gift_recipients_email'] ) ) ? '' : $cart_item['wcsg_gift_recipients_email'];
			wc_get_template( 'html-add-recipient.php', array( 'email_field_args' => WCS_Gifting::get_recipient_email_field_args( $email ), 'id' => $cart_item_key, 'email' => $email ),'' , plugin_dir_path( WCS_Gifting::$plugin_file ) . 'templates/' );
			return $title . ob_get_clean();
		}
		return $title;
	}

	/**
	 * Adds gifting ui elements to subscription items in the mini cart.
	 *
	 * @param int $quantity The quantity of the cart item
	 * @param array $cart_item Details of an item in WC_Cart
	 * @param string $cart_item_key key of the cart item being displayed in the mini cart.
	 */
	public static function add_gifting_option_minicart( $quantity, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item['wcsg_gift_recipients_email'] ) ) {
			$quantity .= self::generate_minicart_gifting_html( $cart_item_key, $cart_item['wcsg_gift_recipients_email'] );
		}
		return $quantity;
	}

	/**
	 * Updates the cart items for changes made to recipient infomation on the cart page.
	 *
	 * @param bool $cart_updated whether the cart has been updated.
	 */
	public static function cart_update( $cart_updated ) {
		if ( ! empty( $_POST['recipient_email'] ) ) {
			if ( ! empty( $_POST['_wcsgnonce'] ) && wp_verify_nonce( $_POST['_wcsgnonce'], 'wcsg_add_recipient' ) ) {
				$recipients = $_POST['recipient_email'];
				WCS_Gifting::validate_recipient_emails( $recipients );
				foreach ( WC()->cart->cart_contents as $key => $item ) {
					if ( isset( $_POST['recipient_email'][ $key ] ) ) {
						WCS_Gifting::update_cart_item_key( $item, $key, $_POST['recipient_email'][ $key ] );
					}
				}
			} else {
				wc_add_notice( __( 'There was an error with your request. Please try again..', 'woocommerce-subscriptions-gifting' ), 'error' );
			}
		}

		return $cart_updated;
	}

	/**
	 * Returns gifting ui html elements displaying the email of the recipient.
	 *
	 * @param string $cart_item_key The key of the cart item being displayed in the mini cart.
	 * @param string $email The email of the gift recipient.
	 */
	public static function generate_minicart_gifting_html( $cart_item_key, $email ) {

		return '<fieldset id="woocommerce_subscriptions_gifting_field">'
		     . '<label class="woocommerce_subscriptions_gifting_recipient_email">' . esc_html__( 'Recipient: ', 'woocommerce-subscriptions-gifting' ) . '</label>' . esc_html( $email )
		     . '</fieldset>';
	}

	/**
	 * Prevent products being added to the cart if the cart contains a gifted subscription renewal.
	 *
	 * @param bool $passed Whether adding to cart is valid
	 */
	public static function prevent_products_in_gifted_renewal_orders( $passed ) {
		if ( $passed ) {
			foreach ( WC()->cart->cart_contents as $key => $item ) {
				if ( isset( $item['subscription_renewal'] ) ) {
					$subscription = wcs_get_subscription( $item['subscription_renewal']['subscription_id'] );
					if ( WCS_Gifting::is_gifted_subscription( $subscription ) ) {
						$passed = false;
						wc_add_notice( __( 'You can not purchase additional products in gifted subscription renewal orders.', 'woocommerce-subscriptions-gifting' ), 'error' );
						break;
					}
				}
			}
		}
		return $passed;
	}

	/**
	 * Determines if a cart item is able to be gifted.
	 * Only subscriptions that are not a renewal or switch subscription are giftable.
	 *
	 * @return bool | whether the cart item is giftable.
	 */
	public static function is_giftable_item( $cart_item ) {
		return WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) && ! isset( $cart_item['subscription_renewal'] ) && ! isset( $cart_item['subscription_switch'] );
	}
}
WCSG_Cart::init();
