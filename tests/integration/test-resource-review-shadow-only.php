<?php
/** SEO consumes one authority; user-facing navigation is independent of progress. */

require_once __DIR__ . '/../bootstrap-mock.php';

$root = dirname( __DIR__, 2 );
$core = $root . '/includes/vendor/gml-translation-core/src';
$gate = file_get_contents( $root . '/includes/class-publication-gate.php' );
$router = file_get_contents( $root . '/includes/class-seo-router.php' );
$hreflang = file_get_contents( $root . '/includes/class-seo-hreflang.php' );
$sitemap = file_get_contents( $root . '/includes/class-sitemap.php' );
$transformer = file_get_contents( $root . '/includes/class-sitemap-publication-transformer.php' );
$eligibility = file_get_contents( $core . '/class-public-eligibility.php' );

foreach ( [ $gate, $router, $hreflang, $sitemap, $transformer, $eligibility ] as $source ) {
    gml_test_assert( is_string( $source ) && $source !== '', 'Phase 2D publication source is readable' );
}

gml_test_assert( strpos( $gate, "add_action( 'template_redirect', [ \$this, 'enforce' ], 0 )" ) !== false, 'publication gate runs before ordinary template redirects' );
gml_test_assert( strpos( $gate, 'wp_safe_redirect( $source_url, 302' ) !== false, 'anonymous ineligible routes return a temporary source redirect' );
gml_test_assert( strpos( $gate, '$resource instanceof GML_Resource_Identity && $resource->is_eligible()' ) !== false, 'valid language pages are accessible independently of SEO completion' );
gml_test_assert( strpos( $gate, 'X-Robots-Tag: noindex, nofollow' ) !== false, 'private reviewer previews are noindex at the HTTP layer' );
gml_test_assert( strpos( $gate, 'render_preview_banner' ) !== false, 'private reviewer previews remain visibly marked' );

gml_test_assert( strpos( $router, 'GML_Public_Eligibility::get_public_urls' ) === false && strpos( $router, 'GML_Language_Utils::enabled_local_target_codes()' ) !== false, 'language switcher routes use enabled language configuration, not completion' );
gml_test_assert( strpos( $hreflang, 'get_alternate_urls' ) !== false && strpos( $hreflang, 'get_public_status' ) !== false, 'canonical and hreflang use publication eligibility' );
gml_test_assert( strpos( $transformer, 'get_public_clusters_bulk' ) !== false, 'sitemap expansion uses one bulk public-cluster read' );
gml_test_assert( strpos( $sitemap, 'seopress_sitemaps_xml_single' ) !== false, 'SEOPress receives the final multilingual sitemap transform' );
gml_test_assert( strpos( $sitemap, 'wpseo_sitemap_url' ) !== false, 'Yoast receives the multilingual sitemap transform' );
gml_test_assert( strpos( $sitemap, "'_sitemap_url'" ) !== false && strpos( $sitemap, 'rank_math_expand_url' ) !== false, 'Rank Math receives the multilingual transform through its URL serializer hook' );
gml_test_assert( strpos( $sitemap, "add_filter( 'wp_sitemaps_enabled'" ) !== false, 'standalone GML sitemap disables the non-multilingual core sitemap only when GML owns sitemap output' );

foreach ( [ 'machine_status', 'review_status', 'snapshot_matches', 'resource_noindex', 'route_invalid' ] as $token ) {
    gml_test_assert( strpos( $eligibility, $token ) !== false, 'derived eligibility contains ' . $token );
}
gml_test_assert( strpos( $eligibility, 'published_status' ) === false, 'Core does not persist a second publication truth' );

echo "OK test-publication-gate-contract\n";
