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

<p><?php printf( esc_html__( 'Hi there,', 'woocommerce-subscriptions-gifting' ) ); ?></p>
<p><?php printf( esc_html__( '%s just purchased a subscription for you at %s so we\'ve created an account for you to manage the subscription.', 'woocommerce-subscriptions-gifting' ), wp_kses( $subscription_purchaser, wp_kses_allowed_html( 'user_description' ) ), esc_html( $blogname ) ); ?></p>

<p><?php printf( esc_html__( 'Your username is: %s', 'woocommerce-subscriptions-gifting' ), '<strong>' . esc_html( $user_login ) . '</strong>' ); ?></p>
<p><?php printf( esc_html__( 'Your password has been automatically generated: %s', 'woocommerce-subscriptions-gifting' ), '<strong>' . esc_html( $user_password ) . '</strong>' ); ?></p>

<p><?php printf( esc_html__( 'To complete your account we just need your shipping address and you to change your password here: %s.', 'woocommerce-subscriptions-gifting' ), esc_url( wc_get_page_permalink( 'myaccount' ) . 'new-recipient-account/' ) ); ?></p>
<p><?php printf( esc_html__( 'Once completed you may access your account area to view your subscription here: %s.', 'woocommerce-subscriptions-gifting' ), esc_url( wc_get_page_permalink( 'myaccount' ) ) ); ?></p>

<?php do_action( 'woocommerce_email_footer' ); ?>
