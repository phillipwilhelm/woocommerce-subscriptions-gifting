<?php

/**
 * Class WCS_Helper_Subscription
 *
 * This helper class should ONLY be used for unit tests!
 */
class WCS_Helper_Subscription {

	/**
	 * Create an array of a simple subscription for every valid status
	 *
	 * @since 2.0
	 */
	public static function create_subscriptions( $data = array() ) {
		$statuses      = wcs_get_subscription_statuses();
		$subscriptions = array();

		$customer_id = wp_insert_user( array(
				'user_login' => 'testCustomer',
				'user_pass'  => 'password',
				'user_email' => 'test@example.com',
				'role'       => 'customer'
			)
		);

		foreach ( $statuses as $status => $name ) {
			$status = substr( $status, 3 );

			$args = array( 
				'status'           => $status, 
				'customer_id'      => $customer_id,
				'billing_period'   => 'month',
				'billing_interval' => 1,
			);

			if ( ! empty( $data[ $status ] ) ) {
				$args = wp_parse_args( $data[ $status ], $args );
			}

			$subscriptions[ $status ] = wcs_create_subscription( $args );
		}

		return $subscriptions;
	}

	/**
	 * Create a list of subscription in the format such that they can be read in as a DataProvider
	 *
	 * @since 2.0
	 */
	public static function subscriptions_data_provider( $data = array() ) {
		$statuses      = wcs_get_subscription_statuses();
		$subscriptions = array();

		foreach ( $statuses as $status => $name ) {
			$status = substr( $status, 3 );

			$args = array(
				'status'           => $status,
				'customer_id'      => 1,
				'billing_period'   => 'month',
				'billing_interval' => 1,
			);

			if ( ! empty( $data[ $status ] ) ) {
				$args = wp_parse_args( $data[ $status ], $args );
			}

			$subscriptions[] = array( $status, wcs_create_subscription( $args ) );
		}

		return $subscriptions;
	}

	/**
	 * Create mock WC_Subcription for testing.
	 *
	 * @since 2.0
	 */
	public static function create_subscription( $post_meta = null, $subscription_meta = null ) {
		$default_args = array(
			'status'           => '',
			'customer_id'      => 1,
			'start_date'       => current_time( 'mysql' ),
			'billing_period'   => 'month',
			'billing_interval' => 1,
		);
		$args = wp_parse_args( $post_meta, $default_args );

		$default_meta_args = array(
			'order_shipping'          => 0,
			'order_total'             => 10,
			'order_tax'               => 0,
			'order_shipping_tax'      => 0,
			'order_currency'          => 'GBP',
			'schedule_trial_end'      => 0,
			'schedule_end'            => 0,
			'schedule_next_payment'   => 0,
			'payment_method'          => '',
			'payment_method_title'    => '',
			'requires_manual_renewal' => true,
		);
		$subscription_meta_data = wp_parse_args( $subscription_meta, $default_meta_args );

		$subscription = wcs_create_subscription( $args );

		if ( is_wp_error( $subscription ) ) {
			return;
		}

		// mock subscription meta
		foreach ( $subscription_meta_data as $meta_key => $meta_value ) {
			update_post_meta( $subscription->id, '_' . $meta_key, $meta_value );
		}

		return new WC_Subscription( $subscription->id );
	}
}