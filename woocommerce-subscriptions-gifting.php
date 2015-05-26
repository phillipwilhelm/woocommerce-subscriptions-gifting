<?php
/**
* Plugin Name: WooCommerce Gifting Subscriptions
*/

require_once( 'includes/WCSG_Product.php' );

require_once( 'includes/WCSG_Cart.php' );

require_once( 'includes/WCSG_Checkout.php' );


class WCS_Gifting {

	public static function init(){
		add_action( 'wp_enqueue_scripts', __CLASS__ . '::gifting_scripts' );

		add_filter( 'woocommerce_get_users_subscriptions', __CLASS__ . '::add_recipient_subscriptions', 1, 2 );

		add_action ( 'woocommerce_order_details_after_customer_details', __CLASS__ . '::gifting_information_after_customer_details', 1 );
	}


	public static function gifting_information_after_customer_details( $subscription ){
		//check if the subscription is gifted
		if( !empty($subscription->recipient_user) ) {
			$customer_user = new WP_User( $subscription->customer_user );
			$recipient_user = new WP_User( $subscription->recipient_user );
			$current_user = wp_get_current_user();

			if( $current_user->ID == $customer_user->ID ){
				//display the recipient information
				echo add_gifting_information_html( $recipient_user->first_name . ' ' . $recipient_user->last_name, 'Recipient' );
			}else{
				//display the purchaser information
				echo add_gifting_information_html( $customer_user->first_name . ' ' . $customer_user->last_name, 'Purchaser' );
			}
		}
	}

	public static function add_gifting_information_html( $name, $user_title ) {
		return '<tr><th>' . $user_title . ':</th><td data-title="' . $user_title . '">' . $name . '</td></tr>';

	}

	public static function add_recipient_subscriptions( $subscriptions, $user_id ) {
		//get the posts that have been gifted to this user
		$post_ids = get_posts( array(
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'post_type'      => 'shop_subscription',
			'orderby'        => 'date',
			'order'          => 'desc',
			'meta_key'       => '_recipient_user',
			'meta_value'     => $user_id,
			'meta_compare'   => '=',
			'fields'         => 'ids',
		) );
		//add all this user's gifted subscriptions
		foreach ( $post_ids as $post_id ) {
			$subscriptions[ $post_id ] = wcs_get_subscription( $post_id );
			//allow the recipient to view their order
			$user = new WP_User( $user_id );
			$user->add_cap( 'view_order', $post_id);
		}

		return $subscriptions;
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
