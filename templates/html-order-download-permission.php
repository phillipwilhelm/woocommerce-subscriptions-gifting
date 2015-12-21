<div class="wc-metabox closed">
	<h3 class="fixed">
		<button type="button" rel="<?php echo absint( $download->product_id ) . ',' . esc_attr( $download->download_id ) . ',' . esc_attr( $download->user_id ); ?>" class="wcsg_revoke_access button"><?php esc_html_e( 'Revoke Access', 'woocommerce-subscriptions-gifting' ); ?></button>
		<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'woocommerce-subscriptions-gifting' ); ?>"></div>
		<strong>
			<?php echo '#' . absint( $product->id ) . ' &mdash; ' . esc_html( apply_filters( 'woocommerce_admin_download_permissions_title', $product->get_title(), $download->product_id, $download->order_id, $download->order_key, $download->download_id ) ) . ' &mdash; ' . esc_html( sprintf( __( '%s: %s', 'woocommerce-subscriptions-gifting' ), $file_count, wc_get_filename_from_url( $product->get_file_download_path( $download->download_id ) ) ) ) . ' &mdash; ' . esc_html( sprintf( _n( 'Downloaded %s time', 'Downloaded %s times', absint( $download->download_count ), 'woocommerce-subscriptions-gifting' ), absint( $download->download_count ) ) ); ?>
		</strong>
	</h3>
	<table cellpadding="0" cellspacing="0" class="wc-metabox-content">
		<tbody>
			<tr>
				<td>
					<label><?php esc_html_e( 'Downloads Remaining', 'woocommerce-subscriptions-gifting' ); ?>:</label>
					<input type="hidden" name="product_id[<?php esc_attr_e( $download->user_id ); ?>][<?php esc_attr_e( $loop ); ?>]" value="<?php echo absint( $download->product_id ); ?>" />
					<input type="hidden" name="download_id[<?php esc_attr_e( $download->user_id ); ?>][<?php esc_attr_e( $loop ); ?>]" value="<?php echo esc_attr( $download->download_id ); ?>" />
					<input type="number" step="1" min="0" class="short" name="downloads_remaining[<?php esc_attr_e( $download->user_id ); ?>][<?php esc_attr_e( $loop ); ?>]" value="<?php echo esc_attr( $download->downloads_remaining ); ?>" placeholder="<?php esc_attr_e( 'Unlimited', 'woocommerce-subscriptions-gifting' ); ?>" />
				</td>
				<td>
					<label><?php esc_html_e( 'Access Expires', 'woocommerce-subscriptions-gifting' ); ?>:</label>
					<input type="text" class="short date-picker" name="access_expires[<?php esc_attr_e( $download->user_id ); ?>][<?php esc_attr_e( $loop ); ?>]" value="<?php esc_attr_e( $download->access_expires > 0 ? date_i18n( 'Y-m-d', strtotime( $download->access_expires ) ) : '' ); ?>" maxlength="10" placeholder="<?php esc_attr_e( 'Never', 'woocommerce-subscriptions' ); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
				</td>
			</tr>
		</tbody>
	</table>
</div>
