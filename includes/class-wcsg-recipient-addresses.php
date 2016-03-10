<?php
class WCSG_Recipient_Addresses {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_filter( 'wcs_get_users_subscriptions', __CLASS__ . '::get_users_subscriptions', 100, 2 );
	}


	public static function get_users_subscriptions( $subscriptions, $user_id ) {

		if ( ( 'shipping' == get_query_var( 'edit-address' ) || 'billing' == get_query_var( 'edit-address' ) ) && ! isset( $_GET['subscription'] ) ) {

			// We dont want to update the shipping address of subscriptions the user isn't the recipient of.
			if ( 'shipping' == get_query_var( 'edit-address' ) ) {

				foreach ( $subscriptions as $subscription_id => $subscription ) {

					if ( ! empty( $subscription->recipient_user ) && $subscription->recipient_user != $user_id ) {
						unset( $subscriptions[ $subscription_id ] );
					}
				}
			} else if ( 'billing' == get_query_var( 'edit-address' ) ) {

				// We dont want to update the billing address of gifted subscriptions for this user.
				foreach ( $subscriptions as $subscription_id => $subscription ) {

					if ( ! empty( $subscription->recipient_user ) && $subscription->recipient_user == $user_id ) {
						unset( $subscriptions[ $subscription_id ] );
					}
				}
			}
		}

		return $subscriptions;
	}
}
WCSG_Recipient_Addresses::init();
