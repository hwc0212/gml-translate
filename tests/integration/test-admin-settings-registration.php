<?php
/** Regression: Settings API registration is isolated from tab rendering. */

require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../admin/class-admin-settings-registration.php';
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
admin_settings_registration_assert( count( $callbacks ) === 1, 'settings registration hook is registered once' );
admin_settings_registration_assert( $callbacks[0] === [ 'GML_Admin_Settings_Registration', 'register' ], 'registration belongs to its dedicated controller' );

$renderer = file_get_contents( __DIR__ . '/../../admin/class-admin-settings.php' );
admin_settings_registration_assert( strpos( $renderer, 'function register_settings' ) === false, 'tab renderer no longer owns Settings API registration' );

echo "OK test-admin-settings-registration\n";
