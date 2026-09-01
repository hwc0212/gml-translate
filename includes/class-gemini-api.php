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
    const QWEN_API_BASE = 'https://dashscope.aliyuncs.com/compatible-mode/v1';
    const QWEN_MODEL = 'qwen-plus';
    const OPENAI_API_BASE = 'https://api.openai.com/v1';
    const OPENAI_MODEL = 'gpt-4o-mini';

    const ENGINE_GEMINI = 'gemini';
    const ENGINE_DEEPSEEK = 'deepseek';
    const ENGINE_QWEN = 'qwen';
    const ENGINE_OPENAI = 'openai';

    public function __construct( array $override = [] ) {
        $engine = sanitize_key( $override['engine'] ?? get_option( 'gml_translation_engine', self::ENGINE_GEMINI ) );
        if ( ! in_array( $engine, self::supported_engines(), true ) ) {
            $engine = self::ENGINE_GEMINI;
        }

        parent::__construct( self::provider_config( $engine, $override ) + [
            'engine'          => $engine,
            'protected_terms' => get_option( 'gml_protected_terms', [ 'GML', 'WordPress', 'WooCommerce', 'Gemini' ] ),
            'site_name'       => get_bloginfo( 'name' ),
            'tone'            => get_option( 'gml_tone', 'professional and friendly' ),
        ] );
    }

    public static function supported_engines() {
        return [ self::ENGINE_GEMINI, self::ENGINE_DEEPSEEK, self::ENGINE_QWEN, self::ENGINE_OPENAI ];
    }

    private static function provider_config( $engine, array $override ) {
        $definitions = [
            self::ENGINE_GEMINI => [
                'label' => 'Google Gemini', 'style' => self::STYLE_GEMINI,
                'model_option' => 'gml_api_model', 'model' => self::DEFAULT_MODEL,
                'base_option' => '', 'base_url' => self::API_BASE,
            ],
            self::ENGINE_DEEPSEEK => [
                'label' => 'DeepSeek', 'style' => self::STYLE_OPENAI,
                'model_option' => 'gml_deepseek_model', 'model' => self::DEEPSEEK_MODEL,
                'base_option' => 'gml_deepseek_api_base', 'base_url' => self::DEEPSEEK_API_BASE,
            ],
            self::ENGINE_QWEN => [
                'label' => 'Qwen', 'style' => self::STYLE_OPENAI,
                'model_option' => 'gml_qwen_model', 'model' => self::QWEN_MODEL,
                'base_option' => 'gml_qwen_api_base', 'base_url' => self::QWEN_API_BASE,
            ],
            self::ENGINE_OPENAI => [
                'label' => 'OpenAI', 'style' => self::STYLE_OPENAI,
                'model_option' => 'gml_openai_model', 'model' => self::OPENAI_MODEL,
                'base_option' => 'gml_openai_api_base', 'base_url' => self::OPENAI_API_BASE,
            ],
        ];
        $definition = $definitions[ $engine ];
        $api_key = array_key_exists( 'api_key', $override )
            ? (string) $override['api_key']
            : self::stored_api_key( $engine );
        $model = sanitize_text_field( $override['model'] ?? get_option( $definition['model_option'], $definition['model'] ) );
        $base_url = $definition['base_url'];
        if ( $definition['base_option'] !== '' ) {
            $base_url = self::secure_base_url(
                $override['base_url'] ?? get_option( $definition['base_option'], $definition['base_url'] ),
                $definition['base_url']
            );
            if ( $engine === self::ENGINE_DEEPSEEK && ! preg_match( '#/v1$#', $base_url ) ) {
                $base_url .= '/v1';
            } elseif ( $engine === self::ENGINE_QWEN && ! preg_match( '#/compatible-mode/v1$#', $base_url ) ) {
                $base_url = preg_replace( '#/(?:compatible-mode)?$#', '', $base_url ) . '/compatible-mode/v1';
            } elseif ( $engine === self::ENGINE_OPENAI && ! preg_match( '#/v1$#', $base_url ) ) {
                $base_url .= '/v1';
            }
        }
        $host = wp_parse_url( $base_url, PHP_URL_HOST );
        return [
            'api_key'         => $api_key,
            'credential_error'=> ! array_key_exists( 'api_key', $override ) && GML_Translation_Credentials::status( $engine ) === 'unreadable',
            'model'           => $model,
            'label'           => $definition['label'],
            'style'           => $definition['style'],
            'base_url'        => $base_url,
            'allowed_hosts'   => $host ? [ $host ] : [],
        ];
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
        $labels = [
            self::ENGINE_GEMINI => 'Google Gemini',
            self::ENGINE_DEEPSEEK => 'DeepSeek',
            self::ENGINE_QWEN => 'Qwen',
            self::ENGINE_OPENAI => 'OpenAI',
        ];
        return $labels[ $engine ] ?? 'AI';
    }
}
