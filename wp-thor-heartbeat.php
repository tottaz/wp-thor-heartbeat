<?php
/*
Plugin Name: WP Thor Heartbeat
Plugin URI:
Description: Controles the WP heartbeat
Version: 1.2
Author: ThunderBear Design
Author URI: http://thunderbeardesign.com

Build: 1.2
*/

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.0 403 Forbidden' );
    echo 'This file should not be accessed directly!';
    exit; // Exit if accessed directly
}

//
define('THORHEARTBEAT_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));
define('THORHEARTBEAT_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)));
define('THORHEARTBEAT_PLUGIN_FILE_PATH', WP_PLUGIN_DIR . '/' . plugin_basename(__FILE__));
define('THORHEARTBEAT_SL_STORE_URL', 'https://thunderbeardesign.com' ); 
define('THORHEARTBEAT_SL_ITEM_NAME', 'WP Thor Heartbeat' );
// the name of the settings page for the license input to be displayed
define('THORHEARTBEAT_PLUGIN_LICENSE_PAGE', 'thor_heartbeat_admin&tab=licenses' );

if( !class_exists( 'EDDEDDHEARTBEAT_SL_Plugin_Updater' ) ) {
	// load our custom updater
	require_once THORHEARTBEAT_PLUGIN_PATH . '/app/edd-include/EDDEDDHEARTBEAT_SL_Plugin_Updater.php';
}

// retrieve our license key from the DB
$license_key = trim( get_option( 'edd_thor_heartbeat_license_key' ) );
// setup the updater
$edd_updater = new EDDEDDHEARTBEAT_SL_Plugin_Updater( THORHEARTBEAT_SL_STORE_URL, __FILE__, array( 
		'version' 	=> '1.2', 			// current version number
		'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name'	=> urlencode( THORHEARTBEAT_SL_ITEM_NAME ), 	// name of this plugin
		'author' 	=> 'ThunderBear Design',  // author of this plugin
		'url'      	=> home_url()
	)
);

//Load The Admin Class
if (!class_exists('ThorHeartbeatAdmin')) {
    require_once THORHEARTBEAT_PLUGIN_PATH . '/app/classes/ThorHeartbeatAdmin.class.php';
}

$obj = new ThorHeartbeatAdmin(); //initiate admin object    

?>