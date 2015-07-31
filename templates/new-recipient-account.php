<p><?php
	esc_html_e( 'We just need a few details from you to complete your account creation.', 'woocommerce-subscriptions-gifting' ); ?><br><?php
	printf( __( '(not %s? %sSign out%s)', 'woocommerce-subscriptions-gifting' ),
		wp_get_current_user()->user_email,
		'<a href="' . wc_get_endpoint_url( 'customer-logout', '', wc_get_page_permalink( 'myaccount' ) ) . '">',
		'</a>'
	); ?>
</p>
<form action="" method="post">
<?php

	$form_fields = WCSG_Recipient_Details::get_new_recipient_account_form_fields();

	foreach ( $form_fields as $key => $field ) {
		if ( 'shipping_country' == $key ) { ?>
			<h3> <?php esc_html_e( 'Shipping Address', 'woocommerce-subscriptions-gifting' ); ?></h3><?php
		}
		$default_value = isset( $field['default'] ) ? $field['default'] : '';
		woocommerce_form_field( $key, $field, ! empty( $_POST[ $key ] ) ? wc_clean( $_POST[ $key ] ) : $default_value );
	}

?>
<input type="hidden" name="wcsg_new_recipient_customer" value="<?php echo esc_attr( wp_get_current_user()->ID ); ?>" />
<input type="submit" class="button" name="save_address" value="<?php esc_html_e( 'Save', 'woocommerce-subscriptions-gifting' ); ?>" />

</form>
