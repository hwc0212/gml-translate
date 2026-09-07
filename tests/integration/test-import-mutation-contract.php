<?php
require_once __DIR__ . '/../bootstrap-mock.php';

$importer = file_get_contents( __DIR__ . '/../../tools/import-weglot.php' );
gml_test_assert( strpos( $importer, 'GML_Translation_Memory::upsert_batch' ) !== false, 'Weglot importer uses the Core Translation Memory mutation contract' );
gml_test_assert( strpos( $importer, '$wpdb->replace' ) === false, 'Weglot importer contains no direct Translation Memory write bypass' );
gml_test_assert( strpos( $importer, 'skipped_manual' ) !== false, 'Weglot importer preserves manual translations through the Core contract' );

echo "OK test-import-mutation-contract\n";
