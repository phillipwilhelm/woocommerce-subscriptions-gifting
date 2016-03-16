<?php
class WCSG_Memberships_Integration {

	public static $processing_memberships_for_order;

	public static function init() {

		// Store the order id being processed so it can be used later
		add_action( 'woocommerce_order_status_completed', __CLASS__ . '::set_processing_memberships_for_order_flag', 1 );
		add_action( 'woocommerce_order_status_processing', __CLASS__ . '::set_processing_memberships_for_order_flag', 1 );
		add_action( 'woocommerce_order_status_completed', __CLASS__ . '::remove_processing_memberships_for_order_flag', 20 );
		add_action( 'woocommerce_order_status_processing', __CLASS__ . '::remove_processing_memberships_for_order_flag', 20 );

		// We need to hook late so other plugins don't override all our user unique order products
		add_filter( 'wc_memberships_access_granting_purchased_product_id', __CLASS__ . '::get_user_unique_membership_access_granting_product_ids', 100, 3 );

		// We want to hook late so we don't override other plugins preventing granting membership
		add_filter( 'wc_memberships_grant_access_from_new_purchase', __CLASS__ . '::grant_membership_access', 100, 2 );

		// Set the correct subscription id stored on the membership. Called after Memberships has linked the subscription.
		add_action( 'wc_memberships_grant_membership_access_from_purchase', __CLASS__ . '::update_subscription_id', 11 , 2 );
	}

	/**
	 * Grants memberships to recipients and returns false so the purchaser is not granted the membership
	 * unless it is found that the purchaser also purchased the product for themselves.
	 *
	 * @param bool $grant_access whether the membership will be granted with the following membership data
	 * @param array $membership_data {
	 *      @type int|string $user_id user ID for order
	 *      @type int|string $product_id product ID that grants access
	 *      @type int|string $order_id order ID
	 * }
	 */
	public static function grant_membership_access( $grant_access, $membership_data ) {

		if ( $grant_access && WC_Subscriptions_Product::is_subscription( $membership_data['product_id'] ) && WCS_Gifting::order_contains_gifted_subscription( $membership_data['order_id'] ) ) {

			// defaulted to false unless we find the purchaser has purchased the product for themselves
			$grant_access = false;
			$order        = wc_get_order( $membership_data['order_id'] );
			$product_id   = $membership_data['product_id'];
			$user_id      = $membership_data['user_id'];
			$order_items  = $order->get_items();

			$grant_access_to_recipients = array();

			foreach ( $order_items as $order_item_id => $order_item ) {
				if ( wcs_get_canonical_product_id( $order_item ) == $product_id ) {
					if ( isset( $order_item['item_meta']['wcsg_recipient'] ) ) {
						$grant_access_to_recipients[] = WCS_Gifting::get_order_item_recipient_user_id( $order_item );
					} else {
						$grant_access = true;
					}
				}
			}

			if ( ! empty( $grant_access_to_recipients ) ) {

				foreach ( wc_memberships_get_membership_plans() as $plan ) {

					if ( $plan->has_product( $product_id ) ) {
						foreach ( $grant_access_to_recipients as $recipient_user_id ) {
							$plan->grant_access_from_purchase( $recipient_user_id, $product_id, $order->id );
						}
					}
				}
			}
		}

		return $grant_access;
	}

	/**
	 * Sets a order id flag when processing an order so it can be later used inside
	 * self::get_user_unique_membership_access_granting_product_ids().
	 *
	 * @param int $order_id
	 */
	public static function set_processing_memberships_for_order_flag( $order_id ) {
		self::$processing_memberships_for_order = $order_id;
	}

	/**
	 * Removes the order id flag after memberships has processed the order.
	 *
	 * @param int $order_id
	 */
	public static function remove_processing_memberships_for_order_flag( $order_id ) {
		self::$processing_memberships_for_order = null;
	}

	/**
	 * By default memberships will determine what the best product in this order is to grant the membership
	 * (subscriptions with the longest end date take priority). However, because multiple subscriptions with
	 * multiple recipients (purchaser or gift recipient) is possible we need to get the best product per user.
	 *
	 * @param array $product_ids The product id(s) which will grant membership in this order.
	 * @param array $access_granting_product_ids Array of product IDs that can grant access to this plan.
	 * @param WC_Memberships_Membership_Plan $plan Membership plan access will be granted to.
	 */
	public static function get_user_unique_membership_access_granting_product_ids( $product_ids, $all_access_granting_product_ids, $plan ) {
		$order = wc_get_order( self::$processing_memberships_for_order );

		if ( WCS_Gifting::order_contains_gifted_subscription( $order ) ) {
			$user_unique_product_ids = array();
			$product_ids             = array();

			foreach ( $order->get_items() as $order_item_id => $order_item ) {

				if ( in_array( wcs_get_canonical_product_id( $order_item ), $all_access_granting_product_ids ) ) {

					$user_id = ( isset( $order_item['item_meta']['wcsg_recipient'] ) ) ? WCS_Gifting::get_order_item_recipient_user_id( $order_item ) : $order->customer_user;
					$user_unique_product_ids[ $user_id ][] = wcs_get_canonical_product_id( $order_item );
				}
			}

			remove_filter( 'wc_memberships_access_granting_purchased_product_id', __METHOD__, 100 );

			foreach ( $user_unique_product_ids as $user_access_granting_product_ids ) {

				$user_granting_product = wc_memberships()->allow_cumulative_granting_access_orders()
					? $user_access_granting_product_ids
					: $user_access_granting_product_ids[0];

				$product_ids = array_unique( array_merge( $product_ids, (array) apply_filters( 'wc_memberships_access_granting_purchased_product_id', $user_granting_product, $user_access_granting_product_ids, $plan ) ) );
			}

			add_filter( 'wc_memberships_access_granting_purchased_product_id', __METHOD__, 100, 3 );
		}

		return $product_ids;
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

			// check if the member user is a recipient
			if ( $order->user_id != $args['user_id'] ) {
				$recipient_subscriptions         = WCSG_Recipient_Management::get_recipient_subscriptions( $args['user_id'] );
				$recipient_subscription_in_order = array_intersect( array_keys( $subscriptions_in_order ), $recipient_subscriptions );

				$subscription = wcs_get_subscription( reset( $recipient_subscription_in_order ) );

				update_post_meta( $args['user_membership_id'], '_subscription_id', $subscription->id );
				wc_memberships()->get_subscriptions_integration()->update_related_membership_dates( $subscription, 'end', $subscription->get_date( 'end' ) );
			} else {
				foreach ( $subscriptions_in_order as $subscription ) {
					if ( ! isset( $subscription->recipient_user ) ) {
						update_post_meta( $args['user_membership_id'], '_subscription_id', $subscription->id );
						wc_memberships()->get_subscriptions_integration()->update_related_membership_dates( $subscription, 'end', $subscription->get_date( 'end' ) );
					}
				}
			}
		}
	}

}
WCSG_Memberships_Integration::init();
