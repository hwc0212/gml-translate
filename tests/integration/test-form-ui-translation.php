<?php
require_once __DIR__ . '/../bootstrap-mock.php';
$parser_file = getenv( 'GML_PARSER_UNDER_TEST' ) ?: __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-html-parser.php';
require_once $parser_file;

$failures = [];
$checks = 0;
function form_check( $condition, $label ) {
    global $failures, $checks;
    $checks++;
    if ( ! $condition ) $failures[] = $label;
}
function form_render( $html, array $replacements ) {
    $parser = new GML_HTML_Parser();
    $parsed = $parser->parse( $html );
    $parsed['replacements'] = $replacements;
    return $parser->rebuild( $parsed );
}

$source = 'Describe the equipment, intended use, project stage and what you need help with.';
$locales = [
    'de' => [ 'Beschreiben Sie das Gerät, den Verwendungszweck, den Projektstand und wobei Sie Hilfe benötigen.', 'Anfrage senden' ],
    'es' => [ 'Describa el equipo, el uso previsto, la etapa del proyecto y en qué necesita ayuda.', 'Enviar solicitud de cotización' ],
    'fr' => [ "Décrivez l’équipement, l’utilisation prévue, l’avancement du projet et l’aide dont vous avez besoin.", 'Envoyer la demande de devis' ],
    'ru' => [ 'Опишите оборудование, назначение, этап проекта и необходимую помощь.', 'Отправить запрос' ],
];
$html = '<form id="wpcf7-f9491-p37-o1" action="/quote/#wpcf7-f9491-p37-o1" method="post">'
    . '<input type="hidden" name="_wpcf7" value="9491">'
    . '<textarea name="your-message" required placeholder="' . $source . '" aria-label="Project message">Send RFQ &lt;input value="Send RFQ"&gt;</textarea>'
    . '<input class="wpcf7-form-control wpcf7-submit has-spinner" type="submit" value="Send RFQ">'
    . '</form>';
