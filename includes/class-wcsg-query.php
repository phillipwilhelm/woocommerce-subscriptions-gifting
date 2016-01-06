<?php
class WCSG_Query extends WC_Query {

	/**
	 * Setup hooks & filters, when the class is constructed.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'add_endpoints' ) );

		add_filter( 'the_title', array( $this, 'get_endpoint_title' ), 11, 1 );

		$this->init_query_vars();
	}

	/**
	 * Init query vars by loading options.
	 */
	public function init_query_vars() {
		WC()->query->query_vars['new-recipient-account'] = get_option( 'woocommerce_myaccount_new_recipient_account_endpoint', 'new-recipient-account' );
	}

	/**
	 * Set the recipient account details page title.
	 * @param $title
	 */
	public function get_endpoint_title( $title ) {
		global $wp;
		// Enqueue woocommerce country select scripts
		wp_enqueue_script( 'wc-country-select' );
		wp_enqueue_script( 'wc-address-i18n' );
		if ( is_main_query() && in_the_loop() && is_page() && isset( $wp->query_vars['new-recipient-account'] ) ) {
			$title = __( 'Account Details', 'woocommerce-subscriptions-gifting' );
		}

		return $title;
	}

}
new WCSG_Query();
