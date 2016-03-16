jQuery(document).ready(function($){

	$( ".revoke_access" ).click(function() {
		var download_permission_index = $(this).parent().next().find("input[name^=downloads_remaining]").attr('name').match(/\d+/g);
		var permission_id = $("#wcsg_download_permission_ids_" + download_permission_index).val();
		var post_id = $("#post_ID").val();

		if (  0 < permission_id ) {
			var data = {
				action: 'wcsg_revoke_access_to_download',
				post_id: post_id,
				download_permission_id: permission_id,
				nonce: wcs_gifting.revoke_download_permission_nonce
			};

			$.ajax({
			url:  wcs_gifting.ajax_url,
			data: data,
			type: 'POST',
		});
		}

	});
});
