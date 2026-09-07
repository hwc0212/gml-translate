<?php
require_once __DIR__ . '/../bootstrap-mock.php';

$root = dirname( __DIR__, 2 );
$core = $root . '/includes/vendor/gml-translation-core/src';
$public_paths = [
    $core . '/class-authoritative-renderer.php',
    $core . '/class-output-buffer.php',
    $core . '/class-page-cache.php',
    $core . '/class-translation-rewrite.php',
    $core . '/class-translation-readiness.php',
    $core . '/class-translator.php',
    $root . '/includes/class-gettext-filter.php',
    $root . '/includes/class-language-detector.php',
    $root . '/includes/class-language-switcher.php',
    $root . '/includes/class-nav-menu-switcher.php',
    $root . '/includes/class-output-buffer.php',
    $root . '/includes/class-page-cache.php',
    $root . '/includes/class-seo-hreflang.php',
    $root . '/includes/class-seo-router.php',
    $root . '/includes/class-sitemap.php',
    $root . '/includes/class-translator.php',
];
$forbidden = [ 'GML_Resource_Approval', 'gml_resource_reviews', 'gml_resource_review_audit' ];

foreach ( $public_paths as $path ) {
    gml_test_assert( is_file( $path ), 'shadow-only audit target exists: ' . basename( $path ) );
    $source = file_get_contents( $path );
    foreach ( $forbidden as $token ) {
        gml_test_assert( strpos( $source, $token ) === false, basename( $path ) . ' does not consume Human Review state through ' . $token );
    }
}

echo "OK test-resource-review-shadow-only\n";
