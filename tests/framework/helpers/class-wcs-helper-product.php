<?php

/**
 * Class WCS_Helper_Product
 *
 * This helper class should ONLY be used for unit tests!
 */
class WCS_Helper_Product {

	/**
	 * Create a simple subscription product
	 *
	 * @param array $meta_filter
	 * @param array $post_meta
	 * @since 2.0
	 */ 
	public static function create_simple_subscription_product( $meta_filters = array(), $post_filters = array() ) {
		$default_meta_args = array (
			'stock_status'                   => 'instock',
			'downloadable'                   => 'no',
			'virtual'                        => 'no',
			'sold_individually'              => 'no',
			'back_orders'                    => 'no',
			'subscription_payment_sync_date' => 0,
			'subscription_price'             => 10,
			'subscription_period'            => 'month',
			'subscription_period_interval'   => 1,
			'subscription_trial_period'      => 'day',
			'subscription_length'            => 0,
			'subscription_trial_length'      => 0,
			'subscription_limit'             => 'no',
		);
		$meta_data = wp_parse_args( $meta_filters, $default_meta_args );

		$default_post_args = array(
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'post_title'     => 'Monthly WooNinja Goodies',
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
}