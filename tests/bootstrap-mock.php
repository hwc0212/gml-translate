<?php
/** Minimal WordPress primitives for deterministic CLI tests. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

final class GML_Translate_Test_State {
	public static $options = [];
	public static $actions = [];

	public static function reset() {
		self::$options = [];
		self::$actions = [];
	}
}

function get_option( $key, $default = false ) {
	return array_key_exists( $key, GML_Translate_Test_State::$options )
		? GML_Translate_Test_State::$options[ $key ]
		: $default;
}
function update_option( $key, $value, $autoload = null ) {
	GML_Translate_Test_State::$options[ $key ] = $value;
	return true;
}
function delete_option( $key ) {
	unset( GML_Translate_Test_State::$options[ $key ] );
	return true;
}
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	GML_Translate_Test_State::$actions[ $hook ][] = $callback;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	add_action( $hook, $callback, $priority, $accepted_args );
}
function register_activation_hook( $file, $callback ) {}
function register_deactivation_hook( $file, $callback ) {}
function load_plugin_textdomain( $domain, $deprecated = false, $path = false ) { return true; }
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url( $file ) { return 'https://example.com/wp-content/plugins/gml-translate/'; }
function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function __( $text, $domain = 'default' ) { return $text; }
function _e( $text, $domain = 'default' ) { echo $text; }
function esc_url( $url ) { return $url; }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }

function gml_test_assert( $condition, $label ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$label}\n" );
		exit( 1 );
	}
}
