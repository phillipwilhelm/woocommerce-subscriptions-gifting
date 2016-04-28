<?php
class WCSG_Cart {

	public static $cart_item_recipients = array();

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {
		add_filter( 'woocommerce_cart_item_name', __CLASS__ . '::add_gifting_option_cart', 1, 3 );

		add_filter( 'woocommerce_widget_cart_item_quantity', __CLASS__ . '::add_gifting_option_minicart', 1, 3 );

		add_filter( 'woocommerce_update_cart_action_cart_updated', __CLASS__ . '::cart_update', 1, 1 );

		add_filter( 'woocommerce_add_to_cart_validation', __CLASS__ . '::prevent_products_in_gifted_renewal_orders', 10 );

		add_action( 'wp_loaded', __CLASS__ . '::load_cart_from_session', 9 );
		add_filter( 'woocommerce_get_cart_item_from_session', __CLASS__ . '::cart_item_loaded_from_session', 10, 1 );
	}

	/**
	 * Adds gifting ui elements to subscription cart items.
	 *
	 * @param string $title The product title displayed in the cart table.
	 * @param array $cart_item Details of an item in WC_Cart
	 * @param string $cart_item_key The key of the cart item being displayed in the cart table.
	 */
	public static function add_gifting_option_cart( $title, $cart_item, $cart_item_key ) {

		if ( is_cart() && ! in_array( 'get_refreshed_fragments', $_GET ) ) {
			$title .= self::maybe_display_gifting_information( $cart_item, $cart_item_key );
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

		if ( wcs_cart_contains_renewal() ) {

			$cart_item    = wcs_cart_contains_renewal();
			$subscription = wcs_get_subscription( $cart_item['subscription_renewal']['subscription_id'] );

			if ( isset( $subscription->recipient_user ) ) {
				$recipient_user = get_userdata( $subscription->recipient_user );

				$quantity .= self::generate_static_gifting_html( $cart_item_key, $recipient_user->user_email );
			}
		} else if ( ! empty( $cart_item['wcsg_gift_recipients_email'] ) ) {
			$quantity .= self::generate_static_gifting_html( $cart_item_key, $cart_item['wcsg_gift_recipients_email'] );
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
	public static function generate_static_gifting_html( $cart_item_key, $email ) {

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

	/**
	 * Returns the relevent html (static/flat, interactive or none at all) depending on
	 * whether the cart item is a giftable cart item or is a gifted renewal item.
	 *
	 * @param array $cart_item
	 * @param string $cart_item_key
	 */
	public static function maybe_display_gifting_information( $cart_item, $cart_item_key ) {

		$gifting_fields = '';

		if ( self::is_giftable_item( $cart_item ) ) {
			ob_start();

			$email               = '';
			$checkbox_attributes = array();

			if ( ! empty( $cart_item['wcsg_gift_recipients_email'] ) ) {
				$email               = $cart_item['wcsg_gift_recipients_email'];
				$checkbox_attributes = array( 'checked' );
			}

			wc_get_template( 'html-add-recipient.php', array( 'email_field_args' => WCS_Gifting::get_recipient_email_field_args( $email ), 'id' => $cart_item_key, 'email' => $email, 'checkbox_attributes' => $checkbox_attributes ),'' , plugin_dir_path( WCS_Gifting::$plugin_file ) . 'templates/' );

			$gifting_fields = ob_get_clean();

		} else if ( wcs_cart_contains_renewal() ) {

			$cart_item    = wcs_cart_contains_renewal();
			$subscription = wcs_get_subscription( $cart_item['subscription_renewal']['subscription_id'] );

			if ( isset( $subscription->recipient_user ) ) {
				$recipient_user = get_userdata( $subscription->recipient_user );

				$gifting_field  = self::generate_static_gifting_html( $cart_item_key, $recipient_user->user_email );
			}
		}

		return $gifting_fields;
	}

	// load the cart before WC and store the cart data so recipient data stored in the cart can be used for determining purchasability
	public static function load_cart_from_session() {

		$cart = WC()->session->get( 'cart', null );

		//TODO: get updated recipient data from post - cannot use the values stored on the cart as they could be out of date

		foreach ( $cart as $cart_item_key => $cart_item ) {
			$user_id = '';

			if ( isset( $cart_item['wcsg_gift_recipients_email'] ) ) {
				$user_id = email_exists( $cart_item['wcsg_gift_recipients_email'] );

				if ( empty( $user_id ) ) {
					$user_id = 0;
				}
			}

			self::$cart_item_recipients[ $cart_item_key ] = $user_id;
		}

		// point the product recipient to the first user
		$user_id = reset( self::$cart_item_recipients );

		self::update_product_recipient_user( $user_id );

		WC()->session->set( 'cart', $cart );
	}

	// called after the cart has loaded the cart item from session, point the recipient user flag to the next user
	public static function cart_item_loaded_from_session( $data ) {

		// the previous cart item has been processed - remove the first element.
		array_shift( self::$cart_item_recipients );

		// get the next cart item recipient
		$user_id = reset( self::$cart_item_recipients );
		self::update_product_recipient_user( $user_id );

		return $data;
	}

	public static function update_product_recipient_user( $user_id ) {

		// if the recipient is empty, the product is for the purchaser - unset the recipient user flag
		if ( $user_id == '' ) {
			$user_id = null;
		}

		WCSG_Product::$recipient_user = $user_id;
	}
}
WCSG_Cart::init();
