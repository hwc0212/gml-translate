<?php
require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-installer.php';

$source = file_get_contents( __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-installer.php' );
gml_test_assert( GML_Installer::DB_VERSION === '3.0.0', 'pins the reviewed additive resource-readiness database version' );
gml_test_assert( strpos( $source, 'gml_index' ) !== false, 'installer owns the translation index' );
gml_test_assert( strpos( $source, 'gml_queue' ) !== false, 'installer owns the translation queue' );
gml_test_assert( strpos( $source, 'gml_resource_manifests' ) !== false, 'installer owns shadow resource manifests' );
gml_test_assert( strpos( $source, 'gml_resource_strings' ) !== false, 'installer owns resource-to-hash relationships' );
gml_test_assert( strpos( $source, 'gml_resource_readiness' ) !== false, 'installer owns machine-readiness snapshots' );
gml_test_assert( strpos( $source, 'gml_plans' ) === false, 'removed plan table is not recreated' );
gml_test_assert( strpos( $source, 'gml_plan_items' ) === false, 'removed plan item table is not recreated' );
gml_test_assert( strpos( $source, 'UNIQUE KEY queue_hash_lang (source_hash, source_lang, target_lang)' ) !== false, 'queue prevents duplicate provider work' );
gml_test_assert( strpos( $source, 'deduplicate_queue_rows' ) === false, 'upgrade never deduplicates a live queue' );
gml_test_assert( strpos( $source, 'DELETE FROM' ) === false && strpos( $source, 'ALTER TABLE' ) === false, 'upgrade preserves existing rows and table structure' );
gml_test_assert( strpos( $source, 'disable_large_option_autoload' ) !== false, 'upgrade removes large rule arrays from alloptions' );
gml_test_assert( strpos( $source, 'wp_set_option_autoload_values' ) !== false, 'uses the modern WordPress autoload API when available' );
gml_test_assert( strpos( $source, "autoload = 'no'" ) !== false, 'supports the WordPress 6.0-6.5 autoload migration fallback' );
echo "OK test-installer-contract\n";
