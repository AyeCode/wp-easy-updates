<?php
// wp eval-file: checks for WPEU 2.0 fresh download links (fresh_links on /v1/updates, /v1/download-link at download time).
// All HTTP is answered by a pre_http_request mock; nothing leaves the machine.
$p = 0; $f = 0; $chk = function ( $n, $ok, $i = '' ) use ( &$p, &$f ) { WP_CLI::line( ( $ok ? 'ok   ' : 'FAIL ' ) . $n . ( $i ? "  $i" : '' ) ); $ok ? $p++ : $f++; };
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

global $wp_filter;
$admin = null;
foreach ( $wp_filter['upgrader_package_options']->callbacks as $prio => $cbs ) { foreach ( $cbs as $cb ) { if ( is_array( $cb['function'] ) && $cb['function'][0] instanceof External_Updates_Admin ) { $admin = $cb['function'][0]; } } }
$chk( 'fresh_package_options is hooked on upgrader_package_options', (bool) $admin );

// An inactive managed AyeCode plugin with an Update ID.
$file = '';
foreach ( $admin->get_packages_for_update( 'plugin' ) as $pf => $pk ) {
	if ( External_Updates_API::is_ours( $pk['Update URL'] ) && absint( $pk['Update ID'] ) && ! is_plugin_active( $pf ) ) { $file = $pf; $item = absint( $pk['Update ID'] ); break; }
}
$chk( 'found an inactive managed AyeCode plugin', (bool) $file, $file . ' #' . ( $file ? $item : '' ) );

// The production API host (this dev site points WP_EASY_UPDATES_API_BASE at a local Worker).
$base_filter = function () { return 'https://api.ayecode.io'; };
add_filter( 'wp_easy_updates_api_base', $base_filter );
// Core's own update checks after an upgrade are not under test.
foreach ( array( 'wp_version_check', 'wp_update_plugins', 'wp_update_themes' ) as $core_cb ) { remove_action( 'upgrader_process_complete', $core_cb, 10 ); }

// State: no DB writes for token and keys, the down flag restored at the end.
$down_before = get_option( 'exup_api_down_until' );
$token = str_repeat( 'T', 43 );
$use_token = true;
$tok = function () use ( &$use_token, $token ) { return $use_token ? $token : ''; };
add_filter( 'pre_option_ayecode_connect_site_token', $tok );
add_filter( 'pre_option_exup_site_token', '__return_empty_string' );
$keys_filter = function () use ( $file ) { return array( $file => (object) array( 'key' => 'KEY-123', 'license' => 'valid' ) ); };
add_filter( 'pre_option_exup_keys', $keys_filter );

// HTTP mock: records every request, answers per path.
$hits = array();
$answer = array( 'code' => 200, 'body' => array() );
$spy = function ( $pre, $args, $url ) use ( &$hits, &$answer ) {
	$hits[] = array( 'url' => $url, 'args' => $args, 'body' => is_string( $args['body'] ) ? json_decode( $args['body'], true ) : $args['body'] );
	$a = is_callable( $answer ) ? call_user_func( $answer, $url ) : $answer;
	return array( 'response' => array( 'code' => $a['code'], 'message' => '' ), 'body' => wp_json_encode( $a['body'] ), 'headers' => array(), 'cookies' => array() );
};
add_filter( 'pre_http_request', $spy, 10, 3 );
$auth = function ( $h ) { return isset( $h['args']['headers']['Authorization'] ) ? $h['args']['headers']['Authorization'] : ''; };

$stale = 'https://api.ayecode.io/dl/geodir-compare/2.0.0?exp=1&sid=1&sig=old';
$fresh = 'https://api.ayecode.io/dl/geodir-compare/2.0.0?exp=9999999999&sid=1&sig=new';

// 1. fresh_links on the update check.
$hits = array(); $answer = array( 'code' => 200, 'body' => array( 'packages' => array() ) );
External_Updates_API::check_updates( array( $file => array( 'item_id' => $item, 'version' => '1.0', 'license' => '' ) ) );
$chk( 'update check goes to /v1/updates', count( $hits ) === 1 && $hits[0]['url'] === 'https://api.ayecode.io/v1/updates', $hits ? $hits[0]['url'] : 'no request' );
$chk( 'update check sends fresh_links: true', $hits && isset( $hits[0]['body']['fresh_links'] ) && true === $hits[0]['body']['fresh_links'] );
$hits = array();
External_Updates_API::check_updates( array( $file => array( 'item_id' => $item ) ), false );
$chk( 'one-off lookup (install, details) also sends fresh_links', $hits && ! empty( $hits[0]['body']['fresh_links'] ) );

