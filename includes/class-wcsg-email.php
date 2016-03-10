<?php

class WCSG_Email {

	public static $downloadable_email_data = array(
		'customer_completed_order' => array(
			'trigger_action' => 'woocommerce_order_status_completed_notification',
			'heading_filter' => 'woocommerce_email_heading_customer_completed_order',
			'subject_hook'   => 'woocommerce_email_subject_customer_completed_order',
		),
		'customer_completed_renewal_order' => array(
			'trigger_action' => 'woocommerce_order_status_completed_renewal_notification',
			'heading_filter' => '', // shares woocommerce_email_heading_customer_completed_order
			'subject_hook'   => 'woocommerce_subscriptions_email_subject_customer_completed_renewal_order',
		),
		'customer_completed_switch_order' => array(
			'trigger_action' => 'woocommerce_order_status_completed_switch_notification',
			'heading_filter' => 'woocommerce_email_heading_customer_switch_order',
			'subject_hook'   => 'woocommerce_subscriptions_email_subject_customer_completed_switch_order',
		),
		'recipient_completed_renewal_order' => array(
			'trigger_action' => 'woocommerce_order_status_completed_renewal_notification_recipient',
			'heading_filter' => '', // shares woocommerce_email_heading_customer_completed_order
			'subject_hook'   => '', // shares woocommerce_subscriptions_email_subject_customer_completed_renewal_order
		),
	);

	public static $sending_downloadable_email;

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_filter( 'woocommerce_email_classes', __CLASS__ . '::add_new_recipient_customer_email', 11, 1 );

