<?php
/**
 * WP Easy Updates 2.0: AyeCode API client (api.ayecode.io).
 *
 * For AyeCode products the update check, activation and deactivation go to the API with a per-site token
 * instead of posting licence keys to each product domain. Third-party EDD stores keep the legacy path.
 *
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class External_Updates_API {

	const OPTION_TOKEN = 'exup_site_token';
	const OPTION_DOWN  = 'exup_api_down_until';

	/** Our product domains, as they appear in plugin "Update URL" headers. */
	public static function our_domains() {
		return apply_filters( 'wp_easy_updates_our_domains', array( 'wpgeodirectory.com', 'wpinvoicing.com', 'wpgetpaid.com', 'userswp.io' ) );
	}

	public static function is_ours( $update_url ) {
		$host = strtolower( (string) wp_parse_url( $update_url, PHP_URL_HOST ) );
		if ( ! $host ) {
			return false;
		}
		foreach ( self::our_domains() as $domain ) {
			if ( $host === $domain || '.' . $domain === substr( $host, - strlen( '.' . $domain ) ) ) {
				return true;
			}
		}
		return false;
	}

	public static function base() {
		$base = defined( 'WP_EASY_UPDATES_API_BASE' ) ? WP_EASY_UPDATES_API_BASE : 'https://api.ayecode.io';
		return untrailingslashit( apply_filters( 'wp_easy_updates_api_base', $base ) );
	}

	/**
	 * Site token: the one AyeCode Connect holds when the site is connected, else ours. The API keeps one token per
	 * site, so Connect's (the newest) must win or the two plugins keep replacing each other's.
	 */
	public static function get_token() {
		$token = get_option( 'ayecode_connect_site_token', '' );
		if ( ! $token ) {
			$token = get_option( self::OPTION_TOKEN, '' );
		}
		return is_string( $token ) ? $token : '';
	}

	public static function set_token( $token ) {
		if ( is_string( $token ) && strlen( $token ) >= 32 ) {
			update_option( self::OPTION_TOKEN, sanitize_text_field( $token ), false );
		}
	}

	/** True for an hour after the API failed, so the legacy path is used without waiting on timeouts. */
	public static function is_down() {
		return (int) get_option( self::OPTION_DOWN, 0 ) > time();
	}

	protected static function mark_down() {
		update_option( self::OPTION_DOWN, time() + HOUR_IN_SECONDS, false );
	}

	/**
	 * JSON request. Returns the decoded body, or WP_Error.
	 */
	public static function request( $path, array $body, $with_token = true ) {
		$headers = array( 'Content-Type' => 'application/json', 'Accept' => 'application/json', 'User-Agent' => 'WP Easy Updates/' . WP_EASY_UPDATES_VERSION . '; ' . home_url() );
		$token   = $with_token ? self::get_token() : '';
		if ( $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}
		$response = wp_remote_post( self::base() . $path, array(
			'timeout'   => 20,
			'sslverify' => true, // always: these requests carry the site token and licence keys
			'headers'   => $headers,
			'body'      => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $response ) ) {
			self::mark_down();
			return $response;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code >= 500 ) {
			self::mark_down();
			return new WP_Error( 'api_down', 'API returned ' . $code );
		}
		if ( 401 === $code && $token ) {
			// Our own token is dropped and keys are sent instead. AyeCode Connect's token is Connect's to replace.
			if ( get_option( self::OPTION_TOKEN ) === $token ) {
				delete_option( self::OPTION_TOKEN );
			}
			return new WP_Error( 'api_unauthorized', 'Site token rejected' );
		}
		if ( $code >= 400 ) {
			return new WP_Error( 'api_error', isset( $data['message'] ) ? $data['message'] : 'API error ' . $code, $data );
		}
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Batched update check for every AyeCode package on this site, in one request.
	 *
	 * @param array $packages slug => package array (slug, item_id, version, beta, license)
	 * @param bool  $record_versions false for a one-off lookup (install, view details) so the site's installed
	 *                               versions on the API are not replaced by a single entry.
	 * @return object|false object keyed by slug with EDD-SL shaped entries, as api_request() returns
	 */
	public static function check_updates( array $packages, $record_versions = true ) {
		$list = array();
		$versions = array();
		foreach ( $packages as $slug => $p ) {
			$list[] = array(
				'slug'    => $slug,
				'item_id' => isset( $p['item_id'] ) ? (int) $p['item_id'] : 0,
				'version' => isset( $p['version'] ) ? $p['version'] : '',
				'beta'    => ! empty( $p['beta'] ),
				'license' => isset( $p['license'] ) ? $p['license'] : '',
			);
			$versions[ $slug ] = isset( $p['version'] ) ? $p['version'] : '';
		}
		// fresh_links: package links are short lived, so WPEU asks for a new one when the download starts (download_link()).
		$body = array( 'url' => home_url(), 'packages' => $list, 'fresh_links' => true );
		if ( $record_versions ) {
			$body['versions'] = $versions;
		}
		$data = self::request( '/v1/updates', $body );
		if ( is_wp_error( $data ) || empty( $data['packages'] ) ) {
			return false;
		}
		if ( ! empty( $data['site']['token'] ) ) {
			self::set_token( $data['site']['token'] );
		}
		$out = new stdClass();
		foreach ( $data['packages'] as $slug => $info ) {
			if ( ! is_string( $slug ) || '' === $slug || "\0" === $slug[0] || ! is_array( $info ) ) {
				continue; // malformed entry: skip it rather than fatal the update check
			}
			$out->{$slug} = (object) $info;
		}
		return $out;
	}

	/**
	 * True if a package URL is an API download link (same scheme and host as the API base).
	 */
	public static function is_api_package( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$ours = strtolower( (string) wp_parse_url( self::base(), PHP_URL_HOST ) );
		return $host && $host === $ours && strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) === strtolower( (string) wp_parse_url( self::base(), PHP_URL_SCHEME ) );
	}

	/**
	 * A fresh download link for an item, asked for when the download starts: API links only live a few minutes but
	 * WordPress keeps update offers (and their package URL) for hours. Same auth as the update check: the site token
	 * when there is one, else the item's licence key.
	 *
	 * @param int    $item_id The item's "Update ID".
	 * @param string $license The item's licence key, sent only when the site has no token.
	 * @param bool   $beta    True if the site takes beta versions of this item.
	 * @return string|WP_Error the new package URL
	 */
	public static function download_link( $item_id, $license = '', $beta = false ) {
		$body = array( 'url' => home_url(), 'item_id' => (int) $item_id );
		if ( $license && ! self::get_token() ) {
			$body['license'] = $license;
		}
		if ( $beta ) {
			$body['beta'] = true;
		}
		$data = self::request( '/v1/download-link', $body );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( empty( $data['package'] ) || ! self::is_api_package( $data['package'] ) ) {
			return new WP_Error( 'api_bad_link', 'No usable download link in the API response' );
		}
		return $data['package'];
	}

	/**
	 * Activate a key for one or more item ids. Returns the API's response as an object with the legacy
	 * fields the UI reads (license, error, expires, site_count ...), or WP_Error.
	 */
	public static function activate( $key, array $item_ids ) {
		$body = array( 'key' => $key, 'url' => home_url() );
		if ( count( $item_ids ) === 1 ) {
			$body['item_id'] = (int) reset( $item_ids );
		} else {
			$body['item_ids'] = array_map( 'intval', $item_ids );
		}
		$token = self::get_token();
		if ( $token ) {
			$body['site_token'] = $token;
		}
		$data = self::request( '/v1/licences/activate', $body );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( ! empty( $data['site']['token'] ) ) {
			self::set_token( $data['site']['token'] );
		}
		return (object) $data;
	}

	public static function deactivate( $key ) {
		$data = self::request( '/v1/licences/deactivate', array( 'key' => $key, 'url' => home_url() ) );
		return is_wp_error( $data ) ? $data : (object) $data;
	}
}
