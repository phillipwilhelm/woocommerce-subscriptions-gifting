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

		add_action( 'admin_enqueue_scripts', __CLASS__ . '::enqueue_scripts', 12 );

		add_action( 'wp_ajax_wcsg_revoke_access_to_download', __CLASS__ . '::revoke_access_to_download' );
	}

	/**
	 * Delete download permissions via ajax function
	 */
	public static function revoke_access_to_download() {
		check_ajax_referer( 'revoke-access', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			die( -1 );
		}

		global $wpdb;

		$download_id = $_POST['download_id'];
		$product_id  = intval( $_POST['product_id'] );
		$order_id    = intval( $_POST['order_id'] );
		$user_id     = intval( $_POST['user_id'] );

		$wpdb->query( $wpdb->prepare("
		DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
		WHERE order_id = %d AND product_id = %d AND download_id = %s AND user_id = %d;
		",$order_id, $product_id, $download_id, $user_id ) );

		die();
	}

	/**
	 * Enqueue scripts
	 */
	public static function enqueue_scripts() {
		global $post;

		$screen = get_current_screen();

		if ( 'shop_subscription' == $screen->id ) {
			$subscription = wcs_get_subscription( $post->ID );

			if ( isset( $subscription->recipient_user ) ) {
				wp_enqueue_script( 'wcsg-admin-meta-boxes-subscription', plugin_dir_url( WCS_Gifting::$plugin_file ) . 'js/wcsg-meta-boxes-subscription.js', array( 'jquery' ), null );
			}
		}
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

				foreach ( $downloads as $user_id => $download_ids ) {
					foreach ( $download_ids as $index => $download_id ) {
						$expiry = ( ( isset( $access_expires[ $user_id ][ $index ] ) ) && '' != $access_expires[ $user_id ][ $index ] ) ? date_i18n( 'Y-m-d', strtotime( wc_clean( $access_expires[ $user_id ][ $index ] ) ) ) : null;
						$data   = array(
							'downloads_remaining' => wc_clean( $downloads_remaining[ $user_id ][ $index ] ),
							'access_expires'      => $expiry,
						);
						$format      = array( '%s', '%s' );

						if ( $user_id == $subscription->customer_user && isset( $_POST['customer_user'] ) && isset( $_POST['user_email'] ) ) {
							$data['user_id']    = absint( $_POST['customer_user'] );
							$data['user_email'] = wc_clean( $_POST['_billing_email'] );

							array_push( $format, '%d', '%s' );
						}

						$wpdb->update( $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
							$data,
							array(
								'order_id' 		=> $subscription_id,
								'product_id' 	=> absint( $product_ids[ $user_id ][ $index ] ),
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
					echo '<div id="wcsg_user_' . esc_attr( $subscription->customer_user ) .  '_downloads" class="wcsg_user_downloads_container">';
					echo sprintf( esc_html__( '%sPurchaser\'s Download Permissions%s', 'woocommerce-subscriptions-gifting' ), '<h4 id="download_user_label_' . esc_attr( $subscription->customer_user ) . '" style="padding-left:1em;font-size: 1.1em" >', '</h4>' );
				}

				foreach ( $downloads as $index => $download ) {

					if ( reset( $recipient_permissions ) === $download ) {

						echo '<div id="wcsg_user_' . esc_attr( $subscription->recipient_user ) .  '_downloads" class="wcsg_user_downloads_container">';
						echo sprintf( esc_html__( '%sRecipient\'s Download Permissions%s', 'woocommerce-subscriptions-gifting' ), '<h4 id="download_user_label_' . esc_attr( $subscription->recipient_user ) . '" style="padding-left:1em;font-size: 1.1em" >', '</h4>' );

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

					include( plugin_dir_path( WCS_Gifting::$plugin_file ) . 'templates/' . 'html-order-download-permission.php' );
					$file_counter++;

					if ( end( $recipient_permissions ) === $download || end( $purchaser_permissions ) === $download ) {
						echo '</div>';
					}
				}
				?>
			</div>
		</div>
		<?php
		wp_nonce_field( 'wcsg_save_download_permissions', '_wcsgnonce' );
	}
}
WCSG_Meta_Box_Download_Permissions::init();
