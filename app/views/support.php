<?php
if(isset($_GET['subtab'])) $subtab=$_GET['subtab'];
else $subtab ='';
?>
<div class="thor-subtab-menu">
	<a class="thor-subtab-menu-item" href="<?php echo $url.'&tab=support&subtab=support';?>"> Support</a>
	<a class="thor-subtab-menu-item" href="<?php echo $url.'&tab=support&subtab=documentation';?>">Documentation</a>
	<a class="thor-subtab-menu-item" href="<?php echo $url.'&tab=support&subtab=debuginfo';?>">Debug Info</a>
</div>
<?php

if(!current_user_can('manage_options')) {
	wp_die(__('You have not sufficient capabilities to view this page', 'thor-heartbeat'), __('Get the right permission', 'thor-heartbeat'));
}

?>
<div class="thor-help-wrap">
	<?php switch($subtab) { 
	    case 'documentation': ?>
			<div class="thor-stuffbox" style="margin-top: 50px;">
				<h3 class="thor-h3">Documentation</h3>
				<div class="inside">
					<iframe src="https://thunderbeardesign.com/productdocumentation/wp-thor-heartbeat/" width="100%" height="1000px"></iframe>
				</div>
			</div>
		<?php break; ?>	
	   <?php case 'debuginfo': ?>
			<div class="thor-stuffbox" style="margin-top: 50px;">
				<h3 class="thor-h3">Debug Info</h3>
				<div class="inside">
					<?php
					$this->debug_info();
					$allowed_html = array(
						'a' => array(
							'href' => array(),
							),
						'br' => array(),
						);
					?>
					<div id="debug-info" class="option-wrapper">
					<table class="form-table rtm-debug-info">
						<tbody>
						<?php
						if ( $this->debug_info ) {
							foreach ( $this->debug_info as $configuration => $value ) {
								?>
								<tr>
								<th scope="row"><?php echo esc_html( $configuration ); ?></th>
								<td><?php echo wp_kses( $value, $allowed_html ); ?></td>
								</tr><?php
							}
						}
						?>
						</tbody>
					</table>
					<?php if( isset($_POST['ok']) ) {
						$li = ThorHeartbeatadmin::download_debuginfo_as_text();
						?>
						<div id="message" class="updated">
							<p><strong><?php _e('Debug info completed', 'thor-heartbeat'); ?></strong></p>
							<p><?php printf( __('%1$s click here %2$s to download', 'thor-heartbeat'),'<a href='.$li.' download>' ,'</a>'); ?></p>
						</div>
					<?php } ?>

						<form method="post" action="#">
							<p>
								<input type="hidden" name="ok" value="ok">
							</p>
							
							<p>
								<?php submit_button(__('Create Debug Info', 'thor-heartbeat')); ?>
							</p>
						</form>
					</div>
				</div>
			</div>
		<?php break; ?>
		<?php default: ?>	
			<div class="thor-stuffbox" style="margin-top: 50px;">
				<h3 class="thor-h3">Contact Suport</h3>
				<div class="inside">
					<div class="submit" style="float:left; width:80%;">
						In order to contact ThunderBear Design support team you need to create a ticket providing all the necessary details via our support system: thunderbeardesign.com/support/
					</div>
					<div class="submit" style="float:left; width:20%; text-align:center;">
						<a href="https://thunderbeardesign.com/support/" target="_blank" class="button button-primary button-large"> Submit Ticket</a>
					</div>
					<div class="clear"></div>
				</div>
				<div class="inside">
					<iframe src="https://thunderbeardesign.com/support/" width="100%" height="1000px"></iframe>
				</div>				
			</div>			
		<?php
		} ?>
</div>		