<?php
/** Regression: the standalone adapters retain Core queue and cache safeguards. */

require_once __DIR__ . '/../bootstrap-mock.php';

if ( ! defined( 'GML_VERSION' ) ) {
	define( 'GML_VERSION', 'test-translate' );
}
if ( ! defined( 'GML_PLUGIN_DIR' ) ) {
	define( 'GML_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
}

require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-translation-state.php';
require_once GML_PLUGIN_DIR . 'includes/class-page-cache.php';
require_once GML_PLUGIN_DIR . 'includes/class-translator.php';
require_once GML_PLUGIN_DIR . 'includes/class-queue-processor.php';

GML_Translate_Test_State::reset();
GML_Translate_Test_State::$options[ GML_Page_Cache::GENERATION_OPTION ] = 3;

$plain = GML_Page_Cache::key( 'de', '/de/product/?variation=blue' );
$tracked = GML_Page_Cache::key( 'de', '/de/product/?utm_source=ads&gclid=secret&variation=blue' );
gml_test_assert( $plain === $tracked, 'tracking parameters do not multiply page-cache keys' );
gml_test_assert( GML_Page_Cache::has_tracking_parameters( '/de/product/?gclid=secret' ), 'tracking request is detected for cache bypass' );
gml_test_assert( ! GML_Page_Cache::has_tracking_parameters( '/de/product/?variation=blue' ), 'functional query remains cacheable' );
GML_Page_Cache::invalidate();
gml_test_assert( GML_Page_Cache::generation() === 4, 'generation bump invalidates Redis-backed transients' );

$reflection = new ReflectionClass( 'GML_Queue_Processor' );
$acquire = $reflection->getMethod( 'acquire_process_lock' );
$acquire->setAccessible( true );
$token = $acquire->invoke( null );
gml_test_assert( $token !== '', 'first queue worker obtains the shared lock' );
gml_test_assert( $acquire->invoke( null ) === '', 'overlapping queue worker is rejected' );
GML_Queue_Processor::release_process_lock( 'not-the-owner' );
gml_test_assert( get_option( GML_Queue_Processor::LOCK_OPTION, false ) !== false, 'wrong token cannot release queue lock' );
GML_Queue_Processor::release_process_lock( $token );
gml_test_assert( get_option( GML_Queue_Processor::LOCK_OPTION, false ) === false, 'lock owner releases queue lock' );

gml_test_assert( GML_Queue_Processor::RETRY_LIMIT === 25, 'manual retries remain bounded to a small sample' );
gml_test_assert( GML_Queue_Processor::is_provider_wide_failure( 'Gemini API HTTP 400: invalid model' ), 'provider-wide 400 opens the circuit' );
gml_test_assert( ! GML_Queue_Processor::is_provider_wide_failure( 'Prompt blocked: one source segment' ), 'item-specific prompt rejection does not stop the provider' );

$admin_source = file_get_contents( GML_PLUGIN_DIR . 'admin/class-admin-settings.php' );
gml_test_assert( strpos( $admin_source, 'Retry All Failed' ) === false, 'admin cannot retry an unbounded failed queue' );
gml_test_assert( strpos( $admin_source, "lang:''" ) === false, 'admin retry always requires an explicit language' );
gml_test_assert( strpos( $admin_source, 'wp_cache_flush' ) === false, 'translation cache actions never flush unrelated object caches' );
gml_test_assert( strpos( $admin_source, "_transient_gml_page_%" ) === false, 'translation cache invalidation is Redis-safe and generation based' );

$translator_source = file_get_contents( GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-translator.php' );
gml_test_assert( strpos( $translator_source, 'load_dictionary_for_hashes' ) !== false, 'frontend loads only translation hashes used by the current page' );
gml_test_assert( strpos( $translator_source, 'wp_cache_set( $cache_key, $dictionary' ) === false, 'full language dictionaries are not stored in persistent object cache' );
gml_test_assert( GML_Translator::MAX_SOURCE_BYTES === 32768, 'individual source segments are bounded before queue insertion' );
gml_test_assert( GML_Queue_Processor::MAX_BATCH_INPUT_BYTES === 24576, 'each provider batch stays within the translation output budget' );

echo "OK test-queue-cache-safety\n";
