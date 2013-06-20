<?php
if ( ! current_user_can( 'update_plugins' ) ) {
	wp_die( 'You do not have permission to access that page.', 'Get off my lawn!' );
}

global $wpdb;
$blog_ids_raw = $wpdb->get_results($wpdb->prepare("
	SELECT blog_id
	FROM {$wpdb->blogs}
	WHERE site_id = '{$wpdb->siteid}'
	"),
'ARRAY_A');

foreach( $blog_ids_raw as $b ) {
	$blog_ids[] = intval( $b['blog_id'] );
}

$installed_plugins = get_plugins();

$toggler_options = get_site_option( 'toggler_options' );

$saved_list = ( ! empty( $toggler_options['saved_plugins'] ) ) ? unserialize( stripslashes( $toggler_options['saved_plugins'] ) ) : false;

if ( ! empty($_POST) && check_admin_referer( 'save_list', 'toggler_save_nonce' ) ) {
	if ( isset( $_POST['save'] ) ) {
		update_site_option( 'toggler_options', $_POST['toggler_options'] );
		echo '<div id="message" class="updated"><p>List Updated</p></div>';
	} else if ( isset( $_POST['deactivate'] ) ) {
		deactivate_plugins( array_keys( $installed_plugins ), true, true );
		foreach( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			deactivate_plugins( array_keys( $installed_plugins ), true );
		}
		wp_die( 'All plugins have been deactivated. (Including this one.) <br /><br /><a href="' . network_admin_url( 'plugins.php' ) . '">Go to plugin page.</a>' );
	} else if ( isset( $_POST['reactivate'] ) ) {
		$network_reactivate = array();
		$blogs_reactivate = array();

		foreach( $saved_list as $path => $info ) {
			if ( ! isset( $installed_plugins[$path] ) ) {
				continue;
			}

			if ( $info['network_active'] ) {
				$network_reactivate[] = $path;
			}

			foreach( $info['blogs'] as $blog_id => $blog_info ) {
				$blogs_reactivate[$blog_id][] = $path;
			}
		}

		$network_status = activate_plugins( $network_reactivate, '', true, true );
		foreach( $blog_ids as $blog_id ) {
			if ( isset( $blogs_reactivate[$blog_id] ) ) {
				switch_to_blog( $blog_id );
				$blogs_status[$blog_id] = activate_plugins( $blogs_reactivate[$blog_id], '', false, true );
			}
		}

		echo '<div id="message" class="updated"><p>Plugins Reactivated.</p></div>';
	}
}

foreach( $installed_plugins as $p => $info ) {
	$installed_plugins[$p] = array( 'name' => $info['Name'] );
	$installed_plugins[$p]['blogs'] = array();
	$installed_plugins[$p]['network_active'] = ( is_plugin_active_for_network( $p ) ) ? true : false;
}

foreach( $blog_ids as $id ) {
	$active_plugins = maybe_unserialize( get_blog_option( $id, 'active_plugins' ) );
	foreach( $active_plugins as $path ) {
		$installed_plugins[$path]['blogs'][$id] = array(
			'name' => get_blog_option( $id, 'blogname' ),
			'url' => get_blog_option( $id, 'siteurl' )
			);
	}
}

?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( 'Plugin Toggler' ); ?></h2>

	<h3>Current Plugin States</h3>
	<form method="post">
		<?php wp_nonce_field( 'save_list', 'toggler_save_nonce' ); ?>
		<input type="hidden" name="toggler_options[saved_plugins]" value="<?php echo esc_attr( serialize( $installed_plugins ) ); ?>" />
		<table class="wp-list-table widefat plugins">
			<thead>
				<tr>
					<th><?php echo esc_html( 'Plugin' ); ?></th>
					<th><?php echo esc_html( 'Path' ); ?></th>
					<th><?php echo esc_html( 'Network Active' ); ?></th>
					<th><?php echo esc_html( 'Individually Activated On' ); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th><?php echo esc_html( 'Plugin' ); ?></th>
					<th><?php echo esc_html( 'Path' ); ?></th>
					<th><?php echo esc_html( 'Network Active' ); ?></th>
					<th><?php echo esc_html( 'Individually Activated On' ); ?></th>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach( $installed_plugins as $path => $plug_info ) : ?>
				<?php 
				if ( $plug_info['network_active'] ) {
					$class = "network active";
				} else if ( ! empty( $plug_info['blogs'] ) ) { 
					$class = "active";
				} else { 
					$class = "inactive";
				}
				?>
				<tr class="<?php echo $class; ?>"> 
					<td class="plugin-title"><?php echo '<strong>' . esc_html( $plug_info['name'] ) . '</strong>'; ?></td>
					<td class="plugin-path"><?php echo esc_html( $path ); ?></td>
					<td><?php echo ( $plug_info['network_active'] ) ? 'Yes' : 'No'; ?></td>
					<td>
						<?php
						$first = true;
						foreach( $plug_info['blogs'] as $id => $blog_info ) {
							echo ( $first ) ? '' : '<br />';
							echo '<a href="' . $blog_info['url'] . '">' . esc_html( $blog_info['name'] ) . '</a>';
								//echo '<input type="hidden" name="toggler_options[saved_plugins][blogs][' . $id . '][' . $path . ']" value="1" />';
							$first = false;
						} 
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h3>Saved Plugin States</h3>
	<?php if ( false !== $saved_list ) : ?>
	<table class="wp-list-table widefat plugins">
		<thead>
			<tr>
				<th><?php echo esc_html( 'Plugin' ); ?></th>
				<th><?php echo esc_html( 'Path' ); ?></th>
				<th><?php echo esc_html( 'Network Active' ); ?></th>
				<th><?php echo esc_html( 'Individually Activated On' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><?php echo esc_html( 'Plugin' ); ?></th>
				<th><?php echo esc_html( 'Path' ); ?></th>
				<th><?php echo esc_html( 'Network Active' ); ?></th>
				<th><?php echo esc_html( 'Individually Activated On' ); ?></th>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach( $saved_list as $path => $plug_info ) : ?>
			<?php 
			if ( $plug_info['network_active'] ) {
				$class = 'network active';
			} else if ( ! empty( $plug_info['blogs'] ) ) { 
				$class = 'active';
			} else { 
				$class = 'inactive';
			} 

			if ( ! isset( $installed_plugins[$path] ) ) {
				$class .= ' deleted';
			}

			?>
			<tr class="<?php echo $class ?>">
				<td class="plugin-title"><?php echo '<strong>' . esc_html( $plug_info['name'] ) . '</strong>'; ?></td>
				<td class="plugin-path"><?php echo esc_html( $path ); ?></td>
				<td><?php echo ( $plug_info['network_active'] ) ? 'Yes' : 'No'; ?></td>
				<td>
					<?php
					$first = true;
					foreach( $plug_info['blogs'] as $id => $blog_info ) {
						echo ( $first ) ? '' : '<br />';
						echo '<a href="' . $blog_info['url'] . '">' . esc_html( $blog_info['name'] ) . '</a>';
								//echo '<input type="hidden" name="toggler_options[saved_plugins][blogs][' . $id . '][' . $path . ']" value="1" />';
						$first = false;
					} 
					?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php else : ?>
	<p>No saved state information.</p>
<?php endif; ?>

<p class="submit">
	<input name="save" type="submit" id="save" class="button-primary" value="<?php echo esc_attr( 'Save List' ); ?>" />
	<input name="deactivate" type="submit" id="deactivate" class="button-secondary" value="<?php echo esc_attr( 'Deactivate All Plugins' ); ?>" />
	<?php if ( false !== $saved_list ) : ?>
	<input name="reactivate" type="submit" id="reactivate" class="button-secondary" value="<?php echo esc_attr( 'Reactivate Plugins From List' ); ?>" />
<?php endif; ?>
</p>

</form>