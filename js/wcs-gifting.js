jQuery(document).ready(function($){
  $('.woocommerce_subscription_gifting_checkbox').change(function(){
  //$( '.woocommerce_subscription_gifting_checkbox').click( function(){

    console.log("Checkbox Changed");
    if ($(this).is(':checked')) {
      $(this).parents().children('.woocommerce_subscriptions_gifting_recipient_email').slideDown( 250 );
      console.log("Showing Email");
    } else {
      $(this).parents().children('.woocommerce_subscriptions_gifting_recipient_email').slideUp( 250 );
      console.log("Hiding Email");
    }
  });
});
