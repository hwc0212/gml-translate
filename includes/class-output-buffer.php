<?php
/** Standalone adapter for the shared translated HTML output pipeline. */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'GML_Translation_Output_Buffer', false ) ) {
    require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-output-buffer.php';
}

class GML_Output_Buffer extends GML_Translation_Output_Buffer {}
