<fieldset>
	<input type="checkbox" id="gifting_<?php echo esc_attr( $id ); ?>_option" class="woocommerce_subscription_gifting_checkbox" value="gift" <?php echo esc_attr( ( empty( $email ) ) ? '' : 'checked' ); ?> />
	<?php echo esc_html( apply_filters( 'wcsg_enable_gifting_checkbox_label', get_option( WCSG_Admin::$option_prefix . '_gifting_checkbox_text', __( 'This is a gift', 'woocommerce_subscriptions_gifting' ) ) ) ); ?> <br />
	<p class="form-row form-row <?php echo esc_attr( implode( ' ', $email_field_args['class'] ) ); ?>" style="<?php echo esc_attr( implode( '; ', $email_field_args['style_attributes'] ) ); ?>">
		<label for="recipient_email[<?php echo esc_attr( $id ); ?>]">
			<?php esc_html_e( "Recipient's Email Address:", 'woocommerce-subscriptions-gifting' ); ?>
		</label>
		<input type="email" class="input-text recipient_email" name="recipient_email[<?php echo esc_attr( $id ); ?>]" id="recipient_email[<?php echo esc_attr( $id ); ?>]" placeholder="<?php echo esc_attr( $email_field_args['placeholder'] ); ?>" value="<?php echo esc_attr( $email ); ?>"/>
		<?php wp_nonce_field( 'wcsg_add_recipient', '_wcsgnonce' ); ?>
	</p>
</fieldset>
