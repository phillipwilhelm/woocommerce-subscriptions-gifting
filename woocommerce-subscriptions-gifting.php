<?php
/**
* Plugin Name: WooCommerce Gifting Subscriptions
*/

require_once( 'includes/WCSG_Product.php' );

require_once( 'includes/WCSG_Cart.php' );

require_once( 'includes/WCSG_Checkout.php' );

require_once( 'includes/WCSG_Recipient_Management.php' );

class WCS_Gifting {

	public static function init(){

		add_action( 'wp_enqueue_scripts', __CLASS__ . '::gifting_scripts' );

	}

	public static function gifting_scripts() {
		wp_register_script( 'woocommerce_subscriptions_gifting', plugins_url( '/js/wcs-gifting.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'woocommerce_subscriptions_gifting' );
	}

	public static function generate_gifting_html( $id, $email ) {
		return  '<fieldset>
				<input type="checkbox" id="gifting_' . esc_attr( $id ) . '_option" class="woocommerce_subscription_gifting_checkbox" value="gift" ' . ((empty($email)) ? '' : 'checked') . ' > This is a gift<br>
				<label class="woocommerce_subscriptions_gifting_recipient_email" ' . ((empty($email)) ? 'style="display: none;"' : '') . 'for="recipients_email">'. "Recipient's Email Address: " . '</label>
				<input name="recipient_email[' . esc_attr( $id ) . ']" class="woocommerce_subscriptions_gifting_recipient_email" type = "email" placeholder="recipient@example.com" value = "' . esc_attr( $email ) . '" ' . ((empty($email)) ? 'style="display: none;"' : '') . '>
				</fieldset>';
	}
}
WCS_Gifting::init();
