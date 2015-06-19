<?php
/**
* Customer new account email
*
* @author 		WooThemes
* @package 	WooCommerce/Templates/Emails/Plain
* @version     2.0.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n";

echo sprintf( __( "Hi there," ) ) . "\n\n";
echo sprintf( __( "%s just purchased a subscription for you at %s so we've created an account for you to manage the subscription.", 'woocommerce' ), esc_html( $sub_owner ), esc_html( $blogname ) ) . "\n\n";

echo sprintf( __( "Your username is: <strong>%s</strong>", 'woocommerce' ), esc_html( $user_login ) ) . "\n";
echo sprintf( __( "Your password has been automatically generated: <strong>%s</strong>", 'woocommerce' ), esc_html( $user_pass ) ) . "\n\n";

echo sprintf( __( 'You can access your account area to view your orders and change your password here: %s.', 'woocommerce' ), wc_get_page_permalink( 'myaccount' ) ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
