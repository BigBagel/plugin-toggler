<?php
/*
Plugin Name: Network Plugin Toggler
Plugin URI: http://pito.tamu.edu/
Description: 
Version: 0.1
Network: true
Author: Eric Bakenhus
Author URI: http://pito.tamu.edu/
License: GPL2
*/

if ( realpath( __FILE__ ) === realpath( $_SERVER["SCRIPT_FILENAME"] ) ) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	exit( 'Do not access this file directly.' );
}

/*
register_activation_hook( __FILE__, 'network_plugin_toggler_activate' );

function network_plugin_toggler_activate() {
	if ( ! is_multisite() ) {
		die( 'Sorry, this plugin is only for multisite.' );
	}
}
*/

if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'inc/admin.php');

	if ( class_exists( 'toggler_admin' ) ) {
		global $toggler_admin_instance;
		$toggler_admin_instance = new toggler_admin();
	}
}
?>