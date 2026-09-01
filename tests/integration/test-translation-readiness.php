<?php
/** Regression: index readiness is derived from stored work, never AI state. */

require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-language-utils.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-translation-readiness.php';

class GML_Readiness_Test_DB {
	public $prefix = 'wp_';

	public function get_results( $sql ) {
		if ( strpos( $sql, 'wp_gml_index' ) !== false ) {
			return [
				(object) [ 'target_lang' => 'de', 'item_count' => 120 ],
				(object) [ 'target_lang' => 'es', 'item_count' => 95 ],
				(object) [ 'target_lang' => 'ru', 'item_count' => 40 ],
			];
		}
		return [
			(object) [ 'target_lang' => 'es', 'item_count' => 2 ],
			(object) [ 'target_lang' => 'ru', 'item_count' => 60 ],
		];
	}
}

$GLOBALS['wpdb'] = new GML_Readiness_Test_DB();
GML_Translate_Test_State::$options['gml_ai_translation_enabled'] = false;

gml_test_assert( GML_Translation_Readiness::language_is_index_ready( 'de' ), 'stored complete language remains index-ready with AI disabled' );
gml_test_assert( GML_Translation_Readiness::language_is_index_ready( 'es' ), 'a few historical failures do not suppress an otherwise complete language' );
gml_test_assert( ! GML_Translation_Readiness::language_is_index_ready( 'ru' ), 'a substantially incomplete language remains withheld' );

echo "OK test-translation-readiness\n";
