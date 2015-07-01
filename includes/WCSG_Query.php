<?php
class WCSG_Query extends WC_Query {

	public function __construct() {

		add_action( 'init', array( $this, 'add_endpoints' ) );

		add_filter( 'the_title', array( $this, 'get_endpoint_title' ), 11, 1 );

		$this->init_query_vars();
	}

	public function init_query_vars() {
		$this->query_vars['new-recipient-account'] = get_option( 'woocommerce_myaccount_new_recipient_account_endpoint', 'new-recipient-account' );
	}

	public function get_endpoint_title( $title ) {
		global $wp;
		if ( is_main_query() && in_the_loop() && is_page() && isset( $wp->query_vars['new-recipient-account'] ) ) {
			$title = 'Account Details';
		}

		return $title;
	}

}
new WCSG_Query();
