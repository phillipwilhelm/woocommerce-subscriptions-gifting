<?php
class WCSG_Recipient_Addresses {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_filter( 'wcs_get_users_subscriptions', __CLASS__ . '::get_users_subscriptions', 100, 2 );

		add_filter( 'woocommerce_form_field_checkbox', __CLASS__ . '::display_update_all_addresses_notice', 1, 2 );
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

	/**
	 * Appends a notice to the 'update all subscriptions addresses' checkbox notifing the customer that updating all
	 * subscription addresses will not update gifted subscriptions, depending on which address is being updated.
	 *
	 * @param string the generated html element field string
	 * @param string the id attribute of the html element being generated
	 */
	public static function display_update_all_addresses_notice( $field, $field_id ) {

		if ( 'update_all_subscriptions_addresses' == $field_id && ( 'shipping' == get_query_var( 'edit-address' ) || 'billing' == get_query_var( 'edit-address' ) ) ) {

			switch ( get_query_var( 'edit-address' ) ) {
				case 'shipping':
					$field = substr_replace( $field, '<small>' . sprintf( esc_html__( '%1$sNote:%2$s This will not update the shipping address of subscriptions you have purchased for others.', 'woocommerce-subscriptions-gifting' ), '<strong>', '</strong>' ) . '</small>', strpos( $field,'</p>' ), 0 );
					break;
				case 'billing':
					$field = substr_replace( $field, '<small>' . sprintf( esc_html__( '%1$sNote:%2$s This will not update the billing address of subscriptions purchased for you by someone else.', 'woocommerce-subscriptions-gifting' ), '<strong>', '</strong>' ) . '</small>', strpos( $field,'</p>' ), 0 );
					break;
			}
		}

		return $field;
	}
}
WCSG_Recipient_Addresses::init();
