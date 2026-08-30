<?php
require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-installer.php';

$source = file_get_contents( __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-installer.php' );
gml_test_assert( GML_Installer::DB_VERSION === '2.5.0', 'pins the reviewed database version' );
gml_test_assert( strpos( $source, 'gml_index' ) !== false, 'installer owns the translation index' );
gml_test_assert( strpos( $source, 'gml_queue' ) !== false, 'installer owns the translation queue' );
gml_test_assert( strpos( $source, 'gml_plans' ) === false, 'removed plan table is not recreated' );
gml_test_assert( strpos( $source, 'gml_plan_items' ) === false, 'removed plan item table is not recreated' );
gml_test_assert( strpos( $source, 'UNIQUE KEY queue_hash_lang (source_hash, source_lang, target_lang)' ) !== false, 'queue prevents duplicate provider work' );
gml_test_assert( strpos( $source, 'deduplicate_queue_rows' ) !== false, 'upgrade deduplicates queue state before adding the unique key' );
gml_test_assert( strpos( $source, 'gml_queue_deduplicated_count' ) !== false, 'upgrade records how many duplicate work rows were removed' );
echo "OK test-installer-contract\n";
