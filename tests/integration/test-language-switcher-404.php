<?php
/** Regression: a source 404 never advertises manufactured language routes. */

require_once __DIR__ . '/../bootstrap-mock.php';

if ( ! class_exists( 'WP_Widget' ) ) {
	class WP_Widget {}
}

require_once __DIR__ . '/../../includes/class-language-switcher.php';
require_once __DIR__ . '/../../includes/class-nav-menu-switcher.php';

GML_Translate_Test_State::reset();
GML_Translate_Test_State::$is_404 = true;

gml_test_assert(
	! GML_Language_Switcher::request_supports_language_links(),
	'404 requests do not support language links'
);

$switcher = ( new ReflectionClass( 'GML_Language_Switcher' ) )->newInstanceWithoutConstructor();
gml_test_assert( $switcher->render() === '', 'shortcode and template rendering are empty on a 404' );

$nav = new GML_Nav_Menu_Switcher();
$items = [
	(object) [ 'url' => '#gml-language-switcher', 'type' => 'custom' ],
	(object) [ 'url' => '/about/', 'type' => 'custom' ],
];
$filtered = $nav->filter_menu_objects( $items, (object) [] );
gml_test_assert( count( $filtered ) === 1 && $filtered[0]->url === '/about/', 'menu switcher item is removed from a 404 menu' );
gml_test_assert(
	$nav->render_menu_item( 'placeholder', (object) [ 'type' => GML_Nav_Menu_Switcher::ITEM_TYPE ], 0, (object) [] ) === '',
	'menu walker cannot restore a language switcher on a 404'
);

GML_Translate_Test_State::$is_404 = false;
gml_test_assert( GML_Language_Switcher::request_supports_language_links(), 'resolved public pages still support language links' );

echo "OK test-language-switcher-404\n";
