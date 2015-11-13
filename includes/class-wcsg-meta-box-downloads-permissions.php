<?php

class WCSG_Meta_Box_Download_Permissions {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', __CLASS__ . '::maybe_remove_download_permission_meta_box', 40 );
		add_action( 'woocommerce_process_shop_order_meta', __CLASS__ . '::save_download_permission_meta', 10, 1 );

		//this needs to trigger before WooCommerce so we can unhook their function
		add_action( 'woocommerce_ajax_revoke_access_to_product_download', __CLASS__ . '::revoke_download_access', 9, 3 );
	}

	/**
	 * Removes the default order download permissions meta box for gifted subscriptions and adds our own.
	 */
	public static function maybe_remove_download_permission_meta_box() {
		global $post;

		if ( wcs_is_subscription( $post->ID ) ) {

			$subscription = wcs_get_subscription( $post->ID );

			if ( isset( $subscription->recipient_user ) ) {
				remove_meta_box( 'woocommerce-order-downloads', 'shop_subscription', 'normal' );
				add_meta_box( 'woocommerce-order-downloads', __( 'Downloadable Product Permissions', 'woocommerce-subscriptions-gifting' ) . ' <span class="tips" data-tip="' . esc_attr__( 'Note: Permissions for subscription items will automatically be granted when the order status changes to processing/completed.', 'woocommerce-subscriptions-gifting' ) . '">[?]</span>', 'WCSG_Meta_Box_Download_Permissions::output', 'shop_subscription', 'normal', 'default' );
			}
		}
	}

	/**
	 * Unhooks WooCommerce's default save function and saves download permissions for gifted subscriptions
	 *
	 * @param int $subscription_id
	 */
	public static function save_download_permission_meta( $subscription_id ) {
		global $wpdb;

		if ( wcs_is_subscription( $subscription_id ) ) {

			$subscription = wcs_get_subscription( $subscription_id );

			if ( isset( $subscription->recipient_user ) && ! empty( $_POST['download_id'] ) && ! empty( $_POST['_wcsgnonce'] ) && wp_verify_nonce( $_POST['_wcsgnonce'], 'wcsg_save_download_permissions' ) ) {
				remove_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Downloads::save', 30 );

				$downloads           = $_POST['download_id'];
				$product_ids         = $_POST['product_id'];
				$downloads_remaining = $_POST['downloads_remaining'];
				$access_expires      = $_POST['access_expires'];

				foreach ( $downloads as $index => $download ) {

					$download_data = unserialize( stripslashes( $download ) );

					if ( is_array( $download_data ) && isset( $download_data['download_id'] ) && isset( $download_data['wcsg_user_id'] ) ) {

						$download_id = $download_data['download_id'];
						$user_id     = $download_data['wcsg_user_id'];
						$expiry      = ( ( isset( $access_expires[ $index ] ) ) && '' != $access_expires[ $index ] ) ? date_i18n( 'Y-m-d', strtotime( wc_clean( $access_expires[ $index ] ) ) ) : null;
						$data        = array(
							'downloads_remaining' => wc_clean( $downloads_remaining[ $index ] ),
							'access_expires'      => $expiry,
						);
						$format      = array( '%s', '%s' );

						//update purchaser information (billing email and user id)
						if ( $user_id == $subscription->customer_user && isset( $_POST['customer_user'] ) && isset( $_POST['user_email'] ) ) {
							$data['user_id']    = absint( $_POST['customer_user'] );
							$data['user_email'] = wc_clean( $_POST['_billing_email'] );

							array_push( $format, '%d', '%s' );
						}

						$wpdb->update( $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
							$data,
							array(
								'order_id' 		=> $subscription_id,
								'product_id' 	=> absint( $product_ids[ $index ] ),
								'download_id'	=> wc_clean( $download_id ),
								'user_id'       => $user_id,
							),
							$format, array( '%d', '%d', '%s', '%d' )
						);
					}
				}
			}
		}
	}

	/**
	 * Displays download permission meta box for gifted subscriptions
	 *
	 * @param int $subscription_id
	 */
	public static function output( $post ) {
		global $wpdb;

		$subscription = wcs_get_subscription( $post->ID );
		?>
		<div class="order_download_permissions wc-metaboxes-wrapper">

			<div class="wc-metaboxes">
				<?php
				$download_permissions = $wpdb->get_results( $wpdb->prepare( "
					SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
					WHERE order_id = %d ORDER BY user_id
				", $subscription->id ) );

				$recipient_permissions = array();
				$purchaser_permissions = array();

				foreach ( $download_permissions as $download ) {

					$product = wc_get_product( absint( $download->product_id ) );

					if ( ! $product || ! $product->exists() || ! $product->has_file( $download->download_id ) ) {
						continue;
					}

					if ( $download->user_id == $subscription->customer_user ) {
						$purchaser_permissions[] = $download;
					} else if ( $download->user_id == $subscription->recipient_user ) {
						$recipient_permissions[] = $download;
					}
				}

				$downloads    = array_merge( $purchaser_permissions, $recipient_permissions );
				$file_counter = 1;

				if ( ! empty( $purchaser_permissions ) ) {
					echo sprintf( esc_html__( '%sPurchaser\'s Download Permissions%s', 'woocommerce-subscriptions-gifting' ), '<h3><u>', '</u></h3>' );
				}

				foreach ( $downloads as $index => $download ) {

					if ( $download->user_id == $subscription->recipient_user && ( 0 == $index || $downloads[ $index - 1 ]->user_id != $subscription->recipient_user ) ) {
						echo sprintf( esc_html__( '%sRecipient\'s Download Permissions%s', 'woocommerce-subscriptions-gifting' ), '<h3><u>', '</u></h3>' );
						// reset the file counter
						$file_counter = 1;
					}

					$product    = wc_get_product( absint( $download->product_id ) );
					$file       = $product->get_file( $download->download_id );
					$loop       = $index;
					$file_count = 1;

					$download->download_id = serialize( array(
						'download_id'  => $download->download_id,
						'wcsg_user_id' => $download->user_id,
					) );

					if ( isset( $file['name'] ) ) {
						$file_count = $file['name'];
					} else {
						$file_count = sprintf( __( 'File %d', 'woocommerce-subscriptions-gifting' ), $file_counter );
					}

					include( plugin_dir_path( WC_PLUGIN_FILE ) . 'includes/admin/meta-boxes/views/html-order-download-permission.php' );
					$file_counter++;
				}
				?>
			</div>
		</div>
		<?php
		wp_nonce_field( 'wcsg_save_download_permissions', '_wcsgnonce' );
	}

	/**
	 * Revokes download permissions for gifted subscriptions
	 *
	 * @param string $download download data - download and user id.
	 * @param int $product_id the product id to revoke access to.
	 * @param int $order_id the id of the order/subscription to revoke the access to.
	 */
	public static function revoke_download_access( $download, $product_id, $order_id ) {
		global $wpdb;

		if ( wcs_is_subscription( $order_id ) ) {
			$subscription = wcs_get_subscription( $order_id );

			if ( isset( $subscription->recipient_user ) ) {

				$download_data = unserialize( stripslashes( $download ) );

				if ( is_array( $download_data ) && isset( $download_data['download_id'] ) && isset( $download_data['wcsg_user_id'] ) ) {

					$wpdb->query( $wpdb->prepare( "
					DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
					WHERE order_id = %d AND product_id = %d AND download_id = %s AND user_id = %s;",
					$order_id, $product_id, $download_data['download_id'], $download_data['wcsg_user_id'] ) );
				}
			}
		}
	}
}
WCSG_Meta_Box_Download_Permissions::init();
