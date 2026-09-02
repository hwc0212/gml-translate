<?php
/** Regression: proactive crawling stays same-site, signed, bounded, and locked. */

require_once __DIR__ . '/../bootstrap-mock.php';

if ( ! defined( 'GML_PLUGIN_DIR' ) ) {
	define( 'GML_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
}

require_once GML_PLUGIN_DIR . 'includes/class-content-crawler.php';

class GML_Test_Content_Crawler extends GML_Content_Crawler {
	public static function exact_site_url( $url ) { return parent::is_exact_site_url( $url ); }
	public static function acquire() { return parent::acquire_lock(); }
	public static function release( $token ) { return parent::release_lock( $token ); }
}

GML_Translate_Test_State::reset();
GML_Translate_Test_State::$home_url = 'https://example.com/staging';

gml_test_assert( GML_Test_Content_Crawler::exact_site_url( 'https://example.com/staging/product/' ), 'crawler accepts the exact subdirectory site' );
gml_test_assert( ! GML_Test_Content_Crawler::exact_site_url( 'https://example.com/product/' ), 'crawler rejects paths outside the WordPress subdirectory' );
gml_test_assert( ! GML_Test_Content_Crawler::exact_site_url( 'https://example.com.evil.test/staging/product/' ), 'crawler rejects lookalike hosts' );
gml_test_assert( ! GML_Test_Content_Crawler::is_internal_request(), 'query parameters alone cannot impersonate the crawler' );
$_SERVER['HTTP_X_GML_CRAWL'] = GML_Test_Content_Crawler::request_token();
gml_test_assert( GML_Test_Content_Crawler::is_internal_request(), 'signed internal request is recognized' );
$_SERVER['HTTP_X_GML_CRAWL'] = 'wrong-token';
gml_test_assert( ! GML_Test_Content_Crawler::is_internal_request(), 'invalid crawler signature is rejected' );

$first_lock = GML_Test_Content_Crawler::acquire();
gml_test_assert( is_string( $first_lock ) && $first_lock !== '', 'first crawler obtains its owner-token lock' );
gml_test_assert( GML_Test_Content_Crawler::acquire() === '', 'overlapping crawler is rejected' );
gml_test_assert( ! GML_Test_Content_Crawler::release( 'wrong-owner' ), 'wrong crawler owner cannot release the lock' );
gml_test_assert( GML_Test_Content_Crawler::release( $first_lock ), 'current crawler owner releases its lock' );
$second_lock = GML_Test_Content_Crawler::acquire();
gml_test_assert( is_string( $second_lock ) && $second_lock !== '', 'crawler lock can be acquired after owner-safe release' );
GML_Test_Content_Crawler::release( $second_lock );

$source = file_get_contents( GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-translation-content-crawler.php' );
gml_test_assert( strpos( $source, 'wp_safe_remote_get' ) !== false, 'crawler uses WordPress safe HTTP transport' );
gml_test_assert( strpos( $source, "'redirection'         => 0" ) !== false, 'crawler does not follow redirects' );
gml_test_assert( strpos( $source, "'limit_response_size' => 524288" ) !== false, 'crawler response size is bounded before buffering' );
gml_test_assert( strpos( $source, 'getMessage()' ) === false, 'crawler does not log raw exception messages' );

echo "OK test-crawler-safety\n";
