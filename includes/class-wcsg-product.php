<?php
class WCSG_Product {

	protected static $product_limited_to_recipient = false;

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_filter( 'woocommerce_add_cart_item_data', __CLASS__ . '::add_recipient_data', 1, 1 );

		add_filter( 'woocommerce_get_cart_item_from_session', __CLASS__ . '::get_cart_items_from_session', 1, 3 );

		add_action( 'woocommerce_before_add_to_cart_button', __CLASS__ . '::add_gifting_option_product' );

		// hook functions in preparation of Subscriptions determining if a limited product is purchasable
		add_filter( 'woocommerce_is_purchasable', __CLASS__ . '::add_is_purchasable_hooks', 10 , 2 );
		add_filter( 'woocommerce_subscription_is_purchasable', __CLASS__ . '::remove_is_purchasable_hooks', 10 , 2 );

		add_filter( 'woocommerce_subscription_is_purchasable', __CLASS__ . '::is_purchasable', 100 , 2 );
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
				$recipient_email = sanitize_email( $_POST['recipient_email'][0] );

				if ( $recipient_email == $_POST['recipient_email'][0] && is_email( $recipient_email ) ) {
					if ( WCS_Gifting::email_belongs_to_current_user( $recipient_email ) ) {
						throw new Exception( __( 'You cannot gift a product to yourself.', 'woocommerce-subscriptions-gifting' ) );
					} else {
						$cart_item_data['wcsg_gift_recipients_email'] = $recipient_email;
					}
				} else {
					throw new Exception( __( 'Invalid email address.', 'woocommerce-subscriptions-gifting' ) );
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
			$email               = '';
			$checkbox_attributes = array();

			if ( ! empty( $_POST['recipient_email'][0] ) && ! empty( $_POST['_wcsgnonce'] ) && wp_verify_nonce( $_POST['_wcsgnonce'], 'wcsg_add_recipient' ) ) {
				$email = $_POST['recipient_email'][0];
				$checkbox_attributes = array( 'checked' );
			}

			$email_field_args    = WCS_Gifting::get_recipient_email_field_args( $email );

			if ( self::$product_limited_to_recipient ) {
				unset( $email_field_args['style_attributes']['display'] );
				$checkbox_attributes = array( 'checked', 'disabled' );

				echo '<p>' . esc_html( 'You have an active subscription to this product already. However you can purchase it for someone else.', 'woocommerce-subscriptions-gifting' ) . '<p>';
			}

			wc_get_template( 'html-add-recipient.php', array( 'email_field_args' => $email_field_args, 'id' => 0, 'email' => $email, 'checkbox_attributes' => $checkbox_attributes ), '', plugin_dir_path( WCS_Gifting::$plugin_file ) . 'templates/' );
		}
	}

	public static function add_is_purchasable_hooks( $is_purchasable, $product ) {

		// prepare for Subscriptions to determine if a user has a subscription with a product by only retreiving subscriptions that strictly belong to the user
		// must be hooked on after WCSG_Recipient_Management::get_users_subscriptions() [1]
		add_filter( 'wcs_get_users_subscriptions', 'WCS_Gifting::get_subscriptions_belonging_to_user' , 10 , 2 );

		return $is_purchasable;
	}

	public static function remove_is_purchasable_hooks( $is_purchasable, $product ) {

		remove_filter( 'wcs_get_users_subscriptions', 'WCS_Gifting::get_subscriptions_belonging_to_user' , 10 );

		return $is_purchasable;
	}

	public static function is_purchasable( $is_purchasable, $product ) {

		if ( false == $is_purchasable && is_product() && WC_Subscriptions_Product::is_subscription( $product ) ) {
			// the subscription product is still purchasable, but only on the condition that the user gifts it
			$is_purchasable = true;
			self::$product_limited_to_recipient = true;
		}

		return $is_purchasable;
	}


}
WCSG_Product::init();