// 2. Token case, through the real filter.
$hits = array(); $answer = array( 'code' => 200, 'body' => array( 'package' => $fresh ) );
$o = apply_filters( 'upgrader_package_options', array( 'package' => $stale, 'hook_extra' => array( 'plugin' => $file ) ) );
$h = $hits ? $hits[0] : array();
$chk( 'token: package swapped for the fresh link', $o['package'] === $fresh, $o['package'] );
$chk( 'token: one call to /v1/download-link', count( $hits ) === 1 && $h['url'] === 'https://api.ayecode.io/v1/download-link', $h ? $h['url'] : '' );
$chk( 'token: Bearer site token sent', $h && $auth( $h ) === 'Bearer ' . $token );
$chk( 'token: body has item_id and site url, no licence key', $h && $h['body']['item_id'] === $item && $h['body']['url'] === home_url() && ! isset( $h['body']['license'] ), wp_json_encode( $h ? $h['body'] : '' ) );
$chk( 'token: POST with JSON and SSL verify', $h && 'POST' === $h['args']['method'] && true === $h['args']['sslverify'] && 'application/json' === $h['args']['headers']['Content-Type'] );

// 3. Key case: no token on the site.
$use_token = false; $hits = array();
$o = apply_filters( 'upgrader_package_options', array( 'package' => $stale, 'hook_extra' => array( 'plugin' => $file ) ) );
$h = $hits ? $hits[0] : array();
$chk( 'key: package swapped for the fresh link', $o['package'] === $fresh );
$chk( 'key: no Authorization header', $h && '' === $auth( $h ) );
$chk( 'key: body carries the licence key the update check sends', $h && isset( $h['body']['license'] ) && 'KEY-123' === $h['body']['license'] && $h['body']['item_id'] === $item, wp_json_encode( $h ? $h['body'] : '' ) );
$use_token = true;

// 4. Not ours: no call, package untouched.
$hits = array();
$wporg = 'https://downloads.wordpress.org/plugin/geodir-compare.2.0.0.zip';
$o = apply_filters( 'upgrader_package_options', array( 'package' => $wporg, 'hook_extra' => array( 'plugin' => $file ) ) );
$chk( 'wordpress.org package: untouched and no API call', $o['package'] === $wporg && ! $hits );
$hits = array();
$o = apply_filters( 'upgrader_package_options', array( 'package' => 'https://downloads.wordpress.org/plugin/hello-dolly.1.7.zip', 'hook_extra' => array( 'plugin' => 'hello-dolly/hello.php' ) ) );
$chk( 'wordpress.org plugin: no API call', ! $hits );
$hits = array();
$o = apply_filters( 'upgrader_package_options', array( 'package' => 'https://api.ayecode.io.evil.example/dl/x', 'hook_extra' => array( 'plugin' => $file ) ) );
$chk( 'look-alike host: no API call', ! $hits );
$hits = array();
$o = apply_filters( 'upgrader_package_options', array( 'package' => $stale, 'hook_extra' => array( 'plugin' => 'hello-dolly/hello.php' ) ) );
$chk( 'API package for a plugin with no Update ID: no API call', $o['package'] === $stale && ! $hits );
$hits = array();
$o = apply_filters( 'upgrader_package_options', array( 'package' => $stale, 'hook_extra' => array( 'type' => 'plugin', 'action' => 'install' ) ) );
$chk( 'install with no known item: no API call, untouched', $o['package'] === $stale && ! $hits );

// 5. Failures keep the original package.
$hits = array(); $answer = array( 'code' => 403, 'body' => array( 'error' => 'expired', 'message' => 'Licence key has expired.' ) );
$o = apply_filters( 'upgrader_package_options', array( 'package' => $stale, 'hook_extra' => array( 'plugin' => $file ) ) );
$chk( '403 error: original package kept', $o['package'] === $stale && count( $hits ) === 1 );
$answer = array( 'code' => 500, 'body' => array() );
$o = apply_filters( 'upgrader_package_options', array( 'package' => $stale, 'hook_extra' => array( 'plugin' => $file ) ) );
$chk( '500 error: original package kept', $o['package'] === $stale );
$answer = array( 'code' => 200, 'body' => array( 'package' => 'https://evil.example/x.zip' ) );
$o = apply_filters( 'upgrader_package_options', array( 'package' => $stale, 'hook_extra' => array( 'plugin' => $file ) ) );
$chk( 'link to another host in a 200: original package kept', $o['package'] === $stale );
$answer = array( 'code' => 200, 'body' => array() );
$o = apply_filters( 'upgrader_package_options', array( 'package' => $stale, 'hook_extra' => array( 'plugin' => $file ) ) );
$chk( '200 with no package: original package kept', $o['package'] === $stale );
$hits = array();
$fail = function () { return new WP_Error( 'http_request_failed', 'timed out' ); };
add_filter( 'pre_http_request', $fail, 20 );
$o = apply_filters( 'upgrader_package_options', array( 'package' => $stale, 'hook_extra' => array( 'plugin' => $file ) ) );
remove_filter( 'pre_http_request', $fail, 20 );
$chk( 'network error: original package kept', $o['package'] === $stale );

