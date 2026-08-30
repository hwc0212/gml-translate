<?php
/** Standalone adapter for the shared translation memory service. */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-translator.php';

class GML_Translator extends GML_Translation_Translator {

    protected function ai_translation_available() {
        return class_exists( 'GML_Translation_State' ) && GML_Translation_State::ai_available();
    }
}
