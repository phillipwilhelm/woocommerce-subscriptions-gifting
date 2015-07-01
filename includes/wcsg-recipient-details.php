<?php
class WCSG_Recipient_Details {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {
		add_filter( 'init', __CLASS__ . '::update_recipient_details', 1 );
	}

	public static function update_recipient_details() {
		if ( isset( $_POST['wcsg_new_recipient_customer'] ) ) {
			$form_fields = self::get_new_recipient_account_form_fields();

			$seperate_validation_fields = ['shipping_first_name','shipping_last_name','new_password','repeat_password'];

			if ( empty( $_POST['shipping_first_name'] ) || empty( $_POST['shipping_last_name'] ) ) {
				wc_add_notice( __( 'Please enter your name.' ), 'error' );
			}

			if ( empty( $_POST['new_password'] ) || empty( $_POST['repeat_password'] ) ) {
				wc_add_notice( __( 'Please enter both password fields.', 'woocommerce' ), 'error' );
			}else if( $_POST['new_password'] != $_POST['repeat_password'] ) {
				wc_add_notice( __( 'Passwords do not match.' ), 'error' );
			}

			foreach ( $form_fields as $key => $field ) {
				if ( ( ! empty( $field['required'] ) && empty( $_POST[ $key ] ) ) && !in_array( $key, $seperate_validation_fields ) ) {
					wc_add_notice( $field['label'] . ' ' . __( 'is a required field.' ), 'error' );
				}
			}

			if ( $_POST['shipping_postcode'] && ! WC_Validation::is_postcode( $_POST['shipping_postcode'], $_POST['shipping_country'] ) ){
				wc_add_notice( __( 'Please enter a valid postcode/ZIP.' ), 'error' );
			}

			if ( wc_notice_count( 'error' ) == 0 ) {

				//update the user meta first name and last name and password.

				$user = get_user_by( 'id' , $_POST['wcsg_new_recipient_customer'] );
				$address = array();
				foreach ( $form_fields as $key => $field ) {
					update_user_meta( $user->ID, $key, $_POST[ $key ] );
					if ( false == strpos( $key ,'password' ) ) {
						$address[ str_replace( 'shipping' . '_', '', $key ) ] = woocommerce_clean( $_POST[ $key ] );
					}
				}
				$user->user_pass = $_POST['new_password'];
				update_user_meta( $user->ID, 'first_name', $_POST['shipping_first_name'] );
				update_user_meta( $user->ID, 'last_name', $_POST['shipping_last_name'] );
				update_user_meta( $user->ID, 'nickname', $_POST['shipping_first_name'] );
				$user->display_name = $_POST['shipping_first_name'];
				wp_update_user( $user );
				$recipient_subscriptions = WCSG_Recipient_Management::get_recipient_subscriptions( $user->ID );

				foreach ( $recipient_subscriptions as $subscription_id ) {
					$subscription = wc_get_order( $subscription_id );
					$subscription->set_address( $address, 'Shipping' );
				}
				error_log(print_r($address,true));
				delete_user_meta( $user->ID, 'wcsg_update_account', true );
				wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
				exit;
			}
		}
	}

	public static function get_new_recipient_account_form_fields() {
		$form_fields = WC()->countries->get_address_fields( '', 'shipping_', true );

		$name_fields = array( 'shipping_first_name', 'shipping_last_name' );
		$personal_fields = [];

		//move the name fields to the front of the array for display purposes.
		foreach ( $name_fields as $element ) {
			$personal_fields[ $element ] = $form_fields[ $element ];
			unset( $form_fields[ $element ] );
		}

		$personal_fields['new_password'] = array(
			'type' => 'password',
			'label'  => 'New Password',
			'required' => true,
			'password' => true,
			'class'    => array( 'form-row-first' )
		);
		$personal_fields['repeat_password'] = array(
			'type' => 'password',
			'label' => 'Confirm New Password',
			'required' => true,
			'password' => true,
			'class' => array( 'form-row-last' )
		);

		return array_merge( $personal_fields, $form_fields );
	}
}
WCSG_Recipient_Details::init();
