<?php
/**
 * GML Translate uninstall handler.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$core_uninstaller = __DIR__ . '/includes/vendor/gml-translation-core/src/class-translation-uninstaller.php';
if ( ! is_file( $core_uninstaller ) ) {
    return;
}

require_once $core_uninstaller;

// GML SEO reuses the same tables and options. Removing the standalone adapter
// must never damage data still available to the integrated product.
$seo_installed = defined( 'WP_PLUGIN_DIR' )
    && is_file( WP_PLUGIN_DIR . '/gml-seo/gml-seo.php' );

GML_Translation_Uninstaller::uninstall(
    'gml_translate_uninstall_delete_data',
    $seo_installed
);
