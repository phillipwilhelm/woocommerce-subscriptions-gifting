<?php
class WCSG_Query extends WCS_Query {

	/**
	 * Setup hooks & filters, when the class is constructed.
	 */
	public function __construct() {

		parent::__construct();

		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
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
	 * Enqueue frontend scripts
	 */
	public function enqueue_scripts() {
		global $wp;

		if ( $this->is_query( 'new-recipient-account' ) ) {
			// Enqueue WooCommerce country select scripts
			wp_enqueue_script( 'wc-country-select' );
			wp_enqueue_script( 'wc-address-i18n' );
		}
	}

	/**
	 * Set the endpoint title when viewing the new recipient account page
	 *
	 * @param $endpoint
	 */
	public function get_endpoint_title( $endpoint ) {
		global $wp;

		switch ( $endpoint ) {
			case 'new-recipient-account':
				$title = __( 'Account Details', 'woocommerce-subscriptions-gifting' );
				break;
			default:
				$title = '';
				break;
		}

		return $title;
	}

	/* Function Overrides */

	public function add_menu_items( $menu_items ) {
		return $menu_items;
	}

	public function endpoint_content() {}
}
new WCSG_Query();
