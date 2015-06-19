<?php
/**
* Customer new account email
*
* @author 		WooThemes
* @package 	WooCommerce/Templates/Emails
* @version     1.6.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php printf( __( "Hi there," ) ); ?></p>
<p><?php printf( __( "%s just purchased a subscription for you at %s so we've created an account for you to manage the subscription.", 'woocommerce' ), esc_html( $sub_owner ), esc_html( $blogname ) ); ?></p>

<p><?php printf( __( "Your username is: <strong>%s</strong>", 'woocommerce' ), esc_html( $user_login ) ); ?></p>
<p><?php printf( __( "Your password has been automatically generated: <strong>%s</strong>", 'woocommerce' ), esc_html( $user_pass ) ); ?></p>


<p><?php printf( __( 'You can access your account area to view your orders and change your password here: %s.', 'woocommerce' ), wc_get_page_permalink( 'myaccount' ) ); ?></p>

<?php do_action( 'woocommerce_email_footer' ); ?>
