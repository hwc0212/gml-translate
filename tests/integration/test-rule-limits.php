<?php
/** Regression: admin-controlled glossary and exclusion data stays bounded. */

require_once __DIR__ . '/../bootstrap-mock.php';

if ( ! defined( 'GML_PLUGIN_DIR' ) ) {
	define( 'GML_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
}

require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-language-utils.php';
require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-glossary.php';
require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-exclusion-rules.php';

GML_Translate_Test_State::reset();
$rules = [];
for ( $i = 0; $i < 600; $i++ ) {
	$rules[] = [
		'source'  => str_repeat( 'S', 260 ) . $i,
		'target'  => str_repeat( 'T', 260 ) . $i,
		'lang'    => 'DE_de',
		'enabled' => true,
	];
}
GML_Glossary::save_rules( $rules );
$saved = GML_Glossary::get_rules();
gml_test_assert( count( $saved ) === GML_Glossary::MAX_RULES, 'glossary rule count is bounded' );
gml_test_assert( strlen( $saved[0]['source'] ) === GML_Glossary::MAX_TERM_LENGTH, 'glossary source length is bounded' );
gml_test_assert( $saved[0]['lang'] === 'de-de', 'glossary language code is normalized' );
$prompt = GML_Glossary::build_prompt_instruction( 'de-DE' );
gml_test_assert( substr_count( $prompt, "\n- " ) <= GML_Glossary::MAX_PROMPT_RULES, 'glossary prompt rule count is bounded' );
gml_test_assert( strlen( $prompt ) <= GML_Glossary::MAX_PROMPT_BYTES + 128, 'glossary prompt byte size is bounded' );

GML_Exclusion_Rules::save_rules( [
	[ 'type' => 'selector', 'value' => '.valid-selector', 'enabled' => true ],
	[ 'type' => 'selector', 'value' => 'div > script', 'enabled' => true ],
	[ 'type' => 'url_regex', 'value' => '#^/private/#', 'enabled' => true ],
	[ 'type' => 'url_regex', 'value' => str_repeat( 'a', 300 ), 'enabled' => true ],
	[ 'type' => 'unknown', 'value' => '/ignored/', 'enabled' => true ],
] );
$exclusions = new GML_Exclusion_Rules();
gml_test_assert( $exclusions->get_excluded_selectors() === [ '.valid-selector' ], 'only simple bounded selectors are accepted' );
gml_test_assert( $exclusions->is_page_excluded( '/private/report/?token=secret' ), 'valid bounded URL regex remains supported' );
gml_test_assert( count( $exclusions->get_rules() ) === 2, 'invalid exclusion types and patterns are rejected on save' );

echo "OK test-rule-limits\n";
