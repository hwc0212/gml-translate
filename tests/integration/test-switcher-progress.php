<?php
require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-url-helper.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-language-utils.php';
require_once __DIR__ . '/../../includes/class-seo-router.php';

final class GML_Resource_Identity {
    public static $valid = true;
    public static $url = '';
    public static function current_public() { return self::$valid ? new self() : null; }
    public function is_eligible() { return self::$valid; }
    public function get_source_url() { return self::$url; }
}
final class GML_Public_Eligibility {
    public static function get_public_urls() { throw new RuntimeException('Navigation must not query translation completion'); }
}
GML_Translate_Test_State::$options['gml_source_lang']='en';
GML_Translate_Test_State::$options['gml_languages']=[
    ['code'=>'es','enabled'=>true], ['code'=>'de','enabled'=>true],
    ['code'=>'ru','enabled'=>true], ['code'=>'fr','enabled'=>true],
    ['code'=>'it','enabled'=>false],
    ['code'=>'zh','enabled'=>true,'site_mode'=>'external','external_url'=>'https://external.example/'],
];
foreach (['https://example.com','https://example.com/staging'] as $base) {
    GML_Translate_Test_State::$home_url=$base;
    GML_Resource_Identity::$url=$base.'/about/';
    $urls=GML_SEO_Router::get_language_urls();
    foreach (['es','de','ru','fr'] as $lang) gml_test_assert(($urls[$lang]??'')===$base.'/'.$lang.'/about/', 'enabled language remains clickable independently of progress: '.$lang);
    gml_test_assert(!isset($urls['it']) && !isset($urls['zh']), 'disabled languages and external routes are not invented locally');
}
GML_Resource_Identity::$valid=false;
gml_test_assert(GML_SEO_Router::get_language_urls()===[], 'an unresolved source 404 still creates no language links');
echo "OK test-switcher-progress\n";
