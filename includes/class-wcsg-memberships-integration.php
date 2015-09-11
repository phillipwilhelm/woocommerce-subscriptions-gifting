<?php
class WCSG_Memberships_Integration {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		//We need to grant recipients membership before WooCommerce Memberships handles the order.
		add_action( 'woocommerce_order_status_completed', __CLASS__ . '::grant_membership_access', 10 );
		add_action( 'woocommerce_order_status_processing', __CLASS__ . '::grant_membership_access', 10 );

		//Update the subscription stored on the membership. Called after Memberships has linked the subscription.
		add_action( 'wc_memberships_grant_membership_access_from_purchase', __CLASS__ . '::update_subscription_id', 11 , 2 );
	}

	/**
	 * Grant customer or recipients access to memberships when an order is processed/completed.
	 *
	 * @param int $order_id Order ID
	 */
	public static function grant_membership_access( $order_id ) {
		//prevent WooCommerce Memberships from creating memberships
		remove_action( current_filter(), array( wc_memberships(), 'grant_membership_access' ), 11 );

		$order = wc_get_order( $order_id );
		$items = $order->get_items();

		$membership_plans = wc_memberships()->plans->get_membership_plans();

		if ( empty( $membership_plans ) ) {
			return;
		}

		foreach ( $membership_plans as $plan ) {

			if ( ! $plan->has_products() ) {
				continue;
			}

			$access_granting_product_ids = array();

			foreach ( $items as $order_item_id => $item ) {

				$recipient = wc_get_order_item_meta( $order_item_id, 'wcsg_recipient', true );

				if ( ! empty( $recipient ) ) {
					$user_id = substr( $recipient, strlen( 'wcsg_recipient_id_' ) );
				} else {
					$user_id = $order->get_user_id();
				}

				if ( $plan->has_product( $item['product_id'] ) ) {
					$access_granting_product_ids[ $user_id ][] = $item['product_id'];
				}

				// Variation access
				if ( isset( $item['variation_id'] ) && $item['variation_id'] && $plan->has_product( $item['variation_id'] ) ) {
					$access_granting_product_ids[ $user_id ][] = $item['variation_id'];
				}
			}

			foreach ( $access_granting_product_ids as $user_id => $products ) {
				$product_id = apply_filters( 'wc_memberships_access_granting_purchased_product_id', $products[0], $products, $plan );

				$plan->grant_access_from_purchase( $user_id, $product_id, $order_id );
			}
		}
	}

	/**
	 * Because an order can contain multiple subscriptions with the same product in the one order we need
	 * to update the subscription linked to the membership.
	 * Gets the subscription the membership user has access to via recipient link.
	 *
	 * @param WC_Memberships_Membership_Plan $membership_plan The plan that user was granted access to
	 * @param array $args
	 */
	public static function update_subscription_id( $membership_plan, $args ) {

		$subscriptions_in_order = wcs_get_subscriptions( array(
			'order_id' => $args['order_id'],
			'product_id' => $args['product_id'],
		) );

		if ( 1 != count( $subscriptions_in_order ) ) {

			$order = wc_get_order( $args['order_id'] );
			//check if the member user is a recipient
			if ( $order->user_id != $args['user_id'] ) {
				$recipient_subscriptions = WCSG_Recipient_Management::get_recipient_subscriptions( $args['user_id'] );
				$recipient_subscription_in_order = array_intersect( array_keys( $subscriptions_in_order ), $recipient_subscriptions );
				update_post_meta( $args['user_membership_id'], '_subscription_id', reset( $recipient_subscription_in_order ) );
			} else {
				foreach ( $subscriptions_in_order as $subscription ) {
					if ( ! isset( $subscription->recipient_user ) ) {
						update_post_meta( $args['user_membership_id'], '_subscription_id', $subscription->id );
					}
				}
			}
		}
	}
}

WCSG_Memberships_Integration::init();
