<?php
require_once __DIR__ . '/../fixtures/switcher-bootstrap.php';

$cases = 0;
foreach ( [ '', '/staging' ] as $prefix ) {
    foreach ( [ 'en', 'de' ] as $current ) {
        foreach ( [ true, false ] as $dropdown ) {
            foreach ( [ true, false ] as $fullname ) {
                foreach ( [ true, false ] as $ready ) {
                    $base = 'https://example.com' . $prefix;
                    $switcher = gml_switcher_fixture( 'all', $base );
                    GML_Translate_Test_State::$options['gml_switcher_use_fullname'] = $fullname;
                    GML_Public_Eligibility::$ready = $ready;
                    GML_Public_Eligibility::$calls = 0;
                    $_SERVER['REQUEST_URI'] = $prefix . ( $current === 'en' ? '/' : '/de/' );
                    $before = GML_Translate_Test_State::$options;
                    $urls = GML_SEO_Router::$urls;
                    $html = $switcher->render_component( [ 'is_dropdown' => $dropdown, 'menu_context' => true ] );
                    gml_test_assert( strpos( $html, 'Translation incomplete' ) === false, 'navigation contains no progress message' );
                    gml_test_assert( strpos( $html, 'gml-page-progress-label' ) === false && strpos( $html, '%' ) === false, 'navigation contains no progress label or percentage' );
                    gml_test_assert( GML_Public_Eligibility::$calls === 0, 'renderer does not request a readiness cluster for presentation' );
                    gml_test_assert( substr_count( $html, '<a ' ) === ( $dropdown ? 5 : 6 ), 'all configured valid language links remain present' );
                    foreach ( [ 'en', 'es', 'de', 'ru', 'fr', 'zh' ] as $lang ) {
                        if ( $dropdown && $lang === $current ) continue;
                        $url = $lang === 'zh' ? 'https://external.example/' : $base . ( $lang === 'en' ? '/' : '/' . $lang . '/' );
                        gml_test_assert( strpos( $html, 'href="' . $url . '"' ) !== false, 'language destination unchanged: ' . $lang );
                    }
                    gml_test_assert( strpos( $html, $fullname ? '>Deutsch</span>' : '>DE</span>' ) !== false, 'existing full-name/code preference respected' );
                    gml_test_assert( strpos( $html, $dropdown ? 'aria-expanded="false"' : 'aria-current="page"' ) !== false, 'current language and disclosure semantics retained' );
                    gml_test_assert( GML_Translate_Test_State::$options === $before && GML_SEO_Router::$urls === $urls, 'rendering changes neither settings nor URL ownership' );
                    $cases++;
                }
            }
        }
    }
}
echo "OK test-switcher-navigation-only ($cases cases)\n";
