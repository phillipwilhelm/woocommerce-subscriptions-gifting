<?php

class WCSG_Download_Handler {

	private static $subscription_download_permissions = array();

	/**
	* Setup hooks & filters, when the class is initialised.
	*/
	public static function init() {
		add_filter( 'woocommerce_subscription_settings', __CLASS__ . '::register_download_settings' );
		add_filter( 'woocommerce_downloadable_file_permission_data', __CLASS__ . '::grant_recipient_download_permissions', 11 );
		add_filter( 'woocommerce_get_item_downloads', __CLASS__ . '::get_item_download_links', 15, 3 );

		/* Download Permission Meta Box Functions */
		add_action( 'woocommerce_process_shop_order_meta', __CLASS__ . '::download_permissions_meta_box_save', 10, 1 );
		add_action( 'woocommerce_admin_order_data_after_order_details', __CLASS__ . '::get_download_permissions_before_meta_box', 10, 1 );
		add_filter( 'woocommerce_admin_download_permissions_title', __CLASS__ . '::add_user_to_download_permission_title', 10, 3 );

		// Granting access via download meta box - hooked on prior to WC_AJAX::grant_access_to_download()
		add_action( 'wp_ajax_woocommerce_grant_access_to_download', __CLASS__ . '::grant_access_to_download_via_meta_box', 9 );

		// Revoke access via download meta box - hooked to a custom Ajax handler in place of WC_AJAX::revoke_access_to_download()
		add_action( 'wp_ajax_wcsg_revoke_access_to_download', __CLASS__ . '::ajax_revoke_download_permission' );
	}