		add_action( 'woocommerce_init', __CLASS__ . '::hook_email' );
	}

	/**
	 * Add WCS Gifting email classes.
	 */
	public static function add_new_recipient_customer_email( $email_classes ) {

		require_once( 'emails/class-wcsg-email-customer-new-account.php' );
		require_once( 'emails/class-wcsg-email-completed-renewal-order.php' );
		require_once( 'emails/class-wcsg-email-processing-renewal-order.php' );
		require_once( 'emails/class-wcsg-email-recipient-new-initial-order.php' );

		$email_classes['WCSG_Email_Customer_New_Account']        = new WCSG_Email_Customer_New_Account();
		$email_classes['WCSG_Email_Completed_Renewal_Order']     = new WCSG_Email_Completed_Renewal_Order();
		$email_classes['WCSG_Email_Processing_Renewal_Order']    = new WCSG_Email_Processing_Renewal_Order();
		$email_classes['WCSG_Email_Recipient_New_Initial_Order'] = new WCSG_Email_Recipient_New_Initial_Order();

		return $email_classes;
	}

	/**
	 * Hooks up all of WCS Gifting emails after the WooCommerce object is constructed.
	 */
	public static function hook_email() {

		add_action( 'woocommerce_created_customer', __CLASS__ . '::maybe_remove_wc_new_customer_email', 9, 2 );
		add_action( 'woocommerce_created_customer', __CLASS__ . '::send_new_recient_user_email', 10, 3 );
		add_action( 'woocommerce_created_customer', __CLASS__ . '::maybe_reattach_wc_new_customer_email', 11, 2 );

		add_action( 'woocommerce_order_status_pending_to_processing', __CLASS__ . '::maybe_send_recipient_order_emails' );
		add_action( 'woocommerce_order_status_pending_to_completed', __CLASS__ . '::maybe_send_recipient_order_emails' );
		add_action( 'woocommerce_order_status_pending_to_on-hold', __CLASS__ . '::maybe_send_recipient_order_emails' );
		add_action( 'woocommerce_order_status_failed_to_processing', __CLASS__ . '::maybe_send_recipient_order_emails' );
		add_action( 'woocommerce_order_status_failed_to_completed', __CLASS__ . '::maybe_send_recipient_order_emails' );
		add_action( 'woocommerce_order_status_failed_to_on-hold', __CLASS__ . '::maybe_send_recipient_order_emails' );

		$renewal_notification_actions = array(
			'woocommerce_order_status_pending_to_processing_renewal_notification',
			'woocommerce_order_status_pending_to_on-hold_renewal_notification',
			'woocommerce_order_status_completed_renewal_notification',
		);

		foreach ( $renewal_notification_actions as $action ) {
			add_action( $action, __CLASS__ . '::maybe_send_recipient_renewal_notification', 12, 1 );
		}

		foreach ( self::$downloadable_email_data as $email_id => $hook_data ) {

			// hook on just before default to store a flag of the email being sent.
			add_action( $hook_data['trigger_action'], __CLASS__ . '::set_sending_downloadable_email_flag', 9 );
			add_action( $hook_data['trigger_action'], __CLASS__ . '::remove_sending_downloadable_email_flag', 11 );

			// hook the subject and heading hooks
			if ( ! empty( $hook_data['heading_filter'] ) ) {
				add_filter( $hook_data['heading_filter'], __CLASS__ . '::maybe_change_download_email_heading', 10, 2 );
			}

			if ( ! empty( $hook_data['subject_hook'] ) ) {
				add_filter( $hook_data['subject_hook'], __CLASS__ . '::maybe_change_download_email_heading', 10, 2 );
			}
		}

		// hook onto emails sent via order actions
		add_action( 'woocommerce_before_resend_order_emails', __CLASS__ . '::set_sending_downloadable_email_flag', 9 );
		add_action( 'woocommerce_after_resend_order_email', __CLASS__ . '::remove_sending_downloadable_email_flag', 11 );
	}

	/**
	 * If an order contains subscriptions with recipient data send an email to the recipient
	 * notifying them on their new subscription(s)
	 *
	 * @param int $order_id
	 */
	public static function maybe_send_recipient_order_emails( $order_id ) {
		$subscriptions = wcs_get_subscriptions( array( 'order_id' => $order_id ) );
		$processed_recipients = array();
		if ( ! empty( $subscriptions ) ) {
			WC()->mailer();
			foreach ( $subscriptions as $subscription ) {
				if ( WCS_Gifting::is_gifted_subscription( $subscription ) ) {
					if ( ! in_array( $subscription->recipient_user, $processed_recipients ) ) {
						$recipient_subscriptions = WCSG_Recipient_Management::get_recipient_subscriptions( $subscription->recipient_user, $order_id );
						do_action( 'wcsg_new_order_recipient_notification', $subscription->recipient_user, $recipient_subscriptions );
						array_push( $processed_recipients, $subscription->recipient_user );
					}
				}
			}
		}
	}

	/**
	 * If a cart item contains recipient data matching the new customer, dont send the core WooCommerce new customer email.
	 *
	 * @param int $customer_id The ID of the new customer being created
	 * @param array $new_customer_data
	 */
	public static function maybe_remove_wc_new_customer_email( $customer_id, $new_customer_data ) {

		foreach ( WC()->cart->cart_contents as $key => $item ) {
			if ( ! empty( $item['wcsg_gift_recipients_email'] ) ) {
				if ( $item['wcsg_gift_recipients_email'] == $new_customer_data['user_email'] ) {
					remove_action( current_filter(), array( 'WC_Emails', 'send_transactional_email' ) );
					break;
				}
			}
		}
	}

	/**
	 * If a cart item contains recipient data matching the new customer, reattach the core WooCommerce new customer email.
	 *
	 * @param int $customer_id The ID of the new customer being created
	 * @param array $new_customer_data
	 */
	public static function maybe_reattach_wc_new_customer_email( $customer_id, $new_customer_data ) {

		foreach ( WC()->cart->cart_contents as $key => $item ) {
			if ( ! empty( $item['wcsg_gift_recipients_email'] ) ) {
				if ( $item['wcsg_gift_recipients_email'] == $new_customer_data['user_email'] ) {
					add_action( current_filter(), array( 'WC_Emails', 'send_transactional_email' ) );
					break;
				}
			}
		}
	}

	/**
	 * If a cart item contains recipient data matching the new customer, init the mailer and call the notification for new recipient customers.
	 *
	 * @param int $customer_id The ID of the new customer being created
	 * @param array $new_customer_data
	 * @param bool $password_generated Whether the password has been generated for the customer
	 */
	public static function send_new_recient_user_email( $customer_id, $new_customer_data, $password_generated ) {
		foreach ( WC()->cart->cart_contents as $key => $item ) {
			if ( isset( $item['wcsg_gift_recipients_email'] ) ) {
				if ( $item['wcsg_gift_recipients_email'] == $new_customer_data['user_email'] ) {
					WC()->mailer();
					$user_password = $new_customer_data['user_pass'];
					$current_user = wp_get_current_user();
					$subscription_purchaser = WCS_Gifting::get_user_display_name( $current_user->ID );
					do_action( 'wcsg_created_customer_notification', $customer_id, $user_password, $subscription_purchaser );
					break;
				}
			}
		}
	}

	/**
	 * If the order contains a subscription that is being gifted, init the mailer and call the notification for recipient renewal notices.
	 *
	 * @param int $order_id The ID of the renewal order with a new status of processing/completed
	 */
	public static function maybe_send_recipient_renewal_notification( $order_id ) {

		$subscriptions = wcs_get_subscriptions_for_renewal_order( $order_id );

		if ( ! empty( $subscriptions ) && is_array( $subscriptions ) ) {
			$subscription = reset( $subscriptions );

			if ( WCS_Gifting::is_gifted_subscription( $subscription ) ) {
				WC()->mailer();
				do_action( current_filter() . '_recipient', $order_id );
			}
		}
	}

	/**
	 * Formats an email's heading and subject so that the correct one is displayed.
	 * If for instance the email recipient doesn't have downloads for this order fallback
	 * to the normal heading and subject,
	 *
	 * @param string $heading The email heading or subject.
	 * @param object $order
	 * @return string $heading
	 */
	public static function maybe_change_download_email_heading( $heading, $order ) {

		if ( empty( self::$sending_downloadable_email ) ) {
			return $heading;
		}

		$user_id = $order->customer_user;
		$mailer  = WC()->mailer();
		$sending_email;

		foreach ( $mailer->emails as $email ) {
			if ( self::$sending_downloadable_email == $email->id ) {
				$sending_email = $email;

				if ( isset( $email->wcsg_sending_recipient_email ) ) {
					$user_id = $email->wcsg_sending_recipient_email;
				}

				break;
			}
		}

		$order_downloads = WCSG_Download_Handler::get_user_downloads_for_order( $order, $user_id );

		$string_to_format = strpos( current_filter(),'email_heading' ) ? 'heading' : 'subject';

		if ( isset( $sending_email ) && empty( $order_downloads ) && isset( $sending_email->{$string_to_format} ) ) {
			$heading = $sending_email->format_string( $sending_email->{$string_to_format} );
		}

		return $heading;
	}

	/**
	 * Set a flag to indicate that an email with downloadable headings and subjects is being sent.
	 * hooked just before the email's trigger function.
	 */
	public static function set_sending_downloadable_email_flag() {

		$current_filter = current_filter();

		if ( 'woocommerce_before_resend_order_emails' == $current_filter && ! empty( $_POST['woocommerce_meta_nonce'] ) && wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) && ! empty( $_POST['wc_order_action'] ) ) {
			$action = wc_clean( $_POST['wc_order_action'] );
			self::$sending_downloadable_email = str_replace( 'send_email_', '', $action );
		} else {
			foreach ( self::$downloadable_email_data as $email_id => $hook_data ) {
				if ( $current_filter == $hook_data['trigger_action'] ) {
					self::$sending_downloadable_email = $email_id;
				}
			}
		}
	}

	/**
	 * Removes the downloadable email being sent flag. Hooked just after the email's trigger function.
	 */
	public static function remove_sending_downloadable_email_flag() {
		self::$sending_downloadable_email = '';
	}
}
WCSG_Email::init();
