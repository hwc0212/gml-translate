<?php
/** Ensure upstream gettext translations remain ready in the HTML buffer. */

require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../gml-translate.php';

class GML_Gettext_Output_Readiness_Probe extends GML_Output_Buffer {
	public function __construct() {}

	public function rendered_readiness( array $translated, $lang ) {
		$this->target_lang = $lang;
		return $this->translation_is_index_ready( $translated );
	}
}

$filter = new GML_Gettext_Filter();
$reflection = new ReflectionClass( $filter );
foreach ( [
	'target_lang' => 'es',
	'dict'        => [ md5( 'Widget title' ) => 'Titulo del widget' ],
] as $property_name => $value ) {
	$property = $reflection->getProperty( $property_name );
	$property->setAccessible( true );
	$property->setValue( $filter, $value );
}

$upstream = $filter->filter_gettext( 'Widget title', 'Widget title', 'default' );
gml_test_assert( $upstream === 'Titulo del widget', 'gettext returns the stored target translation' );

$render = [
	'nodes' => [
		[ 'text' => 'Article body', 'hash' => md5( 'Article body' ), 'context_type' => 'text' ],
		[ 'text' => $upstream, 'hash' => md5( $upstream ), 'context_type' => 'seo_title' ],
	],
	'replacements' => [ 'Article body' => 'Cuerpo del articulo' ],
];

$probe = new GML_Gettext_Output_Readiness_Probe();
gml_test_assert( $probe->rendered_readiness( $render, 'es' ), 'output readiness counts the request-local gettext translation' );
gml_test_assert( ! $probe->rendered_readiness( $render, 'de' ), 'gettext readiness registration cannot cross target languages' );

echo "OK test-gettext-output-readiness\n";
