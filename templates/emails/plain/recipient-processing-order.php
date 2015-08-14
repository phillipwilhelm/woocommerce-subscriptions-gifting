<?php
/**
 * Recipient customer new account email
 *
 * @author James Allan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo $email_heading;

echo esc_html__( 'Hi there,', 'woocommerce-subscriptions-gifting' ) );

echo sprintf( esc_html__( '%s just purchased ' .  _n( 'a subscription', 'subscriptions', count( $subscriptions ), 'woocommerce-subscriptions-gifting' ) . ' for you at %s.', 'woocommerce-subscriptions-gifting' ), esc_html( $subscription_purchaser ), esc_html( $blogname ) );

echo sprintf( esc_html__( 'The order has been received and is being processed. Details of the ' . _n( 'subscription', 'subscriptions', count( $subscriptions ), 'woocommerce-subscriptions-gifting' ) . ' are shown below:', 'woocommerce-subscriptions-gifting' ) ); ?>
