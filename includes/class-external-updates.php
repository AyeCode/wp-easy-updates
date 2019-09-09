<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://ayecode.io/
 * @since      1.0.0
 *
 * @package    External_Updates
 * @subpackage External_Updates/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    External_Updates
 * @subpackage External_Updates/includes
 * @author     Stiofan O'Connor <info@ayecode.io>
 */
class External_Updates {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      External_Updates_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'external-updates';
		$this->version = WP_EASY_UPDATES_VERSION;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - External_Updates_Loader. Orchestrates the hooks of the plugin.
	 * - External_Updates_i18n. Defines internationalization functionality.
	 * - External_Updates_Admin. Defines all hooks for the admin area.
	 * - External_Updates_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-external-updates-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-external-updates-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-external-updates-admin.php';

		$this->loader = new External_Updates_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the External_Updates_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new External_Updates_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new External_Updates_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'plugin_action_links', $plugin_admin, 'render_plugin_action_links',10,4 );
		$this->loader->add_action( 'wp_ajax_exup_ajax_handler', $plugin_admin, 'ajax_handler' );

		// windows servers can have issues with long filenames. https://wordpress.org/plugins/fix-windows-compatibility/
		// only add the filters if running on Windows
		if ( 'Darwin' !== PHP_OS && FALSE !== strcasecmp('win', PHP_OS ) && !class_exists( 'DS_WindowsCompatabilityFix', FALSE ) ) {
			$this->loader->add_filter( 'wp_unique_filename', $plugin_admin, 'filter_unique_filename', 1, 4 );
		}
		
		$this->loader->add_filter( 'pre_set_site_transient_update_plugins', $plugin_admin, 'check_for_plugin_updates' );
		$this->loader->add_filter( 'plugins_api', $plugin_admin, 'plugins_api_filter', 10, 3 );
		$this->loader->add_filter( 'upgrader_pre_download', $plugin_admin, 'update_errors', 10, 3 );
		$this->loader->add_filter( 'after_plugin_row', $plugin_admin, 'show_requires_licence', 10, 3 );
		$this->loader->add_filter( 'after_plugin_row', $plugin_admin, 'show_upgrade_notice', 10, 3 );
		$this->loader->add_filter( 'extra_plugin_headers', $plugin_admin, 'add_extra_package_headers', 10, 1 );
		$this->loader->add_filter( 'upgrader_source_selection', $plugin_admin, 'fix_source_destination', 10, 4 );

		// Set the more info plugin details
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'plugin_row_meta', 10, 4 );
		
		// Theme stuff
		$this->loader->add_filter( 'pre_set_site_transient_update_themes', $plugin_admin, 'check_for_theme_updates' );
		$this->loader->add_filter( 'extra_theme_headers', $plugin_admin, 'add_extra_package_headers', 10, 1 );
		$this->loader->add_filter( 'wp_prepare_themes_for_js', $plugin_admin, 'add_theme_licence_actions', 10, 1 );
		$this->loader->add_filter( 'themes_api', $plugin_admin, 'themes_api_filter', 10, 3 );

		// EDD Query Args filter
		$this->loader->add_filter( 'wpeu_edd_api_query_args', $plugin_admin, 'edd_api_query_args', 10, 3 );
		
		//upgrader_process_complete
		$this->loader->add_action( 'upgrader_process_complete', $plugin_admin, 'upgrader_process_complete', 10, 2 );
		
		// add filters for EDD addon info
		$this->loader->add_filter( 'edd_api_button_args', $plugin_admin, 'edd_api_button_args', 10, 1 );

	}
	

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    External_Updates_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}


	

}
