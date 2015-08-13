<?php

$email_field_args = array(
	'type' => 'email',
	'return'      => false,
	'label'       => 'Recipient\'s Email Address:',
	'placeholder' => 'recipient@example.com',
	'class'       => array( 'woocommerce_subscriptions_gifting_recipient_email' ),
	'label_custom_attributes' => array(),
);

if ( ! empty( $email ) && ( WCS_Gifting::email_belongs_to_current_user( $email ) || ! is_email( $email ) ) ) {
	array_push( $email_field_args['class'], 'woocommerce-invalid' );
}

if ( empty( $email ) ) {
	array_push( $email_field_args['label_custom_attributes'], 'style = "display: none"' );
}

?>
<fieldset>
	<input type="checkbox" id="gifting_' . esc_attr( $id ) . '_option" class="woocommerce_subscription_gifting_checkbox" value="gift" <?php echo ( ( empty( $email ) ) ? '' : 'checked' ) ?> /> <?php echo esc_html__( 'This is a gift', 'woocommerce_subscriptions_gifting' ) ?> <br />
	 <?php woocommerce_form_field( 'recipient_email[' . $id . ']', $email_field_args , $email );
	 wp_nonce_field( 'wcsg_add_recipient', '_wcsgnonce' ); ?>
</fieldset>
