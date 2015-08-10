<?php

/**
 *
 *
 */
class WC_Subscription_Mock extends WC_Subscription {

	/**
	 *
	 */
	public function __construct( $subscription ) {
		parent::__construct( $subscription );

	}

	/**
	 * Calculates the next payment date for a subscription
	 *
	 */
	public function calculate_next_payment_date() {
		return parent::calculate_next_payment_date();
	}
}