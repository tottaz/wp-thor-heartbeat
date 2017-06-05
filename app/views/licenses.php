<?php
$license = get_option( 'edd_thor_heartbeat_license_key' );
$status  = get_option( 'edd_thor_heartbeat_license_status' );
?>

<div class="thor-settings-wrap">
	<div class="thor-stuffbox" style="margin-top: 50px;">
		<h3 class="thor-h3"><?php _e('Plugin License'); ?></h3>
		<div class="inside">
			<form method="post" action="options.php">

				<?php settings_fields('edd_thor_heartbeat_license'); ?>

				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('License Key'); ?>
							</th>
							<td>
								<input id="edd_thor_heartbeat_license_key" name="edd_thor_heartbeat_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
								<label class="description" for="edd_thor_heartbeat_license_key"><?php _e('Enter your license key'); ?></label>
							</td>
						</tr>
						<?php if( false !== $license ) { ?>
							<tr valign="top">
								<th scope="row" valign="top">
									<?php _e('Activate License'); ?>
								</th>
								<td>
									<?php if( $status !== false && $status == 'valid' ) { ?>
										<span style="color:green;"><?php _e('active'); ?></span>
										<?php wp_nonce_field( 'edd_thor_heartbeat_nonce', 'edd_thor_heartbeat_nonce' ); ?>
										<input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
									<?php } else {
										wp_nonce_field( 'edd_thor_heartbeat_nonce', 'edd_thor_heartbeat_nonce' ); ?>
										<input type="submit" class="button-secondary" name="edd_thor_heartbeat_license_activate" value="<?php _e('Activate License'); ?>"/>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
				<?php submit_button(); ?>

			</form>
		</div>
	</div>
</div>