<?php
class WCS_API_Subscriptions_Test extends WCS_API_Unit_Test_Case {

	/** @var \WC_API_Subscriptions instance */
	protected $endpoint;

	public function setUp() {

		parent::setUp();
		$this->endpoint = WC()->api->WC_API_Subscriptions;
	}
	/**
	 * Test wcs-api route registration
	 *
	 * @since 2.0
	 */
	public function test_registered_routes() {
		$routes = $this->endpoint->register_routes( array() );

		$this->assertArrayHasKey( '/subscriptions', $routes );
		$this->assertArrayHasKey( '/subscriptions/count', $routes );
		$this->assertArrayHasKey( '/subscriptions/statuses', $routes );
		$this->assertArrayHasKey( '/subscriptions/(?P<subscription_id>\d+)', $routes );
		$this->assertArrayHasKey( '/subscriptions/(?P<subscription_id>\d+)/notes', $routes );
		$this->assertArrayHasKey( '/subscriptions/(?P<subscription_id>\d+)/notes/(?P<id>\d+)', $routes );
		$this->assertArrayHasKey( '/subscriptions/(?P<subscription_id>\d+)/orders', $routes );
	}

	/**
	 * Test WC_API_Subscriptions::edit_subscription()
	 *
	 * @since 2.0
	 */
	public function test_wcs_api_edit_subscription() {
		$this->endpoint->register_routes( array() );

		$subscription = WCS_Helper_Subscription::create_subscription( array( 'status' => 'pending' ) );
		// request data as if it were sent through API request
		$api_request_data = array(
			'subscription' => array(
				'status' => 'active',
			)
		);

		// check the subscription is first pending
		$this->assertEquals( 'pending', $subscription->get_status() );

		$response = $this->endpoint->edit_subscription( $subscription->id, $api_request_data );

		if ( version_compare( phpversion(), '5.3', '>=' ) ) {
			$this->assertNotTrue( empty( $response['subscription']['id'] ) );
		} 

		$edited_subscription = wcs_get_subscription( $response['subscription']['id'] );
		$this->assertEquals( 'active', $edited_subscription->get_status() );
	}

	/**
	 * Tests setting creating a manual subscription with WC_API_Subscriptions::create_subscription()
	 *
	 * @since 2.0
	 */
	public function test_wcs_api_create_subscription_manual() {
		$this->endpoint->register_routes( array() );

		$user_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );

		// no payment method
		$data = array(
			'subscription' => array(
				'status'           => 'active',
				'customer_id'      => $user_id,
				'billing_period'   => 'month',
				'billing_interval' => 1,
			)
		);

		$api_response = $this->endpoint->create_subscription( $data );
		$this->assertTrue( $api_response['creating_subscription']->is_manual() );

		// no payment method
		$data['subscription']['payment_details'] = array( 'method_id' => 'manual', 'method_title' => 'Manual' );
		$api_response = $this->endpoint->create_subscription( $data );
		$this->assertTrue( ! is_wp_error( $api_response ) && $api_response['creating_subscription']->is_manual() );
	}

	/**
	 * Tests setting creating a subscription with WC_API_Subscriptions::create_subscription()
	 * and try to set the payment method to something that is not using the `woocommerce_subscription_payment_meta`
	 * filter.
	 *
	 * @since 2.0
	 */
	public function test_wcs_api_create_subscription_unsupported_payment_method() {
		$this->endpoint->register_routes( array() );

		$data = array(
			'subscription' => array(
				'status'           => 'active',
				'customer_id'      => $this->user_id,
				'billing_period'   => 'month',
				'billing_interval' => 1,
				'payment_details'  => array(
					'method_id'    => 'stripe',
					'method_title' => 'Credit Card (Stripe)',
					'post_meta'    => array(
						'_stripe_customer_id' => 'post_stripe_id',
						'_stripe_card_id'     => 'post_stripe_card_token',
					),
				),
			)
		);

		$api_response = $this->endpoint->create_subscription( $data );
		$subscription = $api_response['creating_subscription'];

		$this->assertTrue( ! is_wp_error( $subscription ) && $subscription->is_manual() );
		$this->assertEquals( '', get_post_meta( $subscription->id, '_payment_method', true ) );

		unset( $data['payment_details']['method_id'] );
		$api_response = $this->endpoint->create_subscription( $data );
		$subscription = $api_response['creating_subscription'];

		$this->assertTrue( ! is_wp_error( $subscription ) && $subscription->is_manual() );
		$this->assertEquals( '', get_post_meta( $subscription->id, '_payment_method', true ) );
	}

	/**
	 * Test creating a subscription with a subscription that uses a payment method
	 * that uses the meta data hook.
	 * We will need a mock of WC_Payment_Gateway to test this functionality - continue manual tests.
	 *
	 * @since 2.0
	 */
	public function test_wcs_api_create_subscription_supported_payment_method() {
		$this->endpoint->register_routes( array() );

		$data = array(
			'subscription' => array(
				'status'           => 'active',
				'customer_id'      => $this->user_id,
				'billing_period'   => 'month',
				'billing_interval' => 1,
				'payment_details'  => array(
					'method_id'    => 'paypal',
					'method_title' => 'PayPal',
					'post_meta'    => array(),
				),
			)
		);

		$api_response = $this->endpoint->create_subscription( $data );
		$subscription = $api_response['creating_subscription'];

		$this->assertFalse( is_wp_error( $subscription ) || ! $subscription->is_manual() );
		$this->assertEquals( 'paypal', get_post_meta( $subscription->id, '_payment_method', true ) );
		$this->assertEquals( 'PayPal', get_post_meta( $subscription->id, '_payment_method_title', true ) );
	}
}
