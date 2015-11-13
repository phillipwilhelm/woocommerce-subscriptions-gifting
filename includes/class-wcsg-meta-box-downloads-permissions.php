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

		if ( wcs_is_subscription( $subscription_id ) ) {

			$subscription = wcs_get_subscription( $subscription_id );

			if ( isset( $subscription->recipient_user ) ) {
				remove_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Downloads::save', 30 );
				//do my own saving
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

					$download->download_id = serialize( array(
						'download_id'  => $download->download_id,
						'wcsg_user_id' => $download->user_id,
					) );

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

			<div class="toolbar">
				<p class="buttons">
					<input type="hidden" id="grant_access_id" name="grant_access_id" data-multiple="true" class="wc-product-search" style="width: 400px;" data-placeholder="<?php esc_attr_e( 'Search for a downloadable product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_downloadable_products_and_variations" />
					<select id="wcsg_customer" style="margin-right: 9px; vertical-align: top;">
						<option value=""><?php esc_html_e( 'Customer', 'woocommerce-subscriptions-gifting' ); ?></option>
						<option value="<?php echo esc_attr( $subscription->recipient_user ) ?>"><?php esc_html_e( 'Recipient' ); ?></option>
						<option value="<?php echo esc_attr( $subscription->customer_user ) ?>"><?php esc_html_e( 'Purchaser' ); ?></option>
					</select>
					<button type="button" class="button grant_access"><?php esc_html_e( 'Grant Access', 'woocommerce-subscriptions-gifting' ); ?></button>
				</p>
				<div class="clear"></div>
			</div>
		</div>
		<?php
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
