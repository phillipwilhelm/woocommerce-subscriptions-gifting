<?php

class WCSG_Email_Processing_Renewal_Order extends WCS_Email_Processing_Renewal_Order {

	public $wcsg_sending_recipient_email;

	/**
	 * Create an instance of the class.
	 */
	function __construct() {

		$this->id             = 'gift_recipient_processing_renewal_order';
		$this->title          = __( 'Processing Renewal Order - Recipient', 'woocommerce-subscriptions-gifting' );
		$this->description    = __( 'This is an order notification sent to the recipient after payment for a subscription renewal order is completed. It contains the renewal order details.', 'woocommerce-subscriptions-gifting' );
		$this->customer_email = true;

		$this->heading        = __( 'Thank you for your order', 'woocommerce-subscriptions-gifting' );
		$this->subject        = __( 'Your {blogname} renewal order receipt from {order_date}', 'woocommerce-subscriptions-gifting' );

		$this->template_html  = 'emails/customer-processing-renewal-order.php';
		$this->template_plain = 'emails/plain/customer-processing-renewal-order.php';
		$this->template_base  = plugin_dir_path( WC_Subscriptions::$plugin_file ) . 'templates/';

		add_action( 'woocommerce_order_status_pending_to_processing_renewal_notification_recipient', array( $this, 'trigger' ) );
		add_action( 'woocommerce_order_status_pending_to_on-hold_renewal_notification_recipient', array( $this, 'trigger' ) );

		WC_Email::__construct();
	}

	/**
	 * trigger function.
	 */
	function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object    = wc_get_order( $order_id );
			$subscriptions   = wcs_get_subscriptions_for_renewal_order( $order_id );
			$subscriptions   = array_values( $subscriptions );
			$recipient_id    = get_post_meta( $subscriptions[0]->id, '_recipient_user', true );
			$this->recipient = get_user_by( 'id', $recipient_id )->user_email;
		}

		$order_date_index = array_search( '{order_date}', $this->find );
		if ( false === $order_date_index ) {
			$this->find[] = '{order_date}';
			$this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );
		} else {
			$this->replace[ $order_date_index ] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->wcsg_sending_recipient_email = $recipient_id;
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		unset( $this->wcsg_sending_recipient_email );
	}
}
