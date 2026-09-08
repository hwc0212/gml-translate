<?php
/** Sitemap resources are expanded once into reciprocal eligible URL clusters. */

require_once __DIR__ . '/../bootstrap-mock.php';

final class GML_Resource_Identity {
    public static $resolved = [];
    public static $resolve_calls = 0;
    private $key;

    public function __construct( $key ) {
        $this->key = $key;
    }

    public function get_key() {
        return $this->key;
    }

    public static function resolve_urls( array $urls ) {
        self::$resolve_calls++;
        $result = [];
        foreach ( $urls as $url ) {
            if ( isset( self::$resolved[ $url ] ) ) $result[ $url ] = self::$resolved[ $url ];
        }
        return $result;
    }
}

class GML_Translation_Provider {
    public $cluster_calls = 0;
    public $expected_resources = 3;

    public function get_public_clusters_bulk( array $resources, array $context = [] ) {
        $this->cluster_calls++;
        gml_test_assert( count( $resources ) === $this->expected_resources, 'sitemap resources are evaluated in one expected batch' );
        gml_test_assert( ! empty( $context['source_sitemap_authority'] ), 'SEO plugin sitemap membership is trusted as source indexability' );
        return [
            'post:page:10' => [
                'source_lang' => 'en',
                'eligible_urls' => [
                    'en' => 'https://example.com/about/',
                    'es' => 'https://example.com/es/about/',
                ],
            ],
            'post:page:20' => [
                'source_lang' => 'en',
                'eligible_urls' => [
                    'en' => 'https://example.com/contact/',
                ],
            ],
            'post:page:30' => [
                'source_lang' => 'en',
                'eligible_urls' => [],
            ],
        ];
    }

    public function get_source_language() {
        return 'en';
    }

    public function get_hreflang_code( $lang ) {
        return $lang;
    }
}

$about = new GML_Resource_Identity( 'post:page:10' );
$contact = new GML_Resource_Identity( 'post:page:20' );
$hidden = new GML_Resource_Identity( 'post:page:30' );
GML_Resource_Identity::$resolved = [
    'https://example.com/about/' => $about,
    'https://example.com/contact/' => $contact,
    'https://example.com/private/' => $hidden,
];

require_once __DIR__ . '/../../includes/class-sitemap-publication-transformer.php';

$xml = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
    . '<url><loc>https://example.com/about/</loc><lastmod>2026-09-07</lastmod></url>'
    . '<url><loc>https://example.com/contact/</loc></url>'
    . '<url><loc>https://example.com/private/</loc></url>'
    . '</urlset>';
$provider = new GML_Translation_Provider();
$transformer = new GML_Sitemap_Publication_Transformer( $provider );
$result = $transformer->transform( $xml, [
    'entrypoint' => 'seopress_sitemap',
    'source_sitemap_authority' => true,
] );

gml_test_assert( GML_Resource_Identity::$resolve_calls === 1, 'sitemap URLs use one bulk resource resolution' );
gml_test_assert( $provider->cluster_calls === 1, 'sitemap resources use one bulk eligibility evaluation' );

$document = new DOMDocument();
gml_test_assert( $document->loadXML( $result ), 'transformed sitemap remains valid XML' );
$xpath = new DOMXPath( $document );
$urls = $xpath->query( '/*[local-name()="urlset"]/*[local-name()="url"]' );
gml_test_assert( $urls->length === 3, 'each eligible language is independently listed as a URL entry' );

$locations = [];
foreach ( $urls as $url_node ) {
    $loc = $xpath->query( './*[local-name()="loc"]', $url_node )->item( 0 )->textContent;
    $links = $xpath->query( './*[local-name()="link" and @rel="alternate"]', $url_node );
    $locations[ $loc ] = $links->length;
}
gml_test_assert( isset( $locations['https://example.com/about/'] ) && $locations['https://example.com/about/'] === 3, 'source entry has en, es, and x-default alternates' );
gml_test_assert( isset( $locations['https://example.com/es/about/'] ) && $locations['https://example.com/es/about/'] === 3, 'translated entry has the identical reciprocal alternate set' );
gml_test_assert( isset( $locations['https://example.com/contact/'] ) && $locations['https://example.com/contact/'] === 2, 'source-only resource has en and x-default without an invented target' );
gml_test_assert( strpos( $result, 'https://example.com/private/' ) === false, 'resource with no eligible URLs is removed from the sitemap' );
gml_test_assert( strpos( $result, '/fr/' ) === false, 'ineligible target URLs never enter the sitemap' );

class GML_Test_Rank_Math_Generator {
    public function sitemap_url( $entry ) {
        return '<url><loc>' . esc_url_raw( $entry['loc'] ) . '</loc><image:image><image:loc>https://example.com/image.jpg</image:loc></image:image></url>';
    }
}

require_once __DIR__ . '/../../includes/class-sitemap.php';
$sitemap_reflection = new ReflectionClass( 'GML_Sitemap' );
$sitemap = $sitemap_reflection->newInstanceWithoutConstructor();
$transformer_property = $sitemap_reflection->getProperty( 'transformer' );
$transformer_property->setAccessible( true );
$rank_provider = new GML_Translation_Provider();
$rank_provider->expected_resources = 1;
$transformer_property->setValue( $sitemap, new GML_Sitemap_Publication_Transformer( $rank_provider ) );
$rank_output = $sitemap->rank_math_expand_url(
    [ 'loc' => 'https://example.com/about/' ],
    new GML_Test_Rank_Math_Generator()
);
gml_test_assert( substr_count( $rank_output, '<url' ) === 2, 'Rank Math URL serializer expands one approved source into two independent URL entries' );
gml_test_assert( strpos( $rank_output, 'https://example.com/es/about/' ) !== false, 'Rank Math expansion includes the eligible translated URL' );
gml_test_assert( substr_count( $rank_output, '<image:image>' ) === 2, 'Rank Math image nodes survive each independently listed language entry' );
gml_test_assert( $rank_provider->cluster_calls === 1, 'Rank Math expansion evaluates all target languages in one cluster call' );

echo "OK test-sitemap-publication-transformer\n";
