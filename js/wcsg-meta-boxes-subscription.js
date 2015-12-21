jQuery(document).ready(function($){
	$(document).on('click', 'button.wcsg_revoke_access',function() {
		if ( window.confirm( woocommerce_admin_meta_boxes.i18n_permission_revoke ) ) {
			var download_div             = $( this ).parent().parent();
			var user_download_container  = $( this ).closest( ".wcsg_user_downloads_container" );
			var number_of_user_downloads = user_download_container.children().length - 1;

			var product = $( this ).attr( 'rel' ).split( ',' )[0];
			var file    = $( this ).attr( 'rel' ).split( ',' )[1];
			var user    = $( this ).attr( 'rel' ).split( ',' )[2];

			if ( product > 0 ) {
				if ( number_of_user_downloads == 1 ) {
					$( user_download_container ).block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
				} else {
					$( download_div ).block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
				}


				var data = {
					action:      'wcsg_revoke_access_to_download',
					product_id:  product,
					download_id: file,
					user_id:     user,
					order_id:    woocommerce_admin_meta_boxes.post_id,
					nonce:       woocommerce_admin_meta_boxes.revoke_access_nonce
				};

				$.post( woocommerce_admin_meta_boxes.ajax_url, data, function() {
					// Success
					if ( number_of_user_downloads == 1 ) {
						$( user_download_container ).fadeOut( '300', function () {
							$( user_download_container ).remove();
						});
					} else {
						$( download_div ).fadeOut( '300', function () {
							$( download_div ).remove();
						});
					}
				});

			} else {
				if ( number_of_user_downloads == 1 ) {
					$( user_download_container ).fadeOut( '300', function () {
						$( user_label ).remove();
					});
				} else {
					$( download_div ).fadeOut( '300', function () {
						$( download_div ).remove();
					});
				}
			}
		}
		return false;
	});
});
