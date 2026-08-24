<?php

/**
 * Fired during plugin activation
 *
 * @link       https://gutenbricks.com
 * @since      1.0.0
 *
 * @package    Gutenbricks
 * @subpackage Gutenbricks/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Gutenbricks
 * @subpackage Gutenbricks/includes
 * @author     WiredWP <ryan@wiredwp.com>
 */
class Gutenbricks_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		$bundles = get_option('_gutenbricks_active_bundles');
		if ( ! $bundles ) {
			update_option( '_gutenbricks_active_bundles', array( 'default' ) );
		}
	}

}
