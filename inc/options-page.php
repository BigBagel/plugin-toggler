<?php
if ( ! current_user_can( 'update_plugins' ) ) {
	wp_die( 'You do not have permission to access that page.', 'Get off my lawn!' );
}

$installed_plugins = get_plugins();

$toggler_options = get_option( 'toggler_options' );

$saved_list = ( ! empty( $toggler_options['saved_plugins'] ) ) ? unserialize( stripslashes( $toggler_options['saved_plugins'] ) ) : false;

if ( ! empty($_POST) && check_admin_referer( 'save_list', 'toggler_save_nonce' ) ) {
	if ( isset( $_POST['save'] ) ) {
		update_option( 'toggler_options', $_POST['toggler_options'] );
		echo '<div id="message" class="updated"><p>List Updated</p></div>';
	} else if ( isset( $_POST['deactivate'] ) ) {
		deactivate_plugins( array_keys( $installed_plugins ), true );
		wp_die( 'All plugins have been deactivated. (Including this one.) <br /><br /><a href="' . admin_url( 'plugins.php' ) . '">Go to plugin page.</a>' );
	} else if ( isset( $_POST['reactivate'] ) ) {
		$reactivate = array();

		foreach( $saved_list as $path => $info ) {
			if ( isset( $installed_plugins[$path] ) ) {
				$reactivate[] = $path;
			}
		}
		$status = activate_plugins( $reactivate, '', false, true );
		echo '<div id="message" class="updated"><p>Plugins Reactivated.</p></div>';
	}
}

foreach( $installed_plugins as $p => $info ) {
	$installed_plugins[$p] = array( 'name' => $info['Name'] );
	$installed_plugins[$p]['active'] = is_plugin_active( $p );
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
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th><?php echo esc_html( 'Plugin' ); ?></th>
					<th><?php echo esc_html( 'Path' ); ?></th>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach( $installed_plugins as $path => $plug_info ) : ?>
				<?php 
				if ( $plug_info['active'] ) {
					$class = "active";
				} else { 
					$class = "inactive";
				}
				?>
				<tr class="<?php echo $class; ?>"> 
					<td class="plugin-title"><?php echo '<strong>' . esc_html( $plug_info['name'] ) . '</strong>'; ?></td>
					<td class="plugin-path"><?php echo esc_html( $path ); ?></td>
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
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><?php echo esc_html( 'Plugin' ); ?></th>
				<th><?php echo esc_html( 'Path' ); ?></th>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach( $saved_list as $path => $plug_info ) : ?>
			<?php 
			if ( $plug_info['active'] ) { 
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