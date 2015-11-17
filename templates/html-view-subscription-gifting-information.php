<tr>
	<th><?php echo esc_html( $user_title ) ?>:</th>
	<td data-title="<?php echo esc_attr( $user_title ) ?>"> <?php echo wp_kses( $name, wp_kses_allowed_html( 'user_description' ) ); ?></td>
</tr>
