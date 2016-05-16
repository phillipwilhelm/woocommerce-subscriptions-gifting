<?php
class WCSG_Query extends WC_Query {

	/**
	 * Setup hooks & filters, when the class is constructed.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'add_endpoints' ) );

		add_filter( 'the_title', array( $this, 'get_endpoint_title' ), 11, 1 );

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 11 );

		$this->init_query_vars();
	}

	/**
	 * Init query vars by loading options.
	 */
	public function init_query_vars() {
		$this->query_vars = array(
			'new-recipient-account' => get_option( 'woocommerce_myaccount_view_subscriptions_endpoint', 'new-recipient-account' ),
		);
	}

	/**
	 * Set the recipient account details page title.
	 * @param $title
	 */
	public function get_endpoint_title( $title ) {
		global $wp;

		if ( is_main_query() && in_the_loop() && is_page() && isset( $wp->query_vars['new-recipient-account'] ) ) {
			$title = __( 'Account Details', 'woocommerce-subscriptions-gifting' );

			// Enqueue WooCommerce country select scripts
			wp_enqueue_script( 'wc-country-select' );
			wp_enqueue_script( 'wc-address-i18n' );
		}

		return $title;
	}

	/**
	 * Fix for endpoints on the homepage
	 *
	 * Based on WC_Query->pre_get_posts(), but only applies the fix for endpoints on the homepage from it
	 * instead of duplicating all the code to handle the main product query.
	 *
	 * @param mixed $q query object
	 */
	public function pre_get_posts( $q ) {
		// We only want to affect the main query
		if ( ! $q->is_main_query() ) {
			return;
		}
		if ( $q->is_home() && 'page' === get_option( 'show_on_front' ) && absint( get_option( 'page_on_front' ) ) !== absint( $q->get( 'page_id' ) ) ) {
			$_query = wp_parse_args( $q->query );
			if ( ! empty( $_query ) && array_intersect( array_keys( $_query ), array_keys( $this->query_vars ) ) ) {
				$q->is_page     = true;
				$q->is_home     = false;
				$q->is_singular = true;
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				add_filter( 'redirect_canonical', '__return_false' );
			}
		}
	}

}
new WCSG_Query();
