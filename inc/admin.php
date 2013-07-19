<?php
if ( realpath( __FILE__ ) === realpath( $_SERVER["SCRIPT_FILENAME"] ) ) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	exit( 'Do not access this file directly.' );
}

/**
 * Main plugin class
 *
 * @since 0.1
 * @package PluginToggler
 *
 * @param string $page_hook Contains the plugin's admin page's hook
 */
class Toggler_Admin {

	protected $page_hook;

	public function __construct() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'add_network_admin_page' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
 	 * Adds plugin page and submenu item to network plugin menu 
 	 *
 	 * @since 0.1
 	*/
	public function add_network_admin_page() {
		$this->page_hook = add_plugins_page( 'Plugin Toggler', 'Toggler', 'update_plugins', 'plugin_toggler', array( $this, 'network_options_page' ) );
	}

	/**
	 * Adds plugin page and submenu item to plugin menu 
 	 * 
 	 * @since 0.1
 	*/
	public function add_admin_page() {
		$this->page_hook = add_plugins_page( 'Plugin Toggler', 'Toggler', 'update_plugins', 'plugin_toggler', array( $this, 'options_page' ) );
	}

	/**
 	 * Enqueues JavaScript and CSS script files on admin page
 	 *
 	 * @since 0.1
 	*/
	public function enqueue_admin_scripts( $hook ) {
		if ( $hook != $this->page_hook ) {
			return;
		}
		wp_enqueue_style( 'toggler_admin_styles', plugins_url( 'styles/admin.css', TOGGLER_ABSPATH ) );
		wp_enqueue_script( 'toggler_admin_cookie', plugins_url( 'scripts/jquery.cookie.js', TOGGLER_ABSPATH ), array( 'jquery' ), false, true );
		wp_enqueue_script( 'toggler_admin_scripts', plugins_url( 'scripts/admin.js', TOGGLER_ABSPATH ), array( 'jquery' ), false, true );
	}

