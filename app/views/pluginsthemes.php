<?php
if(isset($_GET['subtab'])) $subtab=$_GET['subtab'];
else $subtab ='';
?>
<div class="thor-subtab-menu">
	<a class="thor-subtab-menu-item" href="<?php echo $url.'&tab=pluginsthemes&subtab=plugins';?>"> Plugins</a>
	<a class="thor-subtab-menu-item" href="<?php echo $url.'&tab=pluginsthemes&subtab&subtab=themes';?>">Themes</a>
</div>
<?php


if(!current_user_can('manage_options')) {
	wp_die(__('You have not sufficient capabilities to view this page', 'fcm'), __('Get the right permission', 'fcm'));
}

?>
<div class="thor-stuffbox" style="margin-top: 50px;">
	<h3 class="thor-h3"><?php _e('ThunderBear Design › Plugins and Themes','fcm'); ?></h3>		

	<div class="thor-help-wrap">
		<?php switch($subtab) { 
		    case 'themes': ?>
				<div class="thor-stuffbox" style="margin-top: 50px;">
					<h3 class="thor-h3">Themes</h3>
						<h3>Here you find a list of Themes from ThunderBear Design Inc</h3>
					<div class="inside">
						<iframe src="https://thunderbeardesign.com/products/themes/" width="100%" height="1000px"></iframe>
					</div>
				</div>
			<?php break; ?>	
			<?php default: ?>	
				<div class="thor-stuffbox" style="margin-top: 50px;">
					<h3 class="thor-h3">Plugins</h3>
					<div class="inside">
							<h3>Here you find a list plugins from ThunderBear Design Inc</h3>
						<div class="inside">
							<iframe src="https://thunderbeardesign.com/products/plugins/" width="100%" height="1000px"></iframe>
						</div>
						<div class="clear"></div>
					</div>
				</div>			
			<?php
			} ?>
	</div>
</div>