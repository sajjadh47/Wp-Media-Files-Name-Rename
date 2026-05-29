<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @package           Wp_Media_Files_Name_Rename
 * @author            Sajjad Hossain Sagor <sagorh672@gmail.com>
 *
 * Plugin Name:       Rename WP Media Files Name
 * Plugin URI:        https://wordpress.org/plugins/wp-media-files-name-rename/
 * Description:       Change Media Attachments Files Name Easily.
 * Version:           2.0.3
 * Requires at least: 5.6
 * Requires PHP:      8.0
 * Author:            Sajjad Hossain Sagor
 * Author URI:        https://sajjadhsagor.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-media-files-name-rename
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'WP_MEDIA_FILES_NAME_RENAME_PLUGIN_VERSION', '2.0.3' );

/**
 * Define Plugin Folders Path
 */
define( 'WP_MEDIA_FILES_NAME_RENAME_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

define( 'WP_MEDIA_FILES_NAME_RENAME_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'WP_MEDIA_FILES_NAME_RENAME_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-media-files-name-rename-activator.php
 *
 * @since    2.0.0
 */
function on_activate_wp_media_files_name_rename() {
	require_once WP_MEDIA_FILES_NAME_RENAME_PLUGIN_PATH . 'includes/class-wp-media-files-name-rename-activator.php';

	Wp_Media_Files_Name_Rename_Activator::on_activate();
}

register_activation_hook( __FILE__, 'on_activate_wp_media_files_name_rename' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-media-files-name-rename-deactivator.php
 *
 * @since    2.0.0
 */
function on_deactivate_wp_media_files_name_rename() {
	require_once WP_MEDIA_FILES_NAME_RENAME_PLUGIN_PATH . 'includes/class-wp-media-files-name-rename-deactivator.php';

	Wp_Media_Files_Name_Rename_Deactivator::on_deactivate();
}

register_deactivation_hook( __FILE__, 'on_deactivate_wp_media_files_name_rename' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 * @since    2.0.0
 */
require WP_MEDIA_FILES_NAME_RENAME_PLUGIN_PATH . 'includes/class-wp-media-files-name-rename.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function run_wp_media_files_name_rename() {
	$plugin = new Wp_Media_Files_Name_Rename();

	$plugin->run();
}

run_wp_media_files_name_rename();
