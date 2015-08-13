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
			ob_start();
			$email = ( empty( $cart_item['wcsg_gift_recipients_email'] ) ) ? '' : $cart_item['wcsg_gift_recipients_email'];
			wc_get_template( 'html-add-recipient.php', array( 'id' => $cart_item_key, 'email' => $email ),'' , plugin_dir_path( WCS_Gifting::$plugin_file ) . 'templates/' );
			return  $title . ob_get_clean();
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
		if ( ! empty( $_POST['recipient_email'] ) ) {
			if ( ! empty( $_POST['_wcsgnonce'] ) && wp_verify_nonce( $_POST['_wcsgnonce'], 'wcsg_add_recipient' ) ) {
				$recipients = $_POST['recipient_email'];
				WCS_Gifting::validate_recipient_emails( $recipients );
				foreach ( WC()->cart->cart_contents as $key => $item ) {
					WCS_Gifting::update_cart_item_key( $item, $key, $_POST['recipient_email'][ $key ] );
				}
			} else {
				wc_add_notice( __( 'There was an error with your request. Please try again..', 'woocommerce-subscriptions-gifting' ), 'error' );
			}
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
