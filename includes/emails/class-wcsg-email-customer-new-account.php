<?php

class WCSG_Email_Customer_New_Account extends WC_Email {

	public $subscription_owner;
	public $user_login;
	public $user_email;
	public $user_password;

	/**
	 * Create an instance of the class.
	 */
	function __construct() {

		// Call override values
		$this->id             = 'WCSG_Email_Customer_New_Account';
		$this->title          = __( 'New Recipient Account', 'woocommerce-subscriptions-gifting' );
		$this->description    = __( 'New account notification emails are sent to the subscription recipient when an account is created for them.', 'woocommerce-subscriptions-gifting' );
		$this->customer_email = true;

		$this->subject        = __( 'Your account on {site_title}', 'woocommerce-subscriptions-gifting' );
		$this->heading        = __( 'Welcome to {site_title}', 'woocommerce-subscriptions-gifting' );

		$this->template_html  = 'emails/new-recipient-customer.php';
		$this->template_plain = 'emails/plain/new-recipient-customer.php';
		$this->template_base  = plugin_dir_path( WCS_Gifting::$plugin_file ) . 'templates/';

		// Triggers for this email
		add_action( 'wcsg_created_customer_notification', array( $this, 'trigger' ), 10 , 3 );

		WC_Email::__construct();
	}

	/**
	 * trigger function.
	 */
	function trigger( $user_id, $user_password, $subscription_purchaser ) {
		if ( $user_id ) {
			$this->object             = get_user_by( 'id', $user_id );
			$this->user_password      = $user_password;
			$this->user_login         = stripslashes( $this->object->user_login );
			$this->user_email         = stripslashes( $this->object->user_email );
			$this->recipient          = $this->user_email;
			$this->subscription_owner = $subscription_purchaser;
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
			'user_login'             => $this->user_login,
			'user_password'          => $this->user_password,
			'blogname'               => $this->get_blogname(),
			'subscription_purchaser' => $this->subscription_owner,
			'sent_to_admin'          => false,
			'plain_text'             => false,
			'email'                  => $this,
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
			'user_login'             => $this->user_login,
			'user_password'          => $this->user_password,
			'blogname'               => $this->get_blogname(),
			'subscription_purchaser' => $this->subscription_owner,
			'sent_to_admin'          => false,
			'plain_text'             => true,
			'email'                  => $this,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}
}
