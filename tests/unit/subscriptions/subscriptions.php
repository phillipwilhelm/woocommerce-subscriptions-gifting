<?php

/**
 * Class: WC_Subscriptions_Get_Date_Test
 */
class WC_Subscription_Test extends WCS_Unit_Test_Case {

	/** An array of basic subscriptions used to test against */
	public $subscriptions = array();

	/**
	 * Setup the suite for testing the WC_Subscription class
	 *
	 * @since 2.0
	 */
	public function setUp() {
		parent::setUp();

		$this->subscriptions = WCS_Helper_Subscription::create_subscriptions();
	}

	/**
	 * Test the logic around the function WC_Subscriptions::can_be_updated_to( 'wc-pending' );
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_pending() {

		$expected_results = array(
			'pending'        => false,
			'active'         => false,
			'on-hold'        => false,
			'cancelled'      => false,
			'pending-cancel' => false,
			'expired'        => false,
			'switched'       => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];
			$actual_result = $subscription->can_be_updated_to( 'pending' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to pending.' );

			$actual_result = $subscription->can_be_updated_to( 'wc-pending' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to wc-pending.' );
		}
	}

	/**
	 * Test the logic around the function WC_Subscriptions::can_be_updated_to( 'wc-active' );
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_active() {

		$expected_results = array(
			'pending'              => true,
			'active'               => false,
			'on-hold'              => true,
			'cancelled'            => false,
			'pending-cancel'       => false,
			'expired'              => false,
			'switched'             => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];

			$actual_result = $subscription->can_be_updated_to( 'active' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to active.' );

			$actual_result = $subscription->can_be_updated_to( 'wc-active' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to wc-active.' );
		}

		// Additional test cases checking the logic around WC_Subscription::payment_method_supports() function
		add_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );

		$this->assertEquals( false, $this->subscriptions['on-hold']->can_be_updated_to( 'active' ), '[FAILED]: Should not be able to activate an on-hold subscription if the payment gateway does not support it.' );
		$this->assertEquals( true, $this->subscriptions['pending']->can_be_updated_to( 'active' ), '[FAILED]: Should be able to update pending status to active if the payment method does not support subscription reactivation.' );

		remove_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );
	}

	/**
	 * Test the logic around the function WC_Subscriptions::can_be_updated_to( 'wc-on-hold' );
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_onhold() {
		$expected_results = array(
			'pending'              => true,
			'active'               => true,
			'on-hold'              => false,
			'cancelled'            => false,
			'pending-cancel'       => false,
			'expired'              => false,
			'switched'             => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];
			$actual_result = $subscription->can_be_updated_to( 'on-hold' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to on-hold.' );

			$actual_result = $subscription->can_be_updated_to( 'wc-on-hold' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to wc-on-hold.' );

		}

		// Additional test cases checking the logic around WC_Subscription::payment_method_supports() function
		add_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );

		$this->assertEquals( false, $this->subscriptions['active']->can_be_updated_to( 'on-hold' ), '[FAILED]: Should not be able to put subscription on-hold if the payment gateway does not support it.' );
		$this->assertEquals( false, $this->subscriptions['pending']->can_be_updated_to( 'on-hold' ), '[FAILED]: Should be able to update pending status on-hold if the payment method does not support subscription suspension.' );

		remove_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );
	}

	/**
	 * Test the logic around the function WC_Subscriptions::can_be_updated_to( 'wc-cancelled' );
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_cancelled() {
		$expected_results = array(
			'pending'        => true,
			'active'         => true,
			'on-hold'        => true,
			'cancelled'      => false,
			'pending-cancel' => true, // subscription has pending-cancel and has not yet ended
			'expired'        => false,
			'switched'       => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];
			$actual_result = $subscription->can_be_updated_to( 'wc-cancelled' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to wc-cancelled.' );

			$actual_result = $subscription->can_be_updated_to( 'cancelled' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to cancelled.' );

		}

		// Additional test cases checking the logic around WC_Subscription::payment_method_supports() function
		add_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );

		$this->assertEquals( false, $this->subscriptions['pending-cancel']->can_be_updated_to( 'cancelled' ) );
		$this->assertEquals( false, $this->subscriptions['active']->can_be_updated_to( 'cancelled' ) );
		$this->assertEquals( false, $this->subscriptions['pending']->can_be_updated_to( 'cancelled' ) );
		$this->assertEquals( false, $this->subscriptions['on-hold']->can_be_updated_to( 'cancelled' ) );

		remove_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );
	}

	/**
	 * Test the logic around the function WC_Subscriptions::can_be_updated_to( 'wc-switched' );
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_switched() {
		$expected_results = array(
			'pending'              => false,
			'active'               => false,
			'on-hold'              => false,
			'cancelled'            => false,
			'pending-cancel'       => false,
			'expired'              => false,
			'switched'             => false, // should statuses be able to be udpated to their previous status ?!
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];
			$actual_result = $subscription->can_be_updated_to( 'wc-switched' );

			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to wc-switched.' );
		}
	}

	/**
	 * Test the logic around the function WC_Subscriptions::can_be_updated_to( 'wc-expired' );
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_expired() {
		$expected_results = array(
			'pending'        => true,
			'active'         => true,
			'on-hold'        => true,
			'cancelled'      => false,
			'pending-cancel' => true,
			'expired'        => true,
			'switched'       => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];
			$actual_result = $subscription->can_be_updated_to( 'wc-expired' );

			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: Updating subscription (' . $status . ') to wc-expired.' );
		}
	}

	/**
	 * Test the logic around the function WC_Subscriptions::can_be_updated_to( 'wc-pending-cancel' );
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_pending_cancellation() {
		$expected_results = array(
			'pending'        => false,
			'active'         => true,
			'on-hold'        => false,
			'cancelled'      => false,
			'pending-cancel' => false,
			'expired'        => false,
			'switched'       => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];
			$actual_result = $subscription->can_be_updated_to( 'pending-cancel' );

			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to pending-cancel.' );
		}

		// Additional test cases checking the logic around WC_Subscription::payment_method_supports() function
		add_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );

		$this->assertEquals( false, $this->subscriptions['active']->can_be_updated_to( 'pending-cancel' ), '[FAILED]: Active Subscription statuses cannot be updated to pending-cancel if the payment method does not support it.' );

		remove_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );
	}

	/**
	 * Test the logic around the function WC_Subscriptions::can_be_updated_to( 'trash' );
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_trash() {
		$expected_results = array(
			'pending'        => true,
			'active'         => true,
			'on-hold'        => true,
			'cancelled'      => true,
			'pending-cancel' => true,
			'expired'        => true,
			'switched'       => true,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];
			// although wc-trash is not a legitimate status, it should still work
			$actual_result = $subscription->can_be_updated_to( 'wc-trash' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: ' . $status . ' to trash.' );

		}

		// Additional test cases checking the logic around WC_Subscription::payment_method_supports() function
		add_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );

		$this->assertEquals( false, $this->subscriptions['active']->can_be_updated_to( 'trash' ), '[FAILED]: Should not be able to  move active subscription to the trash if the payment method does not support it.' );
		$this->assertEquals( false, $this->subscriptions['pending']->can_be_updated_to( 'trash' ), '[FAILED]: Should not be able to move a Pending subscription with a payment method that does not support subscription cancellation to the trash.' );

		remove_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );
	}

	/**
	 * Test the logic around the function WC_Subscriptions::can_be_updated_to( 'deleted' );
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_deleted() {
		$expected_results = array(
			'pending'        => false,
			'active'         => false,
			'on-hold'        => false,
			'cancelled'      => false,
			'pending-cancel' => false,
			'expired'        => false,
			'switched'       => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {

			$expected_result = $expected_results[ $status ];
			$actual_result = $subscription->can_be_updated_to( 'deleted' );
			$this->assertEquals( $expected_result, $actual_result );

		}
	}

	/**
	 * Test case testing what happens when a unexpected status is entered.
	 *
	 * @since 2.0
	 */
	public function test_can_be_updated_to_other() {
		$expected_results = array(
			'pending'        => false,
			'active'         => false,
			'on-hold'        => false,
			'cancelled'      => false,
			'pending-cancel' => false,
			'expired'        => false,
			'switched'       => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];
			$actual_result = $subscription->can_be_updated_to( 'fgsdyfg' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: Should not be able to update subscription (' . $status . ') to fgsdyfg.' );

			$actual_result = $subscription->can_be_updated_to( 7783 );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: Should not be be able to update subscription (' . $status . ') to 7783.' );
		}
	}

