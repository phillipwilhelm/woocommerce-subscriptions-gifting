jQuery(document).ready(function($){

	$(".wc-metaboxes").on('click', '.revoke_access',function() {
		var permission_id = $(this).siblings().find('.wcsg_download_permission_id').val();
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