// 6. Real core upgrader runs (single, bulk; auto-updates use upgrade()). The download is stopped at
// upgrader_pre_download, which records the package core would fetch.
$answer = array( 'code' => 200, 'body' => array( 'package' => $fresh ) );
$offer = (object) array( 'slug' => 'geodir-compare', 'plugin' => $file, 'new_version' => '99.0', 'package' => $stale );
$tr = function () use ( $file, $offer ) { $t = new stdClass(); $t->last_checked = time(); $t->checked = array(); $t->response = array( $file => $offer ); return $t; };
add_filter( 'site_transient_update_plugins', $tr );
$downloaded = array();
$stop = function ( $r, $package ) use ( &$downloaded ) { $downloaded[] = $package; return new WP_Error( 'wpeu_test_stop', 'stopped by test' ); };
add_filter( 'upgrader_pre_download', $stop, 1, 2 );
$hits = array();
$up = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
$up->upgrade( $file );
$chk( 'single/auto update: core downloads the fresh link', $downloaded === array( $fresh ), implode( ' ', $downloaded ) );
$downloaded = array(); $hits = array();
$up = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
$up->bulk_upgrade( array( $file ) );
$chk( 'bulk update: core downloads the fresh link', $downloaded === array( $fresh ), implode( ' ', $downloaded ) );
$chk( 'bulk update: one /v1/download-link call', count( $hits ) === 1 && false !== strpos( $hits[0]['url'], '/v1/download-link' ) );
remove_filter( 'site_transient_update_plugins', $tr );

// 7. Install path: plugins_api for a not installed AyeCode item (as the extensions screens call it), then install.
$hits = array();
$answer = function ( $url ) use ( $stale, $fresh ) {
	if ( false !== strpos( $url, '/v1/updates' ) ) {
		return array( 'code' => 200, 'body' => array( 'packages' => array( 'not-installed-x' => array( 'name' => 'X', 'slug' => 'not-installed-x', 'new_version' => '1.0', 'package' => $stale, 'download_link' => $stale ) ) ) );
	}
	return array( 'code' => 200, 'body' => array( 'package' => $fresh ) );
};
add_filter( 'pre_option_exup_api_down_until', '__return_zero' );
$_REQUEST['update_url'] = 'https://wpgeodirectory.com'; $_REQUEST['item_id'] = 4242; $_REQUEST['license'] = 'INSTALL-KEY';
$api = plugins_api( 'plugin_information', array( 'slug' => 'not-installed-x' ) );
unset( $_REQUEST['update_url'], $_REQUEST['item_id'], $_REQUEST['license'] );
remove_filter( 'pre_option_exup_api_down_until', '__return_zero' );
$chk( 'install: plugins_api returns the API link', is_object( $api ) && ! is_wp_error( $api ) && $api->download_link === $stale );
$downloaded = array(); $hits = array();
$up = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
$up->install( $stale );
$h = $hits ? $hits[0] : array();
$chk( 'install: core downloads the fresh link', $downloaded === array( $fresh ), implode( ' ', $downloaded ) );
$chk( 'install: asks for the item from the request', $h && 4242 === $h['body']['item_id'] && 'Bearer ' . $token === $auth( $h ) && ! isset( $h['body']['license'] ), wp_json_encode( $h ? $h['body'] : '' ) );
$use_token = false; $downloaded = array(); $hits = array();
$up = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
$up->install( $stale );
$h = $hits ? $hits[0] : array();
$chk( 'install, no token: sends the key from the request', $h && 'INSTALL-KEY' === $h['body']['license'] && $downloaded === array( $fresh ), wp_json_encode( $h ? $h['body'] : '' ) );
$use_token = true;
remove_filter( 'upgrader_pre_download', $stop, 1 );

remove_filter( 'pre_http_request', $spy, 10 );
remove_filter( 'wp_easy_updates_api_base', $base_filter );
remove_filter( 'pre_option_ayecode_connect_site_token', $tok );
remove_filter( 'pre_option_exup_site_token', '__return_empty_string' );
remove_filter( 'pre_option_exup_keys', $keys_filter );
update_option( 'exup_api_down_until', $down_before, false ); // the 500 and timeout cases set it

WP_CLI::line( "\n$p passed, $f failed" );
