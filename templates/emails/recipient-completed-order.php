<?php
/**
 * Recipient new subscription(s) notification email
 *
 * @author James Allan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php printf( esc_html__( 'Hi there,', 'woocommerce-subscriptions-gifting' ) ); ?></p>
<p><?php printf( esc_html__( '%s just purchased ' .  _n( 'a subscription', 'subscriptions', count( $subscriptions ), 'woocommerce-subscriptions-gifting' ) . ' for you at %s.', 'woocommerce-subscriptions-gifting' ), wp_kses( $subscription_purchaser, wp_kses_allowed_html( 'user_description' ) ), esc_html( $blogname ) ); ?>
<?php printf( esc_html__( ' Details of the ' . _n( 'subscription', 'subscriptions', count( $subscriptions ), 'woocommerce-subscriptions-gifting' ) . ' are shown below.', 'woocommerce-subscriptions-gifting' ) ); ?>
</p>
<?php

$new_recipient = get_user_meta( $recipient_user->ID, 'wcsg_update_account', true );

if ( 'true' == $new_recipient ) : ?>

<p><?php esc_html_e( 'We noticed you didn\'t have an account so we created one for you. Your account login details will have been sent to you in a separate email.' ); ?></p>

<?php else : ?>

<p><?php printf( esc_html__( 'You may access your account area to view your new ' . _n( 'subscription', 'subscriptions', count( $subscriptions ), 'woocommerce-subscriptions-gifting' ) . ' here: %1$sMy Account%2$s.', 'woocommerce-subscriptions-gifting' ),
	'<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) )  . '">',
	'</a>'
); ?></p>

<?php endif;

if ( 0 < count( $subscriptions ) ) : ?>
	<table cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
<?php endif;

foreach ( $subscriptions as $subscription_id ) {
	$subscription = wcs_get_subscription( $subscription_id );
	$items        = $subscription->get_items();
	$total        = $subscription->get_formatted_order_total();?>
	<thead>
		<tr>
			<td style="padding: -6" colspan="3"><h3><?php printf( esc_html__( 'Subscription #%s', 'woocommerce-subscriptions-gifting' ), esc_attr( $subscription_id ) ) ?></h3></td>
		</tr>
	</thead>
		<tr>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Product', 'woocommerce-subscriptions-gifting' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Quantity', 'woocommerce-subscriptions-gifting' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Price', 'woocommerce-subscriptions-gifting' ); ?></th>
		</tr>
	<tbody>
		<?php echo wp_kses_post( $subscription->email_order_items_table( true, false, true ) ); ?>
	</tbody><?php
}
echo '</table>';
do_action( 'woocommerce_email_footer' );
