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
	public static $home_url = 'https://example.com';

	public static function reset() {
		self::$options = [];
		self::$actions = [];
		self::$home_url = 'https://example.com';
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
function remove_action( $hook, $callback, $priority = 10 ) {
	if ( empty( GML_Translate_Test_State::$actions[ $hook ] ) ) return false;
	GML_Translate_Test_State::$actions[ $hook ] = array_values( array_filter(
		GML_Translate_Test_State::$actions[ $hook ],
		static function( $registered ) use ( $callback ) { return $registered !== $callback; }
	) );
	return true;
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
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function home_url( $path = '' ) {
	$base = rtrim( GML_Translate_Test_State::$home_url, '/' );
	return $path === '' ? $base : $base . '/' . ltrim( $path, '/' );
}
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function wp_unslash( $value ) { return $value; }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function get_locale() { return 'en_US'; }
function is_admin() { return false; }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function wp_cache_get( $key, $group = '' ) { return false; }
function wp_cache_set( $key, $value, $group = '', $expire = 0 ) { return true; }
function wp_cache_delete( $key, $group = '' ) { return true; }

function gml_test_assert( $condition, $label ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$label}\n" );
		exit( 1 );
	}
}
