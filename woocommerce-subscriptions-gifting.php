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

class WCS_Gifting {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_action( 'wp_enqueue_scripts', __CLASS__ . '::gifting_scripts' );

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
		return '<fieldset>'
		     . '<input type="checkbox" id="gifting_' . esc_attr( $id ) . '_option" class="woocommerce_subscription_gifting_checkbox" value="gift" ' . ( ( empty( $email ) ) ? '' : 'checked' ) . ' >' . esc_html__( 'This is a gift', 'woocommerce_subscriptions_gifting' ) . '<br>'
		     . '<label class="woocommerce_subscriptions_gifting_recipient_email" ' . ( ( empty( $email ) ) ? 'style="display: none;"' : '' ) . 'for="recipients_email">' . esc_html__( 'Recipient\'s Email Address: ', 'woocommerce_subscriptions_gifting' ) . '</label>'
		     . '<input name="recipient_email[' . esc_attr( $id ) . ']" class="woocommerce_subscriptions_gifting_recipient_email" type = "email" placeholder="recipient@example.com" value = "' . esc_attr( $email ) . '" ' . ( ( empty( $email ) ) ? 'style="display: none;"' : '' ) . '>'
		     . '</fieldset>';
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
			$new_key        = WC()->cart->generate_cart_id( $item['product_id'], $item['variation_id'], $item['variation'], array( 'wcsg_gift_recipients_email' => $new_recipient_data ) );
			$cart_item      = WC()->cart->get_cart_item( $new_key );

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
WCS_Gifting::init();
