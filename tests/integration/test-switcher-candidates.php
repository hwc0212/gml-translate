<?php
require_once __DIR__ . '/../fixtures/switcher-bootstrap.php';

foreach ( [ '', '/staging' ] as $prefix ) {
    $base = 'https://example.com' . $prefix;
    $_SERVER['REQUEST_URI'] = $prefix . '/';
    $switcher = gml_switcher_fixture( 'none', $base );
    $html = $switcher->render_component();
    gml_test_assert( strpos( $html, 'gml-dropdown-btn' ) === false, 'zero alternatives has no disclosure button' );
    gml_test_assert( strpos( $html, 'gml-dropdown-menu' ) === false, 'zero alternatives has no empty panel' );
    gml_test_assert( strpos( $html, 'gml-current-language' ) !== false, 'current language remains visible' );

    $switcher = gml_switcher_fixture( 'incomplete', $base );
    $seo_urls = GML_SEO_Router::$urls;
    $html = $switcher->render_component();
    gml_test_assert( substr_count( $html, '<li>' ) === 1, 'only enabled external navigation is available' );
    gml_test_assert( strpos( $html, 'href="https://external.example/"' ) !== false, 'external homepage navigation is retained' );
    gml_test_assert( GML_SEO_Router::$urls === $seo_urls, 'navigation never changes SEO public URLs' );
    gml_test_assert( strpos( $html, 'hreflang="de"' ) === false, 'incomplete local remains excluded' );
    gml_test_assert( strpos( $html, 'aria-controls="gml-language-options-' ) !== false, 'disclosure references its own panel' );
    gml_test_assert( strpos( $html, 'role="listbox"' ) === false, 'navigation links do not claim listbox semantics' );

    $switcher = gml_switcher_fixture( 'all', $base );
    $html = $switcher->render_component();
    gml_test_assert( substr_count( $html, '<li>' ) === 5, 'four local alternatives plus external navigation' );
    foreach ( [ 'es', 'de', 'ru', 'fr' ] as $lang ) {
        gml_test_assert( strpos( $html, 'href="' . $base . '/' . $lang . '/"' ) !== false, 'local URL contains base directory once' );
    }
    $_SERVER['REQUEST_URI'] = $prefix . '/ru/';
    $html = $switcher->render_component();
    gml_test_assert( strpos( $html, 'hreflang="ru"' ) === false, 'current language is not an alternative' );
    gml_test_assert( strpos( $html, 'hreflang="en"' ) !== false, 'translated page links back to source' );

    GML_Translate_Test_State::$options['gml_languages'][4]['enabled'] = false;
    $html = $switcher->render_component();
    gml_test_assert( strpos( $html, 'external.example' ) === false, 'disabled external language stays absent' );
    GML_Translate_Test_State::$options['gml_languages'][4]['enabled'] = true;
    GML_Translate_Test_State::$options['gml_languages'][4]['external_url'] = 'javascript:alert(1)';
    $html = $switcher->render_component();
    gml_test_assert( strpos( $html, 'javascript:' ) === false, 'invalid external URL is rejected' );
    GML_Translate_Test_State::$is_404 = true;
    gml_test_assert( $switcher->render_component() === '', '404 still has no language links' );
}
echo "OK test-switcher-candidates\n";