	/**
	 * Gets the correct user's download links for a downloadable order item.
	 * If the request is from within an email, the links belonging to the email recipient are returned otherwise
	 * if the request is from the view subscription page use the current user id,
	 * otherwise the links for order's customer user are returned.
	 *
	 * @param array $files Downloadable files for the order item
	 * @param array $item Order line item.
	 * @param object $order
	 * @return array $files
	 */
	public static function get_item_download_links( $files, $item, $order ) {

		if ( ! empty( $order->recipient_user ) ) {
			$subscription_recipient = get_user_by( 'id', $order->recipient_user );
			$user_id                = ( wcs_is_subscription( $order ) && wcs_is_view_subscription_page() ) ? get_current_user_id() : $order->customer_user;
			$mailer                 = WC()->mailer();

			foreach ( $mailer->emails as $email ) {
				if ( isset( $email->wcsg_sending_recipient_email ) ) {
					$user_id = $order->recipient_user;
					break;
				}
			}

			$files = self::get_user_downloads_for_order_item( $order, $user_id, $item );
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
	 * Before displaying the meta box, save the download permissions so they can be used later when displaying
	 * user information and outputting download permission hidden fields.
	 *
	 * @param WC_Subscription $subscription
	 */
	public static function get_download_permissions_before_meta_box( $subscription ) {
		global $wpdb;

		if ( WCS_Gifting::is_gifted_subscription( $subscription ) ) {

			self::$subscription_download_permissions = self::get_subscription_download_permissions( $subscription->id );
		}
	}

	/**
	 * Formats the download permission title to also include information about the user the permission belongs to.
	 * This is to make it clear to store managers which user's permissions are being edited.
	 *
	 * @param string $download_title the download permission title displayed in order download permission meta boxes
	 */
	public static function add_user_to_download_permission_title( $download_title, $product_id, $order_id ) {

		$subscription = wcs_get_subscription( $order_id );

		if ( WCS_Gifting::is_gifted_subscription( $subscription ) ) {

			foreach ( self::$subscription_download_permissions as $index => $download ) {
				if ( ! isset( $download->displayed ) ) { ?>
					<input type="hidden" class="wcsg_download_permission_id" name="wcsg_download_permission_ids[<?php echo esc_attr( $index ); ?>]" value="<?php echo absint( $download->permission_id ); ?>" />
					<input type="hidden" class="wcsg_download_permission_id" name="wcsg_download_user_ids[<?php echo esc_attr( $index ); ?>]" value="<?php echo absint( $download->user_id ); ?>" /><?php

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

	/**
	 * Get all download permissions for a subscription
	 *
	 * @param int $subscription_id
	 */
	public static function get_subscription_download_permissions( $subscription_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE order_id = %d ORDER BY product_id", $subscription_id ) );
	}

	/**
	 * Grants download permissions from the edit subscription meta box grant access button.
	 * Outputs meta box table rows for each permission granted.
	 */
	public static function grant_access_to_download_via_meta_box() {

		check_ajax_referer( 'grant-access', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			die( -1 );
		}

		global $wpdb;

		$wpdb->hide_errors();

		$order_id     = intval( $_POST['order_id'] );
		$product_ids  = $_POST['product_ids'];
		$loop         = intval( $_POST['loop'] );
		$file_counter = 0;

		if ( WCS_Gifting::is_gifted_subscription( $order_id ) ) {

			$subscription         = wcs_get_subscription( $order_id );
			$download_permissions = self::get_subscription_download_permissions( $order_id );
			$file_names           = array();

			if ( ! $subscription->billing_email ) {
				die();
			}

			if ( ! is_array( $product_ids ) ) {
				$product_ids = array( $product_ids );
			}

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				$files   = $product->get_files();

				if ( $files ) {
					foreach ( $files as $download_id => $file ) {

						$file_counter ++;

						if ( isset( $file['name'] ) ) {
							$file_names[ $download_id ] = $file['name'];
						} else {
							$file_names[ $download_id ] = sprintf( __( 'File %d', 'woocommerce-subscriptions-gifting' ), $file_counter );
						}

						wc_downloadable_file_permission( $download_id, $product_id, $subscription );
					}
				}
			}

			if ( 0 < count( $file_names ) ) {
				$updated_download_permissions = self::get_subscription_download_permissions( $order_id );
				$new_download_permissions     = array_diff( array_keys( $updated_download_permissions ), array_keys( $download_permissions ) );

				foreach ( $new_download_permissions as $new_download_permission_index ) {

					$loop ++;

					$download   = $updated_download_permissions[ $new_download_permission_index ];
					$file_count = $file_names[ $download->download_id ];

					self::$subscription_download_permissions[ $loop ] = $download;

					include( plugin_dir_path( WC_PLUGIN_FILE ) . 'includes/admin/meta-boxes/views/html-order-download-permission.php' );
				}
			}

			die();
		}
	}

	/**
	 * WooCommerce revokes download permissions based only on the product an order ID, that means when
	 * revoking downloads on a gift subscription with permissions for both the purchaser and recipient,
	 * it will revoke both sets of permissions instead of only the permission against which the store
	 * manager clicked the "Revoke Access" button.
	 *
	 * To workaround this, we add the permission ID as a hidden fields against each download permission
	 * with @see self::add_user_to_download_permission_title(). We then trigger a custom Ajax request
	 * that passes the permission ID to the server to make sure we only revoke only that permission.
	 *
	 * We also need to remove WC's handler, which is the WC_Ajax:;revoke_access_to_download() method attached
	 * to the 'woocommerce_revoke_access_to_download' Ajax action. To do this, we have out wcsg-admin.js file
	 * enqueued after WooCommerce's 'wc-admin-order-meta-boxes' script and then in our JavaScript call
	 * $( '.order_download_permissions' ).off() to remove WooCommerce's Ajax method.
	 *
	 * @since 1.0
	 */
	public static function ajax_revoke_download_permission() {
		global $wpdb;

		check_admin_referer( 'revoke_download_permission', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			die( -1 );
		}

		$subscription_id = intval( $_POST['post_id'] );

		if ( WCS_Gifting::is_gifted_subscription( $subscription_id ) ) {

			$permission_id = intval( $_POST['download_permission_id'] );

			if ( ! empty( $permission_id ) ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE order_id = %d AND permission_id = %d", $subscription_id, $permission_id ) );
			}
		}

		die();
	}

	/**
	 * Retrieves a user's download permissions for an order.
	 *
	 * @param  WC_Order $order
	 * @param  int $user_id
	 * @param  array $item
	 *
	 * @return array
	 */
	public static function get_user_downloads_for_order_item( $order, $user_id, $item ) {
		global $wpdb;

		$product_id = wcs_get_canonical_product_id( $item );

		$downloads = $wpdb->get_results( $wpdb->prepare("
			SELECT *
			FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE user_id = %d
			AND order_id = %d
			AND product_id = %d
		", $user_id, $order->id, $product_id ) );

		$files   = array();
		$product = wc_get_product( $product_id );

		foreach ( $downloads as $download ) {

			if ( $product->has_file( $download->download_id ) ) {
				$files[ $download->download_id ]                 = $product->get_file( $download->download_id );
				$files[ $download->download_id ]['download_url'] = add_query_arg(
					array(
						'download_file' => $product_id,
						'order'         => $download->order_key,
						'email'         => $download->user_email,
						'key'           => $download->download_id,
					),
					home_url( '/' )
				);
			}
		}
		return $files;
	}

	/**
	 * Retrieves all the user's download permissions for an order by checking
	 * for downloads stored on the subscriptions in the order.
	 *
	 * @param  WC_Order $order
	 * @param  int $user_id
	 *
	 * @return array
	 */
	public static function get_user_downloads_for_order( $order, $user_id ) {

		$subscriptions   = wcs_get_subscriptions_for_order( $order, array( 'order_type' => array( 'any' ) ) );
		$order_downloads = array();

		foreach ( $subscriptions as $subscription ) {
			foreach ( $subscription->get_items() as $subscription_item ) {
				$order_downloads = array_merge( $order_downloads, self::get_user_downloads_for_order_item( $subscription, $user_id, $subscription_item ) );
			}
		}

		return $order_downloads;
	}
}
WCSG_Download_Handler::init();
