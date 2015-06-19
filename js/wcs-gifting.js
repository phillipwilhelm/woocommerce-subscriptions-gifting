jQuery(document).ready(function($){
	$(document).on('click', '.woocommerce_subscription_gifting_checkbox',function() {
		if ($(this).is(':checked')) {
			$(this).parents().children('.woocommerce_subscriptions_gifting_recipient_email').slideDown( 250 );
		} else {
			$(this).parents().children('.woocommerce_subscriptions_gifting_recipient_email').slideUp( 250 );
		}
	});
});
