<?php
/**
 * Recipient customer new account email
 *
 * @author James Allan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php printf( __( "Hi there,", 'woocommerce-subscriptions-gifting' ) ); ?></p>
<p><?php printf( __( "%s just purchased a subscription for you at %s so we've created an account for you to manage the subscription.", 'woocommerce-subscriptions-gifting' ), esc_html( $subscription_purchaser ), esc_html( $blogname ) ); ?></p>

<p><?php printf( __( "Your username is: <strong>%s</strong>", 'woocommerce-subscriptions-gifting' ), esc_html( $user_login ) ); ?></p>
<p><?php printf( __( "Your password has been automatically generated: <strong>%s</strong>", 'woocommerce-subscriptions-gifting' ), esc_html( $user_password ) ); ?></p>

<p><?php printf( __( 'To complete your account creation you can update your shipping address and change your password here: %s.', 'woocommerce-subscriptions-gifting' ), wc_get_page_permalink( 'myaccount' ) ); ?></p>
<p><?php printf( __( 'Once completed you may access your account area to view your orders here: %s.', 'woocommerce-subscriptions-gifting' ), wc_get_page_permalink( 'myaccount' ) ); ?></p>

<?php do_action( 'woocommerce_email_footer' ); ?>
