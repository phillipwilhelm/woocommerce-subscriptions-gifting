<?php
/**
 *
 * @see WCG_Unit_Test_Case::setUp()
 * @since 2.0
 */
class WCSG_Test_Download_Permission_Functions extends WC_Unit_Test_Case {

	public function test_grant_recipient_download_permissions() {

		$recipient_user        = wp_create_user( 'recipient', 'password', 'email@example.com' );
		$purchaser_user        = wp_create_user( 'purchaser', 'password', 'purchaser_user@example.com' );
		$gifted_subscription   = WCS_Helper_Subscription::create_subscription( array( 'customer_id' => $purchaser_user ), array( 'recipient_user' => $recipient_user ) );
		$subscription          = WCS_Helper_Subscription::create_subscription( array( 'customer_id' => $purchaser_user ) );
		$subscription_product  = WCS_Helper_Product::create_simple_subscription_product( array( 'downloadable' => 'yes', 'virtual' => 'yes' ) );
		update_post_meta($subscription_product->id, '_downloadable_files',array('file1','file2'));//:2:{s:32:"c253421a7d8fd0bf50dd9251baa4044f";a:2:{s:4:"name";s:4:"Pear";s:4:"file";s:58:"http://localhost/trial/wp-content/uploads/2015/05/Pear.jpg";}s:32:"cc92b963101a96835e1b15d367aedfc6";a:2:{s:4:"name";s:5:"Apple";s:4:"file";s:60:"http://localhost/trial/wp-content/uploads/2015/05/apple.jpeg";}}
		//don't grant purchaser access.
		update_option( 'woocommerce_subscriptions_gifting_downloadable_products', 'no' );

		//Default Case - Gifted subscription should grant recipient download permission.
		$data                = array( 'order_id' => $gifted_subscription->id );
		$result              = WCSG_Download_Handler::grant_recipient_download_permissions( $data );
		$purchaser_downloads = self::get_download_permissions( $purchaser_user );

		$this->assertEquals( $result['user_id'], $recipient_user );
		$this->assertTrue( empty( $purchaser_downloads ) );

		//Not gifted - Not gifted subscription shouldn't change the user granted download permission
		$data                = array( 'order_id' => $subscription->id );
		$result              = WCSG_Download_Handler::grant_recipient_download_permissions( $data );
		$this->assertTrue( empty( $result['user_id'] ) );

		//test granting purchasers access.
		$updated = update_option( 'woocommerce_subscriptions_gifting_downloadable_products', 'yes' );

		$data                = array( 'order_id' => $gifted_subscription->id, 'download_id' => 1, 'product_id' => $subscription_product->id );
		$result              = WCSG_Download_Handler::grant_recipient_download_permissions( $data );
		$purchaser_downloads = self::get_download_permissions( $purchaser_user );

		//both recipient and purchaser should have permissions
		$this->assertEquals( $result['user_id'], $recipient_user );
		$this->assertTrue( ! empty( $purchaser_downloads ) );

		//Clean-up
		wp_delete_post( $gifted_subscription->id );
		wp_delete_post( $subscription->id );


		wp_delete_user( $purchaser_user );
		wp_delete_user( $recipient_user );

	}

	public static function get_download_permissions( $user_id ){

		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "
			SELECT permissions.*
			FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions as permissions
			WHERE user_id = %d
			AND permissions.order_id > 0
			AND
				(
					permissions.downloads_remaining > 0
					OR
					permissions.downloads_remaining = ''
				)
			AND
				(
					permissions.access_expires IS NULL
					OR
					permissions.access_expires >= %s
					OR
					permissions.access_expires = '0000-00-00 00:00:00'
				)
			ORDER BY permissions.order_id, permissions.product_id, permissions.permission_id;
			", $user_id, date( 'Y-m-d', current_time( 'timestamp' ) ) ) );
	}
}
