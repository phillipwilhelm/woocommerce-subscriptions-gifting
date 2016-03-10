<?php
class WCSG_Recipient_Addresses {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_filter( 'wcs_get_users_subscriptions', __CLASS__ . '::get_users_subscriptions', 100, 2 );
	}


	/**
	 * Returns the subset of user subscriptions which should be included when updating all subscription addresses.
	 * When setting shipping addresses only include those which the user has purchased for themselves or have been gifted to them.
	 * When setting billing addresses only include subscriptions that belong to the user and those they have gifted to another user.
	 *
	 * @param array|subscriptions
	 * @return array|subscriptions
	 */
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
