<?php
// wp eval-file: checks for the WPEU 2.0 security fixes.
$p = 0; $f = 0; $chk = function ( $n, $ok, $i = '' ) use ( &$p, &$f ) { WP_CLI::line( ( $ok ? 'ok   ' : 'FAIL ' ) . $n . ( $i ? "  $i" : '' ) ); $ok ? $p++ : $f++; };
$A = 'External_Updates_Admin';
class WPEU_Test_Gadget { public $x = 1; }
$obj = serialize( array( 'description' => new WPEU_Test_Gadget() ) );
$r = $A::safe_unserialize( $obj );
$chk( 'serialized objects never come back as real objects', is_array( $r ) && ! ( $r['description'] instanceof WPEU_Test_Gadget ), get_class( $r['description'] ) );
$chk( 'plain serialized arrays still unserialize', $A::safe_unserialize( serialize( array( 'a' => 'b' ) ) ) === array( 'a' => 'b' ) );
$chk( 'non-serialized values pass through', $A::safe_unserialize( 'hello' ) === 'hello' && $A::safe_unserialize( array( 1 ) ) === array( 1 ) );
$js = $A::js_args( array( 'a"b', "c'd", '</script>', '&amp;' ) );
$chk( 'js_args has no raw quotes, tags or ampersands', strpbrk( substr( $js, 1, -1 ), "'<>&" ) === false && substr_count( $js, '"' ) === 8, $js );
$chk( 'js_args stays valid JS strings', json_decode( '[' . $js . ']', true ) === array( 'a"b', "c'd", '</script>', '&amp;' ) );

global $wp_filter;
$admin = null;
foreach ( $wp_filter['plugins_api']->callbacks as $prio => $cbs ) { foreach ( $cbs as $cb ) { if ( is_array( $cb['function'] ) && $cb['function'][0] instanceof External_Updates_Admin ) { $admin = $cb['function'][0]; } } }
$chk( 'found the WPEU admin instance', (bool) $admin );
$hits = array();
$spy = function ( $pre, $args, $url ) use ( &$hits ) { $hits[] = $url; return array( 'response' => array( 'code' => 200 ), 'body' => '{}', 'headers' => array(), 'cookies' => array() ); };
add_filter( 'pre_http_request', $spy, 10, 3 );
$_REQUEST['update_url'] = 'https://evil.example/'; $_REQUEST['item_id'] = 5;
$admin->plugins_api_filter( false, 'plugin_information', (object) array( 'slug' => 'not-installed-x' ) );
$chk( 'a foreign update_url from a link is never requested', ! array_filter( $hits, function ( $u ) { return strpos( $u, 'evil.example' ) !== false; } ), implode( ' ', $hits ) );
$hits = array(); $_REQUEST['update_url'] = 'https://wpgeodirectory.com'; $_REQUEST['license'] = 'k'; $_REQUEST['wpeu_activate'] = 1;
$keys_before = get_option( 'exup_keys' );
$admin->plugins_api_filter( false, 'plugin_information', (object) array( 'slug' => 'not-installed-x' ) );
$chk( 'our own update_url still works for the details popup', count( $hits ) > 0, implode( ' ', $hits ) );
$chk( 'a link (not core AJAX) never activates a key', get_option( 'exup_keys' ) == $keys_before );
unset( $_REQUEST['update_url'], $_REQUEST['item_id'], $_REQUEST['license'], $_REQUEST['wpeu_activate'] );
remove_filter( 'pre_http_request', $spy, 10 );

$chk( 'membership domain: ours accepted', $admin->sanitize_membership_domain( 'WPGeoDirectory.com' ) === 'wpgeodirectory.com' );
$chk( 'membership domain: others refused', $admin->sanitize_membership_domain( 'evil.example' ) === '' && $admin->sanitize_membership_domain( 'wpgeodirectory.com/x' ) === '' && $admin->sanitize_membership_domain( '127.0.0.1:8080' ) === '' );

$managed = array_keys( $admin->get_packages_for_update( 'plugin' ) );
$file = $managed ? $managed[0] : '';
$t = new stdClass(); $t->response = array(
	$file => (object) array( 'id' => 'w.org/plugins/x', 'package' => 'https://downloads.wordpress.org/plugin/x.99.zip', 'new_version' => '99' ),
	'hello-dolly/hello.php' => (object) array( 'id' => 'w.org/plugins/hello-dolly', 'package' => 'https://downloads.wordpress.org/plugin/hello-dolly.zip' ),
);
$t = $admin->drop_wporg_offers( $t, 'plugin' );
$chk( 'wordpress.org offer removed for a managed plugin', $file && ! isset( $t->response[ $file ] ), $file );
$chk( 'wordpress.org offers for other plugins kept', isset( $t->response['hello-dolly/hello.php'] ) );
$t->response = array( $file => (object) array( 'package' => 'https://api.ayecode.io/dl/1/x', 'new_version' => '3' ) );
$chk( 'our own offer is kept', isset( $admin->drop_wporg_offers( $t, 'plugin' )->response[ $file ] ) );

wp_set_current_user( 0 );
$chk( 'update_plugins is the AJAX capability on single site', ! is_multisite() );
WP_CLI::line( "\n$p passed, $f failed" );
