<?php

class WCSG_Email_Completed_Renewal_Order extends WCS_Email_Completed_Renewal_Order {

	/**
	 * Create an instance of the class.
	 */
	function __construct() {

		//WCS_Email_Completed_Renewal_Order::__construct();

		$this->id             = 'recipient_completed_renewal_order';
		$this->title          = __( 'Completed Renewal Order - Gift Recipient', 'woocommerce-subscriptions' );
		$this->description    = __( 'Renewal order complete emails are sent to the gift recipient when a subscription renewal order is marked complete and usually indicates that the item for that renewal period has been shipped.', 'woocommerce-subscriptions' );;
		$this->heading        = __( 'Your renewal order is complete', 'woocommerce-subscriptions' );
		$this->subject        = __( 'Your {blogname} renewal order from {order_date} is complete', 'woocommerce-subscriptions' );

		// Other settings
		$this->heading_downloadable = $this->get_option( 'heading_downloadable', __( 'Your subscription renewal order is complete - download your files', 'woocommerce-subscriptions' ) );
		$this->subject_downloadable = $this->get_option( 'subject_downloadable', __( 'Your {blogname} subscription renewal order from {order_date} is complete - download your files', 'woocommerce-subscriptions' ) );

		$this->template_html  = 'emails/customer-completed-renewal-order.php';
		$this->template_plain = 'emails/plain/customer-completed-renewal-order.php';
		$this->template_base  = plugin_dir_path( WC_Subscriptions::$plugin_file ) . 'templates/';

		add_action( 'woocommerce_order_status_completed_renewal_notification_recipient', array( $this, 'trigger' ) );

		WC_Email::__construct();
	}

	/**
	 * trigger function.
	 */
	function trigger( $order_id ) {
		if ( $order_id ) {
			$this->object    = new WC_Order( $order_id );
			$subscription    = wcs_get_subscriptions_for_renewal_order( $order_id );
			$gift_recipient  = new WP_User( get_post_meta( array_values( $subscription )[0]->id, '_recipient_user' )[0] );
			$this->recipient = $gift_recipient->user_email;
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
		error_log('sending renewal order completed');
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}
}
