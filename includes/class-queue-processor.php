<?php
/**
 * Standalone queue adapter for GML Translation Core.
 *
 * @package GML_Translate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-translation-queue-processor.php';

class GML_Queue_Processor extends GML_Translation_Queue_Processor {

    const TEXT_DOMAIN = 'gml-translate';

    protected function translation_work_enabled() {
        return class_exists( 'GML_Translation_State' ) && GML_Translation_State::work_enabled();
    }

    protected function ai_translation_available() {
        return class_exists( 'GML_Translation_State' ) && GML_Translation_State::ai_available();
    }
}
