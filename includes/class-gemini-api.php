<?php
/**
 * GML Gemini API - Google Gemini API integration
 *
 * Uses the official v1beta REST API:
 * POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
 *
 * @package GML_Translate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GML_Gemini_API {

    /** Default model — gemini-2.0-flash (stable, fast, cost-effective) */
    const DEFAULT_MODEL = 'gemini-2.0-flash';

    /** API base URL */
    const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';

    private $api_key;
    private $model;
    private $protected_terms = [];

    public function __construct() {
        $this->api_key        = $this->get_api_key();
        $this->model          = get_option( 'gml_api_model', self::DEFAULT_MODEL );
        $this->protected_terms = get_option( 'gml_protected_terms', [ 'GML', 'WordPress', 'WooCommerce', 'Gemini' ] );
    }

    // ── Key management ────────────────────────────────────────────────────────

    private function get_api_key() {
        $stored = get_option( 'gml_api_key_encrypted' );
        if ( ! $stored ) {
            return null;
        }
        if ( function_exists( 'openssl_decrypt' ) ) {
            $key        = wp_salt( 'auth' );
            $iv_len     = openssl_cipher_iv_length( 'AES-256-CBC' );
            $raw        = base64_decode( $stored );
            if ( strlen( $raw ) > $iv_len ) {
                $iv         = substr( $raw, 0, $iv_len );
                $cipher     = substr( $raw, $iv_len );
                $decrypted  = openssl_decrypt( $cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
                if ( $decrypted !== false ) {
                    return $decrypted;
                }
            }
        }
        return $stored; // fallback: stored as plain text
    }

    public static function save_api_key( $api_key ) {
        if ( function_exists( 'openssl_encrypt' ) ) {
            $key    = wp_salt( 'auth' );
            $iv_len = openssl_cipher_iv_length( 'AES-256-CBC' );
            $iv     = openssl_random_pseudo_bytes( $iv_len );
            $enc    = openssl_encrypt( $api_key, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
            update_option( 'gml_api_key_encrypted', base64_encode( $iv . $enc ) );
        } else {
            update_option( 'gml_api_key_encrypted', $api_key );
        }
    }

    // ── Public translation methods ────────────────────────────────────────────

    /**
     * Translate a single text string.
     */
    public function translate( $text, $source_lang, $target_lang ) {
        if ( ! $this->api_key ) {
            throw new Exception( 'Gemini API key not configured' );
        }
        $response = $this->call_api(
            $this->build_system_instruction( $source_lang, $target_lang, 'text' ),
            $text
        );
        return $this->extract_text( $response );
    }

    /**
     * Translate a single SEO meta text.
     */
    public function translate_seo( $text, $source_lang, $target_lang ) {
        if ( ! $this->api_key ) {
            throw new Exception( 'Gemini API key not configured' );
        }
        $response = $this->call_api(
            $this->build_system_instruction( $source_lang, $target_lang, 'seo' ),
            $text
        );
        return $this->extract_text( $response );
    }

    /**
     * Batch-translate multiple text strings in a single API call.
     *
     * Sends all texts as a numbered list, asks Gemini to return translations
     * in the same numbered format. This saves ~90% of system instruction tokens
     * and reduces HTTP round-trips from N to 1.
     *
     * @param array  $texts       Indexed array of source texts
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @param string $type        'text' or 'seo'
     * @return array  Same-indexed array of translated texts (or null on failure)
     */
    public function translate_batch( array $texts, $source_lang, $target_lang, $type = 'text' ) {
        if ( ! $this->api_key ) {
            throw new Exception( 'Gemini API key not configured' );
        }
        if ( empty( $texts ) ) {
            return [];
        }

        // Single item — no need for batch format, use direct API call
        if ( count( $texts ) === 1 ) {
            $response = $this->call_api(
                $this->build_system_instruction( $source_lang, $target_lang, $type ),
                reset( $texts )
            );
            return [ $this->extract_text( $response ) ];
        }

        // Build numbered input:
        // [1] Hello world
        // [2] Contact us
        // [3] About our company
        $numbered = [];
        $i = 1;
        foreach ( $texts as $text ) {
            $numbered[] = "[{$i}] {$text}";
            $i++;
        }
        $user_text = implode( "\n", $numbered );

        $system = $this->build_batch_instruction( $source_lang, $target_lang, $type, count( $texts ) );

        $response = $this->call_api( $system, $user_text );
        $raw_output = $this->extract_text( $response );

        // Parse numbered output back into array
        return $this->parse_batch_output( $raw_output, count( $texts ) );
    }

    /**
     * Build system instruction for batch translation.
     */
    
        private function build_batch_instruction( $source_lang, $target_lang, $type, $count ) {
            $src       = $this->get_lang_name( $source_lang );
            $tgt       = $this->get_lang_name( $target_lang );
            $site      = get_bloginfo( 'name' );
            $protected = implode( ', ', $this->protected_terms );

            if ( $type === 'seo_title' ) {
                $seo_rule = 'These are page TITLES only. Keep each ≤60 chars. Do NOT add descriptions. ';
            } elseif ( $type === 'seo' ) {
                $seo_rule = 'These are SEO meta descriptions. Keep each ≤160 chars. ';
            } else {
                $seo_rule = 'Tone: ' . get_option( 'gml_tone', 'professional and friendly' ) . '. ';
            }

            // Glossary rules — inject "Always translate X as Y" instructions
            $glossary = '';
            if ( class_exists( 'GML_Glossary' ) ) {
                $glossary = GML_Glossary::build_prompt_instruction( $target_lang );
            }

            return "Translate {$count} {$src} texts to {$tgt} for \"{$site}\". {$seo_rule}"
                 . "Keep these unchanged: {$protected}. "
                 . $glossary
                 . "Input/output: numbered [1]…[{$count}]. Return EXACTLY {$count} lines. "
                 . "Plain text only — no HTML, markdown, quotes, or explanations.";
        }


    /**
     * Parse batch output like "[1] Привет\n[2] Свяжитесь с нами" into array.
     *
     * @param string $output Raw Gemini output
     * @param int    $expected_count Expected number of segments
     * @return array Indexed array of translated texts
     * @throws Exception if parsing fails
     */
    private function parse_batch_output( $output, $expected_count ) {
        $results = [];

        // Match [N] followed by the translation text
        if ( preg_match_all( '/\[(\d+)\]\s*(.+)/m', $output, $matches, PREG_SET_ORDER ) ) {
            foreach ( $matches as $m ) {
                $idx = (int) $m[1];
                $text = trim( $m[2] );
                // Strip HTML tags safety net
                if ( strpos( $text, '<' ) !== false ) {
                    $text = wp_strip_all_tags( $text );
                    $text = trim( $text );
                }
                // Strip Markdown formatting
                $text = preg_replace( '/^\*{1,2}[^*]+:\*{1,2}\s*/', '', $text );
                $text = preg_replace( '/\*{1,2}([^*]+)\*{1,2}/', '$1', $text );
                $text = preg_replace( '/__([^_]+)__/', '$1', $text );
                $text = trim( $text );
                $results[ $idx ] = $text;
            }
        }

        // Verify we got all segments
        $parsed = [];
        for ( $i = 1; $i <= $expected_count; $i++ ) {
            if ( ! isset( $results[ $i ] ) || $results[ $i ] === '' ) {
                throw new Exception( "Batch translation missing segment [{$i}] — got " . count( $results ) . " of {$expected_count}" );
            }
            $parsed[] = $results[ $i ];
        }

        return $parsed;
    }

    // ── Prompt / system instruction builders ─────────────────────────────────

    private function get_lang_name( $code ) {
        $map = [
            'en' => 'English',     'zh' => 'Chinese',     'ja' => 'Japanese',
            'fr' => 'French',      'de' => 'German',      'es' => 'Spanish',
            'pt' => 'Portuguese',  'ru' => 'Russian',     'ko' => 'Korean',
            'ar' => 'Arabic',      'it' => 'Italian',     'nl' => 'Dutch',
            'pl' => 'Polish',      'tr' => 'Turkish',     'vi' => 'Vietnamese',
            'hi' => 'Hindi',       'th' => 'Thai',        'id' => 'Indonesian',
            'ms' => 'Malay',       'tl' => 'Filipino',    'sv' => 'Swedish',
            'da' => 'Danish',      'nb' => 'Norwegian',   'fi' => 'Finnish',
            'cs' => 'Czech',       'sk' => 'Slovak',      'hu' => 'Hungarian',
            'ro' => 'Romanian',    'bg' => 'Bulgarian',   'hr' => 'Croatian',
            'sr' => 'Serbian',     'sl' => 'Slovenian',   'uk' => 'Ukrainian',
            'el' => 'Greek',       'he' => 'Hebrew',      'lt' => 'Lithuanian',
            'lv' => 'Latvian',     'et' => 'Estonian',    'ca' => 'Catalan',
            'fa' => 'Persian',     'ur' => 'Urdu',        'bn' => 'Bengali',
            'ta' => 'Tamil',       'te' => 'Telugu',      'sw' => 'Swahili',
            'af' => 'Afrikaans',   'ka' => 'Georgian',    'hy' => 'Armenian',
            'az' => 'Azerbaijani', 'kk' => 'Kazakh',     'uz' => 'Uzbek',
            'mn' => 'Mongolian',   'km' => 'Khmer',      'my' => 'Myanmar (Burmese)',
            'lo' => 'Lao',         'ne' => 'Nepali',
        ];
        return $map[ $code ] ?? $code;
    }

    /**
     * Build a system instruction string.
     * Using systemInstruction keeps the translation directive separate from
     * the user content, which improves accuracy and reduces prompt injection.
     */
    private function build_system_instruction( $source_lang, $target_lang, $type = 'text' ) {
        $src       = $this->get_lang_name( $source_lang );
        $tgt       = $this->get_lang_name( $target_lang );
        $site      = get_bloginfo( 'name' );
        $protected = implode( ', ', $this->protected_terms );

        // Glossary rules
        $glossary = '';
        if ( class_exists( 'GML_Glossary' ) ) {
            $glossary = GML_Glossary::build_prompt_instruction( $target_lang );
        }

        if ( $type === 'seo_title' ) {
            return "You are an SEO expert translator for the website \"{$site}\". "
                 . "Translate the following {$src} page TITLE into natural, search-optimised {$tgt}. "
                 . "Keep it under 60 characters. This is a TITLE only — do NOT add a description. "
                 . "Do NOT translate these brand/product names: {$protected}. "
                 . $glossary
                 . "IMPORTANT: Return ONLY the translated title text. "
                 . "No HTML tags, no markdown, no quotes, no explanations, no 'Description:' prefix.";
        }

        if ( $type === 'seo' ) {
            return "You are an SEO expert translator for the website \"{$site}\". "
                 . "Translate the following {$src} SEO meta description into natural, search-optimised {$tgt}. "
                 . "Keep it under 160 characters. "
                 . "Do NOT translate these brand/product names: {$protected}. "
                 . $glossary
                 . "IMPORTANT: Return ONLY the translated plain text. "
                 . "Do NOT wrap the output in any HTML tags. No <h1>, <p>, <div> or any other tags. "
                 . "No explanations, no quotes, no markdown.";
        }

        $tone = get_option( 'gml_tone', 'professional and friendly' );
        return "You are a professional website translator for \"{$site}\". "
             . "Translate the following {$src} plain text into natural {$tgt}. "
             . "Tone: {$tone}. "
             . "The input is plain text extracted from a webpage — it contains NO HTML tags. "
             . "Do NOT translate these brand/product names: {$protected}. "
             . $glossary
             . "IMPORTANT: Return ONLY the translated plain text. "
             . "Do NOT add any HTML tags (no <h1>, <h2>, <p>, <div>, <span>, etc.). "
             . "Do NOT add markdown, quotes, or explanations. Just the translated text.";
    }

    // ── Core API call ─────────────────────────────────────────────────────────

    /**
     * Call the Gemini generateContent REST endpoint.
     *
     * Request format (official v1beta):
     * {
     *   "systemInstruction": { "parts": [{ "text": "..." }] },
     *   "contents": [{ "role": "user", "parts": [{ "text": "..." }] }],
     *   "generationConfig": { "temperature": 0.2, "maxOutputTokens": 2048 }
     * }
     */
    private function call_api( $system_instruction, $user_text, $retry = 0 ) {
        $url = self::API_BASE . '/models/' . $this->model . ':generateContent?key=' . $this->api_key;

        $body = [
            'systemInstruction' => [
                'parts' => [ [ 'text' => $system_instruction ] ],
            ],
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [ [ 'text' => $user_text ] ],
                ],
            ],
            'generationConfig' => [
                'temperature'     => 0.2,   // low = more deterministic translations
                'maxOutputTokens' => 4096,
            ],
        ];

        $response = wp_remote_post( $url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            if ( $retry < 2 ) {
                sleep( 1 );
                return $this->call_api( $system_instruction, $user_text, $retry + 1 );
            }
            throw new Exception( 'API request failed: ' . $response->get_error_message() );
        }

        $status = wp_remote_retrieve_response_code( $response );
        $raw    = wp_remote_retrieve_body( $response );

        if ( $status === 429 && $retry < 3 ) {
            // Rate limited — back off exponentially
            sleep( pow( 2, $retry + 1 ) );
            return $this->call_api( $system_instruction, $user_text, $retry + 1 );
        }

        if ( $status >= 500 && $retry < 2 ) {
            sleep( 2 );
            return $this->call_api( $system_instruction, $user_text, $retry + 1 );
        }

        if ( $status !== 200 ) {
            error_log( "GML Gemini API error (HTTP {$status}): {$raw}" );
            throw new Exception( "Gemini API returned HTTP {$status}" );
        }

        $data = json_decode( $raw, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new Exception( 'Invalid JSON from Gemini API' );
        }

        return $data;
    }

    // ── Response parsing ──────────────────────────────────────────────────────

    private function extract_text( $response ) {
        // Check for blocked prompt
        if ( isset( $response['promptFeedback']['blockReason'] ) ) {
            throw new Exception( 'Prompt blocked: ' . $response['promptFeedback']['blockReason'] );
        }

        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ( $text === null ) {
            error_log( 'GML: Unexpected Gemini response: ' . wp_json_encode( $response ) );
            throw new Exception( 'No text in Gemini API response' );
        }

        $text = trim( $text );

        // Safety net: strip any HTML tags Gemini may have added despite instructions.
        if ( strpos( $text, '<' ) !== false ) {
            $text = wp_strip_all_tags( $text );
            $text = trim( $text );
        }

        // Safety net: strip Markdown formatting Gemini may have added.
        // Common patterns: **bold**, *italic*, __bold__, _italic_,
        // **Title:** prefix, **Description:** prefix, etc.
        // Remove **Label:** prefixes (e.g. "**Title:** actual text")
        $text = preg_replace( '/^\*{1,2}[^*]+:\*{1,2}\s*/', '', $text );
        // Remove remaining bold/italic markers
        $text = preg_replace( '/\*{1,2}([^*]+)\*{1,2}/', '$1', $text );
        $text = preg_replace( '/__([^_]+)__/', '$1', $text );
        $text = trim( $text );

        return $text;
    }

    // ── API key test ──────────────────────────────────────────────────────────

    /**
     * Test an API key by sending a minimal translation request.
     *
     * @param string|null $api_key Key to test (uses stored key if null)
     * @return array { valid: bool, message: string }
     */
    public static function test_api_key( $api_key = null ) {
        try {
            if ( ! $api_key ) {
                $instance = new self();
                $api_key  = $instance->api_key;
            }
            if ( ! $api_key ) {
                return [ 'valid' => false, 'message' => __( 'No API key provided', 'gml-translate' ) ];
            }

            $url  = self::API_BASE . '/models/' . self::DEFAULT_MODEL . ':generateContent?key=' . $api_key;
            $body = [
                'systemInstruction' => [
                    'parts' => [ [ 'text' => 'You are a translator. Return only the translation.' ] ],
                ],
                'contents' => [
                    [ 'role' => 'user', 'parts' => [ [ 'text' => 'Translate "Hello" to Chinese.' ] ] ],
                ],
                'generationConfig' => [ 'maxOutputTokens' => 20 ],
            ];

            $resp   = wp_remote_post( $url, [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $body ),
                'timeout' => 15,
            ] );

            if ( is_wp_error( $resp ) ) {
                return [ 'valid' => false, 'message' => $resp->get_error_message() ];
            }

            $status = wp_remote_retrieve_response_code( $resp );
            $data   = json_decode( wp_remote_retrieve_body( $resp ), true );

            if ( $status === 200 && isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
                return [ 'valid' => true, 'message' => __( 'API key is valid!', 'gml-translate' ) ];
            }
            if ( $status === 400 ) {
                return [ 'valid' => false, 'message' => __( 'Invalid API key format', 'gml-translate' ) ];
            }
            if ( $status === 403 ) {
                return [ 'valid' => false, 'message' => __( 'API key invalid or lacks permission', 'gml-translate' ) ];
            }
            if ( $status === 429 ) {
                return [ 'valid' => false, 'message' => __( 'Rate limit exceeded — try again later', 'gml-translate' ) ];
            }
            return [ 'valid' => false, 'message' => sprintf( __( 'API error (HTTP %d)', 'gml-translate' ), $status ) ];

        } catch ( Exception $e ) {
            return [ 'valid' => false, 'message' => $e->getMessage() ];
        }
    }
}
