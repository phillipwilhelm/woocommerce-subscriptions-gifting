<?php
/**
 * Plugin Name: WooCommerce Subscriptions Gifting
 * Plugin URI:
 * Description: Allow customers to buy products and services with recurring payments for other recipients.
 * Author: Prospress Inc.
 * Author URI: http://prospress.com/
 * Version: 1.0-bleeding
 *
 * Copyright 2015 Prospress, Inc.  (email : freedoms@prospress.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package		WooCommerce Subscriptions Gifting
 * @author		James Allan
 * @since		1.0
 */

require_once( 'includes/class-wcsg-product.php' );

require_once( 'includes/class-wcsg-cart.php' );

require_once( 'includes/class-wcsg-checkout.php' );

require_once( 'includes/class-wcsg-recipient-management.php' );

require_once( 'includes/class-wcsg-recipient-details.php' );

require_once( 'includes/class-wcsg-email.php' );

class WCS_Gifting {

	public static $plugin_file = __FILE__;

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_action( 'wp_enqueue_scripts', __CLASS__ . '::gifting_scripts' );

		add_action( 'plugins_loaded', __CLASS__ . '::load_dependant_classes' );
	}

	/**
	 * loads classes after plugins for classes dependant on other plugin files
	 */
	public static function load_dependant_classes() {
		require_once( 'includes/class-wcsg-query.php' );
	}

	/**
	 * Register/queue frontend scripts.
	 */
	public static function gifting_scripts() {
		wp_register_script( 'woocommerce_subscriptions_gifting', plugins_url( '/js/wcs-gifting.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'woocommerce_subscriptions_gifting' );
	}

	/**
	 * Returns gifting ui html elements assigning values and styles specified by whether $email is provided
	 * @param int|id The id attribute used to differeniate items on cart and checkout pages
	 */
	public static function generate_gifting_html( $id, $email ) {
		$email_field_args = array(
			'return'      => true,
			'label'       => 'Recipient\'s Email Address:',
			'placeholder' => 'recipient@example.com',
			'class'       => array( 'woocommerce_subscriptions_gifting_recipient_email' ),
		);

		if ( ! empty( $email ) && ( self::email_belongs_to_current_user( $email ) || ! is_email( $email ) ) ) {
			array_push( $email_field_args['class'], 'woocommerce-invalid' );
		}

		$email_field = woocommerce_form_field( 'recipient_email[' . $id . ']', $email_field_args , $email );

		if ( empty( $email ) ) {
			$email_field = str_replace( '<p', '<p style ="display: none"', $email_field );
		}

		$email_field = str_replace( 'type="text"', 'type="email"', $email_field );

		return '<fieldset>'
			 . '<input type="checkbox" id="gifting_' . esc_attr( $id ) . '_option" class="woocommerce_subscription_gifting_checkbox" value="gift" ' . ( ( empty( $email ) ) ? '' : 'checked' ) . ' />' . esc_html__( 'This is a gift', 'woocommerce_subscriptions_gifting' ) . '<br />'
			 . $email_field
			 . '</fieldset>';
	}

	/**
	 * Determines if an email address belongs to the current user,
	 * @param string Email address.
	 * @return bool Returns whether the email address belongs to the current user.
	 */
	public static function email_belongs_to_current_user( $email ) {
		$current_user_email = wp_get_current_user()->user_email;
		return $current_user_email == $email;
	}

	/**
	 * Validates an array of recipient emails scheduling error notices if an error is found.
	 * @param array An array of recipient email addresses.
	 * @return bool returns whether any errors have occurred.
	 */
	public static function validate_recipient_emails( $recipients ) {
		$invalid_email_found = false;
		$self_gifting_found  = false;
		$current_user_email  = wp_get_current_user()->user_email;

		foreach ( $recipients as $key => $recipient ) {
			$cleaned_recipient = sanitize_email( $recipient );
			if ( is_email( $cleaned_recipient ) ) {
				if ( ! $self_gifting_found && self::email_belongs_to_current_user( $cleaned_recipient ) ) {
					wc_add_notice( __( 'You cannot gift a product to yourself.', 'woocommerce-subscriptions-gifting' ), 'error' );
					$self_gifting_found = true;
				}
			} else if ( ! empty( $recipient ) && ! $invalid_email_found ) {
				wc_add_notice( __( ' Invalid email address.', 'woocommerce-subscriptions-gifting' ), 'error' );
				$invalid_email_found = true;
			}
		}
	}

	/**
	 * Attaches recipient information to a subscription cart item key when the recipient information is updated. If necessary
	 * combines cart items if the same cart key exists in the cart.
	 * @param object|item The item in the cart to be updated
	 * @param string|key
	 * @param new_recipient_data The new recipient information for the item
	 */
	public static function update_cart_item_key( $item, $key , $new_recipient_data ) {
		if ( empty( $item['wcsg_gift_recipients_email'] ) || $item['wcsg_gift_recipients_email'] != $new_recipient_data ) {
			$cart_item_data = ( empty( $new_recipient_data ) ) ? null : array( 'wcsg_gift_recipients_email' => $new_recipient_data );
			$new_key        = WC()->cart->generate_cart_id( $item['product_id'], $item['variation_id'], $item['variation'], $cart_item_data );
			$cart_item      = WC()->cart->get_cart_item( $new_key );

			if ( $new_key != $key ) {
				if ( ! empty( $cart_item ) ) {
					$combined_quantity = $item['quantity'] + $cart_item['quantity'];
					WC()->cart->cart_contents[ $new_key ]['quantity'] = $combined_quantity;
					unset( WC()->cart->cart_contents[ $key ] );
				} else { // there is no item in the cart with the same new key
					WC()->cart->cart_contents[ $new_key ] = WC()->cart->cart_contents[ $key ];
					WC()->cart->cart_contents[ $new_key ]['wcsg_gift_recipients_email'] = $new_recipient_data;
					unset( WC()->cart->cart_contents[ $key ] );
				}
			}
		}
	}
}
WCS_Gifting::init();
