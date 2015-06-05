<?php
class WCSG_Recipient_Management{

	public static function init() {
		add_filter( 'wcs_get_users_subscriptions', __CLASS__ . '::add_recipient_subscriptions', 1, 2 );

		add_action ( 'woocommerce_order_details_after_customer_details', __CLASS__ . '::gifting_information_after_customer_details', 1 );

		add_filter ( 'wcs_view_subscription_actions', __CLASS__ . '::add_recipient_actions', 1, 2 );

		//we want to change the current user to the owner just before maybe_change_users_subscription is called
		add_action( 'init', __CLASS__ . '::change_user_recipient_subscription', 99 );

		add_filter( 'wcs_get_view_subscription_url', __CLASS__ . '::after_change_recipient_subscription', 1, 2 );

	}

	public static function add_recipient_actions( $actions, $subscription ) {
		//check if the current user is the recipient of the subscription
		if ( $subscription->recipient_user == wp_get_current_user()->ID ) {

			if ( $subscription->can_be_updated_to( 'cancelled' ) ) {
				$actions['cancel'] = array(
					'url'  => self::get_recipient_change_status_link( $subscription->id, 'cancelled', $subscription->recipient_user ),
					'name' => __( 'Cancel', 'woocommerce-subscriptions' )
				);
			}
			if ( $subscription->can_be_updated_to( 'on-hold' ) ) {
				$actions['suspend'] = array(
					'url'  => self::get_recipient_change_status_link( $subscription->id, 'on-hold', $subscription->recipient_user ),
					'name' => __( 'Suspend', 'woocommerce-subscriptions' )
				);
			}
		}
		return $actions;
	}

	private static function get_recipient_change_status_link( $subscription_id, $status, $recipient_id ) {

		$subscription = wcs_get_subscription( $subscription_id );
		$current_user = wp_get_current_user()->ID;

		//temporarily set the user to the owner of the sub to generate nonce
		wp_set_current_user( $subscription->customer_user );
		$action_link = add_query_arg( array( 'subscription_id' => $subscription_id, 'change_subscription_to' => $status, 'wcsg_requesting_recipient_id' => $recipient_id ) );
		$action_link = wp_nonce_url( $action_link, $subscription_id );
		//set the user back
		wp_set_current_user( $current_user );

		return $action_link;
	}

	public static function change_user_recipient_subscription() {
		//check if the request is being made from the recipient (wcsg_requesting_recipient_id is set)
		if ( isset( $_GET['wcsg_requesting_recipient_id'] ) && isset( $_GET['change_subscription_to'] ) && isset( $_GET['subscription_id'] ) && isset( $_GET['_wpnonce'] ) ) {
			$subscription = wcs_get_subscription( $_GET['subscription_id'] );
			//temporarily assign the user to the subscription owner
			wp_set_current_user( $subscription->customer_user );
		}

	}

	public static function after_change_recipient_subscription( $url, $subscription_id ) {
		if ( isset( $_GET['wcsg_requesting_recipient_id'] ) ) {
			$subscription = wcs_get_subscription( $subscription_id );
			wp_set_current_user( $subscription->recipient_user );
		}

		return $url;
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
			$user->add_cap( 'view_order', $post_id );
		}
		return $subscriptions;
	}

	public static function gifting_information_after_customer_details( $subscription ){
		//check if the subscription is gifted
		if ( ! empty( $subscription->recipient_user ) ) {
			$customer_user = new WP_User( $subscription->customer_user );
			$recipient_user = new WP_User( $subscription->recipient_user );
			$current_user = wp_get_current_user();

			if ( $current_user->ID == $customer_user->ID ){
				echo self::add_gifting_information_html( $recipient_user->first_name . ' ' . $recipient_user->last_name, 'Recipient' );
			}else{
				echo self::add_gifting_information_html( $customer_user->first_name . ' ' . $customer_user->last_name, 'Purchaser' );
			}
		}
	}


	public static function add_gifting_information_html( $name, $user_title ) {
		return '<tr><th>' . $user_title . ':</th><td data-title="' . $user_title . '">' . $name . '</td></tr>';

	}

}
WCSG_Recipient_Management::init();
