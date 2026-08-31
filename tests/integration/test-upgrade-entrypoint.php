<?php
/** Regression: public bootstrap must never run a schema migration. */
require_once __DIR__ . '/../bootstrap-mock.php';

$source = file_get_contents( __DIR__ . '/../../gml-translate.php' );
gml_test_assert( strpos( $source, '$this->maybe_upgrade_db();' ) === false, 'frontend bootstrap does not invoke database migration' );
gml_test_assert( strpos( $source, 'GML_Installer::register_hooks();' ) !== false, 'registers the guarded admin migration entrypoint' );
echo "OK test-upgrade-entrypoint\n";
