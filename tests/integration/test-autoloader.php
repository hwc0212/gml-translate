<?php
require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../gml-translate.php';

foreach ( [ 'GML_HTML_Parser', 'GML_Translator', 'GML_Gemini_API', 'GML_Installer' ] as $class ) {
	gml_test_assert( class_exists( $class ), "autoloads {$class}" );
}

gml_test_assert( isset( GML_Translate_Test_State::$actions['plugins_loaded'] ), 'registers plugins_loaded hooks' );
echo "OK test-autoloader\n";
