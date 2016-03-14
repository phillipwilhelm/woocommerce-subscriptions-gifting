<?php

class WCSG_Download_Handler {

	private static $subscription_download_permissions;

	/**
	* Setup hooks & filters, when the class is initialised.
	*/
	public static function init() {
		add_filter( 'woocommerce_subscription_settings', __CLASS__ . '::register_download_settings' );
		add_filter( 'woocommerce_downloadable_file_permission_data', __CLASS__ . '::grant_recipient_download_permissions', 11 );
		add_filter( 'woocommerce_get_item_downloads', __CLASS__ . '::get_item_download_links', 10, 3 );

		/* Download Permission Meta Box Functions */
		add_action( 'woocommerce_process_shop_order_meta', __CLASS__ . '::download_permissions_meta_box_save', 10, 1 );
		add_action( 'woocommerce_admin_order_data_after_order_details', __CLASS__ . '::add_download_permission_fields', 10, 1 );
		add_filter( 'woocommerce_admin_download_permissions_title', __CLASS__ . '::add_user_to_download_permission_title', 10, 3 );
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

		if ( wcs_is_subscription( $order ) && wcs_is_view_subscription_page() ) {
			$subscription = wcs_get_subscription( $wp_query->query['view-subscription'] );

			if ( isset( $subscription->recipient_user ) ) {
				$downloads = wc_get_customer_available_downloads( get_current_user_id() );

				foreach ( $downloads as $download ) {
					$product_id = wcs_get_canonical_product_id( $item );

					if ( $product_id == $download['product_id'] && $order->id == $download['order_id'] ) {
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

	/**
	 * Outputs hidden fields on the edit subscription screen, storing download permission ids and users to be used when saving download permissions.
	 * Saves the download permissions so they can be used later, when displaying user information
	 *
	 * @param WC_Subscription $subscription
	 */
	public static function add_download_permission_fields( $subscription ) {
		global $wpdb;

		if ( WCS_Gifting::is_gifted_subscription( $subscription ) ) {

			self::$subscription_download_permissions = $wpdb->get_results( $wpdb->prepare( "
				SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
				WHERE order_id = %d ORDER BY product_id
			", $subscription->id ) );

			foreach ( self::$subscription_download_permissions as $index => $download ) { ?>
				<input type="hidden" name="wcsg_download_permission_ids[<?php echo esc_attr( $index ); ?>]" value="<?php echo absint( $download->permission_id ); ?>" />
				<input type="hidden" name="wcsg_download_user_ids[<?php echo esc_attr( $index ); ?>]" value="<?php echo absint( $download->user_id ); ?>" /><?php
			}
		}
	}

	/**
	 * Formats the download permission title to also include information about the user the permission belongs to.
	 * This is to make it clear to store managers which user's permissions are being edited.
	 *
	 * @param string $download_title the download permission title displayed in order download permisssion meta boxes
	 */
	public static function add_user_to_download_permission_title( $download_title, $product_id, $order_id ) {

		$subscription = wcs_get_subscription( $order_id );

		if ( WCS_Gifting::is_gifted_subscription( $subscription ) ) {
			foreach ( self::$subscription_download_permissions as $index => $download ) {
				if ( ! isset( $download->displayed ) ) {

					$user_role = ( $download->user_id == $subscription->recipient_user ) ? __( 'Recipient', 'woocommerce-subscriptions-gifting' ) : __( 'Purchaser', 'woocommerce-subscriptions-gifting' );
					$user      = get_userdata( $download->user_id );
					$user_name = ucfirst( $user->first_name ) . ( ( ! empty( $user->last_name ) ) ? ' ' . ucfirst( $user->last_name ) : '' );

					$download_title = $user_role . ' (' . ( empty( $user_name ) ? ucfirst( $user->display_name ) : $user_name ) . ') &mdash; ' . $download_title;
					$download->displayed = true;
					break;
				}
			}
		}

		return $download_title;
	}

	/**
	 * Save download permission meta box data. Unhooks WC_Meta_Box_Order_Downloads::save() to prevent the WC save function from being called.
	 *
	 * @param int $subscription_id
	 */
	public static function download_permissions_meta_box_save( $subscription_id ) {
		global $wpdb;

		if ( isset( $_POST['wcsg_download_permission_ids'] ) && isset( $_POST['woocommerce_meta_nonce'] ) && wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {

			remove_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Downloads::save', 30 );

			$permission_ids      = $_POST['wcsg_download_permission_ids'];
			$user_ids            = $_POST['wcsg_download_user_ids'];
			$download_ids        = $_POST['download_id'];
			$product_ids         = $_POST['product_id'];
			$downloads_remaining = $_POST['downloads_remaining'];
			$access_expires      = $_POST['access_expires'];

			$subscription = wcs_get_subscription( $subscription_id );

			foreach ( $download_ids as $index => $download_id ) {

				$expiry = ( array_key_exists( $index, $access_expires ) && '' != $access_expires[ $index ] ) ? date_i18n( 'Y-m-d', strtotime( $access_expires[ $index ] ) ) : null;

				$data = array(
					'downloads_remaining' => wc_clean( $downloads_remaining[ $index ] ),
					'access_expires'      => $expiry,
				);

				$format = array( '%s', '%s' );

				// if we're updating the purchaser's permissions, update the download user id and email, in case it has changed
				if ( $user_ids[ $index ] != $subscription->recipient_user ) {
					$data['user_id'] = absint( $_POST['customer_user'] );
					$format[] = '%d';

					$data['user_email'] = wc_clean( $_POST['_billing_email'] );
					$format[] = '%s';
				}

				$wpdb->update( $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
					$data,
					array(
						'order_id'    => $subscription_id,
						'product_id'  => absint( $product_ids[ $index ] ),
						'download_id' => wc_clean( $download_ids[ $index ] ),
						'permission_id'  => $permission_ids[ $index ],
						),
					$format, array( '%d', '%d', '%s', '%d' )
				);
			}
		}
	}

}
WCSG_Download_Handler::init();
