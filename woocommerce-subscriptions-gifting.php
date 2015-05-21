<?php
/**
* Plugin Name: woocommerce gifting subscriptions
*/
add_action( 'wp_enqueue_scripts', 'gifting_scripts' );

/*Adds gifting input*/
//Product Page
add_action( 'woocommerce_before_add_to_cart_button','add_gifting_option_product' );

//Cart Page.
add_filter( 'woocommerce_cart_item_name', 'add_gifting_option_cart', 1, 3 );

//Checkout Page
add_filter( 'woocommerce_checkout_cart_item_quantity', 'add_gifting_option_checkout', 1, 2 );

/*Hooks to add recipient data to the Cart Item Data*/
//Product Page
add_filter( 'woocommerce_add_cart_item_data', 'add_recipient_data', 1, 1 );
add_filter( 'woocommerce_get_cart_item_from_session', 'get_cart_items_from_session', 1, 3 );

//Cart Page
add_filter( 'woocommerce_update_cart_action_cart_updated', 'cart_update' );


add_filter( 'woocommerce_subscriptions_recurring_cart_key', 'add_recipient_email_recurring_cart_key', 1, 2 );

add_action( 'woocommerce_checkout_subscription_created', 'subscription_created', 1 , 3);

function subscription_created( $subscription, $order, $recurring_cart ) {
	error_log('subscription created');
	error_log('cart: '. print_r( reset( $recurring_cart->cart_contents), true ));
	if ( isset( reset( $recurring_cart->cart_contents )['wcsg_gift_recipients_email'] ) ){
		$recipient_email = reset( $recurring_cart->cart_contents )['wcsg_gift_recipients_email'];

		$recipient_user_id = email_exists( $recipient_email );

		if ( empty( $recipient_user_id ) ) {
			//create a username for the new customer
			$username = explode( '@', $recipient_email );
			$username = sanitize_user( $username[0] );
			$counter = 1;
			$_username = $username;
			while ( username_exists( $username ) ) {
				$username = $_username . $counter;
				$counter++;
			}
			//create a password
			$password = wp_generate_password();

    		$recipient_user_id = wc_create_new_customer( $recipient_email, $username, $password );
		}
		error_log( 'recipient_user_id = ' . print_r( $recipient_user_id, true ) );

		update_post_meta( $subscription->id, '_recipient_user', $recipient_user_id );

	}
}

function add_recipient_email_recurring_cart_key( $cart_key, $cart_item ) {
	if ( isset( $cart_item['wcsg_gift_recipients_email'] ) ) {
		$cart_key .= '_' . $cart_item['wcsg_gift_recipients_email'];
	}
	return $cart_key;

}

function cart_update() {
	//post data from cart page.
	foreach( WC()->cart->cart_contents as $key => $item ) {
		if ( isset( $_POST['recipient_email'][ $key ] ) ) {
			if ( !isset( $item['wcsg_gift_recipients_email'] ) || $item['wcsg_gift_recipients_email'] != $_POST['recipient_email'][ $key ] ) {
				$cart_item_data = WC()->cart->get_item_data( $item );
				$cart_item_data['wcsg_gift_recipients_email'] = $_POST['recipient_email'][ $key ];
				$new_key = WC()->cart->generate_cart_id( $item['product_id'], $item['variation_id'], $item['variation'], $cart_item_data );
				//
				if( !empty( WC()->cart->get_cart_item( $new_key ) ) ){
					error_log( 'An item in the cart already has that id' );
					error_log( 'cart_item_data = ' . print_r( $item, true ) );
					$combined_quantity = $item['quantity'] + WC()->cart->get_cart_item( $new_key )['quantity'];
					error_log( 'combined Quantity:' . $combined_quantity );
					error_log( 'Old Quantity:' . $item['quantity'] );
					error_log( 'New Quantity:' . WC()->cart->get_cart_item( $new_key )['quantity'] );
					//WC()->cart->set_quantity( $new_key, $combined_quantity, false );
					WC()->cart->cart_contents[ $new_key ]['quantity'] = $combined_quantity;
					unset( WC()->cart->cart_contents[ $key ] );

				} else {// there is no item in the cart with the same new key
					error_log( 'No Item with that id - So everything is good' );
					WC()->cart->cart_contents[ $new_key ] = WC()->cart->cart_contents[$key];
					WC()->cart->cart_contents[ $new_key ]['wcsg_gift_recipients_email'] = $_POST['recipient_email'][ $key ];
					unset( WC()->cart->cart_contents[ $key ] );

				}
				error_log( 'data added via post data' );
			}
		}
	}
}

