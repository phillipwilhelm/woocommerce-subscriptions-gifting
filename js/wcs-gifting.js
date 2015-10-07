jQuery(document).ready(function($){
	$(document).on('click', '.woocommerce_subscription_gifting_checkbox',function() {
		if ($(this).is(':checked')) {
			$(this).siblings('.woocommerce_subscriptions_gifting_recipient_email').slideDown( 250 );
		} else {
			$(this).siblings('.woocommerce_subscriptions_gifting_recipient_email').slideUp( 250 );
			$(this).parent().find('.recipient_email').val('');
		}
	});
});
