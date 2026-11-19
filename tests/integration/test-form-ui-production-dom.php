<?php
require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-html-parser.php';
$fixture = json_decode( file_get_contents( __DIR__ . '/../fixtures/rfq-dom-sanitized.json' ), true );
$source = 'Describe the equipment, intended use, project stage and what you need help with.';
$html = str_replace( 'type="submit" value=""', 'type="submit" value="Send RFQ"', $fixture['html'], $count );
gml_test_assert( $count === 1, 'one sanitized submit caption restored from the recorded DOM field' );
$locales = json_decode( file_get_contents( __DIR__ . '/../fixtures/rfq-ui-translations.json' ), true );
$parser = new GML_HTML_Parser();
$parsed = $parser->parse( $html );
$texts = array_column( $parsed['nodes'], 'text' );
gml_test_assert( in_array( $source, $texts, true ) && in_array( 'Send RFQ', $texts, true ), 'both UI strings discovered in the real CF7 DOM fixture' );
$nodes = array_filter( $parsed['nodes'], static function( $node ) use ( $source ) {
    return $node['text'] === $source || $node['text'] === 'Send RFQ';
} );
foreach ( $nodes as $node ) {
    gml_test_assert( $node['context_type'] === 'attribute' && $node['hash'] === md5( $node['text'] ), 'existing attribute/TM identity retained' );
}
foreach ( $locales as $lang => $copy ) {
    $parsed['replacements'] = [ $source => $copy[0], 'Send RFQ' => $copy[1] ];
    $result = $parser->rebuild( $parsed );
    $expected = str_replace(
        [ 'placeholder="' . $source . '"', 'type="submit" value="Send RFQ"' ],
        [ 'placeholder="' . htmlspecialchars( $copy[0], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . '"', 'type="submit" value="' . htmlspecialchars( $copy[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . '"' ],
        $html
    );
    gml_test_assert( $result === $expected, "$lang: full production-shaped DOM differs only in two approved UI attributes" );
}
$parsed['replacements'] = [];
gml_test_assert( $parser->rebuild( $parsed ) === $html, 'English/missing TM fallback is byte-identical' );
echo "OK test-form-ui-production-dom (9 checks)\n";
