<?php
/** Regression: standalone multilingual SEO is subdirectory-safe and AI-independent. */

require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-translation-state.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-url-helper.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-language-utils.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-translation-queue-scope.php';

class GML_Queue_Processor {
	public static function language_is_index_ready( $language ) {
		return $language === 'de';
	}
}

require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-translation-provider.php';
require_once __DIR__ . '/../../includes/class-seo-hreflang.php';
require_once __DIR__ . '/../../includes/class-seo-router.php';
require_once __DIR__ . '/../../includes/class-sitemap.php';

GML_Translate_Test_State::reset();
GML_Translate_Test_State::$home_url = 'https://example.com/ygnaglul';
GML_Translate_Test_State::$options['gml_multilingual_enabled'] = true;
GML_Translate_Test_State::$options['gml_ai_translation_enabled'] = false;
GML_Translate_Test_State::$options['gml_source_lang'] = 'en';
GML_Translate_Test_State::$options['gml_languages'] = [
	[ 'code' => 'de', 'enabled' => true ],
	[ 'code' => 'es', 'enabled' => true ],
	[
		'code'               => 'zh-cn',
		'enabled'            => true,
		'site_mode'          => 'external',
		'external_url'       => 'https://cnxhe.cn/',
		'external_path_mode' => 'same_path',
	],
	[
		'code'               => 'fr',
		'enabled'            => true,
		'site_mode'          => 'external',
		'external_url'       => 'https://fr.example.net/',
		'external_path_mode' => 'homepage',
	],
];
$_SERVER['REQUEST_URI'] = '/ygnaglul/de/about/?utm_source=test';

$provider = new GML_Translation_Provider();
$seo      = new GML_SEO_Hreflang( $provider );
ob_start();
$seo->inject_multilingual_meta();
$head = ob_get_clean();

$_SERVER['REQUEST_URI'] = '/ygnaglul/about/';
ob_start();
$seo->inject_multilingual_meta();
$source_head = ob_get_clean();
$_SERVER['REQUEST_URI'] = '/ygnaglul/de/about/?utm_source=test';

gml_test_assert( substr_count( $head, '<link rel="canonical"' ) === 1, 'standalone emits one canonical without another SEO authority' );
gml_test_assert( strpos( $head, 'rel="canonical" href="https://example.com/ygnaglul/about/"' ) !== false, 'unidentified or unapproved target fails closed to the source canonical' );
gml_test_assert( strpos( $head, 'hreflang=' ) === false, 'unidentified or unapproved target advertises no language alternates' );
gml_test_assert( strpos( $source_head, 'hreflang="de"' ) === false, 'source route does not advertise a target without a current approved resource snapshot' );
gml_test_assert( strpos( $head, 'hreflang="zh-CN"' ) === false, 'unverified external sites do not bypass the publication gate' );
gml_test_assert( strpos( $head, '/ygnaglul/de/ygnaglul/' ) === false, 'head output never duplicates the WordPress subdirectory' );

$urls = GML_SEO_Router::get_language_urls();
gml_test_assert( $urls === [], 'router exposes no switcher links without an identifiable approved resource cluster' );
gml_test_assert( GML_Language_Utils::detect_prefix_from_path( '/ygnaglul/zh-cn/about/', true ) === '', 'external language is not registered as a local route' );
gml_test_assert( GML_Language_Utils::sanitize_external_site_url( 'https://example.com/another-site/' ) === '', 'external mode rejects the current WordPress host' );
gml_test_assert( GML_Translation_Queue_Scope::enabled_languages() === [ 'de', 'es' ], 'external language sites are excluded from the local AI queue' );

GML_Translate_Test_State::$is_404 = true;
ob_start();
$seo->inject_multilingual_meta();
$not_found_head = ob_get_clean();
gml_test_assert( $not_found_head === '', 'standalone emits no canonical, hreflang, or locale discovery metadata on a 404' );
gml_test_assert(
	$seo->filter_canonical_url( 'https://example.com/ygnaglul/missing/' ) === 'https://example.com/ygnaglul/missing/',
	'standalone does not manufacture a translated canonical for a 404'
);
GML_Translate_Test_State::$is_404 = false;

if ( ! defined( 'GML_SEO_VER' ) ) define( 'GML_SEO_VER', 'test' );
GML_Translate_Test_State::$actions = [];
new GML_SEO_Hreflang( $provider );
new GML_Sitemap( $provider );
gml_test_assert( empty( GML_Translate_Test_State::$actions['wp_head'] ), 'standalone emits no competing head SEO when GML SEO is active' );
gml_test_assert( empty( GML_Translate_Test_State::$actions['template_redirect'] ), 'standalone emits no competing sitemap when GML SEO is active' );

echo "OK test-multilingual-seo-subdirectory\n";
