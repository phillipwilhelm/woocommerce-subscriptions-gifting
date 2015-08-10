<?php
class WCSG_Recipient_Details {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {
		add_filter( 'template_redirect', __CLASS__ . '::update_recipient_details', 1 );
		add_action( 'template_redirect',  __CLASS__ . '::my_account_template_redirect' );
		add_filter( 'wc_get_template', __CLASS__ . '::add_new_customer_template', 10, 5 );
	}

	/**
	 * locates the new recipient details page template if the user is flagged for requiring further details.
	 * @param $located
	 * @param $template_name
	 * @param $args
	 * @param $template_path
	 * @param $default_path
	 */
	public static function add_new_customer_template( $located, $template_name, $args, $template_path, $default_path ) {
		global $wp;
		$current_user = wp_get_current_user();
		if ( get_user_meta( $current_user->ID, 'wcsg_update_account', true ) ) {
			if ( 'myaccount/my-account.php' == $template_name && isset( $wp->query_vars['new-recipient-account'] ) ) {
				$located = wc_locate_template( 'new-recipient-account.php', $template_path, plugin_dir_path( WCS_Gifting::$plugin_file ) . 'templates/' );
			}
		}
		return $located;
	}

	/**
	 * redirects the user to the relevant page if they are trying to access my account or recipient account details page.
	 */
	public static function my_account_template_redirect() {
		global $wp;
		$current_user = wp_get_current_user();
		if ( is_account_page() && ! isset( $wp->query_vars['customer-logout'] ) ) {
			if ( get_user_meta( $current_user->ID, 'wcsg_update_account', true )  && ! isset( $wp->query_vars['new-recipient-account'] ) ) {
				wp_redirect( wc_get_page_permalink( 'myaccount' ) . 'new-recipient-account/' );
				exit();
			} else if ( ! get_user_meta( $current_user->ID, 'wcsg_update_account', true ) && isset( $wp->query_vars['new-recipient-account'] ) ) {
				wp_redirect( wc_get_page_permalink( 'myaccount' ) );
				exit();
			}
		}
	}

	/**
	 * Validates the new recipient account details page updating user data and removing the 'required account update' user flag
	 * if there are no errors in validation.
	 */
	public static function update_recipient_details() {
		if ( isset( $_POST['wcsg_new_recipient_customer'] ) ) {
			$form_fields = self::get_new_recipient_account_form_fields();

			$seperate_validation_fields = ['shipping_first_name','shipping_last_name','new_password','repeat_password'];

			if ( empty( $_POST['shipping_first_name'] ) || empty( $_POST['shipping_last_name'] ) ) {
				wc_add_notice( __( 'Please enter your name.', 'woocommerce-subscriptions-gifting' ), 'error' );
			}

			if ( empty( $_POST['new_password'] ) || empty( $_POST['repeat_password'] ) ) {
				wc_add_notice( __( 'Please enter both password fields.', 'woocommerce-subscriptions-gifting' ), 'error' );
			} else if ( $_POST['new_password'] != $_POST['repeat_password'] ) {
				wc_add_notice( __( 'Passwords do not match.', 'woocommerce-subscriptions-gifting' ), 'error' );
			}

			foreach ( $form_fields as $key => $field ) {
				if ( ( ! empty( $field['required'] ) && empty( $_POST[ $key ] ) ) && ! in_array( $key, $seperate_validation_fields ) ) {
					wc_add_notice( $field['label'] . ' ' . __( 'is a required field.', 'woocommerce-subscriptions-gifting' ), 'error' );
				}
			}

			if ( $_POST['shipping_postcode'] && ! WC_Validation::is_postcode( $_POST['shipping_postcode'], $_POST['shipping_country'] ) ) {
				wc_add_notice( __( 'Please enter a valid postcode/ZIP.', 'woocommerce-subscriptions-gifting' ), 'error' );
			}

			if ( 0 == wc_notice_count( 'error' ) ) {
				//update the user meta first name and last name and password.
				$user = get_user_by( 'id' , $_POST['wcsg_new_recipient_customer'] );
				$address = array();
				foreach ( $form_fields as $key => $field ) {
					if ( false == strpos( $key, 'password' ) && $key != 'set_billing' ) {
						update_user_meta( $user->ID, $key, wc_clean( $_POST[ $key ] ) );
						if ( isset( $_POST['set_billing'] ) ) {
							update_user_meta( $user->ID, str_replace( 'shipping', 'billing', $key ), wc_clean( $_POST[ $key ] ) );
						}
						$address[ str_replace( 'shipping' . '_', '', $key ) ] = wc_clean( $_POST[ $key ] );
					}
				}
				$user->user_pass = wc_clean( $_POST['new_password'] );

				$user_first_name = wc_clean( $_POST['shipping_first_name'] );
				update_user_meta( $user->ID, 'first_name', $user_first_name );
				update_user_meta( $user->ID, 'nickname', $user_first_name );
				$user->display_name = $user_first_name;

				update_user_meta( $user->ID, 'last_name', wc_clean( $_POST['shipping_last_name'] ) );

				wp_update_user( $user );

				$recipient_subscriptions = WCSG_Recipient_Management::get_recipient_subscriptions( $user->ID );

				foreach ( $recipient_subscriptions as $subscription_id ) {
					$subscription = wcs_get_subscription( $subscription_id );
					$subscription->set_address( $address, 'shipping' );
				}
				delete_user_meta( $user->ID, 'wcsg_update_account', true );
				wc_add_notice( __( 'Your account has been updated.', 'woocommerce-subscriptions-gifting' ), 'notice' );
				wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
				exit;
			}
		}
	}

	/**
	 * Creates an array of form fields for the new recipient user details form
	 * @return array Form elements for recipient details page
	 */
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
			'type'     => 'password',
			'label'    => esc_html__( 'New Password', 'woocommerce-subscriptions-gifting' ),
			'required' => true,
			'password' => true,
			'class'    => array( 'form-row-first' )
		);
		$personal_fields['repeat_password'] = array(
			'type'     => 'password',
			'label'    => esc_html__( 'Confirm New Password', 'woocommerce-subscriptions-gifting' ),
			'required' => true,
			'password' => true,
			'class'    => array( 'form-row-last' )
		);
		$form_fields['set_billing'] = array(
			'type'     => 'checkbox',
			'label'    => esc_html__( 'Set my billing address to the same as above.', 'woocommerce-subscriptions-gifting' ),
			'class'    => array( 'form-row' ),
			'required' => false,
			'default'  => 1
		);

		return array_merge( $personal_fields, $form_fields );
	}
}
WCSG_Recipient_Details::init();
