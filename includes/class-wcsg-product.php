<?php
class WCSG_Product {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_filter( 'woocommerce_add_cart_item_data', __CLASS__ . '::add_recipient_data', 1, 1 );
		add_filter( 'woocommerce_get_cart_item_from_session', __CLASS__ . '::get_cart_items_from_session', 1, 3 );

		add_action( 'woocommerce_before_add_to_cart_button', __CLASS__ . '::add_gifting_option_product' );

	}

	/**
	 * Attaches recipient information to cart item data when a subscription is added to cart via product page.
	 * If the recipient email is invalid (incorrect email format or belongs to the current user) an exception is thrown
	 * and caught by WooCommerce add to cart function - preventing the product being entered into the cart.
	 *
	 * @param cart_item_data
	 * @return cart_item_data
	 */
	public static function add_recipient_data( $cart_item_data ) {

		if ( isset( $_POST['recipient_email'] ) && ! empty( $_POST['recipient_email'][0] ) ) {
			if ( ! empty( $_POST['_wcsgnonce'] ) && wp_verify_nonce( $_POST['_wcsgnonce'], 'wcsg_add_recipient' ) ) {

				if ( WCS_Gifting::validate_recipient_emails( $_POST['recipient_email'] ) ) {

					$cart_item_data['wcsg_gift_recipients_email'] = sanitize_email( $_POST['recipient_email'][0] );

				} else {
					// throw exception to be caught by WC add_to_cart(). validate_recipient_emails() will have added the relevant notices
					throw new Exception();
				}

			} else {
				throw new Exception( __( 'There was an error with your request. Please try again..', 'woocommerce-subscriptions-gifting' ) );
			}
		}

		return $cart_item_data;
	}

	/**
	 * Adds the recipient information to the session cart item data.
	 *
	 * @param object|item The Session Data stored for an item in the cart
	 * @param object|values The data stored on a cart item
	 * @return object|item The session data with added cart item recipient information
	 */
	public static function get_cart_items_from_session( $item, $values ) {
		if ( array_key_exists( 'wcsg_gift_recipients_email', $values ) ) { // previously added at the product page via $cart_item_data
			$item['wcsg_gift_recipients_email'] = $values['wcsg_gift_recipients_email'];
			unset( $values['wcsg_gift_recipients_email'] );
		}
		return $item;
	}

	/**
	 * Adds gifting ui elements to the subscription product page.
	 */
	public static function add_gifting_option_product() {
		global $product;
		if ( WC_Subscriptions_Product::is_subscription( $product ) && ! isset( $_GET['switch-subscription'] ) ) {
			$email = '';
			if ( ! empty( $_POST['recipient_email'][0] ) && ! empty( $_POST['_wcsgnonce'] ) && wp_verify_nonce( $_POST['_wcsgnonce'], 'wcsg_add_recipient' ) ) {
				$email = $_POST['recipient_email'][0];
			}
			wc_get_template( 'html-add-recipient.php', array( 'email_field_args' => WCS_Gifting::get_recipient_email_field_args( $email ), 'id' => 0, 'email' => $email ), '', plugin_dir_path( WCS_Gifting::$plugin_file ) . 'templates/' );
		}
	}
}
WCSG_Product::init();
