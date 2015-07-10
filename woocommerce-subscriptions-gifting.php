<?php
/**
* Plugin Name: WooCommerce Gifting Subscriptions
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
}
WCS_Gifting::init();
