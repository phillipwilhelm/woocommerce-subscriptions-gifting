<?php
/**
* Plugin Name: WooCommerce Gifting Subscriptions
*/

require_once( 'includes/WCSG_Product.php' );

require_once( 'includes/WCSG_Cart.php' );

require_once( 'includes/WCSG_Checkout.php' );

require_once( 'includes/WCSG_Recipient_Management.php' );

require_once( 'includes/wcsg-recipient-details.php' );


class WCS_Gifting {
	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_action( 'wp_enqueue_scripts', __CLASS__ . '::gifting_scripts' );
		// Load dependant files
		add_action( 'plugins_loaded', __CLASS__ . '::load_dependant_classes' );

		add_filter( 'wc_get_template', __CLASS__ . '::add_new_customer_template', 10, 5 );

		add_action( 'template_redirect',  __CLASS__ . '::my_account_template_redirect' );

	}

	public static function my_account_template_redirect() {
		global $wp;
		$current_user = wp_get_current_user();
		if( is_account_page() ) {
			if( get_user_meta( $current_user->ID, 'wcsg_generated_account', true )  && !isset( $wp->query_vars['new-recipient-account'] ) ) {
				wp_redirect( wc_get_page_permalink( 'myaccount' ) . '/new-recipient-account/' );
				exit();
			}else if ( !get_user_meta( $current_user->ID, 'wcsg_generated_account', true ) && isset( $wp->query_vars['new-recipient-account'] ) ) {
				wp_redirect( wc_get_page_permalink( 'myaccount') );
				exit();
			}
		}
	}

	public static function add_new_customer_template( $located, $template_name, $args, $template_path, $default_path ) {
		global $wp;
		$current_user = wp_get_current_user();
		if( get_user_meta( $current_user->ID, 'wcsg_generated_account',true) ) {
			if ( 'myaccount/my-account.php' == $template_name && isset($wp->query_vars['new-recipient-account']) ) {
				$located = wc_locate_template( 'new-recipient-account.php', $template_path, plugin_dir_path( __FILE__ ). 'templates/' );
			}
		}
		return $located;
	}

	public static function load_dependant_classes() {
		require_once( 'includes/WCSG_Query.php' );
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
	}
}
WCS_Gifting::init();
