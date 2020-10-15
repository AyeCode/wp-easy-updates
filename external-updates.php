<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://ayecode.io/
 * @since             1.0.0
 * @package           External_Updates
 *
 * @wordpress-plugin
 * Plugin Name:       WP Easy Updates
 * Plugin URI:        https://wpeasyupdates.com/
 * Description:       Update plugins provided by EDD software licencing or via github with ease.
 * Version:           1.1.18
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       external-updates
 * Domain Path:       /languages
 * Update URL:        https://github.com/AyeCode/wp-easy-updates/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define the version number
define('WP_EASY_UPDATES_VERSION', '1.1.18');

// Define a constant that can be checked against for easy checking of activation status.
define('WP_EASY_UPDATES_ACTIVE', true);

// Define if sslverify should be used
if(!defined('WP_EASY_UPDATES_SSL_VERIFY')){
	define('WP_EASY_UPDATES_SSL_VERIFY', true);
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-external-updates-activator.php
 */
function activate_external_updates() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-external-updates-activator.php';
	External_Updates_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-external-updates-deactivator.php
 */
function deactivate_external_updates() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-external-updates-deactivator.php';
	External_Updates_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_external_updates' );
register_deactivation_hook( __FILE__, 'deactivate_external_updates' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-external-updates.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_external_updates() {

	$plugin = new External_Updates();
	$plugin->run();

}
run_external_updates();