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
	public static $http_requests = [];
	public static $http_responses = [];
	public static $is_404 = false;

	public static function reset() {
		self::$options = [];
		self::$actions = [];
		self::$home_url = 'https://example.com';
		self::$http_requests = [];
		self::$http_responses = [];
		self::$is_404 = false;
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
function add_option( $key, $value, $deprecated = '', $autoload = 'yes' ) {
	if ( array_key_exists( $key, GML_Translate_Test_State::$options ) ) {
		return false;
	}
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
function apply_filters( $hook, $value ) {
	$args = array_slice( func_get_args(), 2 );
	foreach ( GML_Translate_Test_State::$actions[ $hook ] ?? [] as $callback ) {
		$value = call_user_func_array( $callback, array_merge( [ $value ], $args ) );
	}
	return $value;
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
function trailingslashit( $value ) { return rtrim( (string) $value, '/\\' ) . '/'; }
function untrailingslashit( $value ) { return rtrim( (string) $value, '/\\' ); }
function wp_unslash( $value ) { return $value; }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function wp_json_encode( $value, $flags = 0, $depth = 512 ) { return json_encode( $value, $flags, $depth ); }
function get_locale() { return 'en_US'; }
function get_bloginfo( $show = '' ) { return $show === 'name' ? 'Example Site' : ''; }
function wp_salt( $scheme = 'auth' ) { return 'test-salt-' . $scheme; }
function is_admin() { return false; }
function is_404() { return GML_Translate_Test_State::$is_404; }
function is_user_logged_in() { return false; }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function sanitize_text_field( $value ) { return trim( wp_strip_all_tags( (string) $value ) ); }
function esc_url_raw( $url ) { return (string) $url; }
function wp_cache_get( $key, $group = '' ) { return false; }
function wp_cache_set( $key, $value, $group = '', $expire = 0 ) { return true; }
function wp_cache_delete( $key, $group = '' ) { return true; }
function wp_generate_uuid4() { return sprintf( '00000000-0000-4000-8000-%012d', count( GML_Translate_Test_State::$options ) + 1 ); }

class GML_Translate_Test_WPDB {
	public $options = 'test_options';
	private $prepared = [];

	public function prepare( $query ) {
		$this->prepared = [ (string) $query, array_slice( func_get_args(), 1 ) ];
		return '__gml_prepared_lock_query__';
	}

	public function get_var( $query ) {
		if ( $query !== '__gml_prepared_lock_query__' ) return null;
		$args = $this->prepared[1];
		return array_key_exists( $args[0], GML_Translate_Test_State::$options )
			? GML_Translate_Test_State::$options[ $args[0] ]
			: null;
	}

	public function query( $query ) {
		if ( $query !== '__gml_prepared_lock_query__' ) return false;
		$sql  = ltrim( $this->prepared[0] );
		$args = $this->prepared[1];
		if ( strpos( $sql, 'INSERT IGNORE' ) === 0 ) {
			if ( array_key_exists( $args[0], GML_Translate_Test_State::$options ) ) return 0;
			GML_Translate_Test_State::$options[ $args[0] ] = $args[1];
			return 1;
		}
		if ( strpos( $sql, 'UPDATE ' ) === 0 ) {
			if ( ! isset( GML_Translate_Test_State::$options[ $args[1] ] ) || GML_Translate_Test_State::$options[ $args[1] ] !== $args[2] ) return 0;
			GML_Translate_Test_State::$options[ $args[1] ] = $args[0];
			return 1;
		}
		if ( strpos( $sql, 'DELETE FROM' ) === 0 ) {
			if ( ! isset( GML_Translate_Test_State::$options[ $args[0] ] ) || GML_Translate_Test_State::$options[ $args[0] ] !== $args[1] ) return 0;
			unset( GML_Translate_Test_State::$options[ $args[0] ] );
			return 1;
		}
		return false;
	}
}

$wpdb = new GML_Translate_Test_WPDB();

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;

		public function __construct( $code = '', $message = '' ) {
			$this->code = (string) $code;
			$this->message = (string) $message;
		}

		public function get_error_code() { return $this->code; }
		public function get_error_message() { return $this->message; }
	}
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_safe_remote_post( $url, $args = [] ) {
	GML_Translate_Test_State::$http_requests[] = [ 'url' => $url, 'args' => $args ];
	if ( GML_Translate_Test_State::$http_responses ) {
		return array_shift( GML_Translate_Test_State::$http_responses );
	}
	return [ 'response' => [ 'code' => 200 ], 'headers' => [], 'body' => '{}' ];
}
function wp_safe_remote_get( $url, $args = [] ) {
	GML_Translate_Test_State::$http_requests[] = [ 'url' => $url, 'args' => $args ];
	if ( GML_Translate_Test_State::$http_responses ) {
		return array_shift( GML_Translate_Test_State::$http_responses );
	}
	return [ 'response' => [ 'code' => 200 ], 'headers' => [ 'content-type' => 'text/html' ], 'body' => '<!doctype html><html><body>' . str_repeat( 'content ', 20 ) . '</body></html>' ];
}
function wp_remote_retrieve_response_code( $response ) {
	return (int) ( $response['response']['code'] ?? 0 );
}
function wp_remote_retrieve_body( $response ) { return (string) ( $response['body'] ?? '' ); }
function wp_remote_retrieve_header( $response, $name ) {
	return $response['headers'][ strtolower( (string) $name ) ] ?? '';
}

function gml_test_assert( $condition, $label ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$label}\n" );
		exit( 1 );
	}
}