$parser = new GML_HTML_Parser();
$texts = array_column( $parser->parse( $html )['nodes'], 'text' );
form_check( in_array( $source, $texts, true ), 'textarea placeholder discovered' );
form_check( in_array( 'Send RFQ', $texts, true ), 'submit caption discovered' );
form_check( in_array( 'Project message', $texts, true ), 'textarea aria label discovered' );
form_check( ! in_array( 'Send RFQ <input value="Send RFQ">', $texts, true ), 'textarea default content excluded' );
foreach ( $locales as $lang => $copy ) {
    $out = form_render( $html, [ $source => $copy[0], 'Send RFQ' => $copy[1], 'Project message' => 'UI label' ] );
    form_check( strpos( $out, 'placeholder="' . htmlspecialchars( $copy[0], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . '"' ) !== false, "$lang placeholder rendered" );
    form_check( strpos( $out, 'value="' . htmlspecialchars( $copy[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . '"' ) !== false, "$lang submit rendered" );
    form_check( strpos( $out, '>Send RFQ &lt;input value="Send RFQ"&gt;</textarea>' ) !== false, "$lang customer/default content unchanged" );
    form_check( strpos( $out, 'name="_wpcf7" value="9491"' ) !== false, "$lang hidden value unchanged" );
    form_check( strpos( $out, 'action="/quote/#wpcf7-f9491-p37-o1" method="post"' ) !== false, "$lang form destination unchanged" );
    if ( getenv( 'GML_FORM_FIXTURE_DIR' ) ) {
        file_put_contents( getenv( 'GML_FORM_FIXTURE_DIR' ) . '/' . $lang . '.html', $out );
    }
}
foreach ( [ 'submit', 'button', 'reset', 'SUBMIT' ] as $type ) {
    $out = form_render( '<input type="' . $type . '" value="Send RFQ">', [ 'Send RFQ' => 'Translated' ] );
    form_check( strpos( $out, 'value="Translated"' ) !== false, "$type UI value translated" );
}
foreach ( [ 'text', 'email', 'password', 'hidden', 'checkbox', 'radio', 'number', 'file', 'image', '', 'unknown' ] as $type ) {
    $input = '<input type="' . $type . '" name="Send RFQ" value="Send RFQ" data-value="Send RFQ">';
    form_check( form_render( $input, [ 'Send RFQ' => 'Translated' ] ) === $input, "$type value unchanged" );
    form_check( ! in_array( 'Send RFQ', array_column( $parser->parse( $input )['nodes'], 'text' ), true ), "$type value not discovered" );
}
foreach ( [ 'translate="no"', 'class="notranslate"', 'class="test-exclude"', 'id="skip-form"' ] as $rule ) {
    GML_Translate_Test_State::$options['gml_exclude_selectors'] = [ '.test-exclude', '#skip-form' ];
    foreach ( [ '<div ' . $rule . '>%s</div>', '%s' ] as $wrapper ) {
        $control_rule = $wrapper === '%s' ? ' ' . $rule : '';
        $control = '<textarea' . $control_rule . ' placeholder="Send RFQ">Send RFQ</textarea><input' . $control_rule . ' type="submit" value="Send RFQ">';
        $input = sprintf( $wrapper, $control );
        form_check( form_render( $input, [ 'Send RFQ' => 'Translated' ] ) === $input, "excluded form preserved: $rule $wrapper" );
    }
}
GML_Translate_Test_State::$options['gml_exclude_selectors'] = [];
$edge_cases = [
    '<textarea data-check="a > b" placeholder="Send RFQ">literal <input type="submit" value="Send RFQ"> &amp; text</textarea>' => '<textarea data-check="a > b" placeholder="Translated">literal <input type="submit" value="Send RFQ"> &amp; text</textarea>',
    '<input data-value="Send RFQ" type="submit" value="Send RFQ">' => '<input data-value="Send RFQ" type="submit" value="Translated">',
    '<input data-json=\'{"value":"Send RFQ"}\' TYPE=submit VALUE=Send>' => '<input data-json=\'{"value":"Send RFQ"}\' TYPE=submit VALUE="Go">',
    '<textarea placeholder="Send RFQ">Send RFQ</textarea ><input type="submit" value="Send RFQ">' => '<textarea placeholder="Translated">Send RFQ</textarea ><input type="submit" value="Translated">',
    '<textarea placeholder="Send RFQ">Send RFQ' => '<textarea placeholder="Translated">Send RFQ',
];
foreach ( $edge_cases as $input => $expected ) {
    form_check( form_render( $input, [ 'Send RFQ' => 'Translated', 'Send' => 'Go' ] ) === $expected, 'attribute/raw text edge: ' . $input );
}
foreach ( [ '<!-- Send RFQ -->', '<script>Send RFQ</script>', '<svg><text>Send RFQ</text></svg>', '<input type="submit" value="Send RFQ">' ] as $literal ) {
    $input = '<textarea placeholder="Send RFQ">' . $literal . '</textarea>';
    $expected = '<textarea placeholder="Translated">' . $literal . '</textarea>';
    form_check( form_render( $input, [ 'Send RFQ' => 'Translated' ] ) === $expected, 'raw customer content retained: ' . $literal );
}
foreach ( [ 'script', 'style', 'code', 'pre', 'svg', 'noscript', 'iframe' ] as $raw_tag ) {
    $input = '<' . $raw_tag . '><input type="submit" value="Send RFQ"></' . $raw_tag . '><input type="submit" value="Send RFQ">';
    $expected = '<' . $raw_tag . '><input type="submit" value="Send RFQ"></' . $raw_tag . '><input type="submit" value="Translated">';
    form_check( form_render( $input, [ 'Send RFQ' => 'Translated' ] ) === $expected, 'nonvisible control markup retained: ' . $raw_tag );
}
form_check( form_render( '<!-- <input type="submit" value="Send RFQ"> -->', [ 'Send RFQ' => 'Translated' ] ) === '<!-- <input type="submit" value="Send RFQ"> -->', 'comment control markup retained' );
foreach ( [ 'script', 'style', 'code', 'pre', 'svg', 'noscript', 'iframe' ] as $raw_tag ) {
    $input = '<' . $raw_tag . '><textarea placeholder="Send RFQ">literal</textarea></' . $raw_tag . '><textarea placeholder="Send RFQ">Send RFQ</textarea>';
    $expected = '<' . $raw_tag . '><textarea placeholder="Send RFQ">literal</textarea></' . $raw_tag . '><textarea placeholder="Translated">Send RFQ</textarea>';
    form_check( form_render( $input, [ 'Send RFQ' => 'Translated' ] ) === $expected, 'literal textarea in raw block retained: ' . $raw_tag );
}
$encoded = form_render( '<input type="submit" value="A &amp; B">', [ 'A & B' => '"Quoted" & <script>bad()</script>' ] );
form_check( strpos( $encoded, 'value="&quot;Quoted&quot; &amp; bad()"' ) !== false, 'translated caption safely encoded' );
$plain = '<button name="Send RFQ" value="Send RFQ">Send RFQ</button>';
form_check( form_render( $plain, [ 'Send RFQ' => 'Translated' ] ) === '<button name="Send RFQ" value="Send RFQ">Translated</button>', 'button text translates but submission value does not' );
foreach ( [ 'action', '0', ' ' ] as $name ) {
    $input = '<input type="submit" name="' . $name . '" value="Send RFQ">';
    form_check( form_render( $input, [ 'Send RFQ' => 'Translated' ] ) === $input, 'named submit business value preserved: ' . $name );
    form_check( ! in_array( 'Send RFQ', array_column( $parser->parse( $input )['nodes'], 'text' ), true ), 'named submit business value not discovered: ' . $name );
}
$backslash = chr(92);
$backslash_cases = [
    '<svg/><textarea placeholder=" Send RFQ ">Send RFQ</textarea><input type="submit" value=" Send RFQ ">' =>
        '<svg/><textarea placeholder=" Translated ">Send RFQ</textarea><input type="submit" value=" Translated ">',
    '<p>Send RFQ</p><div data-placeholder="Send RFQ" data-config=\'aria-label="Send RFQ"\' aria-label="Send RFQ"></div>' =>
        '<p>Translated</p><div data-placeholder="Send RFQ" data-config=\'aria-label="Send RFQ"\' aria-label="Translated"></div>',

    '<textarea data-path="C:'.$backslash.'" placeholder="Send RFQ">Send RFQ</textarea>' =>
        '<textarea data-path="C:'.$backslash.'" placeholder="Translated">Send RFQ</textarea>',
    '<input data-path="C:'.$backslash.'" type="submit" value="Send RFQ">' =>
        '<input data-path="C:'.$backslash.'" type="submit" value="Translated">',
];
foreach ($backslash_cases as $input=>$expected) form_check(form_render($input,['Send RFQ'=>'Translated'])===$expected,'Form and component attribute boundaries: '.$input);
$quoted_markup = '<div data-template=\'<script>Send RFQ</script><title>Send RFQ</title><textarea>Send RFQ</textarea>\'><p>Send RFQ</p></div>';
form_check( form_render( $quoted_markup, [ 'Send RFQ'=>'Translated' ] ) === str_replace( '<p>Send RFQ</p>', '<p>Translated</p>', $quoted_markup ), 'tag-like configuration and its whitespace are kept byte for byte' );
$title = '<title data-value="Send RFQ"> Send RFQ </title><p>Send RFQ</p>';
form_check( form_render( $title, [ 'Send RFQ'=>'Translated' ] ) === '<title data-value="Send RFQ"> Translated </title><p>Translated</p>', 'title text with spaces translates without changing its attributes' );
$nested_svg = '<svg aria-label="Send RFQ"><svg><text>Send RFQ</text></svg><text>Send RFQ</text></svg><textarea placeholder="Send RFQ">Send RFQ</textarea><p>Send RFQ</p>';
form_check( form_render( $nested_svg, [ 'Send RFQ'=>'Translated' ] ) === str_replace( [ 'placeholder="Send RFQ"', '<p>Send RFQ</p>' ], [ 'placeholder="Translated"', '<p>Translated</p>' ], $nested_svg ), 'nested SVG text stays intact while following form controls translate' );
form_check( form_render( $html, [] ) === $html, 'missing translation returns byte-identical source' );
echo json_encode( [ 'checks' => $checks, 'failed' => count( $failures ), 'failures' => $failures ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
exit( $failures ? 1 : 0 );
