<?php
/** Standalone adapter for the shared manual translation editor. */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-translation-editor.php';

class GML_Translation_Editor extends GML_Translation_Editor_Core {
    const TEXT_DOMAIN = 'gml-translate';
}
