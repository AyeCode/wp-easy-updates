<?php
// wp eval-file: checks for WPEU 2.1 language packs (#24 in ayecode-platform): the update check sends the site's
// languages, the API's packs land in the update_plugins transient's `translations`, a wordpress.org pack for the same
// text domain and language is dropped, bad offers are ignored, and pack downloads are left to core.
// Loads this checkout's plugin when WP Easy Updates is not active. All HTTP is answered by a pre_http_request mock;
// options are read through pre_option filters, so nothing is written to the site.
$p = 0; $f = 0; $chk = function ( $n, $ok, $i = '' ) use ( &$p, &$f ) { WP_CLI::line( ( $ok ? 'ok   ' : 'FAIL ' ) . $n . ( $i ? "  $i" : '' ) ); $ok ? $p++ : $f++; };
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( ! class_exists( 'External_Updates' ) ) {
	require dirname( __DIR__ ) . '/external-updates.php';
}

global $wp_filter;
$admin = null;
foreach ( $wp_filter['pre_set_site_transient_update_plugins']->callbacks as $cbs ) { foreach ( $cbs as $cb ) { if ( is_array( $cb['function'] ) && $cb['function'][0] instanceof External_Updates_Admin ) { $admin = $cb['function'][0]; } } }
$chk( 'update check is hooked', (bool) $admin );

// A managed AyeCode plugin on this site.
$file = ''; $domain = '';
foreach ( $admin->get_packages_for_update( 'plugin' ) as $pf => $pk ) {
	if ( External_Updates_API::is_ours( $pk['Update URL'] ) && absint( $pk['Update ID'] ) && ! empty( $pk['TextDomain'] ) ) { $file = $pf; $domain = $pk['TextDomain']; $item = absint( $pk['Update ID'] ); break; }
}
$chk( 'found a managed AyeCode plugin with a text domain', (bool) $file, "$file $domain" );

add_filter( 'wp_easy_updates_api_base', function () { return 'https://api.ayecode.io'; } );
add_filter( 'pre_option_ayecode_connect_site_token', function () { return str_repeat( 'T', 43 ); } );
add_filter( 'pre_option_exup_site_token', '__return_empty_string' );
add_filter( 'pre_option_exup_keys', '__return_empty_array' );
add_filter( 'pre_option_exup_api_down_until', '__return_zero' );
$langs = array( 'de_DE', 'fr_FR' );
$lang_filter = function () use ( &$langs ) { return $langs; };
add_filter( 'plugins_update_check_locales', $lang_filter );

$ours_de = 'https://api.ayecode.io/dl/t/' . $item . '/3/de_DE?exp=9999999999&sid=1&k=K&sig=abc';
$hits = array();
$spy = function ( $pre, $args, $url ) use ( &$hits, $file, $domain, $ours_de ) {
	$req = json_decode( $args['body'], true );
	$hits[] = array( 'url' => $url, 'body' => $req );
	// Like the API: an entry for every package asked about.
	$answered = array();
	foreach ( (array) $req['packages'] as $pk ) { $answered[ $pk['slug'] ] = array( 'new_version' => isset( $pk['version'] ) ? $pk['version'] : '1.0', 'slug' => $pk['slug'], 'package' => '' ); }
	$body = array(
		'packages'     => $answered,
		'translations' => array(
			array( 'type' => 'plugin', 'slug' => $domain, 'language' => 'de_DE', 'version' => '3.0.0-alpha', 'updated' => '2026-11-06 17:33:16', 'package' => $ours_de, 'autoupdate' => true ),
			array( 'type' => 'plugin', 'slug' => $domain, 'language' => 'it_IT', 'version' => '3.0.0-alpha', 'updated' => '2026-11-06 17:33:16', 'package' => 'https://evil.example/x.zip', 'autoupdate' => true ),
			array( 'type' => 'plugin', 'slug' => '../etc', 'language' => 'de_DE', 'package' => $ours_de ),
			array( 'type' => 'plugin', 'slug' => $domain, 'language' => 'not a locale', 'package' => $ours_de ),
		),
		'site'         => array( 'id' => 1, 'local' => false ),
	);
	return array( 'response' => array( 'code' => 200, 'message' => '' ), 'body' => wp_json_encode( $body ), 'headers' => array(), 'cookies' => array() );
};
add_filter( 'pre_http_request', $spy, 10, 3 );

