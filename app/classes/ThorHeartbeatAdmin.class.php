<?php 
/**
 * Admin Main Class
 *
 * @param void
 *
 * @return void
 */
if (!class_exists('ThorHeartbeatAdmin')) {
	
	class ThorHeartbeatAdmin {

		public function __construct() {
			// Activation and deactivation hook.
    		register_activation_hook(WP_PLUGIN_DIR . '/wp-thor-heartbeat/wp-thor-heartbeat.php',  array($this, 'thor_heartbeat_activate'));
			register_deactivation_hook( WP_PLUGIN_DIR . '/wp-thor-heartbeat/wp-thor-heartbeat.php',  array($this, 'thor_heartbeat_deactivate' ));
			// Admin Menu
			add_action('admin_menu', array($this, 'thor_heartbeat_admin_menu'));
			add_action('admin_init', array($this, 'thor_heartbeat_settings_init'));

			// Software Licensing and Updates
			add_action('admin_init', array($this, 'edd_thor_heartbeat_register_option'));

			// Activate, check or deactivate Licenses
			add_action('admin_init', array($this, 'edd_thor_heartbeat_activate_license'));
			add_action('admin_init', array($this, 'edd_thor_heartbeat_deactivate_license'));
			add_action( 'admin_notices', array($this, 'edd_thor_heartbeat_admin_notices'));

			add_action('wpmu_new_blog',  array($this, 'thor_heartbeat_on_new_blog'), 10, 6); 		
			add_action('activate_blog',  array($this, 'thor_heartbeat_on_new_blog'), 10, 6);
			
			add_action('admin_enqueue_scripts', array($this, 'thor_heartbeat_head') );
			
			add_action('plugins_loaded', array($this, 'thor_heartbeat_load_textdomain'));

			$options = get_option('thor_heartbeat_settings');

			$heartbeat_location  = $options['thor_heartbeat_location'];
			$heartbeat_frequency = $options['thor_heartbeat_frequency'];

			if ( $heartbeat_location == 'disable-heartbeat-everywhere') {
				add_action( 'init', 'wp_thor_stop_heartbeat', 1 );

				function wp_thor_stop_heartbeat() {
					wp_deregister_script('heartbeat');
				}
			} elseif ($heartbeat_location == 'disable-heartbeat-dashboard') {
				add_action( 'init', 'wp_thor_stop_heartbeat', 1 );
				function wp_thor_stop_heartbeat() {
					global $pagenow;

					if ( $pagenow == 'index.php'  )
						wp_deregister_script('heartbeat');
				}
			} elseif ($heartbeat_location == 'allow-heartbeat-post-edit') {

				add_action( 'init', 'wp_thor_stop_heartbeat', 1 );
				function wp_thor_stop_heartbeat() {
					global $pagenow;

					if ( $pagenow != 'post.php' && $pagenow != 'post-new.php' )
						wp_deregister_script('heartbeat');
				}
			}

			if ( is_numeric( $heartbeat_frequency ) ) {
				function heartbeat_frequency( $settings ) {
					global $heartbeat_frequency;
					$settings['interval'] = $heartbeat_frequency;
					return $settings;
				}
				add_filter( 'heartbeat_settings', 'heartbeat_frequency' );
			}

			add_filter('admin_footer_text', array($this, 'thor_heartbeat_admin_footer'));
		}

		/* ***************************** PLUGIN (DE-)ACTIVATION *************************** */

		/**
		 * Run single site / network-wide activation of the plugin.
		 *
		 * @param bool $networkwide Whether the plugin is being activated network-wide.
		 */
		function thor_heartbeat_activate() {

		    $networkwide = ($_SERVER['SCRIPT_NAME'] == '/wp-admin/network/plugins.php')?true:false;

			if ( ! is_multisite() || ! $networkwide ) {
				ThorHeartbeatAdmin::_thor_heartbeat_activate();
			}
			else {
				/* Multi-site network activation - activate the plugin for all blogs */
				ThorHeartbeatAdmin::thor_heartbeat_network_activate_deactivate( true );
			}
		}

		/**
		 * Run single site / network-wide de-activation of the plugin.
		 *
		 * @param bool $networkwide Whether the plugin is being de-activated network-wide.
		 */
		function thor_heartbeat_deactivate() {

		    $networkwide = ($_SERVER['SCRIPT_NAME'] == '/wp-admin/network/plugins.php')?true:false;

			if ( ! is_multisite() || ! $networkwide ) {
				ThorHeartbeatAdmin::_thor_heartbeat_deactivate();
			}
			else {
				/* Multi-site network activation - de-activate the plugin for all blogs */
				ThorHeartbeatAdmin::heartbeat_network_activate_deactivate( false );
			}
		}

		/**
		 * Run network-wide (de-)activation of the plugin
		 *
		 * @param bool $activate True for plugin activation, false for de-activation.
		 */
		function thor_heartbeat_network_activate_deactivate( $activate = true ) {
			global $wpdb;

			$network_blogs = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d", $wpdb->siteid ) );

			if ( is_array( $network_blogs ) && $network_blogs !== array() ) {
				foreach ( $network_blogs as $blog_id ) {
					switch_to_blog( $blog_id );

					if ( $activate === true ) {
						ThorHeartbeatAdmin::_thor_heartbeat_activate();
					}
					else {
						ThorHeartbeatAdmin::_thor_heartbeat_deactivate();
					}

					restore_current_blog();
				}
			}
		}

		/**
		 * On deactivation, flush the rewrite rules so XML sitemaps stop working.
		 */
		function _thor_heartbeat_deactivate() {

		    // Delete Licenses Key
			delete_option('edd_thor_heartbeat_license_key' );
			delete_option('edd_thor_heartbeat_license_status' );

			// Delete plugin options
			delete_option('thor_heartbeat_location' );
			delete_option('thor_heartbeat_frequency' );		

			do_action( 'thor_heartbeat_deactivate' );
		}

		/**
		 * Run activation routine on creation / activation of a multisite blog if WP THOR Heartbeat is activated network-wide.
		 *
		 * Will only be called by multisite actions.
		 *
		 * @internal Unfortunately will fail if the plugin is in the must-use directory
		 * @see      https://core.trac.wordpress.org/ticket/24205
		 *
		 * @param int $blog_id Blog ID.
		 */
		function thor_heartbeat_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

			global $wpdb;

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
		 
			if (is_plugin_active_for_network('wp-thor-heartbeat/wp-thor-heartbeat.php')) {
				$old_blog = $wpdb->blogid;
				switch_to_blog($blog_id);
				ThorHeartbeatAdmin::heartbeat_activate();
				switch_to_blog($old_blog);
			}
		}

		/**
		 * Runs on activation of the plugin.
		 *
		 * @param void
		 *
		 * @return void
		 */
		function _thor_heartbeat_activate() {

			do_action( 'thor_heartbeat_activate' );
		}

		/**
		 * Set The Header
		 *
		 * @param void
		 *
		 * @return void
		 */	
		public function thor_heartbeat_head(){

				wp_enqueue_style( 'thor-heartbeat-admin-style', THORHEARTBEAT_PLUGIN_URL . '/app/views/css/style.css' );
				wp_enqueue_style( 'thor-heartbeat-font-awesome', THORHEARTBEAT_PLUGIN_URL . '/app/views/css/font-awesome.css' );
				wp_enqueue_style( 'thor-heartbeat-bootstrap-style', THORHEARTBEAT_PLUGIN_URL . '/app/views/css/bootstrap.css' );
				wp_enqueue_style( 'thor-heartbeat-bootstrap-theme-style', THORHEARTBEAT_PLUGIN_URL . '/app/views/css/bootstrap-theme.css' );

				wp_enqueue_script( 'thor-heartbeat-bootstrap-js', THORHEARTBEAT_PLUGIN_URL . '/app/views/js/bootstrap.js' );
		}

		/**
		 * Add Admin Menues
		 *
		 * @param void
		 *
		 * @return void
		 */	
		public function thor_heartbeat_admin_menu(){
			add_menu_page ( 'Thor Heartbeat', 'Thor Heartbeat', 'manage_options', 'thor_heartbeat_admin', array($this, 'thor_heartbeat_admin'), plugins_url( 'wp-thor-heartbeat/app/views/images/wp-thor-heartbeat.png' ), 6 );
		}
		
		/**
		 * Set Admin Menues
		 *
		 * @param void
		 *
		 * @return void
		 */			
		public function thor_heartbeat_admin(){
			//current tab
			if (isset($_GET['tab'])){
				$tab = $_GET['tab'];
			} else {
				$tab = 'general_settings';
			}
			
			//url admin
			$url = get_admin_url() . 'admin.php?page=thor_heartbeat_admin';

			//all tabs available
			$tabs_arr = array(
							'general_settings' => 'General Settings',
							'licenses'	=> 'Licenses',
							'support' => 'Support',
							'hireus' => 'Services',
							'pluginsthemes'	=> 'Plugins/Themes'				
						);
			
			//include dashboard header
			require_once THORHEARTBEAT_PLUGIN_PATH . '/app/views/dashboard-head.php';
			
			switch ($tab){
				case 'general_settings':
					require_once THORHEARTBEAT_PLUGIN_PATH . '/app/views/settings.php';
				break;
				case 'licenses':
					require_once THORHEARTBEAT_PLUGIN_PATH . '/app/views/licenses.php';
					break;
				case 'support':
					require_once THORHEARTBEAT_PLUGIN_PATH . '/app/views/support.php';
				break;
				case 'hireus':
					require_once THORHEARTBEAT_PLUGIN_PATH . '/app/views/hireus.php';
				break;
				case 'pluginsthemes':
					require_once THORHEARTBEAT_PLUGIN_PATH . '/app/views/pluginsthemes.php';
				break;
			}
		}

		/**
		 * Set The Settings Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_heartbeat_settings_init(  ) { 

			register_setting('thor-heartbeat-settings', 'thor_heartbeat_settings' );
			add_settings_section( 'thor_heartbeat_settings_section', '', array( $this, 'thor_heartbeat_settings_section_callback' ), 'thor-heartbeat-settings', 'section_general' );
			
			add_settings_field( 'thor_heartbeat_location',  __('Location', 'thor_heartbeat'), array( $this, 'thor_heartbeat_location_render' ), 'thor-heartbeat-settings', 'thor_heartbeat_settings_section' );

			add_settings_field('thor_heartbeat_frequency', __('Frequency', 'thor_heartbeat'), array( $this, 'thor_heartbeat_frequency_render' ), 'thor-heartbeat-settings', 'thor_heartbeat_settings_section');
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_heartbeat_location_render() { 

			$options = get_option('thor_heartbeat_settings');

			$selected = $options['thor_heartbeat_location'];
			
			echo ' <select id="thor_heartbeat_location" name="thor_heartbeat_settings[thor_heartbeat_location]"> ';
			echo ' <option '; 
			if ('use-default' == $selected) echo 'selected="selected"'; 
			echo ' value="use-default">'. __( 'Use default', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('disable-heartbeat-everywhere' == $selected) echo 'selected="selected"'; 
			echo ' value="disable-heartbeat-everywhere">'. __( 'Disable everywhere', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('disable-heartbeat-dashboard' == $selected) echo 'selected="selected"'; 
			echo ' value="disable-heartbeat-dashboard">'. __( 'Disable on dashboard page', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('allow-heartbeat-post-edit' == $selected) echo 'selected="selected"'; 
			echo ' value="allow-heartbeat-post-edit">'. __( 'Allow only on post edit pages', 'thor-heartbeat' ) .'</option>';

			echo '</select>';

			if (isset($this->options['thor_heartbeat_location'])) {
				esc_attr( $this->options['thor_heartbeat_location']);
			}
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_heartbeat_frequency_render() { 

			$options = get_option('thor_heartbeat_settings');

			$selected = $options['thor_heartbeat_frequency'];
			
			echo ' <select id="thor_heartbeat_frequency" name="thor_heartbeat_settings[thor_heartbeat_frequency]"> ';
			echo ' <option '; 
			if ('use-default' == $selected) echo 'selected="selected"'; 
			echo ' value="use-default">'. __( 'default', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('15' == $selected) echo 'selected="selected"'; 
			echo ' value="15">'. __( '15 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('20' == $selected) echo 'selected="selected"'; 
			echo ' value="20">'. __( '20 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('25' == $selected) echo 'selected="selected"'; 
			echo ' value="25">'. __( '25 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('30' == $selected) echo 'selected="selected"'; 
			echo ' value="30">'. __( '30 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('35' == $selected) echo 'selected="selected"'; 
			echo ' value="35">'. __( '35 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('40' == $selected) echo 'selected="selected"'; 
			echo ' value="40">'. __( '40 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('45' == $selected) echo 'selected="selected"'; 
			echo ' value="45">'. __( '45 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('50' == $selected) echo 'selected="selected"'; 
			echo ' value="50">'. __( '50 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('55' == $selected) echo 'selected="selected"'; 
			echo ' value="55">'. __( '55 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '<option '; 
			if ('60' == $selected) echo 'selected="selected"'; 
			echo ' value="60">'. __( '60 Seconds', 'thor-heartbeat' ) .'</option>';

			echo '</select>';

			if (isset($this->options['thor_heartbeat_frequency'])) {
				esc_attr( $this->options['thor_heartbeat_frequency']);
			}
		}

		/**
		 * Settings Section Callbck
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_heartbeat_settings_section_callback() { 
			echo __('Set your settings for the plugin', 'thor_heartbeat');
		}

		/**
		 * load the translations
		 *
		 * @param void
		 *
		 * @return void
		 */
		function thor_heartbeat_load_textdomain() {
			load_plugin_textdomain('thor_heartbeat', false, basename(dirname( __FILE__ )).'/lang'); 
		}

		/**
		 * Admin Footer.
		 *
		 * @param void
		 *
		 * @return void
		 */
		function thor_heartbeat_admin_footer() {
			global $pagenow;
			
			if ($pagenow == 'admin.php') {
				$page = $_GET['page'];
				switch($page) {
					case 'thor_heartbeat_admin':
						echo "<div class=\"social-links alignleft\"><i>Created by <a href=\"http://thunderbeardesign.com\" target=\"_blank\">ThunderBear Design</a></i>
						<a href=\"http://twitter.com/tbearmarketing\" class=\"twitter\" target=\"_blank\"><span
						class=\"dashicons dashicons-twitter\"></span></a>
						<a href=\"fb.me/thunderbeardesign\" class=\"facebook\"
				   target=\"_blank\"><span class=\"dashicons dashicons-facebook\"></span></a>
						<a href=\"https://thunderbeardesign.com/feed/\" class=\"rss\" target=\"_blank\"><span
						class=\"dashicons dashicons-rss\"></span></a>
						</div>";
						break;
					default:
						return;
				}
			}
		}

		/**
		 * Write debug info as a text file and download it.
		 *
		 * @param void
		 *
		 * @return void
		 */
		public function download_debuginfo_as_text() {

			global $wpdb, $wp_version;
			$debug_info = array();
			$debug_info['Home URL'] = esc_url( home_url() );
			$debug_info['Site URL'] = esc_url( site_url() );
			$debug_info['PHP'] = esc_html( PHP_VERSION );
			$debug_info['MYSQL'] = esc_html( $wpdb->db_version() );
			$debug_info['WordPress'] = esc_html( $wp_version );
			$debug_info['OS'] = esc_html( PHP_OS );
			if ( extension_loaded( 'imagick' ) ) {
				$imagickobj = new Imagick();
				$imagick    = $message = preg_replace( " #((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#i", "'<a href=\"$1\" target=\"_blank\">$3</a>$4'", $imagickobj->getversion() );
			} else {
				$imagick['versionString'] = 'Not Installed';
			}
			$debug_info['Imagick'] = $imagick['versionString'];
			if ( extension_loaded( 'gd' ) ) {
				$gd = gd_info();
			} else {
				$gd['GD Version'] = 'Not Installed';
			}
			$debug_info['GD'] = esc_html( $gd['GD Version'] );
			$debug_info['[php.ini] post_max_size'] = esc_html( ini_get( 'post_max_size' ) );
			$debug_info['[php.ini] upload_max_filesize'] = esc_html( ini_get( 'upload_max_filesize' ) );
			$debug_info['[php.ini] memory_limit'] = esc_html( ini_get( 'memory_limit' ) );
			$active_theme = wp_get_theme();
			$debug_info['Theme Name'] = esc_html( $active_theme->Name );
			$debug_info['Theme Version'] = esc_html( $active_theme->Version );
			$debug_info['Author URL'] = esc_url( $active_theme->{'Author URI'} );

			$heartbeat_options = get_option( 'thor_heartbeat_settings' );
			$heartbeat_options = array_merge( $debug_info, $heartbeat_options );
			if( ! empty( $heartbeat_options ) ) {

				$url = wp_nonce_url('admin.php?page=thor_heartbeat_admin&tab=support&subtab=debuginfo','thor-debuginfo');
				if (false === ($creds = request_filesystem_credentials($url, '', false, false, null)) ) {
					return true;
				}
				
				if (!WP_Filesystem($creds)) {
					request_filesystem_credentials($url, '', true, false, null);
					return true;
				}
				
				global $wp_filesystem;
				$contentdir = trailingslashit($wp_filesystem->wp_content_dir());
				
				$in = '==============================================================================' . PHP_EOL;
				$in .= '================================== Debug Info ================================' . PHP_EOL;
				$in .=  '==============================================================================' . PHP_EOL . PHP_EOL . PHP_EOL;

				foreach ( $heartbeat_options as $option => $value ) {
					$in .= ucwords( str_replace( '_', ' ', $option ) ) . str_repeat( ' ', 50 - strlen($option) ) . wp_strip_all_tags( $value ) . PHP_EOL;
				}

				mb_convert_encoding($in, "ISO-8859-1", "UTF-8");
				
				if(!$wp_filesystem->put_contents($contentdir.'heartbeat_debuginfo.txt', $in, FS_CHMOD_FILE)) {
					echo 'Failed saving file';
				}
				return content_url()."/heartbeat_debuginfo.txt"; 
			}
		}

		/**
		 * Show debug_info.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return void
		 */
		public function debug_info() {
			global $wpdb, $wp_version;
			$debug_info               = array();
			$debug_info['Home URL']   = esc_url( home_url() );
			$debug_info['Site URL']   = esc_url( site_url() );
			$debug_info['PHP']        = esc_html( PHP_VERSION );
			$debug_info['MYSQL']      = esc_html( $wpdb->db_version() );
			$debug_info['WordPress']  = esc_html( $wp_version );
			$debug_info['OS']         = esc_html( PHP_OS );
			if ( extension_loaded( 'imagick' ) ) {
				$imagickobj = new Imagick();
				$imagick    = $message = preg_replace( " #((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#i", "'<a href=\"$1\" target=\"_blank\">$3</a>$4'", $imagickobj->getversion() );
			} else {
				$imagick['versionString'] = 'Not Installed';
			}
			$debug_info['Imagick'] = $imagick['versionString'];
			if ( extension_loaded( 'gd' ) ) {
				$gd = gd_info();
			} else {
				$gd['GD Version'] = 'Not Installed';
			}
			$debug_info['GD']                            = esc_html( $gd['GD Version'] );
			$debug_info['[php.ini] post_max_size']       = esc_html( ini_get( 'post_max_size' ) );
			$debug_info['[php.ini] upload_max_filesize'] = esc_html( ini_get( 'upload_max_filesize' ) );
			$debug_info['[php.ini] memory_limit']        = esc_html( ini_get( 'memory_limit' ) );
			$debug_info['Installed Plugins']             = $this->get_plugin_info();
			$active_theme                                = wp_get_theme();
			$debug_info['Theme Name']                    = esc_html( $active_theme->Name );
			$debug_info['Theme Version']                 = esc_html( $active_theme->Version );
			$debug_info['Author URL']                    = esc_url( $active_theme->{'Author URI'} );

			/* get all Settings */
			$heartbeat_options = get_option( 'thor_heartbeat_settings' );
			if ( is_array( $heartbeat_options ) ) {
				foreach ( $heartbeat_options as $option => $value ) {
					$debug_info[ ucwords( str_replace( '_', ' ', $option ) ) ] = $value;
				}
			}

			$this->debug_info = $debug_info;
		}

		/**
		 * Get plugin_info.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return array $rtmedia_plugins
		 */
		public function get_plugin_info() {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			$heartbeat_plugins = array();
			foreach ( $active_plugins as $plugin ) {
				$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				$version_string = '';
				if ( ! empty( $plugin_data['Name'] ) ) {
					$heartbeat_plugins[] = esc_html( $plugin_data['Name'] ) . ' ' . esc_html__( 'by', 'heartbeat' ) . ' ' . $plugin_data['Author'] . ' ' . esc_html__( 'version', 'heartbeat' ) . ' ' . $plugin_data['Version'] . $version_string;
				}
			}
			if ( 0 === count( $heartbeat_plugins ) ) {
				return false;
			} else {
				return implode( ', <br/>', $heartbeat_plugins );
			}
		}
		// End of Standard Code Strings

		function edd_thor_heartbeat_register_option() {
			// creates our settings in the options table
			register_setting('edd_thor_heartbeat_license', 'edd_thor_heartbeat_license_key', array($this, 'edd_thor_heartbeat_sanitize_license'));
		}

		function edd_thor_heartbeat_sanitize_license( $new ) {
			$old = get_option( 'edd_thor_heartbeat_license_key' );
			if( $old && $old != $new ) {
				delete_option( 'edd_thor_heartbeat_license_status' ); 
				// new license has been entered, so must reactivate
			}
			return $new;
		}

		/************************************
		* this illustrates how to activate a license key
		*************************************/

		function edd_thor_heartbeat_activate_license() {

			// listen for our activate button to be clicked
			if( isset( $_POST['edd_thor_heartbeat_license_activate'] ) ) {

				// run a quick security check
			 	if( ! check_admin_referer( 'edd_thor_heartbeat_nonce', 'edd_thor_heartbeat_nonce' ) )
					return; // get out if we didn't click the Activate button

				// retrieve the license from the database
				$license = trim( get_option( 'edd_thor_heartbeat_license_key' ) );


				// data to send in our API request
				$api_params = array(
					'edd_action' => 'activate_license',
					'license'    => $license,
					'item_name'  => urlencode( THORHEARTBEAT_SL_ITEM_NAME ), // the name of our product in EDD
					'url'        => home_url()
				);

				// Call the custom API.
				$response = wp_remote_post( THORHEARTBEAT_SL_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

				// make sure the response came back okay
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message();
					} else {
						$message = __( 'An error occurred, please try again.' );
					}
				} else {
					$license_data = json_decode( wp_remote_retrieve_body( $response ) );
					if ( false === $license_data->success ) {
						switch( $license_data->error ) {
							case 'expired' :
								$message = sprintf(
									__( 'Your license key expired on %s.' ),
									date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
								);
								break;
							case 'revoked' :
								$message = __( 'Your license key has been disabled.' );
								break;
							case 'missing' :
								$message = __( 'Invalid license.' );
								break;
							case 'invalid' :
							case 'site_inactive' :
								$message = __( 'Your license is not active for this URL.' );
								break;
							case 'item_name_mismatch' :
								$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), THORHEARTBEAT_SL_ITEM_NAME );
								break;
							case 'no_activations_left':
								$message = __( 'Your license key has reached its activation limit.' );
								break;
							default :

								$message = __( 'An error occurred, please try again.' );
								break;
						}
					}
				}

				// Check if anything passed on a message constituting a failure
				if ( ! empty( $message ) ) {
					$base_url = admin_url( 'plugins.php?page=' . THORHEARTBEAT_PLUGIN_LICENSE_PAGE );
					$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

					wp_redirect( $redirect );
					exit();
				}

				// $license_data->license will be either "valid" or "invalid"

				update_option( 'edd_thor_heartbeat_license_status', $license_data->license );
				wp_redirect( admin_url( 'plugins.php?page=' . THORHEARTBEAT_PLUGIN_LICENSE_PAGE ) );
				exit();
			}
		}


		/***********************************************
		* Illustrates how to deactivate a license key.
		* This will decrease the site count
		***********************************************/

		function edd_thor_heartbeat_deactivate_license() {

			// listen for our activate button to be clicked
			if( isset( $_POST['edd_license_deactivate'] ) ) {

				// run a quick security check
			 	if( ! check_admin_referer( 'edd_thor_heartbeat_nonce', 'edd_thor_heartbeat_nonce' ) )
					return; // get out if we didn't click the Activate button

				// retrieve the license from the database
				$license = trim( get_option( 'edd_thor_heartbeat_license_key' ) );


				// data to send in our API request
				$api_params = array(
					'edd_action' => 'deactivate_license',
					'license'    => $license,
					'item_name'  => urlencode( THORHEARTBEAT_SL_ITEM_NAME ), // the name of our product in EDD
					'url'        => home_url()
				);

				// Call the custom API.
				$response = wp_remote_post( THORHEARTBEAT_SL_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

				// make sure the response came back okay
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message();
					} else {
						$message = __( 'An error occurred, please try again.' );
					}

					$base_url = admin_url( 'plugins.php?page=' . THORHEARTBEAT_PLUGIN_LICENSE_PAGE );
					$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

					wp_redirect( $redirect );
					exit();
				}

				// decode the license data
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				// $license_data->license will be either "deactivated" or "failed"
				if( $license_data->license == 'deactivated' ) {
					delete_option( 'edd_thor_heartbeat_license_status' );
				}

				wp_redirect( admin_url( 'plugins.php?page=' . THORHEARTBEAT_PLUGIN_LICENSE_PAGE ) );
				exit();
			}
		}
		/**
		 * This is a means of catching errors from the activation method above and displaying it to the customer
		 */
		public function edd_thor_heartbeat_admin_notices() {
			if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

				switch( $_GET['sl_activation'] ) {

					case 'false':
						$message = urldecode( $_GET['message'] );
						?>
						<div class="error">
							<p><?php echo $message; ?></p>
						</div>
						<?php
						break;

					case 'true':
					default:
						// Developers can put a custom success message here for when activation is successful if they way.
						break;

				}
			}
		}
  	} //end of class
} //end of if class exists