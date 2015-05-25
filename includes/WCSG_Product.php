<?php
	class WCSG_Product {

		//setup hooks
		public static function init() {

			add_filter( 'woocommerce_add_cart_item_data', __CLASS__ . '::add_recipient_data', 1, 1 );
			add_filter( 'woocommerce_get_cart_item_from_session', __CLASS__ . '::get_cart_items_from_session', 1, 3 );

			add_action( 'woocommerce_before_add_to_cart_button', __CLASS__ . '::add_gifting_option_product' );

		}

		//adds repcipient data to the cart item data. Triggered when item is added to cart.
		public static function add_recipient_data( $cart_item_data ) {
			if( isset( $_POST['recipient_email'] ) && !empty( $_POST['recipient_email'][0]) ) {
				$cart_item_data['wcsg_gift_recipients_email'] = $_POST['recipient_email'][0];
				return $cart_item_data;
			}
		}

		//triggered when the cart is pulled from the session??
		public static function get_cart_items_from_session( $item, $values, $key ) {
			if ( array_key_exists( 'wcsg_gift_recipients_email', $values ) ) { //previously added at the product page via $cart_item_data
				$item[ 'wcsg_gift_recipients_email' ] = $values['wcsg_gift_recipients_email'];
				unset( $values['wcsg_gift_recipients_email'] );
			}
				return $item;
		}

		public static function add_gifting_option_product( $data ) {
			global $product;
			if(WC_Subscriptions_Product::is_subscription( $product )) {
				echo WCS_Gifting::generate_gifting_html( 0, '' );
			}
		}
	}
	WCSG_Product::init();
