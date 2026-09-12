<?php
/** Isolated UI fixture: real renderer and URL helpers, no database or provider. */
require_once __DIR__ . '/../bootstrap-mock.php';
function wp_parse_args( $args, $defaults = [] ) { return array_merge( $defaults, (array) $args ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
class WP_Widget {}
class GML_Admin_Settings {
    public static function get_country_from_locale( $lang, $locale ) { return 'us'; }
}
class GML_SEO_Router {
    public static $urls = [];
    public static function get_language_urls() { return self::$urls; }
}
class GML_Resource_Identity {
    public static function current_public() { return new self(); }
}
class GML_Public_Eligibility {
    public static $calls = 0;
    public static $ready = false;
    public static function get_cluster( $resource ) {
        self::$calls++;
        $languages = [];
        foreach ( [ 'es', 'de', 'ru', 'fr' ] as $lang ) {
            $languages[$lang] = [ 'route_valid' => true, 'page_readiness' => [
                'ready' => self::$ready, 'percent' => $lang === 'ru' ? 97.6 : 98.8,
            ] ];
        }
        return [ 'languages' => $languages ];
    }
}
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-language-utils.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-url-helper.php';
require_once __DIR__ . '/../../includes/class-language-switcher.php';

function gml_switcher_fixture( $case = 'all', $base = 'https://example.com' ) {
    GML_Translate_Test_State::reset();
    GML_Translate_Test_State::$home_url = $base;
    GML_Translate_Test_State::$options = [
        'gml_source_lang' => 'en',
        'gml_switcher_show_flags' => false,
        'gml_switcher_use_fullname' => false,
        'gml_languages' => array_map( static function( $code ) {
            return [ 'code' => $code, 'enabled' => true, 'site_mode' => 'local' ];
        }, [ 'es', 'de', 'ru', 'fr' ] ),
    ];
    if ( $case === 'none' ) GML_Translate_Test_State::$options['gml_languages'] = [];
    if ( ! in_array( $case, [ 'none', 'unavailable' ], true ) ) {
        GML_Translate_Test_State::$options['gml_languages'][] = [
            'code' => 'zh', 'enabled' => true, 'site_mode' => 'external',
            'external_url' => 'https://external.example/', 'external_path_mode' => 'homepage',
        ];
    }
    GML_SEO_Router::$urls = [ 'en' => home_url( '/' ) ];
    if ( $case === 'all' ) {
        foreach ( [ 'es', 'de', 'ru', 'fr' ] as $lang ) {
            GML_SEO_Router::$urls[$lang] = home_url( '/' . $lang . '/' );
        }
    }
    if ( $case === 'complete' ) GML_SEO_Router::$urls['ru'] = home_url( '/ru/' );
    return ( new ReflectionClass( 'GML_Language_Switcher' ) )->newInstanceWithoutConstructor();
}
