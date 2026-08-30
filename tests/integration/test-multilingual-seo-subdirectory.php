<?php
/** Regression: standalone multilingual SEO is subdirectory-safe and AI-independent. */

require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-translation-state.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-url-helper.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-language-utils.php';

class GML_Queue_Processor {
	public static function language_is_index_ready( $language ) {
		return $language === 'de';
	}
}

require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-translation-provider.php';
require_once __DIR__ . '/../../includes/class-seo-hreflang.php';
require_once __DIR__ . '/../../includes/class-seo-router.php';

GML_Translate_Test_State::reset();
GML_Translate_Test_State::$home_url = 'https://example.com/ygnaglul';
GML_Translate_Test_State::$options['gml_multilingual_enabled'] = true;
GML_Translate_Test_State::$options['gml_ai_translation_enabled'] = false;
GML_Translate_Test_State::$options['gml_source_lang'] = 'en';
GML_Translate_Test_State::$options['gml_languages'] = [
	[ 'code' => 'de', 'enabled' => true ],
	[ 'code' => 'es', 'enabled' => true ],
];
$_SERVER['REQUEST_URI'] = '/ygnaglul/de/about/?utm_source=test';

$provider = new GML_Translation_Provider();
$seo      = new GML_SEO_Hreflang( $provider );
ob_start();
$seo->inject_multilingual_meta();
$head = ob_get_clean();

gml_test_assert( substr_count( $head, '<link rel="canonical"' ) === 1, 'standalone emits one canonical without another SEO authority' );
gml_test_assert( strpos( $head, 'rel="canonical" href="https://example.com/ygnaglul/de/about/"' ) !== false, 'translated canonical is self-referencing' );
gml_test_assert( strpos( $head, 'hreflang="en" href="https://example.com/ygnaglul/about/"' ) !== false, 'source alternate keeps one site subdirectory' );
gml_test_assert( strpos( $head, 'hreflang="de" href="https://example.com/ygnaglul/de/about/"' ) !== false, 'ready target alternate is present' );
gml_test_assert( strpos( $head, 'hreflang="es"' ) === false, 'incomplete target is not advertised' );
gml_test_assert( strpos( $head, '/ygnaglul/de/ygnaglul/' ) === false, 'head output never duplicates the WordPress subdirectory' );

$urls = GML_SEO_Router::get_language_urls();
gml_test_assert( $urls['en'] === 'https://example.com/ygnaglul/about/', 'router source URL is subdirectory-safe' );
gml_test_assert( $urls['de'] === 'https://example.com/ygnaglul/de/about/', 'router translated URL is subdirectory-safe' );

echo "OK test-multilingual-seo-subdirectory\n";