	/**
	 * Testing WC_Subscription::can_date_be_updated( 'start' )
	 *
	 * @since 2.0
	 */
	public function test_can_start_date_be_updated() {
		$expected_results = array(
			'pending'              => true,
			'active'               => false,
			'on-hold'              => false,
			'cancelled'            => false,
			'pending-cancel'       => false,
			'expired'              => false,
			'switched'             => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {
			$expected_result = $expected_results[ $status ];
			$actual_result = $subscription->can_date_be_updated( 'start' );

			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: Should ' . ( ( $expected_results[ $status ] ) ? '' : 'not' ) .' be able to update date (' . $status . ') to start.' );
		}

	}

	/**
	 * Testing WC_Subscription::can_date_be_updated( 'trial_end' )
	 *
	 * @since 2.0
	 */
	public function test_can_date_be_updated() {
		$expected_results = array(
			'pending'              => true,
			'active'               => true,
			'on-hold'              => true,
			'cancelled'            => false,
			'pending-cancel'       => false,
			'expired'              => false,
			'switched'             => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {

			$expected_result = $expected_results[ $status ];
			$actual_result   = $subscription->can_date_be_updated( 'trial_end' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: Updating trial end date of subscription (' . $status . ').' );

			// test subscriptions with a completed payment count over 1
			add_filter( 'woocommerce_subscription_payment_completed_count', array( $this, 'completed_payment_count_stub' ) );

			$this->assertEquals( false, $subscription->can_date_be_updated( 'trial_end' ), '[FAILED]: Should not be able to update a subscription ( ' . $status . ' ) trial_end date if the completed payments counts is over 1.' );

			remove_filter( 'woocommerce_subscription_payment_completed_count', array( $this, 'completed_payment_count_stub' ) );

		}

		// Additional test cases checking the logic around WC_Subscription::payment_method_supports() function
		add_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );

		$this->assertEquals( true, $this->subscriptions['pending']->can_date_be_updated( 'trial_end' ), '[FAILED]: Should able to update pending subscription even if the payment gateway does not support it.' );
		$this->assertEquals( false, $this->subscriptions['active']->can_date_be_updated( 'trial_end' ), '[FAILED]: Should not be able to update an active subscription trial_end date if the payment gateway does not support it.' );
		$this->assertEquals( false, $this->subscriptions['on-hold']->can_date_be_updated( 'trial_end' ), '[FAILED]: Should not be able to update an active subscription trial_end date if the payment gateway does not support it.' );

		remove_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );
	}

	/**
	 * Testing WC_Subscription::can_date_be_updated( 'end' ) and
	 * WC_Subscription::can_date_be_updated( 'next_payment' )
	 *
	 * @since 2.0
	 */
	public function test_can_end_and_next_payment_date_be_updated() {
		$expected_results = array(
			'pending'        => true,
			'active'         => true,
			'on-hold'        => true,
			'cancelled'      => false,
			'pending-cancel' => false,
			'expired'        => false,
			'switched'       => false,
		);

		foreach ( $this->subscriptions as $status => $subscription ) {

			$expected_result = $expected_results[ $status ];
			$actual_result   = $subscription->can_date_be_updated( 'next_payment' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: Updating next_payment date of subscription (' . $status . ').' );

			$expected_result = $expected_results[ $status ];
			$actual_result   = $subscription->can_date_be_updated( 'end' );
			$this->assertEquals( $expected_result, $actual_result, '[FAILED]: Updating end date of subscription (' . $status . ').' );
		}

		// Additional test cases checking the logic around WC_Subscription::payment_method_supports() function
		add_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );

		$this->assertEquals( true, $this->subscriptions['pending']->can_date_be_updated( 'trial_end' ), '[FAILED]: Should able to update pending subscription even if the payment gateway does not support it.' );
		$this->assertEquals( false, $this->subscriptions['active']->can_date_be_updated( 'trial_end' ), '[FAILED]: Should not be able to update an active subscription trial_end date if the payment gateway does not support it.' );
		$this->assertEquals( false, $this->subscriptions['on-hold']->can_date_be_updated( 'trial_end' ), '[FAILED]: Should not be able to update an active subscription trial_end date if the payment gateway does not support it.' );

		remove_filter( 'woocommerce_subscription_payment_gateway_supports', array( $this, 'payment_method_supports_false' ) );
	}

	/**
	 * Testing WC_Subscription::calculate_date() when given rubbish.
	 *
	 * @since 2.0
	 */
	public function test_calculate_date_rubbish() {

		$this->assertEmpty( $this->subscriptions['active']->calculate_date( 'dhfu' ) );
	}

	/**
	 * Test calculating next payment date
	 * Could possible remove this test as it's pretty redundant if we're also testing the function: WC_Subscription:calculate_next_payment_date()
	 *
	 * @since 2.0
	 */
	public function test_calculate_next_payment_date() {
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'active' ) );
		$subscription->schedule->start = current_time( 'mysql', true );

