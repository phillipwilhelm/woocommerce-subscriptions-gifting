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

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( esc_html__( 'Hi there,', 'woocommerce-subscriptions-gifting' ) ); ?></p>
<p><?php printf( esc_html__( '%1$s just purchased %2$s for you at %3$s.', 'woocommerce-subscriptions-gifting' ), wp_kses( $subscription_purchaser, wp_kses_allowed_html( 'user_description' ) ), esc_html( _n( 'a subscription', 'subscriptions', count( $subscriptions ), 'woocommerce-subscriptions-gifting' ) ), esc_html( $blogname ) ); ?>
<?php printf( esc_html__( ' Details of the %s are shown below.', 'woocommerce-subscriptions-gifting' ), esc_html( _n( 'subscription', 'subscriptions', count( $subscriptions ), 'woocommerce-subscriptions-gifting' ) ) ); ?>
</p>
<?php

$new_recipient = get_user_meta( $recipient_user->ID, 'wcsg_update_account', true );

if ( 'true' == $new_recipient ) : ?>

<p><?php esc_html_e( 'We noticed you didn\'t have an account so we created one for you. Your account login details will have been sent to you in a separate email.', 'woocommerce-subscriptions-gifting' ); ?></p>

<?php else : ?>

<p><?php printf( esc_html__( 'You may access your account area to view your new %1$s here: %2$sMy Account%3$s.', 'woocommerce-subscriptions-gifting' ),
	esc_html( _n( 'subscription', 'subscriptions', count( $subscriptions ), 'woocommerce-subscriptions-gifting' ) ),
	'<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">',
	'</a>'
); ?></p>

<?php endif;

if ( 0 < count( $subscriptions ) ) : ?>
	<table cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
<?php endif;

foreach ( $subscriptions as $subscription_id ) {
	$subscription = wcs_get_subscription( $subscription_id );?>
	<thead>
		<tr>
			<td style="padding: -6" colspan="3"><h3><?php printf( esc_html__( 'Subscription #%s', 'woocommerce-subscriptions-gifting' ), esc_attr( $subscription->get_order_number() ) ) ?></h3></td>
		</tr>
	</thead>
		<tr>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Product', 'woocommerce-subscriptions-gifting' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Quantity', 'woocommerce-subscriptions-gifting' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Price', 'woocommerce-subscriptions-gifting' ); ?></th>
		</tr>
	<tbody>
		<?php echo wp_kses_post( WC_Subscriptions_Email::email_order_items_table( $subscription, array(
			'show_download_links' => true,
			'show_sku'            => false,
			'show_purchase_note'  => true,
		) ) ); ?>
	</tbody><?php
}
echo '</table>';
do_action( 'woocommerce_email_footer', $email );
