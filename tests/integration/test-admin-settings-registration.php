<?php
/** Regression: Settings API registration is isolated from tab rendering. */

require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../admin/class-admin-settings-registration.php';
require_once __DIR__ . '/../../admin/class-resource-review-admin.php';
require_once __DIR__ . '/../../admin/class-admin-settings.php';

function admin_settings_registration_assert( $condition, $label ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$label}\n" );
		exit( 1 );
	}
}

GML_Translate_Test_State::reset();
new GML_Admin_Settings();

$callbacks = GML_Translate_Test_State::$actions['admin_init'] ?? [];
admin_settings_registration_assert( count( $callbacks ) === 2, 'settings and review controllers each register one admin_init hook' );
admin_settings_registration_assert( in_array( [ 'GML_Admin_Settings_Registration', 'register' ], $callbacks, true ), 'settings registration belongs to its dedicated controller' );
$review_callback = array_values( array_filter( $callbacks, static function( $callback ) {
    return is_array( $callback ) && is_object( $callback[0] ) && $callback[0] instanceof GML_Resource_Review_Admin && $callback[1] === 'handle_request';
} ) );
admin_settings_registration_assert( count( $review_callback ) === 1, 'review mutations belong to their dedicated controller' );

$renderer = file_get_contents( __DIR__ . '/../../admin/class-admin-settings.php' );
admin_settings_registration_assert( strpos( $renderer, 'function register_settings' ) === false, 'tab renderer no longer owns Settings API registration' );

echo "OK test-admin-settings-registration\n";
