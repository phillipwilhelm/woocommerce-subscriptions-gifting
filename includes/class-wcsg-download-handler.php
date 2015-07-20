<?php

class WCSG_Download_handler {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {
		add_filter( 'woocommerce_subscription_settings', __CLASS__ . '::register_gifting_settings' );
		add_filter( 'woocommerce_order_is_download_permitted', __CLASS__ . '::is_download_permitted', 10, 2 );
	}

	/**
	 * Adds additional gifting specific settings into Subscriptions settings
	 *
	 * @param array $settings Subscription's current set of settings.
	 * @return array $settings new settings with appended wcsg specific settings.
	 */
	public static function register_gifting_settings( $settings ) {
		$download_settings = array(
			array(
				'name'     => __( 'Gifting Subscriptions', 'woocommerce-subscriptions' ),
				'type'     => 'title',
				'id'       => 'woocommerce_subscriptions_gifting'
			),
			array(
				'name'     => __( 'Downloadable Products', 'woocommerce-subscriptions-gifting' ),
				'desc'     => __( 'Allow both purchaser and recipient to download subscription products.', 'woocommerce-subscriptions-gifting' ),
				'id'       => 'woocommerce_subscriptions_gifting_downloadable_products',
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc_tip' => __( 'If you want both the recipient and purchaser of a subscription to have access to downloadable products.', 'woocommerce-subscriptions-gifting' ),
			),
			array( 'type'  => 'sectionend', 'id' => 'woocommerce_subscriptions_gifting' )
			);

		return array_merge( $settings, $download_settings );
	}

	/**
	 * Determines if the current user is permitted to download a file for a specific order/subscription.
	 * If an order (parent order) is provided all subscriptions in the order will be used to determine if the current user is permitted.
	 *
	 * @param bool $permitted Predetermination of whether downloading is permitted
	 * @param mixed $order A WC_Order object which contains downloadable products.
	 * @return bool $permitted new determination of whether downloading is permitted for the current user.
	 */
	public static function is_download_permitted( $permitted, $order ) {
		$can_purchaser_download = ( 'yes' == get_option( 'woocommerce_subscriptions_gifting_downloadable_products', 'no' ) ) ? true : false;
		$subscriptions = wcs_get_subscriptions_for_order( $order );

		if ( $permitted && wcs_is_subscription( $order ) ) {
			$recipient_id = get_post_meta( $order->id, '_recipient_user', true );
			$is_gift      = ! empty( $recipient_id );

			if ( $is_gift ) {
				return ( $recipient_id == get_current_user_id() ) ? true : $can_purchaser_download;
			} else {
				return true;
			}

		} else if ( $permitted && ( 0 != count( $subscriptions ) ) ) {
			foreach ( $subscriptions as $subscription ) {
				$permitted = $permitted && self::is_download_permitted( $permitted, $subscription );
			}
		}
		return $permitted;
	}
}
WCSG_Download_handler::init();