		$expected_result = gmdate( 'Y-m-d H:i:s', wcs_add_months( strtotime( $subscription->schedule->start ), 1 ) );
		$actual_result   = $subscription->calculate_date( 'next_payment' );

		$this->assertEquals( $expected_result, $actual_result );
	}

	/**
	 * Test calculating trial_end date.
	 *
	 * @since 2.0
	 */
	public function test_calculate_trial_end_date() {
		$now = time();
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'active' ) );

		$subscription->schedule->trail_end = $trial_end = gmdate( 'Y-m-d H:i:s', wcs_add_months( $now, 1 ) );
		$this->assertEquals( $trial_end, $subscription->calculate_date( 'trial_end' ) );
		$subscription->schedule->trail_end = 0;

		// test subscriptions with a completed payment count over 1
		add_filter( 'woocommerce_subscription_payment_completed_count', array( $this, 'completed_payment_count_stub' ) );

		$this->assertEmpty( $subscription->calculate_date( 'trial_end' ), '[FAILED]: Should not be able to update a subscriptions trial_end date if the completed payments counts is over 1.' );
		$this->assertEmpty( $this->subscriptions['pending']->calculate_date( 'trial_end' ), '[FAILED]: Should not be able to update a subscription trial_end date if the completed payments counts is over 1.' );

		remove_filter( 'woocommerce_subscription_payment_completed_count', array( $this, 'completed_payment_count_stub' ) );
	}

	/**
	 * Testing the logic around calculating the end of prepaid term dates
	 *
	 * @since 2.0
	 */
	public function test_calculate_end_of_prepaid_term_date() {
		// Test with next payment being in the future. If there is a future payment that means the customer has paid up until that payment date.
		$now          = time();
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'active' ) );

		$subscription->schedule->next_payment = $expected_date = gmdate( 'Y-m-d H:i:s', wcs_add_months( $now, 1 ) );

		$this->assertEquals( $expected_date, $subscription->calculate_date( 'end_of_prepaid_term' ) );

		$subscription->schedule->next_payment = gmdate( 'Y-m-d H:i:s', strtotime( '-1 week' ) );
		$subscription->schedule->end = $expected_date = gmdate( 'Y-m-d H:i:s', wcs_add_months( $now, 2 ) );

		$this->assertEquals( $expected_date, $subscription->calculate_date( 'end_of_prepaid_term' ) );

		$subscription->schedule->next_payment = 0;

		$this->assertEquals( current_time( 'mysql', true ), $subscription->calculate_date( 'end_of_prepaid_term' ) );

		// the case that shouldn't be possible, but can be forced programmatically.. So I may as well test it anyway!)
		$subscription->schedule->end = '2014-30-12 09:56:36';
		$this->assertEquals( current_time( 'mysql', true ), $subscription->calculate_date( 'end_of_prepaid_term' ) );

		$subscription->schedule->end = current_time( 'mysql', true );
		$this->assertEquals( current_time( 'mysql', true ), $this->subscriptions['active']->calculate_date( 'end_of_prepaid_term' ) );
	}

	/**
	 * Tests the WC_Subscription::get_date() also includes testing getting
	 * dates using the suffix. Fetching dates that already exists.
	 *
	 * @since 2.0
	 */
	public function test_get_date_already_set() {
		// set a date for the pending subscription to test against
		$subscription = $this->subscriptions['pending'];

		$subscription->schedule->start = '2013-12-12 08:08:08';
		$subscription->schedule->trial_end = '2014-01-12 08:08:08';
		$subscription->schedule->end = '2014-08-12 08:08:08';
		$subscription->schedule->last_payment = '2013-12-12 08:08:08';

		// get schedule
		$this->assertEquals( '2013-12-12 08:08:08', $subscription->get_date( 'start_date' ) );
		$this->assertEquals( '2013-12-12 08:08:08', $subscription->get_date( 'start' ) );
		// get scheduled trial end date
		$this->assertEquals( '2014-01-12 08:08:08', $subscription->get_date( 'trial_end_date' ) );
		$this->assertEquals( '2014-01-12 08:08:08', $subscription->get_date( 'trial_end' ) );
		// get scheduled end date
		$this->assertEquals( '2014-08-12 08:08:08', $subscription->get_date( 'end_date' ) );
		$this->assertEquals( '2014-08-12 08:08:08', $subscription->get_date( 'end' ) );
		// get scheduled last payment date
		$this->assertEquals( '2013-12-12 08:08:08', $subscription->get_date( 'last_payment_date' ) );
		$this->assertEquals( '2013-12-12 08:08:08', $subscription->get_date( 'last_payment' ) );
	}

	/**
	 * Test for random cases.
	 *
	 * @since 2.0
	 */
	public function test_get_date_other() {
		// set a date for the pending subscription to test against
		$subscription = $this->subscriptions['pending'];

		$subscription->schedule->rubbish = '2013-12-12 08:08:08';
		$subscription->schedule->empty = null;

		// get scheduled last payment date
		$this->assertEquals( '2013-12-12 08:08:08', $subscription->get_date( 'rubbish_date' ) );
		$this->assertEquals( '2013-12-12 08:08:08', $subscription->get_date( 'rubbish' ) );

		$this->assertEmpty( $subscription->get_date( 'empty' ) );
	}

	/**
	 * Test the get_date() function specifying a date that is not GMT.
	 *
	 * @since 2.0
	 */
	public function test_get_date_not_gmt() {
		$subscription = $this->subscriptions['pending'];

		$subscription->schedule->start = $expected_result = '2014-01-01 01:01:01';

		$this->assertEquals( $expected_result, $subscription->get_date( 'start', 'site' ) );
		$this->assertEquals( $expected_result, $subscription->get_date( 'start', 'utc' ) );
	}

	/**
	 * Tests for WC_Subscription::get_gate( $date, 'gmt' )
	 *
	 * @since 2.0
	 */
	public function test_get_date_gmt() {
		$subscription = $this->subscriptions['pending'];

		$subscription->schedule->start = $expected_result = '2014-01-01 01:01:01';
		$this->assertEquals( $expected_result, $subscription->get_date( 'start', 'gmt' ) );
		$this->assertEquals( $expected_result, $subscription->get_date( 'start' ) );

		$subscription->schedule->end = $expected_result = '01/01/2014';
		$this->assertEquals( $expected_result, $subscription->get_date( 'end', 'gmt' ) );
		$this->assertEquals( $expected_result, $subscription->get_date( 'end_date' ) );
	}

	/**
	 * Tests for WC_Subscription::calculate_next_payment_date() on active subscriptions.
	 *
	 * @since 2.0
	 */
	public function test_calculate_next_payment_date_active() {
		// Create a mock of Subscription that has a public calculate_next_payment_date() function.
		$subscription = new WC_Subscription_Mock( WCS_Helper_Subscription::create_subscription( array( 'status' => 'active' ) ) );

		// when the trial end date is in the future.
		$subscription->schedule->start     = current_time( 'mysql', true );
		$subscription->schedule->trial_end = $expected_results = gmdate( 'Y-m-d H:i:s', wcs_add_months( time(), 1 ) );

		$actual_results = $subscription->calculate_next_payment_date();
		$this->assertEquals( $expected_results, $actual_results );

		// If the subscription has an end date and the next billing period comes after that
		$subscription->schedule->trial_end    = 0;
		$subscription->schedule->last_payment = $last_payment = gmdate( 'Y-m-d H:i:s', wcs_add_months( time(), 2 ) );
		$subscription->schedule->end          = gmdate( 'Y-m-d H:i:s', wcs_add_months( time(), 3 ) );

		$actual_results = $subscription->calculate_next_payment_date();
		$this->assertEquals( 0, $actual_results );

		$new_start_time = strtotime( '-1 month' );
		// If the last payment date is later then the trial end date, calculate the next payment based on the last payment time
		$subscription->schedule->start     = gmdate( 'Y-m-d H:i:s', $new_start_time );
		$subscription->schedule->end_trial = gmdate( 'Y-m-d H:i:s', wcs_add_time( 1, 'week', strtotime( 'last_month' ) ) );
		$subscription->schedule->end = 0;
		$this->assertEquals( wcs_add_months( strtotime( $last_payment ), 1 ), strtotime( $subscription->calculate_next_payment_date() ) );

		// trial end is greater than start time but it is not in the future, therefore we use the last payment
		$subscription->schedule->start        = gmdate( 'Y-m-d H:i:s', $new_start_time );
		$subscription->schedule->next_payment = 0;
		$subscription->schedule->last_payment = $last_payment = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
		$subscription->schedule->trial_end    = gmdate( 'Y-m-d H:i:s', wcs_add_time( 1, 'week', $new_start_time ) );

		$expected_results = wcs_add_months( strtotime( $last_payment ), 1 );
		$this->assertEquals( gmdate( 'Y-m-d H:i:s', $expected_results ), $subscription->calculate_next_payment_date() );

		// no trial, last payment or end date
		$subscription->schedule->start        = $start_date = current_time( 'mysql', true );
		$subscription->schedule->last_payment = 0;
		$subscription->schedule->trial_end    = 0;
		$subscription->schedule->end          = 0;

		$expected_results = wcs_add_months( strtotime( $start_date ), 1 );
		$this->assertEquals( $expected_results, strtotime( $subscription->calculate_next_payment_date() ) );

		// make sure the payment is in the future
		$subscription->schedule->start        = '2014-12-01 00:00:00';
		$subscription->schedule->end          = 0;
		$subscription->schedule->trial_end    = 0;
		$subscription->schedule->last_payment = 0;

		$this->assertTrue( strtotime( $subscription->calculate_next_payment_date() ) >= current_time( 'timestamp', true ) );
	}

	/**
	 * Tests for WC_Subscription::calculate_next_payment_date() on subscriptions with different statuses
	 * Overall this a pretty pointless test because there's no checks before calulating the next payment date for status
	 *
	 * @since 2.0
	 */
	public function test_calculate_next_payment_date_per_status() {
		$pending_subscription = new WC_Subscription_Mock( WCS_Helper_Subscription::create_subscription() );
		$pending_subscription->schedule->start = $start_date = current_time( 'mysql', true );
		$this->assertEquals( wcs_add_months( strtotime( $start_date ), 1 ), strtotime( $pending_subscription->calculate_next_payment_date() ) );

		$cancelled_subscription = new WC_Subscription_Mock( WCS_Helper_Subscription::create_subscription( array( 'status' => 'cancelled' ) ) );
		$cancelled_subscription->schedule->start = $start_date = current_time( 'mysql', true );
		$this->assertEquals( wcs_add_months( strtotime( $start_date ), 1 ), strtotime( $cancelled_subscription->calculate_next_payment_date() ) );

		$onhold_subscription = new WC_Subscription_Mock( WCS_Helper_Subscription::create_subscription( array( 'status' => 'on-hold' ) ) );
		$onhold_subscription->schedule->start = $start_date = current_time( 'mysql', true );
		$this->assertEquals( wcs_add_months( strtotime( $start_date ), 1 ), strtotime( $onhold_subscription->calculate_next_payment_date() ) );

		$switched_subscription = new WC_Subscription_Mock( WCS_Helper_Subscription::create_subscription( array( 'status' => 'switched' ) ) );
		$switched_subscription->schedule->start = $start_date = current_time( 'mysql', true );
		$this->assertEquals( wcs_add_months( strtotime( $start_date ), 1 ), strtotime( $switched_subscription->calculate_next_payment_date() ) );

		$expired_subscription = new WC_Subscription_Mock( WCS_Helper_Subscription::create_subscription( array( 'status' => 'expired' ) ) );
		$expired_subscription->schedule->start = $start_date = current_time( 'mysql', true );
		$this->assertEquals( wcs_add_months( strtotime( $start_date ), 1 ), strtotime( $expired_subscription->calculate_next_payment_date() ) );
	}

	/**
	 * Test WC_Subscripiton::delete_date() throws an exception when trying to delete start date.
	 *
	 * @since 2.0
	 */
	public function test_delete_start_date() {
		// make sure the start date doesn't exist
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'active' ) );
		unset( $subscription->schedule->start );

		// it doesn't even need to have a start date to try and delete it.
		$caught = false;
		try {
			$subscription->delete_date( 'start_date' );
		} catch ( Exception $e ) {
			$caught = ( 'The start date of a subscription can not be deleted, only updated.' === $e->getMessage() ) ? true : false;
		}

		$this->assertTrue( $caught, '[FAILED]: Exception and the correct message should have been caught when trying to delete a subscriptions start date.' );

		// set a start date to make sure the exception is still being thrown
		$subscription->schedule->start = '2014-01-01 08:08:08';
		$caught = false;

		try {
			$subscription->delete_date( 'start_date' );
		} catch ( Exception $e ) {
			$caught = ( 'The start date of a subscription can not be deleted, only updated.' === $e->getMessage() ) ? true : false;
		}

		$this->assertTrue( $caught, '[FAILED]: Exception and the correct message should have been caught when trying to delete a subscriptions start date.' );
	}

	/**
	 * Test the exception is thrown when trying to delete the last payment date.
	 *
	 * @since 2.0
	 */
	public function test_delete_last_payment_date() {
		$caught = false;

		try {
			$this->subscriptions['active']->delete_date( 'last_payment' );
		} catch ( Exception $e ) {
			$caught = ( 'The last payment date of a subscription can not be deleted. You must delete the order.' === $e->getMessage() ) ? true : false;
		}

		$this->assertTrue( $caught, '[FAILED]: Exception and the correct message should have been caught when trying to delete a subscriptions last payment date.' );
	}

	/**
	 * Delete a valid date value and check the post meta is updated correctly.
	 *
	 * @since 2.0
	 */
	public function test_delete_date_valid() {
		$this->subscriptions['active']->delete_date( 'end' );
		$this->assertEmpty( $this->subscriptions['active']->schedule->end );

		$meta_key = wcs_get_date_meta_key( 'end' );
		$this->assertEmpty( get_post_meta( $this->subscriptions['active']->id, $meta_key, true ) );
	}

	/**
	 * Try deleting a date that doesn't exist.
	 *
	 * @since 2.0
	 */
	public function test_delete_date_other() {
		$this->subscriptions['pending']->delete_date( 'wcs_rubbish' );
		$this->assertEmpty( $this->subscriptions['pending']->schedule->wcs_rubbish );

		$meta_key = wcs_get_date_meta_key( 'wcs_rubbish' );
		$this->assertEmpty( get_post_meta( $this->subscriptions['pending']->id, $meta_key, true ) );
	}

	/**
	 * Test completed payment count for subscription that has no renewal orders.
	 *
	 * @since 2.0
	 */
	public function test_get_completed_count_one() {
		$order = wc_create_order();
		$order->payment_complete();

		foreach ( array( 'active', 'on-hold', 'pending' ) as $status ) {
			update_post_meta( $order->id, '_subscription_renewal', $this->subscriptions[ $status ]->id );

			$completed_payments = $this->subscriptions[ $status ]->get_completed_payment_count();

			$expected_count = 1;

			$this->assertEquals( $expected_count, $completed_payments );
		}
	}

	/**
	 * Test completed_payment_count() for subscription that have not yet been completed.
	 * Only tests valid cases.
	 *
	 * @since 2.0
	 */
	public function test_get_completed_count_none() {

		foreach ( array( 'active', 'on-hold', 'pending' ) as $status ) {

			$completed_payments = $this->subscriptions[ $status ]->get_completed_payment_count();
			$this->assertEmpty( $completed_payments );

		}
	}

	/**
	 * Testing WC_Subscription::get_completed_count() where the subscription has many completed payments.
	 *
	 * @since 2.0
	 */
	public function test_get_completed_count_many() {
		// create 20 orders to check the completed payments on an order.
		$orders = array();

		for ( $i = 0; $i < 20; $i++ ) {
			$order = wc_create_order();
			$order->payment_complete();

			$orders[] = $order;
		}

		// add 20 orders as completed orders for each subscription and check if the completed orders count is correct
		foreach ( array( 'active', 'on-hold', 'pending' ) as $status ) {

			$expected_count = 0;

			foreach ( $orders as $order ) {

				update_post_meta( $order->id, '_subscription_renewal', $this->subscriptions[ $status ]->id );
				$expected_count++;

				if ( $expected_count % 5 == 0 ) {
					$completed_payments = $this->subscriptions[ $status ]->get_completed_payment_count();

					$this->assertEquals( $expected_count, $completed_payments );
				}
			}
		}
	}

	/**
	 * Testing WC_Subscription::get_completed_count() for those weird cases that we probably don't expect to happen, but potentially could.
	 *
	 * @since 2.0
	 */
	public function test_get_completed_count_invalid_cases() {
		// new WP_Post with subscription as parent
		$post_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_name'   => 'example',
				'post_title'  => 'example_title',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		update_post_meta( $post_id, '_subscription_renewal', $this->subscriptions['active']->id );

		$this->assertEmpty( $this->subscriptions['active']->get_completed_payment_count() );
	}

	/**
	 * Testing WC_Subscription::get_failed_payment_count for subscriptions that have no failed payments.
	 *
	 * @since 2.0
	 */
	public function test_get_failed_payment_count_none() {

		foreach ( array( 'active', 'on-hold', 'pending' ) as $status ) {
			// Continue when WC_Subscription::get_failed_payment_count() is fixed
			// $this->assertEmpty( $this->subscriptions[ $status ]->get_failed_payment_count() );
		}
	}

	/**
	 * Run a few tests for susbcriptions that have one failed payment.
	 *
	 * @since 2.0
	 */
	public function test_get_failed_payment_count_one() {

		$order = wc_create_order();
		wp_update_post( array( 'ID' => $order->id, 'post_status' => 'wc-failed' ) );

		foreach ( array( 'active', 'on-hold', 'pending' ) as $status ) {

			update_post_meta( $order->id, '_subscription_renewal', $this->subscriptions[ $status ]->id );

			$failed_payments = $this->subscriptions[ $status ]->get_failed_payment_count();

			$expected_count = 1;

			$this->assertEquals( $expected_count, $failed_payments );
		}

		// use this approach if $order->update_status( 'failed' ) creates issues
	}

	/**
	 * Tests for WC_Subscription::get_failed_payment_count() for a subscription that has
	 * many failed payments.
	 *
	 * @since 2.0
	 */
	public function test_get_failed_payment_count_many() {
		$orders = array();

		for ( $i = 0; $i < 20; $i++ ) {

			$order = wc_create_order();
			wp_update_post( array( 'ID' => $order->id, 'post_status' => 'wc-failed' ) );
			$orders[] = $order;
		}

		foreach ( array( 'active', 'on-hold', 'pending' ) as $status ) {

			$expected_count = 0;
			foreach ( $orders as $order ) {

				update_post_meta( $order->id, '_subscription_renewal', $this->subscriptions[ $status ]->id );
				$expected_count++;

				$failed_payments = $this->subscriptions[ $status ]->get_failed_payment_count();

				$this->assertEquals( $expected_count, $failed_payments );
			}
		}
	}

	/**
	 * Test getting a single related order for a subscription.
	 *
	 * @since 2.0
	 */
	public function test_get_related_order() {
		// stub REMOTE_ADDR to run in test conditions @see wc_create_order():L104 - not sure if this value exists in travis so dont override if so.
		$_SERVER['REMOTE_ADDR'] = ( isset( $_SERVER['REMOTE_ADDR'] ) ) ? $_SERVER['REMOTE_ADDR'] : '';

		// setup active subscription for testing
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'active' ) );
		$order = wc_create_order();
		update_post_meta( $order->id, '_subscription_renewal', $subscription->id );

		$related_orders = $subscription->get_related_orders();

		$this->assertEquals( 1, count( $related_orders ) );
		$this->assertEquals( $order->id, reset( $related_orders ) );

		$related_orders_more_info = $subscription->get_related_orders( 'all' );
		$expected_outcome = wc_get_order( $order->id );

		$this->assertEquals( 1, count( $related_orders ) );
		$this->assertEquals( $expected_outcome, reset( $related_orders_more_info ) );
	}

	/**
	 * Test WC_Subscription::get_related_orders() for more than one related order.
	 *
	 * @since 2.0
	 */
	public function test_get_related_orders() {
		// setup fresh active subscription
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'active' ) );

		$orders = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$order = wc_create_order();
			update_post_meta( $order->id, '_subscription_renewal', $subscription->id );

			$orders[ $i ] = $order;
			sleep( 1 ); // so get_related_orders() get order the query nicely by date
		}

		// test no param
		$related_orders = $subscription->get_related_orders();
		$this->assertEquals( 3, count( $related_orders ) );
		$this->assertEquals( $orders[0]->id, array_pop( $related_orders ) );

		// test with 'ids' param
		$related_orders = $subscription->get_related_orders( 'ids' );
		$this->assertEquals( 3, count( $related_orders ) );
		$this->assertEquals( $orders[0]->id, array_pop( $related_orders ) );
		$this->assertEquals( $orders[1]->id, array_pop( $related_orders ) );
		$this->assertEquals( $orders[2]->id, array_pop( $related_orders ) );

		$related_orders_more_info = $subscription->get_related_orders( 'all' );
		$expected_result = array();

		foreach ( $orders as $order ) {
			$expected_result[ $order->id ] = $order;
		}

		$this->assertEquals( 3, count( $related_orders_more_info ) );
		$this->assertEquals( $expected_result, $related_orders_more_info );
	}

	/**
	 * Test updating an active subscription to pending cancellation.
	 *
	 * @since 2.0
	 */
	public function test_update_status_to_pending_canellation() {
		$expected_to_pass = array( 'active' );

		foreach ( $this->subscriptions as $status => $subscription ) {
			// nothing to check on pending cancellation subs.
			if ( 'pending-cancel' == $status  ) {
				continue;
			}

			if ( in_array( $status, $expected_to_pass ) ) {

				try {
					$subscription->schedule->start = gmdate( 'Y-m-d H:i:s', strtotime( '-1 month' ) );
					$subscription->update_status( 'pending-cancel' );

					$expected_date  = time();
					$actual_results = strtotime( $subscription->schedule->end );
					$this->assertEquals( $expected_date, $actual_results, '', 1 );
				} catch ( Exception $e ) {
					$this->fail( $e->getMessage() );
				}

			} else {
				$exception_caught = false;

				try {
					$subscription->update_status( 'pending-cancel' );
				} catch ( Exception $e ) {
					$exception_caught = ( 'Unable to change subscription status to "pending-cancel".' == $e->getMessage() ) ? true : false;
				}

				$this->assertTrue( $exception_caught, '[FAILED]: Expected exception was not caught when updating ' . $status . ' to pending cancellation.' );
			}
		}
	}

	/**
	 * Test updating a subscription status to active.
	 *
	 * @since 2.0
	 */
	public function test_update_status_to_active() {

		// list of subscription that will not throw a "cannot update status" exception
		$expected_to_pass = array( 'pending', 'on-hold', 'active' );

		foreach ( $this->subscriptions as $status => $subscription ) {

			if ( in_array( $status, $expected_to_pass ) ) {

				$subscription->update_status( 'active' );

				// check the user has the default subscriber role
				$user_data = get_userdata( $subscription->customer_user );
				$roles     = $user_data->roles;

				$this->assertFalse( in_array( 'administrator', $roles ) );
				$this->assertTrue( in_array( 'subscriber', $roles ) );

			} else {

				$exception_caught = false;

				try {
					$subscription->update_status( 'active' );
				} catch ( Exception $e ) {
					$exception_caught = ( 'Unable to change subscription status to "active".' == $e->getMessage() ) ? true : false;
				}

				$this->assertTrue( $exception_caught, '[FAILED]: Expected exception was not caught when updating ' . $status . ' to active.' );

			}
		}
	}

	/**
	 * Test updating a subscription status to on-hold. This test does not check if the user's
	 * role has been updated to inactive, this is because the same user is used throughout testing
	 * and will almost always have an active subscription.
	 *
	 * Checks the suspension count on the subscription is updated correctly.
	 *
	 * @since 2.0
	 */
	public function test_update_status_to_onhold() {
		$expected_to_pass = array( 'pending', 'active' );

		foreach ( $this->subscriptions as $status => $subscription ) {
			// skip over subscriptions with the status on-hold, we don't need to check the suspension count
			if ( $status == 'on-hold' ) {
				continue;
			}

			if ( in_array( $status, $expected_to_pass ) ) {

				// set the suspension count to 0 to make sure it is correctly being incrememented
				$suspension_count = $subscription->update_suspension_count( 0 );

				$subscription->update_status( 'on-hold' );

				$actual_suspension_count = get_post_meta( $subscription->id, '_suspension_count', true );
				$this->assertEquals( $suspension_count + 1, $actual_suspension_count );

			} else {

				// expecting an exception to be thrown
				$exception_caught = false;

				try {
					$subscription->update_status( 'on-hold' );
				} catch ( Exception $e ) {
					$exception_caught = ( 'Unable to change subscription status to "on-hold".' == $e->getMessage() ) ? true : false;
				}

				$this->assertTrue( $exception_caught, '[FAILED]: Expected exception was not caught when updating ' . $status . ' to on-hold.' );

			}
		}
	}

	/**
	 * Test updating the status of a subscription to expired and making sure the
	 * correct end date is set correctly.
	 *
	 * @since 2.0
	 */
	public function test_update_status_to_expired() {
		$expected_to_pass = array( 'active', 'pending', 'pending-cancel', 'on-hold' );

		foreach ( $this->subscriptions as $status => $subscription ) {

			// skip over subscriptions with the status expired or switched, we don't need to check the end date for them.
			if ( $status == 'expired' || $status == 'switched'  ) {
				// skip switched until bug is fixed - PR for the fix has been made.
				continue;
			}

			if ( in_array( $status, $expected_to_pass ) ) {
				// need to push start date back (so that it's not set to now) so that the end date can be set to now
				$subscription->schedule->start = gmdate( 'Y-m-d H:i:s', strtotime( '-1 month' ) );

				try {
					$subscription->update_status( 'expired' );
					// end date should be set to the current time
					$expected_end_date = time();
					$actual_end_date = strtotime( $subscription->schedule->end );
					// delta set to 3 as a margin of error between the dates, shouldn't be more than 1 but just to be safe.
					$this->assertEquals( $expected_end_date, $actual_end_date, '', 3 );
				} catch ( Exception $e ) {
					$this->fail( $e->getMessage() );
				}

			} else {

				// expecting an exception to be thrown
				$exception_caught = false;

				try {
					$subscription->update_status( 'expired' );
				} catch ( Exception $e ) {
					$exception_caught = ( 'Unable to change subscription status to "expired".' == $e->getMessage() ) ? true : false;
				}

				$this->assertTrue( $exception_caught, '[FAILED]: Expected exception was not caught when updating ' . $status . ' to expired.' );

			}
		}
	}

	/**
	 * Test updating a subscription status to cancelled. Potentially look at combining the test function
	 *
	 * @since 2.0
	 */
	public function test_update_status_to_cancelled() {
		$expected_to_pass = array( 'active', 'pending', 'pending-cancel', 'on-hold' );

		foreach ( $this->subscriptions as $status => $subscription ) {
			// skip over subscriptions with the status cancelled as we don't need to check the end date
			if ( $status == 'cancelled' ) {
				continue;
			}

			if ( in_array( $status, $expected_to_pass ) ) {

				// need to push start date back (so that it's not set to now) so that the end date can be set to now
				$subscription->schedule->start = gmdate( 'Y-m-d H:i:s', strtotime( '-1 month' ) );

				try {
					$subscription->update_status( 'cancelled' );
					// end date should be set to the current time
					$expected_end_date = time();
					$actual_end_date = strtotime( $subscription->schedule->end );
					// delta set to 3 as a margin of error between the dates, shouldn't be more than 1 but just to be safe.
					$this->assertEquals( $expected_end_date, $actual_end_date, '', 3 );
				} catch ( Exception $e ) {
					$this->fail( $e->getMessage() );
				}

			} else {

				$exception_caught = false;

				try {
					$subscription->update_status( 'cancelled' );
				} catch ( Exception $e ) {
					$exception_caught = ( 'Unable to change subscription status to "cancelled".' == $e->getMessage() ) ? true : false;
				}

				$this->assertTrue( $exception_caught, '[FAILED]: Expected exception was not caught when updating ' . $status . ' to cancelled.' );

			}
		}
	}

	/**
	 * Test updating a subscription to either expired, cancelled or switched.
	 *
	 * @since 2.0
	 */
	public function test_user_inactive_update_status_to_cancelled() {
		// create a new user with no active subscriptions
		$user_id      = wp_create_user( 'susan', 'testuser', 'susan@example.com' );
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'pending', 'start_date' => '2015-07-14 00:00:00', 'customer_id' => $user_id ) );

		try {
			$subscription->update_status( 'cancelled' );
		} catch ( Exception $e ) {
			$this->fail( $e->getMessage() );
		}

		// check the user has the default inactive role
		$user_data = get_userdata( $subscription->customer_user );
		$roles = $user_data->roles;

		$this->assertContains( 'customer', $roles );

		// create a new user with 1 currently active subscription
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'active', 'start_date' => '2015-07-14 00:00:00', 'customer_id' => $user_id ) );

		try {
			$subscription->update_status( 'cancelled' );
		} catch ( Exception $e ) {
			$this->fail( $e->getMessage() );
		}

		$user_data = get_userdata( $subscription->customer_user );
		$roles = $user_data->roles;

		$this->assertContains( 'customer', $roles );
	}

	/**
	 * Test to make sure that a users role is set to inactive when updating an active
	 * or pending subscription to expired.
	 *
	 * @since 2.0
	 */
	public function test_user_inactive_update_status_to_expired() {
		// create a new user with no active subscriptions
		$user_id      = wp_create_user( 'susan', 'testuser', 'susan@example.com' );
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'pending', 'start_date' => '2015-07-14 00:00:00', 'customer_id' => $user_id ) );

		try {
			$subscription->update_status( 'expired' );
		} catch ( Exception $e ) {
			$this->fail( $e->getMessage() );
		}

		// check the user has the default inactive role
		$user_data = get_userdata( $subscription->customer_user );
		$roles = $user_data->roles;
		$this->assertContains( 'customer', $roles );

		// create a new user with 1 currently active subscription
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'active', 'start_date' => '2015-07-14 00:00:00', 'customer_id' => $user_id ) );

		try {
			$subscription->update_status( 'cancelled' );
		} catch ( Exception $e ) {
			$this->fail( $e->getMessage() );
		}

		$user_data = get_userdata( $subscription->customer_user );
		$roles = $user_data->roles;
		$this->assertContains( 'customer', $roles );
	}

	/**
	 * Test  updating a subscription status to the trash
	 *
	 * @since 2.0
	 */
	public function test_update_status_to_trash() {
	}

	/**
	 * Test the logic within WC_Subscription::update_status( 'deleted' )
	 *
	 * @since 2.0
	 */
	public function test_update_status_to_deleted() {
	}

	/**
	 *
	 *
	 * @since 2.0
	 */
	public function test_update_status_to_other() {
	}

	/**
	 * Check exceptions are thrown correctly when trying to update status from active to pending.
	 *
	 * @since 2.0
	 */
	public function test_update_status_exception_thrown_one() {

		if ( version_compare( phpversion(), '5.3', '>=' ) ) {
			$this->setExpectedException( 'Exception', 'Unable to change subscription status to "pending".' );
			$this->subscriptions['active']->update_status( 'pending' );
		}
	}

	/**
	 * Check exceptions are thrown correctly when trying to update status from pending to pending-cancel.
	 *
	 * @since 2.0
	 */
	public function test_update_status_exception_thrown_two() {

		if ( version_compare( phpversion(), '5.3', '>=' ) ) {
			$this->setExpectedException( 'Exception', 'Unable to change subscription status to "pending-cancel".' );
			$this->subscriptions['pending']->update_status( 'pending-cancel' );
		}
	}

	/**
	 * Test $subscription->update_parent()
	 *
	 * @since 2.0
	 */
	public function test_update_parent_valid() {

		foreach ( $this->subscriptions as $subscription ) {
			$parent_order = wc_create_order();
			$new_order    = wc_create_order();

			$subscription->update_parent( $parent_order->id );
			$this->assertEquals( $parent_order->id, $subscription->post->post_parent );
			$this->assertEquals( $parent_order, $subscription->order );

			$subscription->update_parent( $new_order->id );
			$this->assertEquals( $new_order->id, $subscription->post->post_parent );
			$this->assertEquals( $new_order, $subscription->order );
		}
	}

	/**
	 * Test update_parent with a product ID or some sort of string
	 *
	 * @since 2.0
	 */
	public function test_update_parent_invalid() {
		//$this->markTestSkipped( 'This test has not been implemented yet.' );
	}

	/**
	 * Test $subscription->needs_payment() if subscription is pending or failed or $0
	 *
	 * @dataProvider subscription_data_provider
	 * @since 2.0
	 */
	public function test_needs_payment_pending_failed( $status ) {
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => $status ) );

		if ( in_array( $status, array( 'pending', 'failed' ) ) ) {
			$subscription->order_total = 0;
			$this->assertFalse( $subscription->needs_payment() ); // pending or failed subscriptions with $0 total don't need paying for

			$subscription->order_total = 10;
			$this->assertTrue( $subscription->needs_payment() );
		} else {
			$this->assertFalse( $subscription->needs_payment() );
		}
	}

	/**
	 * Test $subscription->needs_payment() for the parent order
	 *
	 * @depends test_needs_payment_pending_failed
	 * @dataProvider subscription_data_provider
	 * @since 2.0
	 */
	public function test_needs_payment_parent_order( $status ) {
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => $status ) );
		$order        = wc_create_order();

		$order->order_total = 100;

		if ( in_array( $status, array( 'pending', 'failed' ) ) ) {
			$subscription->order_total = 0;
		}

		$subscription->order = $order;
		$this->assertTrue( $subscription->needs_payment() );

		$order->payment_complete();
		$this->assertFalse( $subscription->needs_payment() );
	}

	/**
	 * Test $subscription->needs_payment() for renewal orders
	 *
	 * @depends test_needs_payment_parent_order
	 * @dataProvider subscription_data_provider
	 * @since 2.0
	 */
	public function test_needs_payment_renewal_orders( $status ) {

		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => $status ) );

		if ( in_array( $status, array( 'pending', 'failed' ) ) ) {
			$subscription->order_total = 0;
		}
		$subscription->order = false;

		$renewal_order = wcs_create_renewal_order( $subscription );
		update_post_meta( $renewal_order->id, '_order_total', 100 );

		$this->assertTrue( $subscription->needs_payment() );

		foreach ( array( 'on-hold', 'failed', 'cancelled' ) as $status ) {

			$renewal_order->post_status = 'wc-' . $status;
			$this->assertTrue( $subscription->needs_payment() );
		}

		$renewal_order->update_status( 'active' );
		$this->assertFalse( $subscription->needs_payment() );
	}

	/**
	 * Tests for $subscription->payment_method_supports
	 *
	 * @since 2.0
	 */
	public function test_payment_method_supports() {
		$subscription  = WCS_Helper_Subscription::create_subscription( array( 'status' => 'active' ) );
		$supports      = array(
			'random_text',
			'gateway_scheduled_payments',
			'subscriptions',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_cancellation',
			'subscription_date_changes',
			'subscription_amount_changes',
			'subscription_payment_method_change_customer',
		);

		WC_PayPal_Standard_Subscriptions::init();
		add_filter( 'wooocommerce_paypal_credentials_are_set', '__return_true' );
		add_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );

		foreach ( $supports as $feature ) {
			$subscription->payment_gateway = false;

			// filter checks
			add_filter( 'woocommerce_subscription_payment_gateway_supports', '__return_false' );

			$this->assertFalse( $subscription->payment_method_supports( $feature ) );

			remove_filter( 'woocommerce_subscription_payment_gateway_supports', '__return_false' );

			// manual subscription
			$this->assertTrue( $subscription->payment_method_supports( $feature ) );

			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			$subscription->payment_gateway         = $available_gateways['paypal'];
			$subscription->requires_manual_renewal = false;

			if ( in_array( $feature, array( 'random_text', 'subscription_date_changes', 'subscription_amount_changes' ) ) ) {
				$this->assertFalse( $subscription->payment_method_supports( $feature ) );
			} else {
				$this->assertTrue( $subscription->payment_method_supports( $feature ), 'supports = ' . $feature );
			}
		}

		remove_filter( 'wooocommerce_paypal_credentials_are_set', '__return_true' );
		remove_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );
	}

	/**
	 * Test is_manual inside WC_Subscription class.
	 *
	 * @dataProvider subscription_data_provider
	 * @since 2.0
	 */
	public function test_is_manual( $status ) {
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => $status ) );

		$this->assertTrue( $subscription->is_manual() );

		add_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );
		$this->assertTrue( $subscription->is_manual() );

		$subscription->payment_gateway = 'non-empty string';
		$subscription->requires_manual_renewal = false;

		$this->assertFalse( $subscription->is_manual() );

		$available_gateways            = WC()->payment_gateways->get_available_payment_gateways();
		$subscription->payment_gateway = $available_gateways['paypal'];

		$this->assertFalse( $subscription->is_manual() );
		remove_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );
	}

	/**
	 * Test update_manual within the WC_Subscription class
	 *
	 * @dataProvider subscription_data_provider
	 * @since 2.0
	 */
	public function test_update_manual( $status ) {
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => $status ) );
		add_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );

		$this->assertTrue( $subscription->is_manual() );
		$subscription->payment_gateway = 'stripe';

		$subscription->update_manual( false );
		$this->assertFalse( $subscription->is_manual() );

		$subscription->update_manual( 'true' );
		$this->assertTrue( $subscription->is_manual() );

		$subscription->update_manual( 'false' );
		$this->assertFalse( $subscription->is_manual() );

		$subscription->update_manual( 'true' );
		$this->assertTrue( $subscription->is_manual() );

		$subscription->update_manual( 'junk' );
		$this->assertFalse( $subscription->is_manual() );

		remove_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );
	}

	/**
	 * Tests for has_ended within the WC_Subscription
	 *
	 * @dataProvider subscription_data_provider
	 * @since 2.0
	 */
	public function test_has_ended( $status ) {
		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => $status ) );

		if ( in_array( $status, array( 'active', 'pending', 'on-hold' ) ) ) {
			$this->assertFalse( $subscription->has_ended() );

			add_filter( 'woocommerce_subscription_has_ended', '__return_true' );
			$this->assertTrue( $subscription->has_ended() );
			remove_filter( 'woocommerce_subscription_has_ended', '__return_true' );

			add_filter( 'woocommerce_subscription_ended_statuses', array( $this, 'filter_has_ended' ) );

			if ( 'active' == $status ) {
				$this->assertTrue( $subscription->has_ended() );
			} else {
				$this->assertFalse( $subscription->has_ended() );
			}

			remove_filter( 'woocommerce_subscription_ended_statuses', array( $this, 'filter_has_ended' ) );

		} else {
			$this->assertTrue( $subscription->has_ended() );

			add_filter( 'woocommerce_subscription_has_ended', '__return_true' );
			$this->assertTrue( $subscription->has_ended() );
			remove_filter( 'woocommerce_subscription_has_ended', '__return_true' );
		}
	}

	public function filter_has_ended( $end_statuses ) {
		$end_statuses[] = 'active';
		return $end_statuses;
	}

	public function subscription_data_provider() {
		return array(
			array( 'active' ),
			array( 'pending' ),
			array( 'on-hold' ),
			array( 'cancelled' ),
			array( 'pending-cancel' ),
			array( 'expired' ),
		);
	}
}

// mock method
function wc_next_scheduled_action( $hook, $args = null, $group = '' ) {
	return true;
}
// mock method
function wc_unschedule_action( $hook, $args = array(), $group = '' ) {
	return;
}