function gifting_scripts() {
	wp_register_script( 'woocommerce_subscriptions_gifting', plugins_url( '/js/wcs-gifting.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'woocommerce_subscriptions_gifting' );
}

function add_gifting_option_product( $data ) {
	global $product;
	if(is_subscription( $product )) {
		echo generate_gifting_html( 0, '' );
	}
}

function add_gifting_option_checkout( $quantity, $cart_item ) {
	echo $quantity;
	if( is_checkout() ){
		error_log("Is the checkout page");

	}else {
		error_log("this is not the checkout page");
	}
	if( is_subscription( $cart_item['data'] ) ) {
		if( !isset( $cart_item['wcsg_gift_recipients_email'] ) ) {
			echo generate_gifting_html( $cart_item['product_id'], '');
		}else{
			echo generate_gifting_html( 0, $cart_item['wcsg_gift_recipients_email'] );
		}
	}
}

function add_gifting_option_cart( $title, $cart_item, $cart_item_key ) {
	echo $title;
	error_log( 'adding gifting for: '. $cart_item_key );

	if ( is_cart() && is_subscription( $cart_item['data'] ) ) {
		if ( !isset( $cart_item['wcsg_gift_recipients_email'] ) ) {
			echo generate_gifting_html( $cart_item_key, '' );
		} else {
			echo generate_gifting_html( $cart_item_key, $cart_item['wcsg_gift_recipients_email'] );
		}
	}
}

function generate_gifting_html( $id, $email ) {
	//return '<br><label  style="display = block">Recipient: </label>' . $email;
	return  '<fieldset>
			<input type="checkbox" id="gifting_'. $id .'_option" class="woocommerce_subscription_gifting_checkbox" value="gift" ' . ((empty($email)) ? '' : 'checked') . ' > This is a gift<br>
			<label class="woocommerce_subscriptions_gifting_recipient_email" ' . ((empty($email)) ? 'style="display: none;"' : '') . 'for="recipients_email">'. "Recipient's Email Address: " . '</label>
			<input name="recipient_email[' . $id . ']" class="woocommerce_subscriptions_gifting_recipient_email" type = "email" placeholder="recipient@example.com" value = "' . $email .'" ' . ((empty($email)) ? 'style="display: none;"' : '') . '>
			</fieldset>';
}

function is_subscription( $product ) {
	return $product->product_type == 'subscription' || $product->product_type == 'variable-subscription';
}


//triggered when the cart is pulled from the session??
function get_cart_items_from_session( $item, $values, $key ) {

	if ( array_key_exists( 'wcsg_gift_recipients_email', $values ) ) { //previously added at the product page via $cart_item_data
		$item[ 'wcsg_gift_recipients_email' ] = $values['wcsg_gift_recipients_email'];
		unset( $values['wcsg_gift_recipients_email'] );
	}
		return $item;
}

//adds repcipient data to the cart item data. Triggered when item is added to cart.
function add_recipient_data( $cart_item_data ) {
	if( isset( $_POST['recipient_email'] ) && !empty( $_POST['recipient_email'][0]) ) {
		//error_log('assigning email: ' . $_POST['recipient_email'][0]);
		$cart_item_data['wcsg_gift_recipients_email'] = $_POST['recipient_email'][0];
		error_log( 'cart_item_data = ' . print_r( $cart_item_data, true ) );
		return $cart_item_data;
	}
}
