<?php
require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-installer.php';

$source = file_get_contents( __DIR__ . '/../../includes/vendor/gml-translation-core/src/class-installer.php' );
gml_test_assert( GML_Installer::DB_VERSION === '3.3.0', 'pins the derived publication eligibility database version' );
gml_test_assert( strpos( $source, 'gml_index' ) !== false, 'installer owns the translation index' );
gml_test_assert( strpos( $source, 'gml_queue' ) !== false, 'installer owns the translation queue' );
gml_test_assert( strpos( $source, 'gml_resource_manifests' ) !== false, 'installer owns resource manifests' );
gml_test_assert( strpos( $source, 'gml_resource_strings' ) !== false, 'installer owns resource-to-hash relationships' );
gml_test_assert( strpos( $source, 'gml_resource_readiness' ) !== false, 'installer owns machine-readiness snapshots' );
gml_test_assert( strpos( $source, 'gml_resource_translation_versions' ) !== false, 'installer owns review-safe translation generations' );
gml_test_assert( strpos( $source, 'gml_resource_reviews' ) !== false, 'installer owns current human review decisions' );
gml_test_assert( strpos( $source, 'gml_resource_review_audit' ) !== false, 'installer owns append-only human review history' );
gml_test_assert( strpos( $source, 'gml_plans' ) === false, 'removed plan table is not recreated' );
gml_test_assert( strpos( $source, 'gml_plan_items' ) === false, 'removed plan item table is not recreated' );
gml_test_assert( strpos( $source, 'UNIQUE KEY queue_hash_lang (source_hash, source_lang, target_lang)' ) !== false, 'queue prevents duplicate provider work' );
gml_test_assert( strpos( $source, 'deduplicate_queue_rows' ) === false, 'upgrade never deduplicates a live queue' );
gml_test_assert( strpos( $source, 'DELETE FROM' ) === false, 'upgrade never deletes existing translation or readiness rows' );
gml_test_assert(
    substr_count( $source, 'ALTER TABLE' ) === 3
        && strpos( $source, 'ALTER TABLE $table ADD COLUMN $column $definition' ) !== false
        && strpos( $source, 'ALTER TABLE $table ADD KEY status_id (status, id)' ) !== false
        && strpos( $source, 'ALTER TABLE $table ADD KEY source_url_hash (source_url_hash)' ) !== false,
    'schema upgrades are limited to idempotent additive columns and bounded readiness or URL lookup indexes'
);
gml_test_assert( strpos( $source, 'DROP TABLE' ) === false && strpos( $source, 'DROP COLUMN' ) === false, 'snapshot-safe schema upgrade contains no destructive migration' );
gml_test_assert( strpos( $source, 'disable_large_option_autoload' ) !== false, 'upgrade removes large rule arrays from alloptions' );
gml_test_assert( strpos( $source, 'wp_set_option_autoload_values' ) !== false, 'uses the modern WordPress autoload API when available' );
gml_test_assert( strpos( $source, "autoload = 'no'" ) !== false, 'supports the WordPress 6.0-6.5 autoload migration fallback' );
echo "OK test-installer-contract\n";
