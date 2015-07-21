<?php
class WCSG_Checkout {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {
		add_filter( 'woocommerce_checkout_cart_item_quantity', __CLASS__ . '::add_gifting_option_checkout', 1, 3 );

		add_action( 'woocommerce_checkout_subscription_created', __CLASS__ . '::subscription_created', 1 , 3 );

		add_filter( 'woocommerce_subscriptions_recurring_cart_key', __CLASS__ . '::add_recipient_email_recurring_cart_key', 1, 2 );

		add_action( 'woocommerce_checkout_process', __CLASS__ . '::check_recipient_email' );

	}

	/**
	 * Adds gifting ui elements to the checkout page, adding in previously set
	 * recipient information if it exists.
	 *
	 * @param int|quantity
	 * @param object|cart_item The Cart_Item for which we are adding ui elements
	 * @param string|cart_item_key
	 * @return int|quantity The quantity of the cart item with ui elements appended on
	 */
	public static function add_gifting_option_checkout( $quantity, $cart_item, $cart_item_key ) {
		if ( WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) ) {

			$email = '';
			if ( ! empty( $_POST['recipient_email'][ $cart_item_key ] ) ) {
				$email = $_POST['recipient_email'][ $cart_item_key ];
			} else if ( ! empty( $cart_item['wcsg_gift_recipients_email'] ) ) {
				$email = $cart_item['wcsg_gift_recipients_email'];
			}

			$quantity .= WCS_Gifting::generate_gifting_html( $cart_item_key, $email );
		}
		return $quantity;
	}

	/**
	 * Attaches recipient information to a subscription when it is purchased via checkout.
	 *
	 * @param object|subscription The subscription that has just been created
	 * @param object|order
	 * @param object|recurring_cart An array of subscription products that make up the subscription
	 */
	public static function subscription_created( $subscription, $order, $recurring_cart ) {
		foreach ( $recurring_cart->cart_contents as $key => $item ) {
			// check for last minute changes made on the checkout page
			if ( isset( $_POST['recipient_email'][ $key ] ) ) {
				$recipient_email = sanitize_email( $_POST['recipient_email'][ $key ] );
				if ( is_email( $recipient_email ) ){
					$item['wcsg_gift_recipients_email'] = $recipient_email;
				}
			}

			if ( ! empty( $item['wcsg_gift_recipients_email'] ) ) {

				$recipient_email = $item['wcsg_gift_recipients_email'];
				$recipient_user_id = email_exists( $recipient_email );

				if ( empty( $recipient_user_id ) ) {
					// create a username for the new customer
					$username  = explode( '@', $recipient_email );
					$username  = sanitize_user( $username[0] );
					$counter   = 1;
					$_username = $username;
					while ( username_exists( $username ) ) {
						$username = $_username . $counter;
						$counter++;
					}
					$password = wp_generate_password();
					$recipient_user_id = wc_create_new_customer( $recipient_email, $username, $password );
				}
				update_post_meta( $subscription->id, '_recipient_user', $recipient_user_id );
			}
		}
	}

	/**
	 * Attaches the recipient email to a recurring cart key to differentiate subscription products
	 * gifted to different recipients.
	 *
	 * @param string|cart_key
	 * @param object|cart_item
	 * @return string|cart_key The cart_key with a recipient's email appended
	 */
	public static function add_recipient_email_recurring_cart_key( $cart_key, $cart_item ) {
		if ( isset( $cart_item['wcsg_gift_recipients_email'] ) ) {
			$cart_key .= '_' . $cart_item['wcsg_gift_recipients_email'];
		}
		return $cart_key;

	}

	/**
	 * When processing the checkout check if the recipient emails are valid
	 * before proceeding. If an error occurs schedule a checkout reload so the user
	 * can see the emails causing the errors.
	 */
	public static function check_recipient_email() {
		if ( ! empty( $_POST['recipient_email'] ) ) {
			$recipients          = $_POST['recipient_email'];
			$invalid_email_found = false;
			$self_gifting_found  = false;
			foreach ( $recipients as $key => $recipient ) {
				//change to the update cart function once it is merged with master.
				WC()->cart->cart_contents[ $key ]['wcsg_gift_recipients_email'] = $recipient;
				$recipient = sanitize_email( $recipient );
				if ( is_email( $recipient ) ) {
					if ( ! $self_gifting_found && WCS_Gifting::recipient_email_is_current_user( $recipient ) ) {
						wc_add_notice( __( 'You cannot gift a product to yourself.', 'woocommerce-subscriptions-gifting' ), 'error' );
						$self_gifting_found = true;
					}
				} else if ( ! $invalid_email_found ) {
					wc_add_notice( __( ' Invalid email address.', 'woocommerce-subscriptions-gifting' ), 'error' );
					$invalid_email_found = true;
				}
			}
			if ( $invalid_email_found || $self_gifting_found ) {
				WC()->session->set( 'reload_checkout', true );
			}
		}
	}
}
WCSG_Checkout::init();
