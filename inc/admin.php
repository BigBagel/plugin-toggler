<?php

if ( realpath( __FILE__ ) === realpath( $_SERVER["SCRIPT_FILENAME"] ) ) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	exit( 'Do not access this file directly.' );
}

class toggler_admin {

	protected $page_hook;

	public function __construct() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'add_network_admin_page' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function add_network_admin_page() {
		$this->page_hook = add_plugins_page( 'Plugin Toggler', 'Toggler', 'update_plugins', 'plugin_toggler', array( $this, 'network_options_page' ) );
	}

	public function add_admin_page() {
		$this->page_hook = add_plugins_page( 'Plugin Toggler', 'Toggler', 'update_plugins', 'plugin_toggler', array( $this, 'options_page' ) );
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( $hook != $this->page_hook ) {
			return;
		}
		wp_enqueue_style( 'toggler_admin_styles', plugins_url( 'styles/admin.css', dirname( __FILE__ ) ) );
	}

	public function network_options_page() {
		require_once( 'network-options-page.php' );
	}

	public function options_page() {
		require_once( 'options-page.php' );
	}
}

?>