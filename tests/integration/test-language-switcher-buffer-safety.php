<?php
/** Regression: switcher rendering is safe inside another buffer callback. */

require_once __DIR__ . '/../bootstrap-mock.php';

if ( ! function_exists( 'shortcode_atts' ) ) { function shortcode_atts( $pairs, $atts, $shortcode = '' ) { return array_merge( $pairs, (array) $atts ); } }
if ( ! function_exists( 'wp_parse_args' ) ) { function wp_parse_args( $args, $defaults = [] ) { return array_merge( $defaults, (array) $args ); } }
if ( ! function_exists( 'esc_html' ) ) { function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); } }
if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode( $tag, $callback ) {} }
if ( ! function_exists( 'register_widget' ) ) { function register_widget( $class ) {} }
if ( ! function_exists( 'wp_enqueue_style' ) ) { function wp_enqueue_style() {} }
if ( ! function_exists( 'wp_enqueue_script' ) ) { function wp_enqueue_script() {} }
if ( ! function_exists( 'wp_localize_script' ) ) { function wp_localize_script() {} }
if ( ! class_exists( 'WP_Widget' ) ) { class WP_Widget { public function __construct() {} } }
if ( ! class_exists( 'GML_Admin_Settings' ) ) { class GML_Admin_Settings { public static function get_country_from_locale( $lang, $locale ) { return 'us'; } } }
if ( ! class_exists( 'GML_SEO_Router' ) ) {
    class GML_SEO_Router {
        public static function get_language_urls() { return [ 'en' => 'https://example.com/', 'de' => 'https://example.com/de/' ]; }
    }
}

require_once __DIR__ . '/../../includes/class-language-switcher.php';

GML_Translate_Test_State::reset();
GML_Translate_Test_State::$options = [
    'gml_source_lang'              => 'en',
    'gml_languages'                => [ [ 'code' => 'de', 'enabled' => true, 'country' => 'de' ] ],
    'gml_switcher_is_dropdown'     => false,
    'gml_switcher_show_flags'      => false,
    'gml_switcher_show_names'      => true,
    'gml_switcher_use_fullname'    => true,
    'gml_switcher_appearance'      => 'inherit',
    'gml_switcher_panel_alignment' => 'auto',
];
$_SERVER['REQUEST_URI'] = '/';

$switcher = ( new ReflectionClass( 'GML_Language_Switcher' ) )->newInstanceWithoutConstructor();
$ordinary = $switcher->render_shortcode( [] );
gml_test_assert( strpos( $ordinary, 'gml-language-switcher' ) !== false, 'ordinary shortcode returns switcher HTML' );
gml_test_assert( $switcher->render_shortcode( [] ) === $ordinary, 'repeated shortcode rendering is stable' );

ob_start();
ob_start( static function( $buffer ) use ( $switcher ) {
    return $buffer . $switcher->render_shortcode( [] ) . $switcher->render_shortcode( [] );
} );
echo 'outer-buffer-content';
ob_end_flush();
$nested = ob_get_clean();
gml_test_assert( strpos( $nested, 'outer-buffer-content' ) !== false, 'outer buffer content is preserved' );
gml_test_assert( substr_count( $nested, 'gml-language-switcher' ) >= 2, 'nested callback can render the shortcode more than once' );

$menu = $switcher->append_to_menu( '<li>base</li>', (object) [ 'theme_location' => 'primary' ] );
gml_test_assert( strpos( $menu, 'gml-menu-item' ) !== false && strpos( $menu, 'gml-language-switcher' ) !== false, 'menu integration consumes the same renderer' );

GML_Translate_Test_State::$options['gml_switcher_position'] = 'header_right';
ob_start();
$switcher->render_auto_position_inline();
$automatic = ob_get_clean();
gml_test_assert( strpos( $automatic, 'gml-auto-switcher' ) !== false && strpos( $automatic, 'gml-language-switcher' ) !== false, 'automatic positioning output remains intact' );

echo "OK test-language-switcher-buffer-safety\n";
