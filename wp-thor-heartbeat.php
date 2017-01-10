<?php
/*
Plugin Name: WP Thor Heartbeat
Plugin URI:
Description: Controles the WP heartbeat
Version: 1.0.0
Author: ThunderBear Design
Author URI: http://thunderbeardesign.com

Build: 1.0.0
*/

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.0 403 Forbidden' );
    echo 'This file should not be accessed directly!';
    exit; // Exit if accessed directly
}

//
define('THORHEARTBEAT_VERSION', '1.0.0');
define('THORHEARTBEAT_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));
define('THORHEARTBEAT_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)));
define('THORHEARTBEAT_PLUGIN_FILE_PATH', WP_PLUGIN_DIR . '/' . plugin_basename(__FILE__));

//Load The Admin Class
if (!class_exists('ThorHeartbeatAdmin')) {
    require_once THORHEARTBEAT_PLUGIN_PATH . '/app/classes/ThorHeartbeatAdmin.class.php';
}

$obj = new ThorHeartbeatAdmin(); //initiate admin object    

?>