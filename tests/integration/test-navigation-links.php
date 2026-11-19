<?php
/** Keep file and service URLs intact while localizing same-site navigation. */
require_once __DIR__ . '/../bootstrap-mock.php';
function content_url( $path = '' ) { return home_url( '/assets/' . ltrim( $path, '/' ) ); }
function includes_url( $path = '' ) { return home_url( '/wp-includes/' . ltrim( $path, '/' ) ); }
function rest_url( $path = '' ) { return home_url( '/api/' . ltrim( $path, '/' ) ); }
function site_url( $path = '' ) { return home_url( $path ); }
function wp_get_upload_dir() { return [ 'baseurl' => home_url( '/media' ) ]; }
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-language-utils.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-url-helper.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-output-buffer.php';
class Navigation_Buffer_Test extends GML_Translation_Output_Buffer {
    public function __construct() { $this->target_lang = 'de'; }
    public function rewrite( $html ) { return $this->rewrite_internal_links( $html ); }
}
$cases = json_decode( file_get_contents( __DIR__ . '/../fixtures/navigation-links.json' ), true );
$buffer = new Navigation_Buffer_Test();
$count = 0;
$failures = [];
foreach ( [ '', '/staging' ] as $base ) {
    $home = 'https://example.test' . $base;
    GML_Translate_Test_State::$home_url = $home;
    GML_Translate_Test_State::$options = [
        'gml_source_lang' => 'en', 'gml_languages' => [ [ 'code' => 'de', 'enabled' => true ], [ 'code' => 'fr', 'enabled' => false ] ],
    ];
    $expand = static function( $value ) use ( $base, $home ) {
        return strtr( $value, [ '{home}' => $home, '{origin}' => 'https://example.test', '{base}' => $base ] );
    };
    foreach ( $cases as $case ) {
        $url = $expand( $case['url'] );
        $expected = $expand( ! empty( $case['keep'] ) ? $case['url'] : ( $base ? ( $case['subdirExpected'] ?? $case['expected'] ) : $case['expected'] ) );
        foreach ( [ 'a' => 'href', 'form' => 'action' ] as $tag => $attribute ) {
            $download = $tag === 'a' && ! empty( $case['download'] );
            $tail = $download ? ' download' : '';
            $html = '<' . $tag . ' ' . $attribute . '="' . esc_attr( $url ) . '"' . $tail . '>Test</' . $tag . '>';
            $wanted = '<' . $tag . ' ' . $attribute . '="' . esc_attr( $download ? $url : $expected ) . '"' . $tail . '>Test</' . $tag . '>';
            $actual = $buffer->rewrite( $html );
            $count++;
            if ( $actual !== $wanted ) $failures[] = [ $base, $case['name'], $tag, $actual, $wanted ];
        }
    }
}
echo json_encode( [ 'checks' => $count, 'failures' => $failures ], JSON_UNESCAPED_SLASHES ) . "\n";
if ( ! in_array( '--audit', $argv, true ) && $failures ) exit( 1 );
