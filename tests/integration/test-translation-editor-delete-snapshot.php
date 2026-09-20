<?php
/** Translation Editor deletion must carry the displayed full-row snapshot. */

require_once __DIR__ . '/../bootstrap-mock.php';

$root = dirname( __DIR__, 2 );
$script = file_get_contents( $root . '/assets/js/translation-editor.js' );
$editor = file_get_contents( $root . '/includes/vendor/gml-translation-core/src/class-translation-editor.php' );
$memory = file_get_contents( $root . '/includes/vendor/gml-translation-core/src/class-translation-memory.php' );

foreach ( [ $script, $editor, $memory ] as $source ) {
    gml_test_assert( is_string( $source ) && $source !== '', 'translation deletion source is readable' );
}

gml_test_assert(
    strpos( $script, "edit_snapshot: tr.attr('data-edit-snapshot')" ) !== false,
    'browser sends the displayed translation snapshot with delete'
);
gml_test_assert( strpos( $editor, "delete_by_id( \$id, \$expected )" ) !== false, 'AJAX delete requires and forwards the snapshot' );
gml_test_assert( strpos( $memory, 'SELECT * FROM $table WHERE id=%d FOR UPDATE' ) !== false, 'Core locks and rereads the row before delete' );
gml_test_assert( substr_count( $memory, 'self::edit_token(' ) >= 3, 'Core compares full-row tokens before and during the transaction' );

echo "OK test-translation-editor-delete-snapshot\n";
