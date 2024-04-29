<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://realcoder.com.au
 * @since      1.0.0
 *
 * @package    Rcreviews
 * @subpackage Rcreviews/admin/partials
 */
?>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
	<div id="icon-themes" class="icon32"></div>
	<h2>Settings</h2>
	<!--NEED THE settings_errors below so that the errors/success messages are shown after submission - wasn't working once we started using add_menu_page and stopped using add_options_page so needed this-->
	<?php settings_errors(); ?>
	<form
		method="POST" action="options.php">
		<?php
		settings_fields( 'rcreviews_settings' );
		do_settings_sections( 'rcreviews_settings' );
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">Connection Status</th>
					<td
						id="connection-status"><?php echo get_option( 'rcreviews_access_token' ) != '' ? '<span class="text-success">Success</span>' : '<span class="text-warning">Failure</span>' ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>

