<header>
	<h2><?php esc_html_e( 'Recent Orders', 'woocommerce-subscriptions-gifting' ); ?></h2>
</header>

<table class="shop_table shop_table_responsive my_account_orders">

	<thead>
		<tr>
			<th class="order-number"><span class="nobr"><?php esc_html_e( 'Order', 'woocommerce-subscriptions-gifting' ); ?></span></th>
			<th class="order-date"><span class="nobr"><?php esc_html_e( 'Date', 'woocommerce-subscriptions-gifting' ); ?></span></th>
			<th class="order-status"><span class="nobr"><?php esc_html_e( 'Status', 'woocommerce-subscriptions-gifting' ); ?></span></th>
			<th class="order-total"><span class="nobr"><?php esc_html_e( 'Total', 'woocommerce-subscriptions-gifting' ); ?></span></th>
			<th class="order-actions">&nbsp;</th>
		</tr>
	</thead>

	<tbody>
		<?php foreach ( $subscription_orders as $subscription_order ) {
			$order        = wc_get_order( $subscription_order );
			$display_link = current_user_can( 'view_order', $order->id );
			$item_count = $order->get_item_count();
			?><tr class="order">
				<td class="order-number" data-title="<?php esc_attr_e( 'Order Number', 'woocommerce-subscriptions-gifting' ); ?>">
					<?php if ( $display_link ) : ?>
					<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
						#<?php echo esc_html( $order->get_order_number() ); ?>
					</a>
					<?php else : ?>
					<b>#<?php echo esc_html( $order->get_order_number() );?><b>
					<?php endif; ?>
				</td>
				<td class="order-date" data-title="<?php esc_attr_e( 'Date', 'woocommerce-subscriptions-gifting' ); ?>">
					<time datetime="<?php echo esc_attr( date( 'Y-m-d', strtotime( $order->order_date ) ) ); ?>" title="<?php echo esc_attr( strtotime( $order->order_date ) ); ?>"><?php echo wp_kses_post( date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ) ); ?></time>
				</td>
				<td class="order-status" data-title="<?php esc_attr_e( 'Status', 'woocommerce-subscriptions-gifting' ); ?>" style="text-align:left; white-space:nowrap;">
					<?php
					echo esc_html( wc_get_order_status_name( $order->get_status() ) );
					if ( ! in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $order ) ) ) {
						if ( wp_get_current_user()->ID != $order->get_user_id() ) {
							echo '</br><small>' . esc_html( 'Purchased by ' . $order->get_user()->display_name ) . '</small>';
						}
					}
					?>
				</td>
				<td class="order-total" data-title="<?php esc_attr_e( 'Total', 'woocommerce-subscriptions-gifting' ); ?>">
					<?php
					// translators: price for number of items
					echo wp_kses_post( sprintf( _n( '%s for %s item', '%s for %s items', $item_count, 'woocommerce-subscriptions-gifting' ), $order->get_formatted_order_total(), $item_count ) );
					?>
				</td>
				<td class="order-actions">
					<?php $actions = array();

					if ( $order->needs_payment() ) {
						$actions['pay'] = array(
							'url'  => $order->get_checkout_payment_url(),
							'name' => esc_html_x( 'Pay', 'pay for a subscription', 'woocommerce-subscriptions-gifting' ),
						);
					}

					if ( in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_cancel', array( 'pending', 'failed' ), $order ) ) ) {
						$actions['cancel'] = array(
							'url'  => $order->get_cancel_order_url( wc_get_page_permalink( 'myaccount' ) ),
							'name' => esc_html_x( 'Cancel', 'cancel subscription', 'woocommerce-subscriptions-gifting' ),
						);
					}
					if ( $display_link ) {
						$actions['view'] = array(
							'url'  => $order->get_view_order_url(),
							'name' => esc_html_x( 'View', 'view a subscription', 'woocommerce-subscriptions-gifting' ),
						);
					}
					$actions = apply_filters( 'woocommerce_my_account_my_orders_actions', $actions, $order );

					if ( $actions ) {
						foreach ( $actions as $key => $action ) {
							echo wp_kses_post( '<a href="' . esc_url( $action['url'] ) . '" class="button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a>' );
						}
					}
					?>
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>

<?php do_action( 'woocommerce_subscription_details_after_subscription_recent_order_table', $subscription ); ?>
