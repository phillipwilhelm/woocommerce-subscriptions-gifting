<?php

class WCSG_Email_Recipient_Processing_Order extends WC_Email {

	public $subscription_owner;
	public $subscriptions;
	/**
	 * Create an instance of the class.
	 */
	function __construct() {

		$this->id             = 'recipient_processing_order';
		$this->title          = __( 'Recipient Processing Order', 'woocommerce-subscriptions-gifting' );
		$this->description    = __( 'This email is sent to recipients notifying them of subscriptions purchased for them.', 'woocommerce-subscriptions-gifting' );

		$this->heading        = __( 'Order Received', 'woocommerce-subscriptions-gifting' );
		$this->subject        = __( 'Your new subscriptions at {site_title}', 'woocommerce-subscriptions-gifting' );

		$this->template_html  = 'emails/recipient-processing-order.php';
		$this->template_plain = 'emails/plain/recipient-processing-order.php';
		$this->template_base  = plugin_dir_path( WCS_Gifting::$plugin_file ) . 'templates/';

		// Trigger for this email
		add_action( 'wcsg_processing_order_recipient_notification', array( $this, 'trigger' ),10 , 2 );

		WC_Email::__construct();
	}

	/**
	 * trigger function.
	 */
	function trigger( $recipient_user, $recipient_subscriptions ) {
		if ( $recipient_user ) {
			$this->object             = get_user_by( 'id', $recipient_user );
			$this->recipient          = stripslashes( $this->object->user_email );
			$subscription             = wcs_get_subscription( $recipient_subscriptions[0] );
			$this->subscription_owner = WCSG_Email::get_purchaser_name_for_email( $subscription->customer_user );
			$this->subscriptions      = $recipient_subscriptions;
		}
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * get_content_html function.
	 */
	function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'email_heading'          => $this->get_heading(),
			'blogname'               => $this->get_blogname(),
			'recipient_user'         => $this->object,
			'subscription_purchaser' => $this->subscription_owner,
			'subscriptions'          => $this->subscriptions,
			'sent_to_admin'          => false,
			'plain_text'             => false,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 */
	function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'email_heading'          => $this->get_heading(),
			'blogname'               => $this->get_blogname(),
			'recipient_user'         => $this->object,
			'subscription_purchaser' => $this->subscription_owner,
			'subscriptions'          => $this->subscriptions,
			'sent_to_admin'          => false,
			'plain_text'             => true,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();

	}
}