	/**
 	 * Displays network admin page
 	 *
 	 * @since 1.0
 	*/
	public function network_options_page() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( 'You do not have permission to access that page.', 'Get off my lawn!' );
		}

		$this->setup( true );
	}

	/**
 	 * Displays admin page
 	 *
 	 * @since 1.0
 	*/
	public function options_page() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( 'You do not have permission to access that page.', 'Get off my lawn!' );
		}

		$this->setup( false );
	}

	/**
 	 * Processes variables to prepare for plugin lists
 	 *
 	 * @param bool $multi
 	 *
 	 * @since 1.1
 	*/
	private function setup( $multi = false ) {
		if ( $multi ) {
			/* Get an array of all blogs */
			global $wpdb;
			$blog_ids_raw = $wpdb->get_results("
				SELECT blog_id
				FROM {$wpdb->blogs}
				WHERE site_id = '{$wpdb->siteid}'
				",
				'ARRAY_A');

			foreach( $blog_ids_raw as $b ) {
				$blog_ids[] = intval( $b['blog_id'] );
			}
		} else {
			$blog_ids = array();
		}

		$installed_plugins = get_plugins();
		$toggler_options = get_site_option( 'toggler_options' );
		$saved_list = ( ! empty( $toggler_options['saved_plugins'] ) ) ? $toggler_options['saved_plugins'] : false;
		$installed_ignore_list = ( isset( $_POST['installed_ignore_list'] ) ) ? $_POST['installed_ignore_list'] : array();
		$saved_ignore_list = ( isset( $_POST['saved_ignore_list'] ) ) ? $_POST['saved_ignore_list'] : array();
		$messages = array();

		/* Save, deactivate, or reactivate plugins based on Post */
		if ( ! empty( $_POST ) && check_admin_referer( 'save_list', 'toggler_save_nonce' ) ) {
			if ( isset( $_POST['save'] ) ) {
				$results = $this->save_plugin_list( $_POST['toggler_options'] );
				$messages = $results['messages'];
				if ( $results['success'] ) {
					$toggler_options = get_site_option( 'toggler_options' );
					$saved_list = ( ! empty( $toggler_options['saved_plugins'] ) ) ? $toggler_options['saved_plugins'] : false;
				}
			} else if ( isset( $_POST['deactivate'] ) ) {
				$messages = $this->deactivate_plugins( $installed_plugins, $installed_ignore_list, $blog_ids, $multi );
			} else if ( isset( $_POST['reactivate'] ) ) {
				$messages = $this->reactivate_plugins( $installed_plugins, $saved_list, $saved_ignore_list, $blog_ids, $multi );
			}
		}

		/* Setup activation status for each plugin */
		foreach ( $installed_plugins as $p => $info ) {
			$installed_plugins[$p] = array( 'name' => $info['Name'] );
			if ( $multi ) {
				$installed_plugins[$p]['blogs'] = array();
				$installed_plugins[$p]['network_active'] = ( is_plugin_active_for_network( $p ) ) ? true : false;
			} else {
				$installed_plugins[$p]['active'] = is_plugin_active( $p );
			}
		}

		if ( $multi ) {
			/* Setup blog specific activation status for each plugin */
			foreach ( $blog_ids as $id ) {
				$active_plugins = maybe_unserialize( get_blog_option( $id, 'active_plugins' ) );
				foreach ( $active_plugins as $path ) {
					$installed_plugins[$path]['blogs'][$id] = array(
						'name' => get_blog_option( $id, 'blogname' ),
						'url' => get_blog_option( $id, 'siteurl' )
					);
				}
			}
		}

		/* Print any messages */
		foreach ( $messages as $message ) {
			echo '<div ' . ( ( isset( $message['id'] ) ) ? 'id="' . $message['id'] . '" ' : '' ) . 'class="' . esc_attr( $message['class'] ) . '"><p>' . $message['text'] . '</p></div>';
		}
		?>

		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php esc_html_e( 'Plugin Toggler', 'plugin_toggler' ); ?></h2>

			<h2 class="nav-tab-wrapper">
				<a href="#current-list" class="current-tab nav-tab nav-tab-active">Current Plugins List</a>
				<a href="#saved-list" class="saved-tab nav-tab">Saved Plugins List</a>
			</h2>

			<form method="post">
			<div id="current-list" class="tabbed active">
				<p class="submit">
					<input name="save" type="submit" id="save-top" class="button-primary" value="<?php echo esc_attr( 'Save List' ); ?>" />
					<input name="deactivate" type="submit" id="deactivate-top" class="button-secondary" value="<?php echo esc_attr( 'Deactivate Plugins' ); ?>" />
				</p>
				<h3>Current Plugin States</h3>
				<?php wp_nonce_field( 'save_list', 'toggler_save_nonce' ); ?>

				<?php
				foreach ( $installed_plugins as $path => $info ) {
					echo '<input type="hidden" name="toggler_options[saved_plugins][' . esc_attr( $path ) . '][name]" value="' . esc_attr( $info['name'] ) . '" />';
					if ( $multi ) {
						echo '<input type="hidden" name="toggler_options[saved_plugins][' . esc_attr( $path ) . '][network_active]" value="' . esc_attr( $info['network_active'] ) . '" />';
						foreach ( $info['blogs'] as $blog_id => $blog_info ) {
							echo '<input type="hidden" name="toggler_options[saved_plugins][' . esc_attr( $path ) . '][blogs][' . $blog_id . '][name]" value="' . esc_attr( $blog_info['name'] ) . '" />';
							echo '<input type="hidden" name="toggler_options[saved_plugins][' . esc_attr( $path ) . '][blogs][' . $blog_id . '][url]" value="' . esc_attr( $blog_info['url'] ) . '" />';
						}
					} else {
						echo '<input type="hidden" name="toggler_options[saved_plugins][' . esc_attr( $path ) . '][active]" value="' . esc_attr( $info['active'] ) . '" />';
					}
				}
				
				$this->display_table( $installed_plugins, $saved_list, $installed_ignore_list, 'installed', $multi );

				?>
				<p class="submit">
					<input name="save" type="submit" id="save-bottom" class="button-primary" value="<?php echo esc_attr( 'Save List' ); ?>" />
					<input name="deactivate" type="submit" id="deactivate-bottom" class="button-secondary" value="<?php echo esc_attr( 'Deactivate Plugins' ); ?>" />
				</p>
			</div>

			<div id="saved-list" class="tabbed">
			<?php if ( false !== $saved_list ) { ?>
				<p class="submit">
					<input name="reactivate" type="submit" id="reactivate-top" class="button-primary" value="<?php echo esc_attr( 'Reactivate Plugins' ); ?>" />
				</p>
			<?php } ?>
			<h3>Saved Plugin States</h3>
				<?php 
				if ( false !== $saved_list ) {
					$this-> display_table( $installed_plugins, $saved_list, $saved_ignore_list, 'saved', $multi );
				} else {
					echo '<p>No saved state information.</p>';
				}
				?>
			<?php if ( false !== $saved_list ) { ?>
			<p class="submit">
				<input name="reactivate" type="submit" id="reactivate-bottom" class="button-primary" value="<?php echo esc_attr( 'Reactivate Plugins' ); ?>" />
			</p>
			<?php } ?>
		</div>

		</form>
		<?php
	}

	/**
 	 * Echos a properly formatted table listing plugins accross the network
 	 *
 	 * @param array $installed_list
 	 * @param array $saved_list optional
 	 * @param array $ignore_list optional
 	 * @param string $out optional
 	 * @param bool $multi optional
 	 *
 	 * @since 1.1
 	*/
	private function display_table( $installed_list, $saved_list = array(), $ignore_list = array(), $out = 'installed', $multi = false ) {
		$list = ( $out == 'saved' ) ? $saved_list : $installed_list;
		?>
		<table class="wp-list-table widefat plugins">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Ignore', 'plugin_toggler' ); ?></th>
					<th><?php esc_html_e( 'Plugin', 'plugin_toggler' ); ?></th>
					<th><?php esc_html_e( 'Path', 'plugin_toggler' ); ?></th>
					<?php if ( $multi ) { ?>
						<th><?php esc_html_e( 'Network Active', 'plugin_toggler' ); ?></th>
						<th><?php esc_html_e( 'Individually Activated On', 'plugin_toggler' ); ?></th>
					<?php } ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th><?php esc_html_e( 'Ignore', 'plugin_toggler' ); ?></th>
					<th><?php esc_html_e( 'Plugin', 'plugin_toggler' ); ?></th>
					<th><?php esc_html_e( 'Path', 'plugin_toggler' ); ?></th>
					<?php if ( $multi ) { ?>
						<th><?php esc_html_e( 'Network Active', 'plugin_toggler' ); ?></th>
						<th><?php esc_html_e( 'Individually Activated On', 'plugin_toggler' ); ?></th>
					<?php } ?>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ( $list as $path => $plug_info ) : ?>
				<?php
				if ( ! isset( $installed_list[$path] ) ) {
					$class = 'deleted';
				} else if ( ! empty( $plug_info['network_active'] ) ) {
					$class = 'network active';
				} else if ( ( ! empty( $plug_info['blogs'] ) ) || ( ! empty( $plug_info['active'] ) ) ) { 
					$class = 'active';
				} else { 
					$class = 'inactive';
				} 

				?>
				<tr class="<?php echo $class ?>">
					<td><input type="checkbox" name="<?php echo esc_attr( $out ); ?>_ignore_list[<?php echo esc_attr( $path ); ?>]" <?php checked( isset( $ignore_list[$path] ) ) ?> value="1" /></td>
					<td class="plugin-title"><?php echo '<strong>' . esc_html( $plug_info['name'] ) . '</strong>'; ?></td>
					<td class="plugin-path"><?php echo esc_html( $path ); ?></td>
					<?php if ( $multi ) { ?>
						<td><?php echo ( $plug_info['network_active'] ) ? 'Yes' : 'No'; ?></td>
						<td>
							<?php
							if ( ! empty( $plug_info['blogs'] ) ) {
								$first = true;
								foreach ( $plug_info['blogs'] as $id => $blog_info ) {
									echo ( $first ) ? '' : '<br />';
									echo '<a href="' . $blog_info['url'] . '">' . esc_html( $blog_info['name'] ) . '</a>';
									$first = false;
								}
							} else {
								echo '&nbsp;';
							}
							?>
						</td>
					<?php } ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
 	 * Saves array to WP database
 	 *
 	 * @param array $list
 	 *
 	 * @return array containing a bool indicating success and an array of messages
 	 *
 	 * @since 1.1
 	*/
	private function save_plugin_list( $list ) {
		delete_site_option( 'toggler_options' );

		if ( add_site_option( 'toggler_options', $list ) ) {
			$success = true;
			$messages[] = array( 'text' => 'List Updated', 'class' => 'updated' );
		} else {
			$success = false;
			$messages[] = array( 'text' => 'List Update Failed!', 'class' => 'error' );
		}

		return array( 'success' => $success, 'messages' => $messages );
	}

	/**
 	 * Deactivates a list of plugins
 	 *
 	 * @param array $installed_list
 	 * @param array $installed_ignore_list
 	 * @param array $blog_ids optional
 	 * @param bool $multi optional
 	 *
 	 * @return array containing an array of messages
 	 *
 	 * @since 1.1
 	*/
	private function deactivate_plugins( $installed_list, $installed_ignore_list, $blog_ids = array(), $multi = false ) {
		$deactivate_list = array();
		foreach ( $installed_list as $path => $info ) {
			if ( ! isset( $installed_ignore_list[$path] ) ) {
				$deactivate_list[] = $path;
			}
		}

		deactivate_plugins( $deactivate_list, true, $multi );

		if ( $multi ) {
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				deactivate_plugins( $deactivate_list, true );
			}
		}	

		if ( in_array( TOGGLER_BASE, $deactivate_list ) ) {
			wp_die( '<div class="updated"><p>All plugins have been deactivated (including this one). <a href="' . network_admin_url( 'plugins.php' ) . '">Go to plugin page.</a></p></div>' );
		} else {
			$messages[] = array( 'text' => 'Plugins Deactivated', 'class' => 'updated' );
		}

		return $messages;
	}

	/**
 	 * Reactivates a list of plugins
 	 *
 	 * @param array $installed_list
 	 * @param array $saved_list
 	 * @param array $ignore_list optional
 	 * @param array $blog_ids optional
 	 * @param bool $multi optional
 	 *
 	 * @return array containing an array of messages
 	 *
 	 * @since 1.1
 	*/
	private function reactivate_plugins( $installed_list, $saved_list, $ignore_list = array(), $blog_ids = array(), $multi = false ) {
		$reactivate = array();
		$reactivate_list = array();
		$blogs_reactivate = array();
		$blog_list = array();

		foreach ( $saved_list as $path => $info ) {
			if ( ! isset( $installed_list[$path] ) || isset( $ignore_list[$path] ) ) {
				continue;
			}

			if ( ( ! empty( $info['active'] ) ) || ( ! empty( $info['network_active'] ) ) ) {
				$reactivate[] = $path;
				$reactivate_list[] = $info['name'];
			}

			if ( isset( $info['blogs'] ) ) {
				foreach ( $info['blogs'] as $blog_id => $blog_info ) {
					$blogs_reactivate[$blog_id][] = $path;
					$blog_list[$blog_info['name']][] = $info['name'];
				}
			}
		}

		$status = activate_plugins( $reactivate, '', $multi, true );

		$text = ( $multi ) ? '<strong>Network Activated:</strong>' : '<strong>Reactivated:</strong>';

		foreach ( $reactivate_list as $name ) {
			$text .= '<br />' . esc_html( $name );
		}

		if ( $multi ) {
			foreach ( $blog_ids as $blog_id ) {
				if ( isset( $blogs_reactivate[$blog_id] ) ) {
					switch_to_blog( $blog_id );
					$blogs_status[$blog_id] = activate_plugins( $blogs_reactivate[$blog_id], '', false, true );
				}
			}

			foreach ( $blog_list as $blog_name => $plug_list ) {
				$text .= '<br /><br /><strong>Activated on ' . esc_html( $blog_name ) . ':</strong>';
				foreach ( $plug_list as $name ) {
					$text .= '<br />' . esc_html( $name );
				}
			}
		}

		$messages[] = array( 'text' => 'Plugins Reactivated<br /> <a id="reactivated-list-toggle" href="#reactivated-list">Show List</a>', 'class' => 'updated' );
		$messages[] = array( 'text' => $text, 'class' => 'updated', 'id' => 'reactivated-list' );

		return $messages;
	}
}

?>