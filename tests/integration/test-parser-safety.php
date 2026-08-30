<?php
require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../includes/class-html-parser.php';

GML_Translate_Test_State::$options['gml_protected_terms'] = [ 'GML', 'WordPress' ];
$parser = new GML_HTML_Parser();
$html   = '<!doctype html><html><body><a id="close" class="close" data-action="close" href="/close/">Close</a><script>document.querySelector(".close")</script></body></html>';
$parsed = $parser->parse( $html );
$parsed['replacements'] = [ 'Close' => 'Schliessen' ];
$result = $parser->rebuild( $parsed );

gml_test_assert( strpos( $result, '>Schliessen</a>' ) !== false, 'translates visible text' );
gml_test_assert( strpos( $result, 'id="close"' ) !== false, 'preserves element ids' );
gml_test_assert( strpos( $result, 'class="close"' ) !== false, 'preserves CSS classes' );
gml_test_assert( strpos( $result, 'data-action="close"' ) !== false, 'preserves data attributes' );
gml_test_assert( strpos( $result, 'href="/close/"' ) !== false, 'preserves URLs' );
gml_test_assert( strpos( $result, 'querySelector(".close")' ) !== false, 'preserves scripts' );
echo "OK test-parser-safety\n";
