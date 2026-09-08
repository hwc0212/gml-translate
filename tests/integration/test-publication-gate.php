<?php
/** Publication gate behavior for anonymous visitors and authorized reviewers. */

$GLOBALS['gml_test_can_preview'] = false;
$GLOBALS['gml_test_redirect'] = null;

function wp_doing_ajax() { return false; }
function current_user_can( $capability ) {
    return $capability === 'manage_options' && ! empty( $GLOBALS['gml_test_can_preview'] );
}
function nocache_headers() {}
function wp_safe_redirect( $url, $status = 302, $by = '' ) {
    $GLOBALS['gml_test_redirect'] = [ 'url' => $url, 'status' => $status, 'by' => $by ];
    return false;
}
function esc_html__( $text, $domain = 'default' ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }

require_once __DIR__ . '/../bootstrap-mock.php';

final class GML_Translation_State {
    public static function multilingual_enabled() { return true; }
}

final class GML_Resource_Identity {
    public static function current_public() { return new self(); }
    public function get_source_url() { return 'https://example.com/about/'; }
}

class GML_Translation_Provider {
    public static $eligible = false;
    public static $reason = 'unreviewed';

    public function get_source_language() { return 'en'; }
    public function get_current_language() { return 'es'; }
    public function get_public_status( $resource, $lang, array $context = [] ) {
        return [
            'target_lang' => $lang,
            'reason' => self::$reason,
            'public_eligible' => self::$eligible,
            'url' => self::$eligible ? 'https://example.com/es/about/' : '',
        ];
    }
    public function get_translated_url( $url, $lang ) { return 'https://example.com/about/'; }
}

require_once __DIR__ . '/../../includes/class-publication-gate.php';

$_SERVER['REQUEST_URI'] = '/es/about/';
$gate = new GML_Publication_Gate( new GML_Translation_Provider() );
$gate->enforce();
gml_test_assert( $GLOBALS['gml_test_redirect']['url'] === 'https://example.com/about/', 'anonymous ineligible route redirects to the source resource' );
gml_test_assert( $GLOBALS['gml_test_redirect']['status'] === 302, 'anonymous publication redirect is temporary' );
gml_test_assert( $GLOBALS['gml_test_redirect']['by'] === 'GML Translate', 'publication redirect identifies its owner' );
gml_test_assert( ! GML_Publication_Gate::is_source_redirect(), 'source redirect guard is reset if WordPress declines the redirect' );

$GLOBALS['gml_test_can_preview'] = true;
$GLOBALS['gml_test_redirect'] = null;
GML_Translation_Provider::$reason = 'stale';
$review_gate = new GML_Publication_Gate( new GML_Translation_Provider() );
$review_gate->enforce();
$preview = GML_Publication_Gate::get_preview_status();
gml_test_assert( $GLOBALS['gml_test_redirect'] === null, 'authorized reviewer preview is not redirected' );
gml_test_assert( ( $preview['reason'] ?? '' ) === 'stale', 'reviewer preview exposes the derived ineligibility reason' );
gml_test_assert( isset( GML_Translate_Test_State::$actions['wp_robots'] ), 'reviewer preview registers WordPress noindex protection' );
gml_test_assert( isset( GML_Translate_Test_State::$actions['seopress_titles_robots_attrs'] ), 'reviewer preview registers SEOPress noindex protection' );
gml_test_assert( $review_gate->force_seopress_robots() === [ 'noindex', 'nofollow' ], 'SEOPress preview robots use the expected array contract' );
ob_start();
$review_gate->render_preview_banner();
$banner = ob_get_clean();
gml_test_assert( strpos( $banner, 'not public' ) !== false && strpos( $banner, '<code>stale</code>' ) !== false, 'reviewer preview is visibly marked with its status' );

GML_Translation_Provider::$eligible = true;
$GLOBALS['gml_test_can_preview'] = false;
$GLOBALS['gml_test_redirect'] = null;
$eligible_gate = new GML_Publication_Gate( new GML_Translation_Provider() );
$eligible_gate->enforce();
gml_test_assert( $GLOBALS['gml_test_redirect'] === null, 'eligible translated route remains public' );

echo "OK test-publication-gate\n";
