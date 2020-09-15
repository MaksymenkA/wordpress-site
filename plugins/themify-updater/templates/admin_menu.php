<?php

if ( ! defined('THEMIFY_UPDATER_MENU_PAGE') ) die();

?>
<div class="wrap">
	<h2 class="nav-tab-wrapper wp-clearfix">
		<a href="<?php echo add_query_arg( array( 'page' => 'themify-license' ), admin_url( 'index.php' ) ); ?>" class="nav-tab<?php echo ! isset( $_GET['promotion'] ) && ! isset( $_GET['status'] ) ? ' nav-tab-active' : ''; ?>"><?php _e( 'Manage License', 'themify-updater' ) ?></a>
		<a href="<?php echo add_query_arg( array( 'page' => 'themify-license', 'promotion' => 1 ), admin_url( 'index.php' ) ); ?>" class="nav-tab<?php echo isset( $_GET['promotion'] ) && $_GET['promotion'] == 1 ? ' nav-tab-active' : ''; ?>"><?php _e( 'Themes', 'themify-updater' ) ?></a>
		<a href="<?php echo add_query_arg( array( 'page' => 'themify-license', 'promotion' => 2 ), admin_url( 'index.php' ) ); ?>" class="nav-tab<?php echo isset( $_GET['promotion'] ) && $_GET['promotion'] == 2 ? ' nav-tab-active' : ''; ?>"><?php _e( 'Plugins', 'themify-updater' ) ?></a>
		<a href="<?php echo add_query_arg( array( 'page' => 'themify-license', 'status' => 1 ), admin_url( 'index.php' ) ); ?>" class="nav-tab<?php echo isset( $_GET['status'] ) && !isset( $_GET['promotion'] ) ? ' nav-tab-active' : ''; ?>"><?php _e( 'Status', 'themify-updater' ) ?></a>
		<div id="themify-updater-search" style="float: right;">
			<div class="search-promo">
				<label for="promo-search" class="search-icon dashicons dashicons-search"></label>
				<input id="promo-search" type="text" class="promo-search" name="promo-search">
				<span class="dashicons dashicons-no-alt clear-search"></span>
			</div>
		</div>
	</h2>
	<?php
		if ( isset( $_GET['promotion'] ) )  :

           $promotion = new Themify_Updater_Promotion( Themify_Updater::get_instance()->license, Themify_Updater::get_instance()->version, $_GET['promotion'] == 1 ? 'theme' : 'plugin' );
           $promotion->load();
		   
        elseif ( isset( $_GET['status'] ) ) :

				if ( isset( $_GET['reCheck-license'] ) ) {
					wp_verify_nonce( $_GET['_wpnonce'], "re-check-license" );
					Themify_Updater::get_instance()->license->license_check_action();
				}
				echo '<table class="themify_system_status wp-list-table widefat fixed striped feeds">';

				// table header.
				echo '<thead><tr><th colspan="2">'. __('Status', 'themify-updater') .'</th><th></th></tr></thead>';

				// table body.
				echo '<tbody id="the-list" data-wp-lists="list:feed">';

				// License Status.
				echo '<tr>';
				echo '<td>'. __('Themify License Status', 'themify-updater') .'</td>';
				echo '<td>'. Themify_Updater::get_instance()->license->get_error_code() .'</td>';
				echo '<td><span class="dashicons dashicons-'.( Themify_Updater::get_instance()->license->get_error_code() === 'ok' ? 'yes' : 'no').'"></span></td>';
				echo '</tr>';

				// WordPress Version.
				if ( empty($wp_version) ) {
					include( ABSPATH . 'wp-includes/version.php' );
				}
				echo '<tr>';
				echo '<td>'. __('Minimum Reguired WordPress Version', 'themify-updater') .'</td>';
				echo '<td>4.0</td>';
				echo '<td><span class="dashicons dashicons-'.( version_compare( $wp_version, '4.0.', '>=') ? 'yes' : 'no').'"></span></td>';
				echo '</tr>';
				
				// simplexml check.
				echo '<tr>';
				echo '<td>'. __('PHP simplexml', 'themify-updater') .'</td>';
				echo '<td>'.( function_exists('simplexml_load_string') ? __('Installed', 'themify-updater') : __('Not Installed', 'themify-updater') ).'</td>';
				echo '<td><span class="dashicons dashicons-'.( function_exists('simplexml_load_string') ? 'yes' : 'no' ).'"></span></td>';
				echo '</tr>';
				
				// allow_url_fopen Version.
				$req = Themify_Updater::get_instance()->version;
				if (!$req->test_server_access()) {
					echo '<tr>';
					echo '<td>'. __('PHP Directive \'allow_url_fopen\'', 'themify-updater') .'</td>';
					echo '<td>'.( ini_get('allow_url_fopen') ? __('ON', 'themify-updater') : __('OFF', 'themify-updater') ).'</td>';
					echo '<td><span class="dashicons dashicons-'.( ini_get('allow_url_fopen') ? 'yes' : 'no' ).'"></span></td>';
					echo '</tr>';
				}
				
				// Re-Check License.
				echo '<tr>';
				echo '<td>Re-Check License</td>';
				echo '<td></td>';
				echo '<td><a href="'. add_query_arg( array( 'page' => 'themify-license', 'status' => 1, 'reCheck-license' => 1, '_wpnonce' => wp_create_nonce( 're-check-license' ) ), admin_url( 'index.php' ) ) .'" class="button button-primary">'. __( 'Re-Check' , 'themify-updater') .'</a></td>';
				echo '</tr>';

				// Close section table.
				echo '</tbody></table><br />';
         ?>
	<?php else : ?>
		<form method="post" action="" class="themify-updater-settings-form">
			<h2><?php _e( 'Themify License Settings', 'themify-updater' ) ?></h2>
			<p><?php _e( 'Enter your Themify username (that is your Themify user ID, not email address) and license key to auto update all Themify themes and plugins.', 'themify-updater' ) ?></p>
			<p><?php printf( __( 'To get your license key, go to <a href="%s" target="_blank">Themify\'s Member Area &gt; License</a> (if you don\'t see your license key, <a href="%s" target="_blank">contact Themify</a>).', 'themify-updater' ), 'https://themify.me/member/softsale/license', 'https://themify.me/contact' ) ?></p>
			<p><?php printf( __( 'Refer to <a href="%s" target="_blank">documentation</a> for more info.', 'themify-updater' ), 'https://themify.me/docs/themify-updater-documentation' ) ?></p>
			<table>
				<tr>
					<td><strong><?php _e( 'Themify Username', 'themify-updater' ) ?></strong></td>
					<td><input type="text" value="<?php echo $username; ?>" name="themify_username" /></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="checkbox" value="1" <?php echo $hideName !== false ? 'checked="checked"' : ''; ?> name="hideName" /><?php _e('Hide my username', 'themify-updater'); ?></td>
				</tr>
				<tr>
					<td><strong><?php _e( 'License Key', 'themify-updater' ) ?></strong></td>
					<td><input type="text" value="<?php echo $key; ?>" name="updater_licence" autocomplete="off" /></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="checkbox" value="1" <?php echo $hideKey !== false ? 'checked="checked"' : ''; ?> name="hideKey" /><?php _e('Hide my license key', 'themify-updater'); ?></td>
				</tr>
				<tr>
					<td><strong><?php _e( 'Auto Update', 'themify-updater' ) ?></strong></td>
					<td>
						<input type="checkbox" value="1" <?php echo $autoUpdate !== false ? 'checked="checked"' : ''; ?> name="autoUpdate" /><?php _e('Auto update all Themify themes & plugins', 'themify-updater'); ?>
						<br />
						<small>WARNING: It is recommended to update manually and view changelogs before updating. Auto updates may cause issues if your WordPress, theme and plugins are not up to date or your server is not compatible with the latest version.<small>
					</td>
				</tr>
				<tr>
					<td><strong><?php _e( 'Update Notice', 'themify-updater' ) ?></strong></td>
					<td><input type="checkbox" value="1" <?php echo $hideNotice !== false ? 'checked="checked"' : ''; ?> name="hidenotice" /><?php _e('Do not show update notices on admin area', 'themify-updater'); ?></td>
				</tr>
				<tr>
					<td><strong><?php _e( 'Update Notification', 'themify-updater' ) ?></strong></td>
					<td><input type="checkbox" value="1" <?php echo $notification !== false ? 'checked="checked"' : ''; ?> name="notification" /><?php _e('Notify me when there are any new updates', 'themify-updater'); ?></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="text" value="<?php echo $noticeEmail; ?>" name="noticeEmail" /> <?php _e( 'Notification email address', 'themify-updater' ) ?></td>
				</tr>
			</table>
			<p><input type="submit" name="submit" id="submit" class="button button-primary" value="Save"></p>
		</form>
	<?php endif; ?>
</div>