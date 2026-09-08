<?php
/** Apply the Core publication decision to standalone frontend routes. */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class GML_Publication_Gate {
    /** @var GML_Translation_Provider */
    private $provider;

    /** @var bool */
    private static $source_redirect = false;

    /** @var array|null */
    private static $preview_status = null;

    /** @var bool */
    private static $preview_banner_printed = false;

    public function __construct( GML_Translation_Provider $provider = null ) {
        $this->provider = $provider ?: new GML_Translation_Provider();

        add_action( 'template_redirect', [ $this, 'enforce' ], 0 );
        add_filter( 'gml_translation_resources_indexable', [ $this, 'filter_indexability_bulk' ], 10, 3 );
    }

    public static function is_source_redirect() {
        return self::$source_redirect;
    }

    public static function get_preview_status() {
        return self::$preview_status;
    }

    public function enforce() {
        if ( is_admin() || wp_doing_ajax() || ! GML_Translation_State::multilingual_enabled() ) return;

        $source = $this->provider->get_source_language();
        $current = $this->provider->get_current_language();
        if ( $current === '' || $current === $source ) return;

        $resource = class_exists( 'GML_Resource_Identity' )
            ? GML_Resource_Identity::current_public()
            : null;
        $status = $resource instanceof GML_Resource_Identity
            ? $this->provider->get_public_status( $resource, $current, [ 'entrypoint' => 'route' ] )
            : [];
        if ( ! empty( $status['public_eligible'] ) ) return;

        if ( current_user_can( $this->preview_capability() ) ) {
            self::$preview_status = $status ?: [
                'target_lang' => $current,
                'reason' => 'invalid_resource',
                'public_eligible' => false,
            ];
            $this->protect_preview();
            return;
        }

        $source_url = $this->source_url( $resource );
        if ( $source_url === '' ) return;

        self::$source_redirect = true;
        nocache_headers();
        if ( wp_safe_redirect( $source_url, 302, 'GML Translate' ) ) exit;
        self::$source_redirect = false;
    }

    /** Prime metadata once, then evaluate each resource without URL/language reads. */
    public function filter_indexability_bulk( $indexable, $resources, $context ) {
        if ( ! is_array( $indexable ) || ! is_array( $resources ) ) return $indexable;

        $listed = is_array( $context['source_listed_resource_keys'] ?? null )
            ? $context['source_listed_resource_keys']
            : [];
        $post_ids = [];
        $term_ids = [];
        foreach ( $resources as $key => $resource ) {
            if ( ! $resource instanceof GML_Resource_Identity ) continue;
            if ( empty( $indexable[ $key ] ) || ! empty( $listed[ $key ] ) ) continue;
            $id = $resource->get_object_id();
            if ( $id < 1 ) continue;
            if ( in_array( $resource->get_type(), [ 'post', 'role' ], true ) ) $post_ids[] = $id;
            if ( $resource->get_type() === 'term' ) $term_ids[] = $id;
        }
        $post_ids = array_values( array_unique( $post_ids ) );
        $term_ids = array_values( array_unique( $term_ids ) );
        if ( $post_ids ) {
            get_posts( [
                'post__in' => $post_ids,
                'post_type' => 'any',
                'post_status' => 'any',
                'posts_per_page' => count( $post_ids ),
                'orderby' => 'post__in',
                'no_found_rows' => true,
                'suppress_filters' => true,
            ] );
            update_meta_cache( 'post', $post_ids );
        }
        if ( $term_ids ) {
            get_terms( [
                'include' => $term_ids,
                'hide_empty' => false,
                'number' => 0,
                'suppress_filter' => true,
            ] );
            update_meta_cache( 'term', $term_ids );
        }

        foreach ( $resources as $key => $resource ) {
            if ( empty( $indexable[ $key ] ) || ! $resource instanceof GML_Resource_Identity ) continue;
            if ( ! empty( $listed[ $key ] ) ) continue;
            $indexable[ $key ] = $this->resource_is_indexable( $resource, (array) $context );
        }
        return $indexable;
    }

    private function resource_is_indexable( GML_Resource_Identity $resource, array $context ) {
        $type = $resource->get_type();
        $id = $resource->get_object_id();
        $indexable = true;

        if ( in_array( $type, [ 'post', 'role' ], true ) && $id > 0 ) {
            $post = get_post( $id );
            $indexable = $post instanceof WP_Post
                && $post->post_status === 'publish'
                && $post->post_password === '';
            if ( $indexable ) $indexable = ! $this->metadata_is_noindex( 'post', $id );
        } elseif ( $type === 'term' && $id > 0 ) {
            $term = get_term( $id, $resource->get_taxonomy() );
            $indexable = $term instanceof WP_Term && ! is_wp_error( $term );
            if ( $indexable ) $indexable = ! $this->metadata_is_noindex( 'term', $id );
        } elseif ( ! in_array( $type, [ 'archive', 'role' ], true ) ) {
            $indexable = false;
        }

        return $indexable;
    }

    private function metadata_is_noindex( $type, $id ) {
        $read = static function( $key ) use ( $type, $id ) {
            return get_metadata( $type, $id, $key, true );
        };
        if ( $read( '_seopress_robots_index' ) === 'yes' ) return true;
        if ( (string) $read( '_yoast_wpseo_meta-robots-noindex' ) === '1' ) return true;

        $rank_math = $read( 'rank_math_robots' );
        $rank_math = is_array( $rank_math ) ? $rank_math : maybe_unserialize( $rank_math );
        if ( is_array( $rank_math ) && in_array( 'noindex', $rank_math, true ) ) return true;
        return is_string( $rank_math ) && preg_match( '/(^|[,\s])noindex([,\s]|$)/i', $rank_math );
    }

    private function protect_preview() {
        nocache_headers();
        if ( ! headers_sent() ) header( 'X-Robots-Tag: noindex, nofollow', true );
        add_filter( 'wp_robots', [ $this, 'force_wp_robots' ], PHP_INT_MAX );
        add_filter( 'wpseo_robots', [ $this, 'force_robots_string' ], PHP_INT_MAX );
        add_filter( 'rank_math/frontend/robots', [ $this, 'force_rank_math_robots' ], PHP_INT_MAX );
        add_filter( 'seopress_titles_robots_attrs', [ $this, 'force_seopress_robots' ], PHP_INT_MAX );
        add_action( 'wp_body_open', [ $this, 'render_preview_banner' ], 0 );
        add_action( 'wp_footer', [ $this, 'render_preview_banner' ], PHP_INT_MAX );
    }

    public function force_wp_robots( $robots ) {
        $robots = is_array( $robots ) ? $robots : [];
        unset( $robots['index'], $robots['follow'] );
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        return $robots;
    }

    public function force_robots_string() {
        return 'noindex, nofollow';
    }

    public function force_rank_math_robots( $robots ) {
        if ( ! is_array( $robots ) ) return [ 'noindex', 'nofollow' ];
        $robots = array_values( array_diff( $robots, [ 'index', 'follow' ] ) );
        $robots[] = 'noindex';
        $robots[] = 'nofollow';
        return array_values( array_unique( $robots ) );
    }

    public function force_seopress_robots() {
        return [ 'noindex', 'nofollow' ];
    }

    public function render_preview_banner() {
        if ( self::$preview_banner_printed || ! self::$preview_status ) return;
        self::$preview_banner_printed = true;
        $reason = sanitize_key( self::$preview_status['reason'] ?? 'unavailable' );
        echo '<aside class="gml-translation-preview-notice" role="status" style="padding:12px 20px;background:#fff3cd;border-bottom:2px solid #dba617;color:#3c2f00;font:600 14px/1.5 system-ui,sans-serif;text-align:center;">';
        echo esc_html__( 'GML Translate preview: this translation is not public and is forced to noindex.', 'gml-translate' );
        echo ' <code>' . esc_html( $reason ) . '</code></aside>';
    }

    private function source_url( $resource ) {
        if ( $resource instanceof GML_Resource_Identity && $resource->get_source_url() !== '' ) {
            return $resource->get_source_url();
        }
        $request = wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' );
        $path = wp_parse_url( $request, PHP_URL_PATH ) ?: '/';
        if ( class_exists( 'GML_URL_Helper' ) ) $path = GML_URL_Helper::strip_home_path( $path );
        $url = home_url( $path );
        return (string) $this->provider->get_translated_url( $url, $this->provider->get_source_language() );
    }

    private function preview_capability() {
        return (string) apply_filters( 'gml_translation_preview_capability', 'manage_options' );
    }
}
