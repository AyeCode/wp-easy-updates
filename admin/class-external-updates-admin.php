<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://ayecode.io/
 * @since      1.0.0
 *
 * @package    External_Updates
 * @subpackage External_Updates/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    External_Updates
 * @subpackage External_Updates/admin
 * @author     Stiofan O'Connor <info@ayecode.io>
 */
class External_Updates_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Flag if plugin update check has run.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool $has_plugin_check_run If plugin check has run.
	 */
	private $has_plugin_check_run;

	/**
	 * Flag if theme update check has run.
	 *
	 * @since    1.0.5
	 * @access   private
	 * @var      bool $has_plugin_check_run If theme check has run.
	 */
	private $has_theme_check_run;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name          = $plugin_name;
		$this->version              = $version;
		$this->has_plugin_check_run = false;
		$this->has_theme_check_run  = false;

	}

	/**
	 * Register the stylesheets for the amin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in External_Updates_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The External_Updates_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/external-updates-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in External_Updates_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The External_Updates_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/external-updates-admin.js', array( 'jquery' ), $this->version, false );

	}



	/**
	 * Get the saved licence keys.
	 *
	 * @since 1.0.1
	 * @param bool $network If network settings should be used.
	 * @return array The array of licence information.
	 */
	public function get_keys($network=false){

		if ( is_network_admin() ) {
			$network = true;
		}

		if($network){
			return get_site_option( 'exup_keys', array() );
		}else{
			return get_option( 'exup_keys', array() );
		}

	}

	/**
	 * Handel ajax actions for licence activation.
	 *
	 * @since 1.0
	 */
	public function ajax_handler() {
		global $wpdb;

		// security checks
		if ( ! current_user_can( 'administrator' ) ) {
			wp_die();
		}
		check_ajax_referer( 'exup-ajax-security', 'security' );

		if ( isset( $_POST['exup_action'] ) && $_POST['exup_action'] == 'activate_key' ) {

			$key    = isset( $_POST['exup_key'] ) ? sanitize_text_field( $_POST['exup_key'] ) : '';
			$package = '';
			if(isset( $_POST['exup_plugin'] ) && $_POST['exup_plugin'] ){
				$package = sanitize_text_field( $_POST['exup_plugin'] );
				$type = 'plugin';
			}elseif(isset( $_POST['exup_theme'] ) && $_POST['exup_theme'] ){
				$package = sanitize_text_field( $_POST['exup_theme'] );
				$type = 'theme';
			}

			if ( $key && $package ) {

				$activate = $this->activate_licence( $package, $key, $type );

				echo json_encode( $activate );
			}

		} elseif ( isset( $_POST['exup_action'] ) && $_POST['exup_action'] == 'deactivate_key' ) {

			$key    = isset( $_POST['exup_key'] ) ? sanitize_text_field( $_POST['exup_key'] ) : '';
			$package = '';
			if(isset( $_POST['exup_plugin'] ) && $_POST['exup_plugin'] ){
				$package = sanitize_text_field( $_POST['exup_plugin'] );
				$type = 'plugin';
			}elseif(isset( $_POST['exup_theme'] ) && $_POST['exup_theme'] ){
				$package = sanitize_text_field( $_POST['exup_theme'] );
				$type = 'theme';
			}

			if ( $key && $package ) {

				$activate = $this->deactivate_licence( $package, $key, $type );

				echo json_encode( $activate );
			}

		}

		wp_die();
	}

	/**
	 * Activate the plugin with the EDD install.
	 *
	 * @param $plugin string The plugin slug.
	 * @param $key    string The plugin licence key.
	 *
	 * @return array|bool
	 */
	public function activate_licence( $package, $key, $type ) {

		if($type=='plugin'){
			$packages = get_plugins();
		}else{
			$packages = $this->get_packages_for_update( 'theme' );
		}


		if ( ! isset( $packages[ $package ] ) ) {
			return false;
		}

		$product = $packages[ $package ];

		$update_url = isset( $product['Update URL'] ) ? $product['Update URL'] : '';
		$update_id  = isset( $product['Update ID'] ) ? $product['Update ID'] : '';

		if ( ! $update_url || ! $update_id ) {
			return false;
		}


		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $key,
			'item_id'    => $update_id, // the name or ID of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( $update_url, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false;
		} else {

			// decode the license data
			$licence_data_json = wp_remote_retrieve_body( $response );
			$licence_data      = json_decode( $licence_data_json );

			if ( $licence_data->license == 'valid' ) {
				$keys = $this->get_keys();
				$licence_data->key = $key;
				$keys[ $package ]   = $licence_data;
				$this->update_keys($keys);

				return array( 'success' => __( 'Licence activated!', 'external-updates' ) );

			} elseif ( $licence_data->license == 'invalid' ) {
				return array( 'error' => __( 'Licence key is invalid', 'external-updates' ) );
			} else {
				return array( 'error' => __( 'Something went wrong!', 'external-updates' ) );
			}

		}
	}

	/**
	 * Save and update the licence key info.
	 *
	 * @since 1.0.1
	 * @param array $keys The licence key info to save.
	 */
	public function update_keys($keys){

		$network_keys = $this->get_keys(true);
		$network_keys = $network_keys + $keys;
		update_site_option( 'exup_keys', $network_keys  ); // update network option

		update_option( 'exup_keys', $keys ); // update single site option

	}

	/**
	 * Deactivate the plugin with the EDD install.
	 *
	 * @param $plugin string The plugin slug.
	 * @param $key    string The plugin licence key.
	 *
	 * @return array|bool
	 */
	public function deactivate_licence( $package, $key, $type ) {

		if($type=='plugin'){
			$packages = get_plugins();
		}else{
			$packages = $this->get_packages_for_update( 'theme' );
		}


		if ( ! isset( $packages[ $package ] ) ) {
			return false;
		}

		$product = $packages[ $package ];

		$update_url = isset( $product['Update URL'] ) ? $product['Update URL'] : '';
		$update_id  = isset( $product['Update ID'] ) ? $product['Update ID'] : '';

		if ( ! $update_url || ! $update_id ) {
			return false;
		}


		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $key,
			'item_id'    => $update_id, // the name or ID of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( $update_url, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return array( 'error' => __( 'Could not connect to licence server.', 'external-updates' ) );
		} else {

			// decode the license data
			$licence_data_json = wp_remote_retrieve_body( $response );
			$licence_data      = json_decode( $licence_data_json );

			// we remove the licence no matter the response so a new one can be added
			$keys = $this->get_keys();
			$licence_data->key = $key;
			unset( $keys[ $package ] );
			$this->update_keys($keys);

			// return the response
			if ( $licence_data->license == 'deactivated' ) {
				return array( 'success' => __( 'Licence deactivated!', 'external-updates' ) );
			} elseif ( $licence_data->license == 'invalid' ) {
				return array( 'error' => __( 'Licence key is invalid', 'external-updates' ) );
			} else {
				return array( 'error' => __( 'Something went wrong!', 'external-updates' ) );
			}

		}
	}

	/**
	 * Check for plugin updates by source.
	 *
	 * @param $_transient_data
	 *
	 * @return array|stdClass
	 */
	public function check_for_plugin_updates( $_transient_data ) {
		return $this->check_for_updates( $_transient_data, 'plugin' );
	}

	/**
	 * Check for theme updates by source.
	 *
	 * @param $_transient_data
	 *
	 * @return array|stdClass
	 */
	public function check_for_theme_updates( $_transient_data ) {
		return $this->check_for_updates( $_transient_data, 'theme' );
	}

	/**
	 * Check for plugin updates by source.
	 *
	 * @param $_transient_data
	 *
	 * @return array|stdClass
	 */
	public function check_for_updates( $_transient_data, $type ) {

		// due to WP core bug this can run twice so we only run on the second one.
		if ( $type == 'plugin' && ! $this->has_plugin_check_run ) {
			$this->has_plugin_check_run = true;
			return $_transient_data;
		}elseif ( $type == 'theme' && ! $this->has_theme_check_run ) {
			$this->has_theme_check_run = true;
			return $_transient_data;
		}

		$sources = $this->get_packages_for_update_by_src( $type );

		if ( ! empty( $sources ) ) {

			foreach ( $sources as $src => $packages ) {

				if ( strpos( $src, '://github.com/' ) !== false ) {

					foreach ( $packages as $package ) {
						$version_info = $this->api_request( 'github_version', $src, $package );

						if ( ! empty( $version_info ) ) {
							$_transient_data = $this->process_update_transient_data( $version_info, $_transient_data, $type );
						}
					}

				} else {

					// we can't send an array until EDD release SL 3.6 https://github.com/easydigitaldownloads/EDD-Software-Licensing/issues/552
					$edd_send_array = apply_filters( 'exup_edd_send_array', false );

					// our own plugins should send as an array
					if ( strpos( $src, 'wpgeodirectory.com' ) !== false || strpos( $src, 'wpinvoicing.com' ) !== false ) {
						$edd_send_array = true;
					}

					if ( $edd_send_array ) {

						$version_info = $this->api_request( 'get_version', $src, $packages );
						if ( ! empty( $version_info ) ) {
							$_transient_data = $this->process_update_transient_data( $version_info, $_transient_data, $type );
						}

					} else {

						foreach ( $packages as $package ) {
							$version_info = $this->api_request( 'get_version', $src, $package );

							if ( ! empty( $version_info ) ) {
								$_transient_data = $this->process_update_transient_data( $version_info, $_transient_data, $type );
							}

						}

					}

				}

			}
		}

		//print_r($_transient_data);



		return $_transient_data;
	}

	public function readme_parse_content( $content = '' ) {

		$parsed = '';
		preg_match('/=(.*?)=/', $content, $info);

		if(!empty($info)){
			$parsed['version'] = trim($info[1]);
			$parsed['content'] = trim(str_replace($info[0],'',$content));

		}

		return $parsed;

	}

	public function get_packages_for_update_by_src( $type ) {
		$update_packages = array();
		$packages       = $this->get_packages_for_update( $type );
		$keys = $this->get_keys();

		foreach ( $packages as $key => $package ) {
			if ( isset( $package['Update URL'] ) && $package['Update URL'] ) {

				// setup the updater
				$update_array = array(
					'slug'    => $key,
					// the addon slug
					'version' => $package['Version'],
					// current version number
					'license' => isset( $keys[ $key ]->key ) ? $keys[ $key ]->key : '',
					// license key (used get_option above to retrieve from DB)
					'item_id' => $package['Update ID'],
					// id of this addon on GD site
					'url'     => home_url(),
					'beta'    => ! empty( $package['beta'] ),
				);


				$update_packages[ $package['Update URL'] ][ $key ] = $update_array + $package;
			}
		}

		return $update_packages;
	}

	public function get_packages_for_update( $type = 'plugin' ) {
		$update_packages = array();
		if($type == 'theme'){
			$packages = $this->get_themes();
		}else{
			$packages = get_plugins();
		}



		foreach ( $packages as $key => $package ) {
			if ( isset( $package['Update URL'] ) && $package['Update URL'] ) {
				if ( isset( $package['Version'] ) ) {
					$package['version'] = $package['Version'];
				}
				$update_packages[ $key ] = $package;
			}
		}

		return $update_packages;
	}

	public function get_themes(){
		$themes_arr = array();
		$themes = wp_get_themes();

		$file_headers = array(
			'Name'        => 'Theme Name',
			'ThemeURI'    => 'Theme URI',
			//'Description' => 'Description',
			'Author'      => 'Author',
			'AuthorURI'   => 'Author URI',
			'Version'     => 'Version',
			'Template'    => 'Template',
			'Status'      => 'Status',
			//'Tags'        => 'Tags',
			'TextDomain'  => 'Text Domain',
			'DomainPath'  => 'Domain Path',
			'Update URL'  => 'Update URL', // our own
			'Update ID'   => 'Update ID', // our own
		);


		if(!empty($themes)){

			foreach($themes as $key => $theme){

				foreach($file_headers as $file_key => $header){
					$themes_arr[$key][$file_key] = $theme->get($header);
				}

			}

		}

		return $themes_arr;
	}

	public function api_request( $_action, $_src, $_data ) {
		if ( $_src == home_url() ) {
			return false; // Don't allow a plugin to ping itself
		}

		if ( strpos( $_src, '://github.com/' ) !== false ) {
			return $this->github_api_request( $_src, $_data );
		}


		$update_array = $_data;
		$single       = false;


		if ( isset( $_data['slug'] ) ) { // its  a single request
			$single                = true;
			$api_params = array(
				'edd_action' => 'get_version',
				'license'    => ! empty( $_data['license'] ) ? $_data['license'] : '',
				'item_id'    => isset( $_data['item_id'] ) ? $_data['item_id'] : false,
				'version'    => isset( $_data['version'] ) ? $_data['version'] : false,
				'slug'       => $_data['slug'],
				'url'        => home_url(),
				'beta'       => ! empty( $data['beta'] ),
			);
		}else{
			$api_params = array(
				'edd_action'   => 'get_version',//$_action,
				'update_array' => $update_array,
				'url'          => home_url()
			);
		}


		$request = wp_remote_post( $_src, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );


		if ( ! is_wp_error( $request ) ) {
			$request = json_decode( wp_remote_retrieve_body( $request ) );


			if ( $single ) {
				$tmp_obj = new stdClass();
				$tmp_obj->{$_data['slug']} = $request;
				$request = $tmp_obj;
			}


			$request = self::unserialize_response( $request);


			return $request;
		}

		return false;

	}

	public function github_api_request( $_src, $_data ) {

		// convert to api url
		$_src = str_replace( '://github.com/', '://api.github.com/repos/', $_src );
		$_src = trailingslashit( $_src ) . 'releases';

		// for testing to provide more than 60 github api calls per hour
		//$_src .= '?client_id=xxxx&client_secret=xxxx';

		$request = wp_remote_get( $_src, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => ''
		) );


		if ( ! is_wp_error( $request ) ) {
			$request = json_decode( wp_remote_retrieve_body( $request ) );

			if ( ! empty( $request ) ) {
				foreach ( $request as $release ) {
					if ( isset( $release->prerelease ) && $release->prerelease != 1 ) {
						return $this->convert_github_release( $_data, $release );
					}
				}
			}

		}

		return false;

	}

	public function convert_github_release( $_data, $release ) {

		$info = new stdClass();

		if ( ! isset( $_data['slug'] ) ) {
			return false;
		}


		$info->{$_data['slug']} = new stdClass();

		$info->{$_data['slug']}->new_version    = isset( $release->tag_name ) ? $release->tag_name : '';
		$info->{$_data['slug']}->stable_version = isset( $release->tag_name ) ? $release->tag_name : '';
		$info->{$_data['slug']}->name           = isset( $_data['Name'] ) ? $_data['Name'] : '';
		$info->{$_data['slug']}->slug           = isset( $_data['slug'] ) ? $_data['slug'] : '';
		$info->{$_data['slug']}->last_updated   = isset( $release->published_at ) ? $release->published_at : '';
		$info->{$_data['slug']}->homepage       = isset( $release->html_url ) ? $release->html_url : '';
		$info->{$_data['slug']}->package        = isset( $release->zipball_url ) ? $release->zipball_url : '';
		$info->{$_data['slug']}->download_link  = isset( $release->zipball_url ) ? $release->zipball_url : '';

		/*
		 * @todo the url is used for the theme details iframe but github disallows iframing so we should replace this url with a local one that grabs the content to display
		 */
		$info->{$_data['slug']}->url            = isset( $release->html_url ) ? $release->html_url : '';
		$info->{$_data['slug']}->sections       = array(
			'description' => isset( $release->body ) ? $release->body : '',
			'changelog'   => isset( $release->body ) ? $release->body : '',
		);

		return $info;


	}

	/**
	 * Unserialize the api response if needed for sections.
	 *
	 * @param array $response The response from the update api request.
	 * @param boolean $single If the request if for a single or multiple plugins.
	 *
	 * @return mixed
	 */
	public function unserialize_response( $response ) {

		foreach ( $response as $rslug => $rplugin ) {
			$response->{$rslug}->sections = maybe_unserialize( $response->{$rslug}->sections );
		}

		return $response;
	}

	/**
	 * Process the transient info and return the data.
	 *
	 * @param array|stdClass $_transient_data Update array build by WordPress.
	 *
	 * @return array|stdClass Modified update array with custom plugin data.
	 */
	public function process_update_transient_data( $version_info, $_transient_data, $type ) {


		$update_array = $this->get_packages_for_update( $type );

		foreach ( $version_info as $name => $package_info ) {

			// check for upgrade notice info
			if( isset($package_info->upgrade_notice_raw) && $package_info->upgrade_notice_raw!='' && !isset($package_info->upgrade_notice) ){

				$readme = $this->readme_parse_content($package_info->upgrade_notice_raw);

				if(isset($readme['version']) && $update_array[ $name ]['version'] < $readme['version']){
					$package_info->upgrade_notice = $this->upgrade_notice_output($readme['content'],$package_info->name);
				}
			}

			if ( version_compare( $update_array[ $name ]['version'], $package_info->new_version, '<' ) ) {
				if($type =='theme'){
					$_transient_data->response[ $name ] = (array) $package_info;
				}else{
					$_transient_data->response[ $name ] = $package_info;
				}

			}
			$_transient_data->checked[ $name ] = $update_array[ $name ]['version'];
		}
		$_transient_data->last_checked = time();

		return $_transient_data;
	}


	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @uses api_request()
	 *
	 * @param mixed $_data
	 * @param string $_action
	 * @param object $_args
	 *
	 * @return object $_data
	 */
	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

		$plugins = $this->get_packages_for_update('plugin');

		if ( $_action != 'plugin_information' || ! isset( $_args->slug ) || ( ! array_key_exists( $_args->slug, $plugins ) ) ) {
			return $_data;
		}


		$update_url = $plugins[ $_args->slug ]['Update URL'];
		$update_id  = $plugins[ $_args->slug ]['Update ID'];

		$update_array[ $_args->slug ] = array(
			'slug'    => $_args->slug,                           // the addon slug
			'version' => $plugins[ $_args->slug ]['Version'],    // current version number
			'license' => '',                                     // license key (used get_option above to retrieve from DB)
			'item_id' => $update_id                              // id of this addon on GD site
		);

		if ( strpos( $update_url, '://github.com/' ) !== false ) {
			$plugins[ $_args->slug ]['slug'] = $_args->slug;
			$api_response                    = $this->github_api_request( $update_url, $plugins[ $_args->slug ] );
		} else {
			$api_response = $this->api_request( 'plugin_information', $update_url, $update_array );
		}


		if ( false !== $api_response ) {
			$_data = reset( $api_response );
		}

		// Convert sections into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->sections ) && ! is_array( $_data->sections ) ) {
			$new_sections = array();
			foreach ( $_data->sections as $key => $key ) {
				$new_sections[ $key ] = $key;
			}

			$_data->sections = $new_sections;
		}


		if(isset($_data->banners)){
			$_data->banners = maybe_unserialize( $_data->banners );
		}

		// Convert banners into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->banners ) && ! is_array( $_data->banners ) ) {
			$new_banners = array();

			foreach ( $_data->banners as $key => $key ) {
				$new_banners[ $key ] = $key;
			}

			$_data->banners = $new_banners;
		}

		return $_data;
	}

	/**
	 * Set plugin update errors.
	 *
	 * @since 1.0.0
	 *
	 * @global object $wpdb WordPress Database object.
	 *
	 * @param bool $false   Whether to bail without returning the package. Default false.
	 * @param string $src   The package file url.
	 * @param object $Uthis The WP_Upgrader instance.
	 *
	 * @return mixed
	 */
	public function update_errors( $false, $src, $Uthis ) {

		// make sure we are not adding the mesage more than once one multiple updates.
		if ( isset($Uthis->strings['no_package']) && strpos( $Uthis->strings['no_package'], ' > ' ) !== false ) {
			return $false;
		}


		if ( isset( $Uthis->skin->plugin_info['Update ID'] ) ) {// check if we are dealing with a plugin that requires a licence key
			$plugin_name = isset( $Uthis->skin->plugin_info['Name'] ) ? $Uthis->skin->plugin_info['Name'] : __( 'Plugin Name', 'external-updates' );
			if ( is_network_admin() ) {
				$Uthis->strings['no_package'] = $Uthis->strings['no_package'] . ' '
				                                . sprintf( __( 'A licence key is required to update, please enter it under the main site: Plugins > %s > Licence key.', 'external-updates' ), $plugin_name );
			} else {
				$Uthis->strings['no_package'] = $Uthis->strings['no_package'] . ' '
				                                . sprintf( __( 'A licence key is required to update, please enter it under: Plugins > %s > Licence key.', 'external-updates' ), $plugin_name );
			}

		}elseif(isset($Uthis->skin) && isset($Uthis->skin->theme_info) && $Uthis->skin->theme_info->get("Update ID")){// check if we are dealing with a theme that requires a licence key
			$plugin_name = ( $Uthis->skin->theme_info->get("Name") ) ? $Uthis->skin->theme_info->get("Name") : __( 'Theme Name', 'external-updates' );
			if ( is_network_admin() ) {
				$Uthis->strings['no_package'] = $Uthis->strings['no_package'] . ' '
				                                . sprintf( __( 'A licence key is required to update, please enter it under the main site: Themes > %s > Theme Details > Licence key.', 'external-updates' ), $plugin_name );
			} else {
				$Uthis->strings['no_package'] = $Uthis->strings['no_package'] . ' '
				                                . sprintf( __( 'A licence key is required to update, please enter it under: Themes > %s > Theme Details > Licence key.', 'external-updates' ), $plugin_name );
			}
		}


		return $false;
	}

	/**
	 * Fires after each row in the Plugins list table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $status      Status of the plugin. Defaults are 'All', 'Active',
	 *                            'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
	 *                            'Drop-ins', 'Search'.
	 */
	public function show_requires_licence($plugin_file, $plugin_data, $status){

		$keys = $this->get_keys();


		if( isset($plugin_data['Update ID']) && $plugin_data['Update ID'] ){

			if( !isset($keys[$plugin_file]) || $keys[$plugin_file]->key=='' ){

			?>
			<tr class="wpeu-plugin-licence-required" data-plugin="<?php echo $plugin_file;?>">
				<td colspan="3" class="plugin-update colspanchange">
					<div class="notice inline notice-warning notice-alt">
						<p>
						<?php
						if ( is_network_admin() ) {
							_e( 'This plugin requires a valid licence key to enable automatic updates. Please enter it on the plugins page of the main site where you use the plugin.', 'geodirectory' );
						}else{
							_e( 'This plugin requires a valid licence key to enable automatic updates.', 'geodirectory' );
						}
						?>
						</p>
					</div>
				</td>
			</tr>
			<?php
			}

		}
	}

	/**
	 * Adds our own paramiters to the plugin header DocBlock info.
	 *
	 * @since 1.0.0
	 * @param array $headers The plugin header info array.
	 * @return array The plugin header array info.
	 */
	public function  add_extra_package_headers($headers){
		$headers_extra = array(
			'UpdateURL' => 'Update URL',
			'UpdateID' => 'Update ID',
		);
		$all_headers = array_merge( $headers_extra, (array) $headers);
		return $all_headers;
	}


	/**
	 * The source name from githib downloads need to be changed to match the package name.
	 * 
	 * @param string $source File source location.
	 * @param string $remote_source Remote file source location.
	 * @param WP_Upgrader $upgrader WP_Upgrader instance.
	 * @param array $hook_extra Extra arguments passed to hooked filters.
	 * @return string The fixed source location.
	 */
	public function fix_source_destination($source, $remote_source, $upgrader, $hook_extra ){

		$type = '';
		if( isset($hook_extra['theme']) && $hook_extra['theme'] ){ $type = 'theme'; }
		elseif( isset($hook_extra['plugin']) && $hook_extra['plugin'] ){ $type = 'plugin'; }

		// is it the type we are looking for
		if( $type ){

			global $wp_filesystem; // we need the file system

			if($type=='theme'){
				$theme = wp_get_theme($hook_extra[$type]);
				$update_url = $theme->get('Update URL');
				$proper_destination = trailingslashit(dirname($source)).trailingslashit($hook_extra[$type]);
			}else{
				$update_url = isset($upgrader->skin->plugin_info['Update URL']) ? $upgrader->skin->plugin_info['Update URL'] : '';

				if ( strpos( $hook_extra[$type], '/' ) !== false ) { // its a folder
					$proper_destination = trailingslashit(dirname($source)).trailingslashit(dirname($hook_extra[$type]));
				}else{ // its a file, no need to change folder name
					return $source;
				}
			}


			// If its a github package we need to move the folder to the correctly named folder
			if ( strpos( $update_url, '://github.com/' ) !== false ) {

				$result = $wp_filesystem->move($source, $proper_destination);
				if ( is_wp_error($result) ) {
					return $result;
				}else{
					$source = $proper_destination;
				}
			}


		}


		return $source;
	}

	/**
	 * Renders the link for the row actions on the plugins page.
	 *
	 * @since 1.0
	 *
	 * @param array $actions An array of row action links.
	 *
	 * @return array
	 */
	public function render_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {

		if ( isset( $plugin_data['Update ID'] ) && $plugin_data['Update ID'] != '' ) {

			$actions[] = $this->render_licence_actions($plugin_file, 'plugin');

		}

		return $actions;
	}

	/**
	 * Adds the theme licence actions to the theme description.
	 *
	 * @since 1.1.0
	 * @param array $prepared_themes The array of theme info.
	 * @return array The modified theme array info.
	 */
	public function add_theme_licence_actions($prepared_themes){

		$themes  = $this->get_packages_for_update( 'theme' );

		if(!empty($themes )){

			foreach( $themes as $key => $theme){

				if(isset($prepared_themes[$key])){

					$prepared_themes[$key]['description'] = $this->render_licence_actions($key, 'theme'). $prepared_themes[$key]['description'];
				}

			}

		}

		return $prepared_themes;
	}


	/**
	 * Builds the frontend html code to activate and deactivate licences.
	 *
	 * @param string $slug The plugin/theme slug or filename.
	 * @param string $type The type of package, `plugin` or `theme`.
	 * @return string The html to output.
	 */
	public function render_licence_actions($slug, $type){

		$ajax_nonce = wp_create_nonce( "exup-ajax-security" );

		$keys = $this->get_keys();

		if ( isset( $keys[ $slug ] ) && $keys[ $slug ]->key ) {

			$key                = sanitize_text_field( $keys[ $slug ]->key );
			$deactivate_display = "";
			$activate_display   = " display:none;";
			$key_disabled       = "disabled";
			$licence_class      = "external-updates-active";
			$licence_notice_class = "";

		} else {
			$deactivate_display = " display:none; ";
			$activate_display   = "";
			$key                = '';
			$key_disabled       = '';
			$licence_class      = '';
			$licence_notice_class = "notice-warning";
		}

		$html = '';

		if($type=='plugin'){
			// activate link
			$html .= '<a href="javascript:void(0);" class="external-updates-licence-toggle ' . $licence_class . '" onclick="exup_enter_licence_key(this);" >' . _x( 'Licence key', 'Plugin action link label.', 'external-updates' ) . '</a>';

			// add licence activation html
			$html .= '<div class="external-updates-key-input" style="display:none;">';
			$html .= '<p>';
			$html .= '<input ' . $key_disabled . ' type="text" value="' . $key . '" class="external-updates-key-value" placeholder="' . __( 'Enter your licence key', 'external-updates' ) . '" />';
			$html .= '<span style="' . $deactivate_display . '" class="button-primary" onclick="exup_deactivate_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\');">' . __( 'Deactivate', 'external-updates' ) . '</span>';
			$html .= '<span style="' . $activate_display . '" class="button-primary" onclick="exup_activate_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\');">' . __( 'Activate', 'external-updates' ) . '</span>';
			$html .= '</p>';
			$html .= '</div>';
		}elseif($type=='theme'){


			$html .= '<div class="notice '.$licence_notice_class.' notice-success notice-alt notice-large wpeu-theme-notice">';
			$html .= '<p>'. __( 'A valid licence key is required to enable automatic updates.', 'external-updates' ) .'</p>';

			// add licence activation html
			$html .= '<div class="external-updates-key-input" >';
			$html .= '<p>';
			$html .= '<input ' . $key_disabled . ' type="text" value="' . $key . '" class="external-updates-key-value" placeholder="' . __( 'Enter your licence key', 'external-updates' ) . '" />';
			$html .= '<span style="' . $deactivate_display . '" class="button-primary" onclick="exup_deactivate_theme_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\');">' . __( 'Deactivate', 'external-updates' ) . '</span>';
			$html .= '<span style="' . $activate_display . '" class="button-primary" onclick="exup_activate_theme_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\');">' . __( 'Activate', 'external-updates' ) . '</span>';
			$html .= '</p>';
			$html .= '</div>';

			$html .= '</div>';

		}


		return $html;


	}

	/**
	 * Fires after each row in the Plugins list table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $status      Status of the plugin. Defaults are 'All', 'Active',
	 *                            'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
	 *                            'Drop-ins', 'Search'.
	 */
	public function show_upgrade_notice($plugin_file, $plugin_data, $status){


		if( isset($plugin_data['Update ID']) && $plugin_data['Update ID'] ){

			if (isset($plugin_data['upgrade_notice']) && strlen(trim($plugin_data['upgrade_notice'])) > 0){

				$class = 'inactive';
				if(!is_network_admin() && is_plugin_active($plugin_file)){
					$class = 'active';
				}
				print_r($plugin_data);
				?>
				<tr class="<?php echo $class;?> wpeu-plugin-upgrade-notice" data-plugin="<?php echo $plugin_file;?>">
					<td colspan="3" class="wpeu-upgrade-notice colspanchange">
							<p>
								<?php
								echo $plugin_data['upgrade_notice'];
								?>
							</p>
					</td>
				</tr>
				<?php
			}


		}
	}

	/**
	 * Style and escape the upgrade notice.
	 *
	 * @since 1.1.2
	 * @param $notice string The upgrade notice string.
	 * @param $name string The plugin name.
	 *
	 * @return string The styled and escaped upgradenotice.
	 */
	public function upgrade_notice_output($notice,$name){
		$html = '<p style="background-color: #d54e21; padding: 10px; color: #f9f9f9; margin-top: 10px">';
		$html .= '<strong>'.sprintf( __( 'IMPORTANT UPGRADE NOTICE ( %s ):', 'external-updates' ), $name ).'</strong> ';
		$html .= esc_html($notice). '</p>';
		return $html;
	}



}
