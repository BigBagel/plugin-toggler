<?php
/*
Plugin Name: Plugin Toggler
Plugin URI: http://pito.tamu.edu/
Description: Allows for mass plugin deactivation/reactivation.
Version: 1.1
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

define( 'TOGGLER_BASE', plugin_basename( __FILE__ ) );
define( 'TOGGLER_ABSPATH', __FILE__ );

if ( is_admin() ) {
	if ( ! class_exists( 'Toggler_Admin' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'inc/admin.php');
	}

	global $toggler_admin_instance;
	$toggler_admin_instance = new Toggler_Admin();
}
?>