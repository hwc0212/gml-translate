<?php
/**
 * WordPress Settings API registration for GML Translate.
 *
 * @package GML_Translate
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class GML_Admin_Settings_Registration {

    private static $registered = false;

    public static function register_hooks() {
        if ( self::$registered ) return;
        self::$registered = true;
        add_action( 'admin_init', [ __CLASS__, 'register' ] );
    }

    public static function register() {
        register_setting( 'gml_settings', 'gml_api_key_encrypted' );
        register_setting( 'gml_settings', 'gml_api_endpoint' );
        register_setting( 'gml_settings', 'gml_source_lang' );
        register_setting( 'gml_settings', 'gml_enabled_languages' );
        register_setting( 'gml_settings', 'gml_industry' );
        register_setting( 'gml_settings', 'gml_tone' );
        register_setting( 'gml_settings', 'gml_protected_terms' );
    }
}
