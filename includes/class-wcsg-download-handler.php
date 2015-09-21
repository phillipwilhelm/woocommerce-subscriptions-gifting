<?php

class WCSG_Download_Handler {

	/**
	* Setup hooks & filters, when the class is initialised.
	*/
	public static function init() {
		add_filter( 'woocommerce_subscription_settings', __CLASS__ . '::register_download_settings' );
		add_filter( 'woocommerce_downloadable_file_permission_data', __CLASS__ . '::grant_recipient_download_permissions', 11 );
		add_filter( 'woocommerce_get_item_downloads', __CLASS__ . '::get_item_download_links', 10, 3 );
	}

	/**
	 * Gets the current user's download links for a downloadable order item.
	 *
	 * @param array $files Downloadable files for the order item
	 * @param array $item Order line item.
	 * @param object $order
	 * @return array $files
	 */
	public static function get_item_download_links( $files, $item, $order ) {
		global $wp_query;

		if ( wcs_is_subscription( $order ) && is_account_page() && isset( $wp_query->query['view-subscription'] ) ) {
			$subscription = wcs_get_subscription( $wp_query->query['view-subscription'] );

			if ( isset( $subscription->recipient_user ) ) {
				$downloads = wc_get_customer_available_downloads( get_current_user_id() );

				foreach ( $downloads as $download ) {
					$product_id = wcs_get_canonical_product_id( $item );

					if ( $product_id == $download['product_id'] && $order->id = $download['order_id'] ) {
						$files[ $download['download_id'] ] = array(
							'name'         => $download['file']['name'],
							'file'         => $download['file']['file'],
							'download_url' => $download['download_url'],
						);
					}
				}
			}
		}
		return $files;
	}

	/**
	 * Grants download permissions to the recipient rather than the purchaser by default. However if the
	 * purchaser can download setting is selected, permissions are granted to both recipient and purchaser.
	 *
	 * @param array $data download permission data inserted into the wp_woocommerce_downloadable_product_permissions table.
	 * @return array $data
	 */
	public static function grant_recipient_download_permissions( $data ) {

		$subscription = wcs_get_subscription( $data['order_id'] );

		if ( wcs_is_subscription( $subscription ) && isset( $subscription->recipient_user ) ) {

			$can_purchaser_download = ( 'yes' == get_option( 'woocommerce_subscriptions_gifting_downloadable_products', 'no' ) ) ? true : false;

			if ( $can_purchaser_download ) {
				remove_filter( 'woocommerce_downloadable_file_permission_data', __CLASS__ . '::grant_recipient_download_permissions', 11 );

				wc_downloadable_file_permission( $data['download_id'], $data['product_id'] , $subscription );

				add_filter( 'woocommerce_downloadable_file_permission_data', __CLASS__ . '::grant_recipient_download_permissions', 11 );
			}

			$recipient_id       = $subscription->recipient_user;
			$recipient          = get_user_by( 'id', $recipient_id );
			$data['user_id']    = $recipient_id;
			$data['user_email'] = $recipient->user_email;
		}
		return $data;
	}

	/**
	 * Adds additional gifting specific settings into Subscriptions settings
	 *
	 * @param array $settings Subscription's current set of settings.
	 * @return array $settings new settings with appended wcsg specific settings.
	 */
	public static function register_download_settings( $settings ) {
		$download_settings = array(
		array(
			'name'     => __( 'Gifting Subscriptions', 'woocommerce-subscriptions-gifting' ),
			'type'     => 'title',
			'id'       => 'woocommerce_subscriptions_gifting',
		),
		array(
			'name'     => __( 'Downloadable Products', 'woocommerce-subscriptions-gifting' ),
			'desc'     => __( 'Allow both purchaser and recipient to download subscription products.', 'woocommerce-subscriptions-gifting' ),
			'id'       => 'woocommerce_subscriptions_gifting_downloadable_products',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => __( 'If you want both the recipient and purchaser of a subscription to have access to downloadable products.', 'woocommerce-subscriptions-gifting' ),
		),
		array( 'type' => 'sectionend', 'id' => 'woocommerce_subscriptions_gifting' ),
		);

		return array_merge( $settings, $download_settings );
	}
}
WCSG_Download_Handler::init();
