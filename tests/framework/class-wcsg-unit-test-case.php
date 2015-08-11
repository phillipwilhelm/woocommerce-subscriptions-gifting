<?php
/**
 *
 * @see WCG_Unit_Test_Case::setUp()
 * @since 2.0
 */
class WCSG_Unit_Test_Case extends WC_Unit_Test_Case {

	/* Susbcription product used for testing */
	public static $simple_subscription_product;

	/* User used throughout testing */
	public $user_id = 1;

	/**
	 * Setup the test case suite
	 *
	 * @since 2.0
	 */
	public function setUp() {

		parent::setUp();

		wp_set_current_user( $this->user_id );

		if( ! self::$simple_subscription_product ) {
			self::$simple_subscription_product = $this->create_simple_subscription_product();
		}
	}

	/**
	 * Creates a simple subscription product for testing.
	 * No validation checking with the functions input values as of yet.
	 *
	 * @param $meta_filter array
	 * @param $post_meta array
	 * @since 2.0
	 */ 
	public function create_simple_subscription_product( $meta_filters = null, $post_filters = null ) {
		$default_meta_args = array (
			'stock_status'                   => 'instock',
			'downloadable'                   => 'no',
			'virtual'                        => 'no',
			'subscription_period'            => 'month',
			'sold_individually'              => 'no',
			'back_orders'                    => 'no',
			'subscription_payment_sync_date' => 0,
			'subscription_price'             => 10,
			'subscription_period'            => 'month',
			'subscription_period_interval'   => 1,
			'subscription_trial_period'      => 'day',
			'subscription_trial_length'      => 0,
			'subscription_limit'             => 'no',
		);
		$meta_data = wp_parse_args( $meta_filters, $default_meta_args );

		$default_post_args = array(
			'post_type'      => 'product',
			'post_author'    => 1,
			'post_title'     => 'Monthly WooNinja Goodies',
			'post_status'    => 'publish',
			'comment_status' => 'open',
			'ping_status'    => 'closed',
			'post_name'      => 'subscription_post_name_sample',
		);
		$post_data = wp_parse_args( $post_filters, $default_post_args );

		$product_id = wp_insert_post( $post_data );

		if ( is_wp_error( $product_id ) ) {
			return false;
		}

		foreach ( $meta_data as $meta_key => $meta_value ) {
			update_post_meta( $product_id, '_' . $meta_key, $meta_value );
		}

		wp_set_object_terms( $product_id, 'subscription', 'product_type' );

		return wc_get_product( $product_id );
	}

	/**
	 *
	 * @since 2.0
	 */
	public function set_subscription_order_address( $subscription_id ) {

	}

	/**
	 * Add default shipping to the subscription
	 *
	 * @since 2.0
	 */
	public function add_default_shipping_to( $subscription ) {
		if ( $subscription instanceof WC_Subscription ) {
			$default_shipping = array(
				'method_id' => 'free_shipping',
				'cost' => 0,
				'method_title' => 'Free Shipping',
			);

			$shipping_rate = new WC_Shipping_Rate( $default_shipping['method_id'], $default_shipping['method_title'], floatval( $default_shipping['cost'] ), array(), $default_shipping['method_id'] );

			$subscription->add_shipping( $shipping_rate );
		}
	}

	/**
	 * Add the mock simple subscription product to the subscription.
	 *
	 * @since 2.0
	 */
	public function add_default_product_to( $subscription ) {

		if ( ! self::$simple_subscription_product || ! self::$simple_subscription_product instanceof WC_Product_Subscription ) {
			self::$simple_subscription_product = $this->create_simple_subscription_product();
		}

		$order_item_meta = array(
			'qty'    => 1,
			'totals' => array(
				'subtotal'     => $subscription->subscription_price,
				'subtotal_tax' => 0,
				'total'        => $subscription->subscription_price,
				'tax'          => 0,
			),
		);

		$order_item_id = $subscription->add_product( self::$simple_subscription_product, $order_item_meta['qty'], $order_item_meta );
	}

	/**
	 * Add default fee to subscription.
	 *
	 * @since 2.0
	 */
	public function add_default_fee_to( $subscription ) {
		$subscription_fee = new stdClass();

		// setup default fee object
		$subscription_fee->name     = 'Default Subscription Fee';
		$subscription_fee->id       = sanitize_title( $subscription_fee->name );
		$subscription_fee->amount   = floatval( 5 );
		$subscription_fee->taxable  = false;
		$subscription_fee->tax      = 0;
		$subscription_fee->tax_data = array();

		$fee_id = $subscription->add_fee( $subscription_fee );
	}

	/**
	 * Add Default taxes to subscription
	 *
	 * @since 2.0
	 */
	public function add_default_tax_to( $subscription ) {
		$tax_rate = array(
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'Default',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$subscription_tax = $subscription->add_tax( $tax_rate_id, 10, 0 );
	}

	/**
	 * Force WC_Subscription::completed_payment_count() to return 10. This is to test almost every condition
	 * within WC_Subscription::can_date_be_updated();
	 *
	 * @since 2.0
	 */
	public function completed_payment_count_stub() {
		return 10;
	}

	/**
	 * Forces WC_Subscription::payment_method_supports( $feature ) to always return false. This is to
	 * help test more of the logic within WC_Subscription::can_be_updated_to().
	 *
	 * @since 2.0
	 */
	public function payment_method_supports_false() {
		return false;
	}
}

/* function wc_schedule_single_action() {
	return;
} */