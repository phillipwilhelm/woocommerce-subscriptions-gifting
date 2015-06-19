jQuery(document).ready(function($){
	$(document).on('change', '.woocommerce_subscription_gifting_checkbox',function(e) {
		if ($(this).is(':checked')) {
			$(this).parents().children('.woocommerce_subscriptions_gifting_recipient_email').slideDown( 250 );
		} else {
			$(this).parents().children('.woocommerce_subscriptions_gifting_recipient_email').slideUp( 250 );
			$(this).parents().children('.woocommerce_subscriptions_gifting_recipient_email').val('');
		}
		e.preventDefault();
	});
});
