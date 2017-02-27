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
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $has_run;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->has_run     = false;

	}

	/**
	 * Register the stylesheets for the admin area.
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

			$ajax_nonce = wp_create_nonce( "exup-ajax-security" );

			$keys = get_option( 'exup_keys', array() );

			if ( isset( $keys[ $plugin_file ] ) && $keys[ $plugin_file ]->key ) {

				$key                = sanitize_text_field( $keys[ $plugin_file ]->key );
				$deactivate_display = "";
				$activate_display   = " display:none;";
				$key_disabled       = "disabled";
				$licence_class      = "external-updates-active";

			} else {
				$deactivate_display = " display:none; ";
				$activate_display   = "";
				$key                = '';
				$key_disabled       = '';
				$licence_class      = '';
			}

			$html = '';

			// activate link
			$html .= '<a href="javascript:void(0);" class="external-updates-licence-toggle ' . $licence_class . '" onclick="exup_enter_licence_key(this);" >' . _x( 'Licence key', 'Plugin action link label.', 'external-updates' ) . '</a>';

			// add licence activation html
			$html .= '<div class="external-updates-key-input" style="display:none;">';
			$html .= '<p>';
			$html .= '<input ' . $key_disabled . ' type="text" value="' . $key . '" class="external-updates-key-value" placeholder="' . __( 'Enter your licence key', 'external-updates' ) . '" />';
			$html .= '<span style="' . $deactivate_display . '" class="button-primary" onclick="exup_deactivate_licence_key(this,\'' . $plugin_file . '\',\'' . $ajax_nonce . '\');">' . __( 'Deactivate', 'external-updates' ) . '</span>';
			$html .= '<span style="' . $activate_display . '" class="button-primary" onclick="exup_activate_licence_key(this,\'' . $plugin_file . '\',\'' . $ajax_nonce . '\');">' . __( 'Activate', 'external-updates' ) . '</span>';
			$html .= '</p>';
			$html .= '</div>';

			$actions[] = $html;

		}

		return $actions;
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
			$plugin = isset( $_POST['exup_plugin'] ) ? sanitize_text_field( $_POST['exup_plugin'] ) : '';

			if ( $key && $plugin ) {

				$activate = $this->activate_licence( $plugin, $key );

				echo json_encode( $activate );
			}

		} elseif ( isset( $_POST['exup_action'] ) && $_POST['exup_action'] == 'deactivate_key' ) {

			$key    = isset( $_POST['exup_key'] ) ? sanitize_text_field( $_POST['exup_key'] ) : '';
			$plugin = isset( $_POST['exup_plugin'] ) ? sanitize_text_field( $_POST['exup_plugin'] ) : '';

			if ( $key && $plugin ) {

				$activate = $this->deactivate_licence( $plugin, $key );

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
	public function activate_licence( $plugin, $key ) {

		$plugins = get_plugins();

		if ( ! isset( $plugins[ $plugin ] ) ) {
			return false;
		}

		$product = $plugins[ $plugin ];

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
				$keys              = get_option( 'exup_keys', array() );
				$licence_data->key = $key;
				$keys[ $plugin ]   = $licence_data;
				update_option( 'exup_keys', $keys );

				return array( 'success' => __( 'Licence activated!', 'external-updates' ) );

			} elseif ( $licence_data->license == 'invalid' ) {
				return array( 'error' => __( 'Licence key is invalid', 'external-updates' ) );
			} else {
				return array( 'error' => __( 'Something went wrong!', 'external-updates' ) );
			}

		}
	}

	/**
	 * Deactivate the plugin with the EDD install.
	 *
	 * @param $plugin string The plugin slug.
	 * @param $key    string The plugin licence key.
	 *
	 * @return array|bool
	 */
	public function deactivate_licence( $plugin, $key ) {

		$plugins = get_plugins();

		if ( ! isset( $plugins[ $plugin ] ) ) {
			return false;
		}

		$product = $plugins[ $plugin ];

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
			return false;
		} else {

			// decode the license data
			$licence_data_json = wp_remote_retrieve_body( $response );
			$licence_data      = json_decode( $licence_data_json );

			if ( $licence_data->license == 'deactivated' ) {
				$keys              = get_option( 'exup_keys', array() );
				$licence_data->key = $key;
				unset( $keys[ $plugin ] );
				update_option( 'exup_keys', $keys );

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
	public function check_for_updates( $_transient_data ) {

		// due to WP core bug this can run twice so we only run on the second one.
		if ( ! $this->has_run ) {
			$this->has_run = true;

			return $_transient_data;
		}


		$sources = $this->get_plugins_for_update_by_src();

		if ( ! empty( $sources ) ) {

			foreach ( $sources as $src => $plugins ) {

				if ( strpos( $src, '://github.com/' ) !== false ) {

					foreach ( $plugins as $plugin ) {
						$version_info = $this->api_request( 'github_version', $src, $plugin );
						if ( ! empty( $version_info ) ) {
							$_transient_data = $this->process_update_transient_data( $version_info, $_transient_data );
						}
					}

				} else {

					// we can't send an array until EDD release SL 3.6 https://github.com/easydigitaldownloads/EDD-Software-Licensing/issues/552
					$edd_send_array = apply_filters( 'exup_edd_send_array', false );

					// our own plugins should send as an array
					if ( strpos( $src, 'wpgeodirectory.com' ) !== false ) {
						$edd_send_array = true;
					}

					if ( $edd_send_array ) {

						$version_info = $this->api_request( 'get_version', $src, $plugins );
						if ( ! empty( $version_info ) ) {
							$_transient_data = $this->process_update_transient_data( $version_info, $_transient_data );
						}

					} else {

						foreach ( $plugins as $plugin ) {
							$version_info = $this->api_request( 'get_version', $src, $plugin );
							if ( ! empty( $version_info ) ) {
								$_transient_data = $this->process_update_transient_data( $version_info, $_transient_data );
							}
						}

					}

				}

			}
		}

		return $_transient_data;
	}

	public function get_plugins_for_update_by_src() {
		$update_plugins = array();
		$plugins        = $this->get_plugins_for_update();
		$keys           = get_option( 'exup_keys', array() );

		foreach ( $plugins as $key => $plugin ) {
			if ( isset( $plugin['Update URL'] ) && $plugin['Update URL'] ) {

				// setup the updater
				$update_array = array(
					'slug'    => $key,
					// the addon slug
					'version' => $plugin['Version'],
					// current version number
					'license' => isset( $keys[ $key ]->key ) ? $keys[ $key ]->key : '',
					// license key (used get_option above to retrieve from DB)
					'item_id' => $plugin['Update ID'],
					// id of this addon on GD site
					'url'     => home_url(),
					'beta'    => ! empty( $plugin['beta'] ),
				);


				$update_plugins[ $plugin['Update URL'] ][ $key ] = $update_array + $plugin;
			}
		}

		return $update_plugins;
	}

	public function get_plugins_for_update() {
		$update_plugins = array();
		$plugins        = get_plugins();

		foreach ( $plugins as $key => $plugin ) {
			if ( isset( $plugin['Update URL'] ) && $plugin['Update URL'] ) {
				if ( isset( $plugin['Version'] ) ) {
					$plugin['version'] = $plugin['Version'];
				}
				$update_plugins[ $key ] = $plugin;
			}
		}

		return $update_plugins;
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
		if ( isset( $_data['slug'] ) ) {
			foreach ( $update_array as $slug => $plugin ) {
				if ( $_data['slug'] == $plugin['slug'] ) {
					$update_array          = array();
					$update_array[ $slug ] = $plugin;
					$single                = true;
				}
			}
		}

		$api_params = array(
			'edd_action'   => 'get_version',//$_action,
			'update_array' => $update_array,
			'url'          => home_url()
		);


		$request = wp_remote_post( $_src, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		if ( ! is_wp_error( $request ) ) {
			$request = json_decode( wp_remote_retrieve_body( $request ) );
			$request = self::unserialize_response( $request, $single );

			return $request;
		}

		return false;

	}

	public function github_api_request( $_src, $_data ) {

		// convert to api url
		$_src = str_replace( '://github.com/', '://api.github.com/repos/', $_src );
		$_src = trailingslashit( $_src ) . 'releases';


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
	public function unserialize_response( $response, $single ) {

		foreach ( $response as $rslug => $rplugin ) {
			$response->{$rslug}->sections = maybe_unserialize( $response->{$rslug}->sections );
			if ( $single ) {
				$response = $response->{$rslug};
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
	public function process_update_transient_data( $version_info, $_transient_data ) {

		$update_array = $this->get_plugins_for_update();

		foreach ( $version_info as $name => $plugin_info ) {
			if ( version_compare( $update_array[ $name ]['version'], $plugin_info->new_version, '<' ) ) {
				$_transient_data->response[ $name ] = $plugin_info;
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

		$plugins = $this->get_plugins_for_update();

		if ( $_action != 'plugin_information' || ! isset( $_args->slug ) || ( ! array_key_exists( $_args->slug, $plugins ) ) ) {
			return $_data;
		}


		$update_url = $plugins[ $_args->slug ]['Update URL'];
		$update_id  = $plugins[ $_args->slug ]['Update ID'];

		$update_array[ $_args->slug ] = array(
			'slug'    => $_args->slug,                      // the addon slug
			'version' => $plugins[ $_args->slug ]['Version'],       // current version number
			'license' => '',           // license key (used get_option above to retrieve from DB)
			'item_id' => $update_id   // id of this addon on GD site
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


		$_data->banners = maybe_unserialize( $_data->banners );
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

		if ( isset( $Uthis->skin->plugin_info['Update ID'] ) ) {// check if we are dealing with a plugin that requires a licence key
			$plugin_name = isset( $Uthis->skin->plugin_info['Name'] ) ? $Uthis->skin->plugin_info['Name'] : __( 'Plugin Name', 'external-updates' );
			if ( is_network_admin() ) {
				$Uthis->strings['no_package'] = $Uthis->strings['no_package'] . ' '
				                                . sprintf( __( 'A licence key is required to update, please enter it under the main site: Plugins > %s > Licence key.', 'plugin-domain' ), $plugin_name );
			} else {
				$Uthis->strings['no_package'] = $Uthis->strings['no_package'] . ' '
				                                . sprintf( __( 'A licence key is required to update, please enter it under: Plugins > %s > Licence key.', 'plugin-domain' ), $plugin_name );
			}

		}


		return $false;
	}
}
