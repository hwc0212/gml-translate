<?php
require_once __DIR__ . '/../bootstrap-mock.php';

$root      = dirname( __DIR__, 2 );
$uninstall = file_get_contents( $root . '/uninstall.php' );
$admin     = file_get_contents( $root . '/admin/class-admin-settings.php' );
$core      = file_get_contents( $root . '/includes/vendor/gml-translation-core/src/class-translation-uninstaller.php' );

gml_test_assert( strpos( $uninstall, 'WP_UNINSTALL_PLUGIN' ) !== false, 'uninstall entry point rejects direct requests' );
gml_test_assert( strpos( $uninstall, 'gml_translate_uninstall_delete_data' ) !== false, 'uninstall entry point reads the explicit retention preference' );
gml_test_assert( strpos( $uninstall, "gml-seo/gml-seo.php" ) !== false, 'standalone uninstall protects data shared with GML SEO' );
gml_test_assert( strpos( $admin, "hash_equals( 'DELETE'" ) !== false, 'complete removal requires an exact typed confirmation' );
gml_test_assert( strpos( $admin, 'Retain all plugin data (recommended)' ) !== false, 'retention is the recommended administrator choice' );
gml_test_assert( strpos( $core, 'gml_index' ) !== false && strpos( $core, 'gml_queue' ) !== false, 'complete removal includes translation memory and queue tables' );
gml_test_assert( strpos( $core, "option_name NOT LIKE %s" ) !== false, 'translation cleanup excludes GML SEO-owned options' );
echo "OK test-uninstall-contract\n";