$transient = function () use ( $domain ) {
	$t = new stdClass();
	$t->last_checked = time(); $t->response = array(); $t->checked = array();
	$t->translations = array(
		array( 'type' => 'plugin', 'slug' => $domain, 'language' => 'de_DE', 'version' => '2.3.5', 'updated' => '2020-01-01 00:00:00', 'package' => 'https://downloads.wordpress.org/translation/plugin/x/de_DE.zip', 'autoupdate' => true ),
		array( 'type' => 'plugin', 'slug' => 'hello-dolly', 'language' => 'fr_FR', 'version' => '1.7', 'updated' => '2020-01-01 00:00:00', 'package' => 'https://downloads.wordpress.org/translation/plugin/hello-dolly/fr_FR.zip', 'autoupdate' => true ),
	);
	return $t;
};

// Core runs the filter twice per check; WPEU acts on the second (has_plugin_check_run).
apply_filters( 'pre_set_site_transient_update_plugins', $transient() );
$t = apply_filters( 'pre_set_site_transient_update_plugins', $transient() );
$req = $hits ? $hits[0]['body'] : array();
$chk( 'one request to /v1/updates', count( $hits ) === 1 && 'https://api.ayecode.io/v1/updates' === $hits[0]['url'] );
$chk( 'request sends the site languages', isset( $req['locales'] ) && array_values( array_intersect( array( 'de_DE', 'fr_FR' ), $req['locales'] ) ) === array( 'de_DE', 'fr_FR' ) && ! in_array( 'en_US', $req['locales'], true ), wp_json_encode( isset( $req['locales'] ) ? $req['locales'] : null ) );
$chk( 'request sends installed translation dates (a map)', isset( $req['translations'] ) && is_array( $req['translations'] ) );

$by = array();
foreach ( $t->translations as $x ) { $x = (array) $x; $by[ $x['slug'] . '|' . $x['language'] ][] = $x['package']; }
$chk( 'our de_DE pack is in the transient', isset( $by[ "$domain|de_DE" ] ) && $by[ "$domain|de_DE" ] === array( $ours_de ), wp_json_encode( $by ) );
$chk( 'the wordpress.org de_DE pack for the same domain is gone', ! in_array( 'https://downloads.wordpress.org/translation/plugin/x/de_DE.zip', isset( $by[ "$domain|de_DE" ] ) ? $by[ "$domain|de_DE" ] : array(), true ) );
$chk( 'other wordpress.org packs are kept', isset( $by['hello-dolly|fr_FR'] ) );
$chk( 'an offer whose package is not an API link is ignored', ! isset( $by[ "$domain|it_IT" ] ) );
$chk( 'malformed offers are ignored', ! isset( $by['../etc|de_DE'] ) && count( $t->translations ) === 2, count( $t->translations ) . ' entries' );

// Core sets the transient again in the same page load: no second request, no duplicates.
$t2 = apply_filters( 'pre_set_site_transient_update_plugins', $transient() );
$chk( 'second pass in the same load: no new request', count( $hits ) === 1 );
$chk( 'second pass: still one entry per domain and language', count( $t2->translations ) === 2 );

// What core's Language_Pack_Upgrader reads.
add_filter( 'site_transient_update_plugins', function () use ( $t ) { return $t; } );
require_once ABSPATH . 'wp-admin/includes/update.php';
$updates = wp_get_translation_updates();
$found = array_filter( $updates, function ( $u ) use ( $domain ) { return $u->slug === $domain && 'de_DE' === $u->language; } );
$chk( 'core sees our pack as a translation update', count( $found ) === 1 && reset( $found )->package === $ours_de );

// The pack download is left to core: WPEU's package filter does not touch a language update.
$hits = array();
$o = apply_filters( 'upgrader_package_options', array( 'package' => $ours_de, 'hook_extra' => array( 'language_update_type' => 'plugin', 'language_update' => reset( $found ) ) ) );
$chk( 'language update download: package untouched, no API call', $o['package'] === $ours_de && ! $hits );

// An English-only site sends no languages.
$langs = array();
$switch = function () { return 'en_US'; };
add_filter( 'locale', $switch );
$chk( 'English site: no languages', array() === External_Updates_API::site_locales() );
remove_filter( 'locale', $switch );

// Installed translation dates come from core's list, only for our domains and the site languages.
$chk( 'installed_translations ignores packages without a text domain', array() === External_Updates_API::installed_translations( array( 'x/x.php' => array( 'Version' => '1' ) ), array( 'de_DE' ) ) );

remove_filter( 'pre_http_request', $spy, 10 );
WP_CLI::line( $f ? "\n$f FAILED, $p passed" : "\nall $p passed" );
