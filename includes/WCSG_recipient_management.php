<?php
class WCSG_Recipient_Management{

	/**
	* Setup hooks & filters, when the class is initialised.
	*/
	public static function init() {
		add_filter( 'wcs_get_users_subscriptions', __CLASS__ . '::add_recipient_subscriptions', 1, 2 );

		add_action ( 'woocommerce_order_details_after_customer_details', __CLASS__ . '::gifting_information_after_customer_details', 1 );

		add_filter ( 'wcs_view_subscription_actions', __CLASS__ . '::add_recipient_actions', 1, 2 );

		//we want to handle the changing of subscription status before Subscriptions core
		add_action( 'init', __CLASS__ . '::change_user_recipient_subscription', 99 );

		add_filter( 'wcs_can_user_put_subscription_on_hold' , __CLASS__ . '::recipient_can_suspend', 1, 2 );
	}

	/**
	 * Adds available user actions to the subscription recipient
	 *
	 * @param array|actions An array of actions the user can peform
	 * @param object|subscription
	 * @return array|actions An updated array of actions the user can perform on a gifted subscription
	 */
	public static function add_recipient_actions( $actions, $subscription ) {

		if ( $subscription->recipient_user == wp_get_current_user()->ID ) {

			if ( $subscription->can_be_updated_to( 'on-hold' ) ) {
				$actions['suspend'] = array(
					'url'  => self::get_recipient_change_status_link( $subscription->id, 'on-hold', $subscription->recipient_user ),
					'name' => __( 'Suspend', 'woocommerce-subscriptions' )
				);
			} elseif ( $subscription->can_be_updated_to( 'active' ) && ! $subscription->needs_payment() ) {
				$actions['reactivate'] = array(
					'url'  => self::get_recipient_change_status_link( $subscription->id, 'active', $subscription->recipient_user ),
					'name' => __( 'Reactivate', 'woocommerce-subscriptions' )
				);
			}

			if ( $subscription->can_be_updated_to( 'cancelled' ) ) {
				$actions['cancel'] = array(
					'url'  => self::get_recipient_change_status_link( $subscription->id, 'cancelled', $subscription->recipient_user ),
					'name' => __( 'Cancel', 'woocommerce-subscriptions' )
				);
			}
		}
		return $actions;
	}

	/**
	 * Generates a link for the user to change the status of a subscription
	 *
	 * @param int|subscription_id
	 * @param string|status The status the recipient has requested to change the subscription to
	 * @param int|recipient_id
	 */
	private static function get_recipient_change_status_link( $subscription_id, $status, $recipient_id ) {

		$action_link = add_query_arg( array( 'subscription_id' => $subscription_id, 'change_subscription_to' => $status, 'wcsg_requesting_recipient_id' => $recipient_id ) );
		$action_link = wp_nonce_url( $action_link, $subscription_id );

		return $action_link;
	}

	/**
	 * Checks if a status change request is by the recipient, and if it is,
	 * validate the request and proceed to change to the subscription.
	 */
	public static function change_user_recipient_subscription() {
		//check if the request is being made from the recipient (wcsg_requesting_recipient_id is set)
		if ( isset( $_GET['wcsg_requesting_recipient_id'] ) && isset( $_GET['change_subscription_to'] ) && isset( $_GET['subscription_id'] ) && isset( $_GET['_wpnonce'] ) ) {

			remove_action( 'init', 'WCS_User_Change_Status_Handler::maybe_change_users_subscription', 100 );

			$subscription = wcs_get_subscription( $_GET['subscription_id'] );
			$user_id      = $subscription->get_user_id();
			$new_status   = $_GET['change_subscription_to'];

			if ( WCS_User_Change_Status_Handler::validate_request( $user_id, $subscription, $new_status, $_GET['_wpnonce'] ) ) {
				WCS_User_Change_Status_Handler::change_users_subscription( $subscription, $new_status );
				wp_safe_redirect( $subscription->get_view_order_url() );
				exit;
			}
		}

	}

	/**
	* Allows the recipient to suspend a subscription, provided the suspension count hasnt been reached
	*
	* @param bool|user_can_suspend Whether the user can suspend a subscription
	*/
	public static function recipient_can_suspend( $user_can_suspend, $subscription ) {

		if ( $subscription->recipient_user == wp_get_current_user()->ID ) {

			// Make sure subscription suspension count hasn't been reached
			$suspension_count    = $subscription->suspension_count;
			$allowed_suspensions = get_option( WC_Subscriptions_Admin::$option_prefix . '_max_customer_suspensions', 0 );

			if ( 'unlimited' === $allowed_suspensions || $allowed_suspensions > $suspension_count ) { // 0 not > anything so prevents a customer ever being able to suspend
				$user_can_suspend = true;
			}
		}

		return $user_can_suspend;

	}

	/**
	 * Adds all the subscriptions that have been gifted to a user to their subscriptions
	 *
	 * @param array|subscriptions An array of subscriptions assigned to the user
	 * @return array|subscriptions An updated array of subscriptions with any subscriptions gifted to the user added.
	 */
	public static function add_recipient_subscriptions( $subscriptions, $user_id ) {
		//get the subscription posts that have been gifted to this user
		$recipient_subs = self::get_recipient_subscriptions( $user_id );

		foreach ( $recipient_subs as $subscription_id ) {
			$subscriptions[ $subscription_id ] = wcs_get_subscription( $subscription_id );
			$user = new WP_User( $user_id );
			$user->add_cap( 'view_order', $subscription_id );
		}
		return $subscriptions;
	}

	/**
	 * Adds recipient/purchaser information to the view subscription page
	 */
	public static function gifting_information_after_customer_details( $subscription ) {
		//check if the subscription is gifted
		if ( ! empty( $subscription->recipient_user ) ) {
			$customer_user  = new WP_User( $subscription->customer_user );
			$recipient_user = new WP_User( $subscription->recipient_user );
			$current_user   = wp_get_current_user();

			if ( $current_user->ID == $customer_user->ID ) {
				echo self::add_gifting_information_html( $recipient_user->display_name, 'Recipient' );
			}else{
				echo self::add_gifting_information_html( $customer_user->display_name, 'Purchaser' );

			}
		}
	}

	/**
	 * Generates and returns the html for additional gifting information specified by the $user_title and $name
	 *
	 * @param string|name The name of the purchaser or recipient
	 * @param string|user_title The title - recipient or purchaser
	*/
	public static function add_gifting_information_html( $name, $user_title ) {
		return '<tr><th>' . $user_title . ':</th><td data-title="' . $user_title . '">' . $name . '</td></tr>';

	}

	/**
	 * Gets an array of subscription ids which have been gifted to a user
	 *
	 * @param user_id The user id of the recipient
	 * @return array An array of subscriptions gifted to the user
	*/
	public static function get_recipient_subscriptions( $user_id ){
		return get_posts( array(
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
	}
}
WCSG_Recipient_Management::init();
