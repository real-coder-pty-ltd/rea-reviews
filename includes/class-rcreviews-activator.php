<?php

/**
 * Fired during plugin activation
 *
 * @link       https://realcoder.com.au
 * @since      1.0.0
 *
 * @package    Rcreviews
 * @subpackage Rcreviews/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Rcreviews
 * @subpackage Rcreviews/includes
 * @author     Julius Genetia <julius@stafflink.com.au>
 */
class Rcreviews_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( 'rcreviews_cron_hook' ) ) {
			wp_schedule_event( time(), 'rcreviews_interval', 'rcreviews_cron_hook' );
		}
	}
}
