<?php
/**
 * Standalone AI provider adapter for the shared translation client.
 *
 * @package GML_Translate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-translation-ai-client.php';

class GML_Gemini_API extends GML_Translation_AI_Client {

    const DEFAULT_MODEL = 'gemini-3.6-flash';
    const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';
    const DEEPSEEK_API_BASE = 'https://api.deepseek.com/v1';
    const DEEPSEEK_MODEL = 'deepseek-chat';

    const ENGINE_GEMINI = 'gemini';
    const ENGINE_DEEPSEEK = 'deepseek';

    public function __construct( array $override = [] ) {
        $engine = sanitize_key( $override['engine'] ?? get_option( 'gml_translation_engine', self::ENGINE_GEMINI ) );
        if ( ! in_array( $engine, [ self::ENGINE_GEMINI, self::ENGINE_DEEPSEEK ], true ) ) {
            $engine = self::ENGINE_GEMINI;
        }

        $api_key = array_key_exists( 'api_key', $override )
            ? (string) $override['api_key']
            : self::stored_api_key( $engine );

        if ( $engine === self::ENGINE_DEEPSEEK ) {
            $model    = sanitize_text_field( $override['model'] ?? get_option( 'gml_deepseek_model', self::DEEPSEEK_MODEL ) );
            $base_url = self::secure_base_url(
                $override['base_url'] ?? get_option( 'gml_deepseek_api_base', self::DEEPSEEK_API_BASE ),
                self::DEEPSEEK_API_BASE
            );
            $style = self::STYLE_OPENAI;
        } else {
            $model    = sanitize_text_field( $override['model'] ?? get_option( 'gml_api_model', self::DEFAULT_MODEL ) );
            $base_url = self::API_BASE;
            $style    = self::STYLE_GEMINI;
        }

        $host = wp_parse_url( $base_url, PHP_URL_HOST );
        parent::__construct( [
            'engine'          => $engine,
            'api_key'         => $api_key,
            'credential_error' => ! array_key_exists( 'api_key', $override ) && GML_Translation_Credentials::status( $engine ) === 'unreadable',
            'model'           => $model,
            'label'           => self::get_engine_label( $engine ),
            'style'           => $style,
            'base_url'        => $base_url,
            'allowed_hosts'   => $host ? [ $host ] : [],
            'protected_terms' => get_option( 'gml_protected_terms', [ 'GML', 'WordPress', 'WooCommerce', 'Gemini' ] ),
            'site_name'       => get_bloginfo( 'name' ),
            'tone'            => get_option( 'gml_tone', 'professional and friendly' ),
        ] );
    }

    private static function stored_api_key( $engine ) {
        return GML_Translation_Credentials::read( $engine );
    }

    /**
     * Existing releases stored AES payloads without a version prefix. Keep the
     * legacy plaintext fallback read-only, but never create new plaintext keys.
     */
    public static function decrypt_key( $stored ) {
        return GML_Translation_Credentials::decode( $stored );
    }

    public static function save_api_key( $api_key, $engine = null ) {
        $engine  = $engine === null ? get_option( 'gml_translation_engine', self::ENGINE_GEMINI ) : sanitize_key( $engine );
        return GML_Translation_Credentials::save( $api_key, $engine );
    }

    public static function test_api_key( $api_key = null, $engine = null ) {
        $engine = $engine === null ? get_option( 'gml_translation_engine', self::ENGINE_GEMINI ) : sanitize_key( $engine );
        if ( trim( (string) $api_key ) === '' ) {
            return [ 'valid' => false, 'message' => __( 'No API key provided', 'gml-translate' ) ];
        }
        $client = new self( [ 'engine' => $engine, 'api_key' => (string) $api_key ] );
        return $client->test_connection();
    }

    public static function secure_base_url( $value, $fallback ) {
        $host = wp_parse_url( $fallback, PHP_URL_HOST );
        $url  = GML_AI_HTTP_Transport::validate_endpoint( $value, $host ? [ $host ] : [] );
        return is_wp_error( $url ) ? untrailingslashit( $fallback ) : $url;
    }

    public static function get_engine_label( $engine = null ) {
        $engine = $engine === null ? get_option( 'gml_translation_engine', self::ENGINE_GEMINI ) : sanitize_key( $engine );
        return $engine === self::ENGINE_DEEPSEEK ? 'DeepSeek' : 'Google Gemini';
    }
}
