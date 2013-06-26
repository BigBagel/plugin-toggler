<?php
	if ( realpath( __FILE__ ) === realpath( $_SERVER["SCRIPT_FILENAME"] ) ) {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		exit( 'Do not access this file directly.' );
	}

	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		exit();
	}
	
	/* Remove the database entry */
	delete_site_option( 'toggler_options' );
?>