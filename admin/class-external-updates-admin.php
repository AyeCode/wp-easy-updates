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
	 * Set if we are inside the upgrader class.
	 *
	 * @var bool
	 */
	private static $_upgrade = FALSE;

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

		}elseif ( isset( $_POST['exup_action'] ) && $_POST['exup_action'] == 'activate_membership_key' ) {

			$key    = isset( $_POST['exup_key'] ) ? sanitize_text_field( $_POST['exup_key'] ) : '';
			$package = '';
			$item_ids = isset($_POST['exup_item_ids']) ? explode(",",$_POST['exup_item_ids']) : array();
			if(isset( $_POST['exup_domain'] ) && $_POST['exup_domain'] ){
				$package = sanitize_text_field( $_POST['exup_domain'] );
			}

			if ( $key && $package && !empty($item_ids)) {

				$activate = $this->activate_membership_licence( $package, $key, $item_ids );//activate_membership_licence( $domain, $key, $item_ids )

				echo json_encode( $activate );
			}

		}elseif ( isset( $_POST['exup_action'] ) && $_POST['exup_action'] == 'deactivate_membership_key' ) {

			$key    = isset( $_POST['exup_key'] ) ? sanitize_text_field( $_POST['exup_key'] ) : '';
			$package = '';
			$item_ids = isset($_POST['exup_item_ids']) ? explode(",",$_POST['exup_item_ids']) : array();
			if(isset( $_POST['exup_domain'] ) && $_POST['exup_domain'] ){
				$package = sanitize_text_field( $_POST['exup_domain'] );
			}

			if ( $key && $package && !empty($item_ids)) {

				$activate = $this->deactivate_membership_licence( $package, $key, $item_ids );//activate_membership_licence( $domain, $key, $item_ids )

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
	public function activate_licence( $package, $key, $type, $update_url = '', $update_id = '' ) {

		if($type=='plugin'){
			$packages = get_plugins();
		}else{
			$packages = $this->get_packages_for_update( 'theme' );
		}


		if($update_url && $update_id){

		}else{
			if ( ! isset( $packages[ $package ] )) {
				return false;
			}

			$product = $packages[ $package ];

			$update_url = isset( $product['Update URL'] ) ? $product['Update URL'] : '';
			$update_id  = isset( $product['Update ID'] ) ? $product['Update ID'] : '';
		}


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
			'sslverify' => WP_EASY_UPDATES_SSL_VERIFY,
			'body'      => $api_params
		) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return array('error' => $response->get_error_message());
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
	 * Activate the plugin with the EDD install.
	 *
	 * @param $plugin string The plugin slug.
	 * @param $key    string The plugin licence key.
	 *
	 * @return array|bool
	 */
	public function activate_membership_licence( $domain, $key, $item_ids ) {

		// @todo remove this once keys are in core plugins
		##########################################################
		######### < Temp Fix for Lifetime membership keys ########
		##########################################################
		if( $domain == 'wpgeodirectory.com' && !empty($item_ids) ){
			$item_ids[] = '807546';
		}elseif($domain == 'userswp.io' && !empty($item_ids) ){
			$item_ids[] = '20570';
		}elseif($domain == 'wpinvoicing.com' && !empty($item_ids) ){
			$item_ids[] = '12351';
		}
		##########################################################
		######### Temp Fix for Lifetime membership keys /> #######
		##########################################################
		
		
		$update_url = "https://".$domain;
		$update_id  = $item_ids;

		if ( ! $update_url || ! $update_id ) {
			return false;
		}

		$error = array( 'error' => __( 'Something went wrong!', 'external-updates' ) );

		foreach($item_ids as $item_id){
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $key,
				'item_id'    => $item_id, // the name or ID of our product in EDD
				'url'        => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( $update_url, array(
				'timeout'   => 15,
				'sslverify' => WP_EASY_UPDATES_SSL_VERIFY,
				'body'      => $api_params
			) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return array('error' => $response->get_error_message());
			} else {

				// decode the license data
				$licence_data_json = wp_remote_retrieve_body( $response );
				$licence_data      = json_decode( $licence_data_json );

				if ( $licence_data->license == 'valid' ) {
					$keys = $this->get_keys();
					$licence_data->key = $key;
					$keys[ $domain ]   = $licence_data;
					$this->update_keys($keys);

					return array( 'success' => __( 'Licence activated!', 'external-updates' ) );

				} elseif ( $licence_data->license == 'invalid' ) {
					$error = array( 'error' => __( 'Licence key is invalid', 'external-updates' ) );
				} else {
					$error = array( 'error' => __( 'Something went wrong!', 'external-updates' ) );
				}

			}
		}

		return $error;

	}

	/**
	 * Deactivate the plugin with the EDD install.
	 *
	 * @param $plugin string The plugin slug.
	 * @param $key    string The plugin licence key.
	 *
	 * @return array|bool
	 */
	public function deactivate_membership_licence( $domain, $key, $item_ids ) {

		// @todo remove this once keys are in core plugins
		##########################################################
		######### < Temp Fix for Lifetime membership keys ########
		##########################################################
		if( $domain == 'wpgeodirectory.com' && !empty($item_ids) ){
			$item_ids[] = '807546';
		}elseif($domain == 'userswp.io' && !empty($item_ids) ){
			$item_ids[] = '20570';
		}elseif($domain == 'wpinvoicing.com' && !empty($item_ids) ){
			$item_ids[] = '12351';
		}
		##########################################################
		######### Temp Fix for Lifetime membership keys /> #######
		##########################################################
		
		$update_url = "https://".$domain;
		$update_id  = $item_ids;

		if ( ! $update_url || ! $update_id ) {
			return false;
		}

		$error = array( 'error' => __( 'Something went wrong!', 'external-updates' ) );

		foreach($item_ids as $item_id){
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $key,
				'item_id'    => $item_id, // the name or ID of our product in EDD
				'url'        => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( $update_url, array(
				'timeout'   => 15,
				'sslverify' => WP_EASY_UPDATES_SSL_VERIFY,
				'body'      => $api_params
			) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return array('error' => $response->get_error_message());
			} else {

				// decode the license data
				$licence_data_json = wp_remote_retrieve_body( $response );
				$licence_data      = json_decode( $licence_data_json );

				if ( $licence_data->license == 'deactivated' ) {
					// we remove the licence no matter the response so a new one can be added
					$keys = $this->get_keys();
					$licence_data->key = $key;
					unset( $keys[ $domain] );
					$this->update_keys($keys);

					return array( 'success' => __( 'Licence deactivated!', 'external-updates' ) );

				} elseif ( $licence_data->license == 'invalid' ) {
					$error = array( 'error' => __( 'Licence key is invalid', 'external-updates' ) );
				} else {
					$error = array( 'error' => __( 'Something went wrong!', 'external-updates' ) );
				}

			}
		}

		return $error;

	}

	/**
	 * Save and update the licence key info.
	 *
	 * @since 1.0.1
	 * @param array $keys The licence key info to save.
	 */
	public function update_keys($keys){

		$network_keys = $this->get_keys(true);
		$network_keys = array_merge($network_keys , $keys );
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
			'sslverify' => WP_EASY_UPDATES_SSL_VERIFY,
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
					if ( strpos( $src, 'wpgeodirectory.com' ) !== false || strpos( $src, 'wpinvoicing.com' ) !== false || strpos( $src, 'userswp.io' ) !== false ) {
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

		$parsed = array();
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

				// check for membership key
//				if(empty($update_array['license']) && isset( $keys[ $package['Update URL'] ]->key )){
//					$update_array['license'] = $keys[ $package['Update URL'] ]->key;
//				}


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
				'beta'       => ! empty( $_data['beta'] ),
			);
		}else{
			$api_params = array(
				'edd_action'   => 'get_version',//$_action,
				'update_array' => $update_array,
				'url'          => home_url()
			);
		}

		/**
		 * Force beta param for GeoDirectory on network as there is no setting if GD is not active.
		 * @todo this can be removed when GD v1 updates are removed.
		 */
		if ( is_network_admin() && strpos( $_src, 'wpgeodirectory.com' ) !== false ) {

			// check if we are dealing with GDv2+
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . "/geodirectory/geodirectory.php" );
			if(isset($plugin_data['Version']) && version_compare($plugin_data['Version'],"2.0.0",'>')){
				if ( ! empty( $api_params['update_array'] ) ) {
					foreach ( $api_params['update_array'] as $key => $val ) {
						$api_params['update_array'][ $key ]['beta'] = true;
					}
				}
				$api_params['beta'] = true;
			}

		}

		/**
		 * Filter the API params before send.
		 *
		 * This can be used to filter things like is beta.
		 *
		 * @param array $api_params The array of API parameters.
		 * @param string $_src The update url.
		 * @since 1.1.6
		 */
		$api_params = apply_filters('wp_easy_updates_api_params',$api_params,$_src);


		$request = wp_remote_post( $_src, array(
			'timeout'   => 15,
			'sslverify' => WP_EASY_UPDATES_SSL_VERIFY,
			'body'      => $api_params
		) );



		if ( ! is_wp_error( $request ) ) {
			$request = json_decode( wp_remote_retrieve_body( $request ) );



			// check for EDD free download
			$request = self::maybe_free_download($request);

			// check for activation and no version
			$request = self::maybe_add_version($request,$api_params);

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

	/**
	 * Check if we are activating a plugin and if so make sure a version number is present so not to cause a PHP notice.
	 *
	 * @param $response
	 * @param $api_params
	 *
	 * @return mixed
	 */
	public function maybe_add_version($response,$api_params){
		if(isset($_REQUEST['wpeu_activate']) && $_REQUEST['wpeu_activate'] && !empty($response) && isset($_REQUEST['item_id'])){
			foreach ( $response as $rslug => $rplugin ) {
				if(!isset($response->{$rslug}->version)){
					if(!empty($api_params['beta']) && !empty($response->{$rslug}->new_version)){
						$response->{$rslug}->version = $response->{$rslug}->new_version;
					}elseif(isset($response->{$rslug}->stable_version)){
						$response->{$rslug}->version = $response->{$rslug}->stable_version;
					}else{
						$response->{$rslug}->version = '';
					}
				}
			}
		}

		return $response;
	}

	/**
	 * Check if the request is for a free plugin and if so add the free download url.
	 *
	 * @param $response
	 *
	 * @return mixed
	 */
	public function maybe_free_download($response){
		if(isset($_REQUEST['free_download']) && $_REQUEST['free_download'] && !empty($response) && isset($_REQUEST['item_id'])){
			foreach ( $response as $rslug => $rplugin ) {
				if(isset($response->{$rslug}->download_link) && $response->{$rslug}->download_link==''){
					$free_download_url = add_query_arg( array(
						'edd_action' => 'free_downloads_process_download',
						'download_id' => absint($_REQUEST['item_id']),
					), $response->{$rslug}->homepage );
					$response->{$rslug}->package = $free_download_url;
					$response->{$rslug}->download_link = $free_download_url;
				}
			}
		}

		return $response;
	}

	public function github_api_request( $_src, $_data ) {

		// convert to api url
		$_src = str_replace( '://github.com/', '://api.github.com/repos/', $_src );
		$_src = trailingslashit( $_src ) . 'releases';

		// for testing to provide more than 60 github api calls per hour
		//$_src .= '?client_id=xxxx&client_secret=xxxx';

		$request = wp_remote_get( $_src, array(
			'timeout'   => 15,
			'sslverify' => WP_EASY_UPDATES_SSL_VERIFY,
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
			'description' => isset( $release->body ) ? wpautop($release->body) : '',
			'changelog'   => isset( $release->body ) ? wpautop($release->body) : '',
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
			if(isset($response->{$rslug}->sections)){
				$response->{$rslug}->sections = maybe_unserialize( $response->{$rslug}->sections );
			}
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

		if(!is_object($_transient_data)){
			return $_transient_data;
		}

		$update_array = $this->get_packages_for_update( $type );

		foreach ( $version_info as $name => $package_info ) {

			// check for upgrade notice info
			if( isset($package_info->upgrade_notice_raw) && $package_info->upgrade_notice_raw!='' && !isset($package_info->upgrade_notice) ){

				$readme = $this->readme_parse_content($package_info->upgrade_notice_raw);

				if(isset($readme['version']) && $update_array[ $name ]['version'] < $readme['version']){
					$package_info->upgrade_notice = $this->upgrade_notice_output($readme['content'],$package_info->name);
				}
			}

			if ( isset($package_info->new_version) && version_compare( $update_array[ $name ]['version'], $package_info->new_version, '<' ) ) {
				if($type =='theme'){
					$_transient_data->response[ $name ] = (array) $package_info;
				}else{
					// for some obscure reason, sections can make the update check run on every page load
					if(isset($package_info->sections)){unset($package_info->sections);}

					$_transient_data->response[ $name ] = $package_info;
				}

			}

			// if plugin param missing then add it.
			if( $type == 'plugin' && empty( $package_info->plugin ) ){
				$package_info->plugin = $name;
			}

			$_transient_data->checked[ $name ] = isset($update_array[ $name ]['version']) ? $update_array[ $name ]['version'] : '';
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

		if ( $_action != 'plugin_information' || ! isset( $_args->slug ) || ( ! array_key_exists( $_args->slug, $plugins ) && !isset($_REQUEST['update_url']) ) ) {
			return $_data;
		}

		$update_url = isset($plugins[ $_args->slug ]['Update URL']) ? $plugins[ $_args->slug ]['Update URL'] : esc_url($_REQUEST['update_url']);
		$update_id  = isset($plugins[ $_args->slug ]['Update ID']) ? $plugins[ $_args->slug ]['Update ID'] : '';
		if(!$update_id && isset($_REQUEST['item_id'])){$update_id = absint($_REQUEST['item_id']);}
		$licence = isset($_REQUEST['license']) ? esc_attr($_REQUEST['license']) : '';

		$update_array[ $_args->slug ] = array(
			'slug'    => $_args->slug,                           // the addon slug
			'version' => isset($plugins[ $_args->slug ]['Version']) ? $plugins[ $_args->slug ]['Version'] : '',    // current version number
			'license' => $licence,                               // license key (used get_option above to retrieve from DB)
			'item_id' => $update_id                              // id of this addon on GD site
		);

		// maybe activate
		if( !empty($_REQUEST['update_url']) && !empty($_REQUEST['license']) && !empty($_REQUEST['wpeu_activate'])){
			$activate = self::activate_licence( $_args->slug, $licence, 'plugin', $update_url, $update_id);
		}

		if ( strpos( $update_url, '://github.com/' ) !== false ) {
			$plugins[ $_args->slug ]['slug'] = $_args->slug;
			$api_response                    = $this->github_api_request( $update_url, $plugins[ $_args->slug ] );
		} else {
			$api_response = $this->api_request( 'plugin_information', $update_url, $update_array );
		}

//		print_r($api_response);exit;


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

		// strip shortcodes from description
		if(isset($_data->sections['description']) && $_data->sections['description']){
			$_data->sections['description'] = strip_shortcodes($_data->sections['description']);
		}

		// add licence input
		//$_data->sections['licence key'] = '12345'.print_r($this->get_keys(),true); // @todo this would be a nice section to add


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
	public function themes_api_filter( $_data, $_action = '', $_args = null ) {
		$themes = $this->get_packages_for_update('theme');

		if ( $_action != 'theme_information' || ! isset( $_args->slug ) || ( ! array_key_exists( $_args->slug,$themes ) && !isset($_REQUEST['update_url']) ) ) {
			return $_data;
		}



		$update_url = isset($themes[ $_args->slug ]['Update URL']) ? $themes[ $_args->slug ]['Update URL'] : esc_url($_REQUEST['update_url']);
		$update_id  = isset($themes[ $_args->slug ]['Update ID']) ? $themes[ $_args->slug ]['Update ID'] : '';
		if(!$update_id && isset($_REQUEST['item_id'])){$update_id = absint($_REQUEST['item_id']);}
		$licence = isset($_REQUEST['license']) ? esc_attr($_REQUEST['license']) : '';

		$update_array[ $_args->slug ] = array(
			'slug'    => $_args->slug,                           // the addon slug
			'version' => isset($themes[ $_args->slug ]['Version']) ? $themes[ $_args->slug ]['Version'] : '',    // current version number
			'license' => $licence,                               // license key (used get_option above to retrieve from DB)
			'item_id' => $update_id                              // id of this addon on GD site
		);



		// maybe activate
		if( !empty($_REQUEST['update_url']) && !empty($_REQUEST['license']) && !empty($_REQUEST['wpeu_activate'])){
			$activate = self::activate_licence( $_args->slug, $licence, 'theme', $update_url, $update_id);
		}


		if ( strpos( $update_url, '://github.com/' ) !== false ) {
			$themes[ $_args->slug ]['slug'] = $_args->slug;
			$api_response                    = $this->github_api_request( $update_url, $themes[ $_args->slug ] );
		} else {
			$api_response = $this->api_request( 'theme_information', $update_url, $update_array );
		}

//		print_r($api_response);exit;


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

		// strip shortcodes from description
		if(isset($_data->sections['description']) && $_data->sections['description']){
			$_data->sections['description'] = strip_shortcodes($_data->sections['description']);
		}

		// add licence input
		//$_data->sections['licence key'] = '12345'.print_r($this->get_keys(),true); // @todo this would be a nice section to add


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
	 * If we just installed a new plugin from licence key we need to update the licence [key] name.
	 * 
	 * @param $upgrader_object
	 * @param $options
	 */
	public function upgrader_process_complete($upgrader_object, $options){

			if(
				isset($options['type']) && $options['type'] == 'plugin'
			&& isset($options['action']) && $options['action'] == 'install'
			&& !empty($_REQUEST['slug']) && !empty($_REQUEST['update_url']) && !empty($_REQUEST['license']) && !empty($_REQUEST['wpeu_activate'])
			&& isset($upgrader_object->result['destination_name'])
			&& $upgrader_object->result['destination_name']
			){

				$plugin_root = $upgrader_object->result['destination_name'];
				$slug = esc_attr($_REQUEST['slug']);
				$packages = get_plugins();
				$keys = self::get_keys();

				foreach($packages as $key => $pacakge){
					if(strpos($key, $plugin_root."/") === 0 && isset($keys[$slug])){
						$keys[$key] = $keys[$slug];
						unset($keys[$slug]);
						self::update_keys($keys);
					}
				}
		}
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

		self::$_upgrade = TRUE; // set doing upgrade

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

			// if it gives a package url but fails to download then show a link to the package which will probably give a reason for the first instance only so we replace the links on the fly with JS for the rest.
			if(!empty($src)){
				$Uthis->strings['download_failed'] =  sprintf( __( '%s ( more info ) %s' ), "<a class='wpeu-download-failed-error' href='$src' target='_blank'>","</a>" ) .' - ' . $Uthis->strings['download_failed'];
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

			// if it gives a package url but fails to download then show a link to the package which will probably give a reason for the first instance only so we replace the links on the fly with JS for the rest.
			if(!empty($src)){
				$Uthis->strings['download_failed'] =  sprintf( __( '%s ( more info ) %s' ), "<a class='wpeu-download-failed-error' href='$src' target='_blank'>","</a>" ) .' - ' . $Uthis->strings['download_failed'];
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


		if( isset($plugin_data['Update ID']) && $plugin_data['Update ID'] && isset($plugin_data['Update URL']) && strpos($plugin_data['Update URL'], 'https://github.com/') !== 0){

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
				
				// set plugin_info if not set
				if(!isset($upgrader->skin->plugin_info) && isset($upgrader->skin->plugin)){
					$upgrader->skin->plugin_info = get_plugin_data( WP_PLUGIN_DIR . '/' . $upgrader->skin->plugin, false, true);
				}

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

		if ( isset( $plugin_data['Update ID'] ) && $plugin_data['Update ID'] != '' && isset($plugin_data['Update URL']) && strpos($plugin_data['Update URL'], 'https://github.com/') !== 0) {

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

					if( !empty($theme['Update ID']) ){ // only show key input if a Update ID is set.
						$prepared_themes[$key]['description'] = $this->render_licence_actions($key, 'theme'). $prepared_themes[$key]['description'];
					}
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
	public function render_licence_actions($slug, $type, $item_ids = array()){

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

		}elseif($type=='membership'){
			// activate link
			//$html .= '<a href="javascript:void(0);" class="external-updates-licence-toggle ' . $licence_class . '" onclick="exup_enter_licence_key(this);" >' . _x( 'Licence key', 'Plugin action link label.', 'external-updates' ) . '</a>';

			// add licence activation html
			$html .= '<p>';
			$html .= '<input ' . $key_disabled . ' type="text" value="' . $key . '" class="external-updates-key-value" placeholder="' . __( 'Enter your licence key', 'external-updates' ) . '" />';
			$html .= '<span style="' . $deactivate_display . '" class="button-primary" onclick="exup_deactivate_membership_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\',\'' . implode(",",$item_ids) . '\');">' . __( 'Deactivate', 'external-updates' ) . '</span>';
			$html .= '<span style="' . $activate_display . '" class="button-primary" onclick="exup_activate_membership_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\',\'' . implode(",",$item_ids) . '\');">' . __( 'Activate', 'external-updates' ) . '</span>';
			$html .= '</p>';
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


	/**
	 * Add the `View details` link back to the plugins page.
	 *
	 * @param $plugin_meta
	 * @param $plugin_file
	 * @param $plugin_data
	 * @param $status
	 *
	 * @return array
	 */
	public function plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status){
		// check if we are using WPEU
		if(isset($plugin_data['Update URL']) && $plugin_data['Update URL'] && isset($plugin_data['Update ID']) && $plugin_data['Update ID']){
			$plugin_name = $plugin_data['Name'];

			$plugin_meta[] = sprintf( '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
				esc_url_raw( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_file .
				                            '&width=600&height=550&update_url='.$plugin_data['Update URL'].'&item_id='.$plugin_data['Update ID'].'&TB_iframe=true' ) ),
				esc_attr( sprintf( __( 'More information about %s' ), $plugin_name ) ),
				esc_attr( $plugin_name ),
				__( 'View details' )
			);
		}
		return $plugin_meta;
	}

	/**
	 * Add a membership key if it exists.
	 *
	 * @param $query_args
	 * @param $api_url
	 * @param $section_id
	 *
	 * @return mixed
	 */
	public function edd_api_query_args($query_args,$api_url,$section_id){

		$url_parts = parse_url($api_url);
		if(isset($url_parts['host'])){
			$host = $url_parts['host'];
			$keys = $this->get_keys();
			if(isset($keys[$host])){
				$query_args['license'] = $keys[$host]->key;
			}
		}
		
		return $query_args;
	}


	public function edd_api_button_args($button_args){

		

		//if()

//		$button_args = array(
//			'type' => $current_tab,
//			'button_text' => __('Free','geodirectory'),
//			'price_text' => __('Free','geodirectory'),
//			'link' => isset($addon->info->link) ? $addon->info->link : '', // link to product
//			'url' => isset($addon->info->link) ? $addon->info->link : '', // button url
//			'class' => 'button-primary',
//			'install_status' => 'get',
//			'installed' => false,
//			'price' => '',
//			'licensing' => isset($addon->licensing->enabled) && $addon->licensing->enabled ? true : false,
//			'license' => isset($addon->licensing->license) && $addon->licensing->license ? $addon->licensing->license : '',
//			'onclick' => '',
//			'slug' => isset($addon->info->slug) ? $addon->info->slug : '',
//			'active' => false,
//			'file' => ''
//		);

		if(isset($button_args['type']) && $button_args['type']=='addons'){

			// if not installed then change the button text to install
			if(empty($button_args['installed']) && !empty($button_args['update_url'])){

				// free
				if($button_args['licensing']===false || (isset($button_args['price']) && $button_args['price']=='0.00')){
					$button_args['button_text'] = __('Install');
					$slug = isset($button_args['slug']) ? esc_attr($button_args['slug']) : '';
					$nonce = wp_create_nonce( 'updates' );
					$item_id = isset($button_args['id']) ? absint($button_args['id']) : '';
					$licence = 'free';
					$update_url = isset($button_args['update_url']) ? esc_url_raw($button_args['update_url']) : '';
					$button_args['onclick'] = 'wpeu_install_plugin(this,"'.$slug.'","'.$nonce.'","'.$update_url.'","'.$item_id.'","'.$licence.'"); return false;';
				}elseif($button_args['licensing'] && empty($button_args['license'])){ // needs licence
					$button_args['button_text'] = __('Install');
					$slug = isset($button_args['slug']) ? esc_attr($button_args['slug']) : '';
					$nonce = wp_create_nonce( 'updates' );
					$item_id = isset($button_args['id']) ? absint($button_args['id']) : '';
					$update_url = isset($button_args['update_url']) ? esc_url_raw($button_args['update_url']) : '';
					$button_args['onclick'] = 'wpeu_licence_popup(this,"'.$slug.'","'.$nonce.'","'.$update_url.'","'.$item_id.'","plugin"); return false;';
				}elseif($button_args['licensing'] && $button_args['license']){ // has licence
					$button_args['button_text'] = __('Install');
					$slug = isset($button_args['slug']) ? esc_attr($button_args['slug']) : '';
					$nonce = wp_create_nonce( 'updates' );
					$item_id = isset($button_args['id']) ? absint($button_args['id']) : '';
					$licence = isset($button_args['license']) ? esc_attr($button_args['license']) : 'license'; //@todo will this always pass a exc_attr() filter?
					$update_url = isset($button_args['update_url']) ? esc_url_raw($button_args['update_url']) : '';
					$button_args['onclick'] = 'wpeu_install_plugin(this,"'.$slug.'","'.$nonce.'","'.$update_url.'","'.$item_id.'","'.$licence.'"); return false;';
				}
			}

		}if(isset($button_args['type']) && $button_args['type']=='themes'){

			// if not installed then change the button text to install
			if(empty($button_args['installed']) && !empty($button_args['update_url'])){

				// free
				if($button_args['licensing']===false || (isset($button_args['price']) && $button_args['price']=='0.00') && !empty($button_args['update_url'])){
					$button_args['button_text'] = __('Install');
					$slug = isset($button_args['slug']) ? esc_attr($button_args['slug']) : '';
					$nonce = wp_create_nonce( 'updates' );
					$item_id = isset($button_args['id']) ? absint($button_args['id']) : '';
					$licence = 'free';
					$update_url = isset($button_args['update_url']) ? esc_url_raw($button_args['update_url']) : '';
					$button_args['onclick'] = 'wpeu_install_theme(this,"'.$slug.'","'.$nonce.'","'.$update_url.'","'.$item_id.'","'.$licence.'"); return false;';
				}elseif($button_args['licensing'] && empty($button_args['license'])){ // needs licence
					$button_args['button_text'] = __('Install');
					$slug = isset($button_args['slug']) ? esc_attr($button_args['slug']) : '';
					$nonce = wp_create_nonce( 'updates' );
					$item_id = isset($button_args['id']) ? absint($button_args['id']) : '';
					$update_url = isset($button_args['update_url']) ? esc_url_raw($button_args['update_url']) : '';
					$button_args['onclick'] = 'wpeu_licence_popup(this,"'.$slug.'","'.$nonce.'","'.$update_url.'","'.$item_id.'","theme"); return false;';
				}elseif($button_args['licensing'] && $button_args['license']){ // has licence
					$button_args['button_text'] = __('Install');
					$slug = isset($button_args['slug']) ? esc_attr($button_args['slug']) : '';
					$nonce = wp_create_nonce( 'updates' );
					$item_id = isset($button_args['id']) ? absint($button_args['id']) : '';
					$licence = isset($button_args['license']) ? esc_attr($button_args['license']) : 'license'; //@todo will this always pass a exc_attr() filter?
					$update_url = isset($button_args['update_url']) ? esc_url_raw($button_args['update_url']) : '';
					$button_args['onclick'] = 'wpeu_install_theme(this,"'.$slug.'","'.$nonce.'","'.$update_url.'","'.$item_id.'","'.$licence.'"); return false;';
				}
			}

		}

		return $button_args;
	}

	/**
	 * Callback for this 'wp_unique_filename' filter that adjusts the filename created in wp_tempnam()
	 * @param string $filename The filename being created
	 * @param string $ext The file's extension
	 * @param string $dir The file's directory
	 * @param callback $unique_filename_callback The unique filename callback reference
	 * @return string The file name to use
	 */
	public static function filter_unique_filename( $filename, $ext, $dir, $unique_filename_callback )
	{
		if ( self::$_upgrade && ( strlen( $filename ) > 120 && '.tmp' === $ext ) ) {
			$file = tempnam( $dir, 'wpeu' );		// creates a new, guaranteed unique filename with 'wpeu' prefix
			@unlink( $file );					// remove the file, since we're changing the name by adding an extension
			$file .= $ext;						// add the extension to the filename being returned
			// the file name returned will not exist. this is the expected behavior in wp_tempnam()
			return basename( $file );			// return just the filename since wp_tempnam() adds the directory
		}

		return $filename;						// the upgrade process is not happening, just return the original filename value
	}

}
