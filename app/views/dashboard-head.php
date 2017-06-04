<div class="thor-dashboard-wrap">
	<div class="thor-admin-header">
		<div class="thor-top-menu-section">
			<div class="thor-dashboard-logo">
				<div class="thor-dashboard-text text-center">
					<a href="<?php echo $url.'&tab=dashboard';?>">
						<img src="<?php echo THORHEARTBEAT_PLUGIN_URL;?>/app/views/images/wp-thor-logo.png"/>
					</a>
				</div>
			</div>
			<div class="thor-dashboard-menu">
				<ul>
				<?php 
					foreach($tabs_arr as $k=>$v){
						$selected = '';
						if($tab==$k) $selected = 'selected';						
						?>
							<li class="<?php echo $selected;?>">
								<a href="<?php echo $url.'&tab='.$k;?>">
									<div class="thor-page-title">
										<i class="fa-thor fa-thor-menu fa-<?php echo $k;?>-thor"></i>
										<div><?php echo $v;?></div>								
									</div>						
								</a>
							</li>	
						<?php 	
					}
				?>		
				</ul>
			</div>
		</div>
	</div>
</div>