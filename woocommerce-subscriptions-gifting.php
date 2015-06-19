<?php
/**
* Plugin Name: WooCommerce Gifting Subscriptions
*/

require_once( 'includes/WCSG_Product.php' );

require_once( 'includes/WCSG_Cart.php' );

require_once( 'includes/WCSG_Checkout.php' );

require_once( 'includes/WCSG_Recipient_Management.php' );

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
		return  '<fieldset>
				<input type="checkbox" id="gifting_' . esc_attr( $id ) . '_option" class="woocommerce_subscription_gifting_checkbox" value="gift" ' . ( ( empty( $email ) ) ? '' : 'checked' ) . ' > This is a gift<br>
				<label class="woocommerce_subscriptions_gifting_recipient_email" ' . ( ( empty( $email ) ) ? 'style="display: none;"' : '' ) . 'for="recipients_email">'. "Recipient's Email Address: " . '</label>
				<input name="recipient_email[' . esc_attr( $id ) . ']" class="woocommerce_subscriptions_gifting_recipient_email" type = "email" placeholder="recipient@example.com" value = "' . esc_attr( $email ) . '" ' . ( ( empty( $email ) ) ? 'style="display: none;"' : '' ) . '>
				</fieldset>';

	/**
	 * Attaches recipient information to a subscription cart item key when the recipient information is updated. If necessary
	 * combines cart items if the same cart key exists in the cart.
	 * @param object|item The item in the cart to be updated
	 * @param string|key
	 * @param new_recipient_data The new recipient information for the item
	*/
	public static function update_cart_item_key( $item, $key , $new_recipient_data ) {

		if ( empty( $item['wcsg_gift_recipients_email'] ) || $item['wcsg_gift_recipients_email'] != $new_recipient_data ) {
			$cart_item_data = WC()->cart->get_item_data( $item );
			$cart_item_data['wcsg_gift_recipients_email'] = $new_recipient_data;
			$new_key = WC()->cart->generate_cart_id( $item['product_id'], $item['variation_id'], $item['variation'], $cart_item_data );

			if( !empty( WC()->cart->get_cart_item( $new_key ) ) ) {
				$combined_quantity = $item['quantity'] + WC()->cart->get_cart_item( $new_key )['quantity'];
				WC()->cart->cart_contents[ $new_key ]['quantity'] = $combined_quantity;
				unset( WC()->cart->cart_contents[ $key ] );

			} else {// there is no item in the cart with the same new key
				WC()->cart->cart_contents[ $new_key ] = WC()->cart->cart_contents[ $key ];
				WC()->cart->cart_contents[ $new_key ]['wcsg_gift_recipients_email'] = $new_recipient_data;
				unset( WC()->cart->cart_contents[ $key ] );
			}
		}
	}
}
WCS_Gifting::init();
