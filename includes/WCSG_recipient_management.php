<?php
class WCSG_Recipient_Management{

	public static function init() {
		add_filter( 'wcs_get_users_subscriptions', __CLASS__ . '::add_recipient_subscriptions', 1, 2 );

		add_action ( 'woocommerce_order_details_after_customer_details', __CLASS__ . '::gifting_information_after_customer_details', 1 );

		add_filter ( 'wcs_view_subscription_actions', __CLASS__ . '::add_recipient_actions', 1, 2 );

	}

	public static function add_recipient_actions( $actions, $subscription ){

		if($subscription->recipient_user == wp_get_current_user()->ID){
			error_log('this is the recipient user');

			$actions['cancel'] = array(
				'url'  => wcs_get_users_change_status_link( $subscription->id, 'cancelled', $subscription->recipient_user ),
				'name' => __( 'Cancel', 'woocommerce-subscriptions' )
			);

			$actions['suspend'] = array(
				'url'  => wcs_get_users_change_status_link( $subscription->id, 'on-hold', $subscription->recipient_user ),
				'name' => __( 'Suspend', 'woocommerce-subscriptions' )
			);
		}
		return $actions;
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

	public static function gifting_information_after_customer_details( $subscription ){
		//check if the subscription is gifted
		if( !empty($subscription->recipient_user) ) {
			$customer_user = new WP_User( $subscription->customer_user );
			$recipient_user = new WP_User( $subscription->recipient_user );
			$current_user = wp_get_current_user();

			if( $current_user->ID == $customer_user->ID ){
				//display the recipient information
				echo self::add_gifting_information_html( $recipient_user->first_name . ' ' . $recipient_user->last_name, 'Recipient' );
			}else{
				//display the purchaser information
				echo self::add_gifting_information_html( $customer_user->first_name . ' ' . $customer_user->last_name, 'Purchaser' );
			}
		}
	}


	public static function add_gifting_information_html( $name, $user_title ) {
		return '<tr><th>' . $user_title . ':</th><td data-title="' . $user_title . '">' . $name . '</td></tr>';

	}

}
WCSG_Recipient_Management::init();
