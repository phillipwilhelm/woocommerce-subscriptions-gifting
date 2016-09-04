<?php
/**
 * Recipient customer new account email
 *
 * @author James Allan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo '= ' . $email_heading . " =\n\n";

echo sprintf( __( 'Hi there,', 'woocommerce-subscriptions-gifting' ) ) . "\n\n";
echo sprintf( __( '%1$s just purchased a subscription for you at %2$s so we\'ve created an account for you to manage the subscription.', 'woocommerce-subscriptions-gifting' ), esc_html( $subscription_purchaser ), esc_html( $blogname ) ) . "\n\n";

echo sprintf( __( 'Your username is: %s', 'woocommerce-subscriptions-gifting' ), esc_html( $user_login ) ) . "\n";
echo sprintf( __( 'Your password has been automatically generated: %s', 'woocommerce-subscriptions-gifting' ), esc_html( $user_password ) ) . "\n\n";

echo sprintf( __( 'To complete your account we just need you to fill in your shipping address and you to change your password here: %s.', 'woocommerce-subscriptions-gifting' ), wc_get_endpoint_url( 'new-recipient-account', '', wc_get_page_permalink( 'myaccount' ) ) ) . "\n\n";
echo sprintf( __( 'Once completed you may access your account area to view your subscription here: %s.', 'woocommerce-subscriptions-gifting' ), wc_get_page_permalink( 'myaccount' ) ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
