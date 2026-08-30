<?php
/**
 * Standalone adapter for the shared translation crawler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-translation-content-crawler.php';

class GML_Content_Crawler extends GML_Translation_Content_Crawler {}
