<?php
/** Multilingual sitemap adapter backed by derived publication eligibility. */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GML_Sitemap {
    /** @var GML_Translation_Provider */
    private $provider;

    /** @var GML_Sitemap_Publication_Transformer */
    private $transformer;

    /** @var bool */
    private $seo_plugin_detected = false;

    public function __construct( GML_Translation_Provider $provider = null ) {
        $this->provider = $provider ?: new GML_Translation_Provider();

        if ( defined( 'GML_SEO_VER' ) || ! $this->has_local_targets() ) return;

        $this->transformer = new GML_Sitemap_Publication_Transformer( $this->provider );
        $this->detect_seo_plugin();

        // SEOPress exposes final XML for post and taxonomy sitemaps. Transforming
        // once at the end permits bounded resource/status reads and independent
        // URL entries for each approved language.
        add_filter( 'seopress_sitemaps_xml_single', [ $this, 'transform_seopress_xml' ], PHP_INT_MAX );
        add_filter( 'seopress_sitemaps_xml_single_term', [ $this, 'transform_seopress_xml' ], PHP_INT_MAX );

        // Yoast exposes one final URL fragment at a time. One resource lookup is
        // still required per callback, but never one query per target language.
        add_filter( 'wpseo_sitemap_urlset', [ $this, 'yoast_add_xmlns' ], 1 );
        add_filter( 'wpseo_sitemap_url', [ $this, 'yoast_expand_url' ], PHP_INT_MAX, 2 );

        add_action( 'init', [ $this, 'register_rank_math_filters' ], 99 );
        // WordPress core cannot serialize xhtml:link data. When no supported SEO
        // authority exists, the GML sitemap replaces it so only one sitemap
        // system advertises multilingual resources.
        add_filter( 'wp_sitemaps_enabled', [ $this, 'filter_core_sitemaps_enabled' ], PHP_INT_MAX );
        add_action( 'init', [ $this, 'add_rewrite_rules' ] );
        add_action( 'template_redirect', [ $this, 'serve_sitemap' ], 5 );
        add_filter( 'robots_txt', [ $this, 'add_to_robots_txt' ], 10, 2 );
    }

    public function register_rank_math_filters() {
        $types = array_merge(
            array_values( get_post_types( [ 'public' => true ], 'names' ) ),
            array_values( get_taxonomies( [ 'public' => true ], 'names' ) )
        );
        foreach ( array_unique( $types ) as $type ) {
            $type = sanitize_key( $type );
            add_filter( 'rank_math/sitemap/' . $type . '_urlset', [ $this, 'yoast_add_xmlns' ], PHP_INT_MAX );
            add_filter( 'rank_math/sitemap/' . $type . '_sitemap_url', [ $this, 'rank_math_expand_url' ], PHP_INT_MAX, 2 );
        }
    }

    public function detect_seo_plugin() {
        $this->seo_plugin_detected = defined( 'SEOPRESS_VERSION' )
            || defined( 'WPSEO_VERSION' )
            || defined( 'RANK_MATH_VERSION' );
        if ( $this->seo_plugin_detected ) return;

        if ( ! function_exists( 'is_plugin_active' ) ) include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $this->seo_plugin_detected = is_plugin_active( 'wp-seopress/seopress.php' )
            || is_plugin_active( 'wp-seopress-pro/seopress-pro.php' )
            || is_plugin_active( 'wordpress-seo/wp-seo.php' )
            || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' )
            || is_plugin_active( 'seo-by-rank-math/rank-math.php' )
            || is_plugin_active( 'seo-by-rank-math-pro/rank-math-pro.php' )
            || is_plugin_active( 'gml-seo/gml-seo.php' );
    }

    public function filter_core_sitemaps_enabled( $enabled ) {
        return $this->seo_plugin_detected ? $enabled : false;
    }

    public function transform_seopress_xml( $xml ) {
        return $this->transformer->transform( $xml, [
            'entrypoint' => 'seopress_sitemap',
            'source_sitemap_authority' => true,
        ] );
    }

    public function yoast_add_xmlns( $urlset ) {
        if ( strpos( $urlset, 'xmlns:xhtml' ) === false ) {
            $urlset = preg_replace( '/>$/', ' xmlns:xhtml="http://www.w3.org/1999/xhtml">', $urlset, 1 );
        }
        return $urlset;
    }

    public function yoast_expand_url( $output, $url ) {
        $loc = is_array( $url ) ? ( $url['loc'] ?? '' ) : '';
        if ( $loc === '' ) return $output;
        return $this->transformer->transform_fragment( $output, $loc, [
            'entrypoint' => 'yoast_sitemap',
            'source_sitemap_authority' => true,
        ] );
    }

    public function rank_math_expand_url( $entry, $generator ) {
        if ( is_array( $entry ) ) {
            if ( empty( $entry['loc'] ) || ! is_object( $generator ) || ! is_callable( [ $generator, 'sitemap_url' ] ) ) return $entry;
            $loc = $entry['loc'];
            $output = $generator->sitemap_url( $entry );
        } elseif ( is_string( $entry ) && preg_match( '/<loc>(.*?)<\/loc>/s', $entry, $match ) ) {
            $loc = html_entity_decode( trim( $match[1] ), ENT_QUOTES | ENT_XML1, 'UTF-8' );
            $output = $entry;
        } else {
            return $entry;
        }
        if ( ! is_string( $output ) || $output === '' ) return $entry;
        return $this->transformer->transform_fragment( $output, $loc, [
            'entrypoint' => 'rank_math_sitemap',
            'source_sitemap_authority' => true,
        ] );
    }

    /** Retained as a no-op for compatibility with integrations using this method. */
    public function wp_core_add_hreflang( $entry, $post_or_term, $type = '' ) {
        unset( $post_or_term, $type );
        return $entry;
    }

    public function add_rewrite_rules() {
        add_rewrite_rule( '^gml-sitemap\.xml$', 'index.php?gml_sitemap=1', 'top' );
        add_rewrite_rule( '^gml-sitemap-([a-z0-9_-]+)\.xml$', 'index.php?gml_sitemap=1&gml_sitemap_type=$matches[1]', 'top' );
        add_filter( 'query_vars', static function( $vars ) {
            $vars[] = 'gml_sitemap';
            $vars[] = 'gml_sitemap_type';
            return $vars;
        } );
    }

    public function serve_sitemap() {
        if ( ! get_query_var( 'gml_sitemap' ) || $this->seo_plugin_detected ) return;

        $type = sanitize_key( get_query_var( 'gml_sitemap_type', '' ) );
        header( 'Content-Type: application/xml; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex' );
        echo $type === '' ? $this->generate_sitemap_index() : $this->generate_sitemap( $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    public function add_to_robots_txt( $output, $public ) {
        if ( $public && ! $this->seo_plugin_detected ) $output .= "\nSitemap: " . home_url( '/gml-sitemap.xml' ) . "\n";
        return $output;
    }

    private function generate_sitemap_index() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ( get_post_types( [ 'public' => true ], 'names' ) as $post_type ) {
            if ( $post_type === 'attachment' || (int) wp_count_posts( $post_type )->publish < 1 ) continue;
            $xml .= $this->sitemap_index_entry( $post_type );
        }
        foreach ( get_taxonomies( [ 'public' => true ], 'names' ) as $taxonomy ) {
            if ( $taxonomy === 'post_format' ) continue;
            $term_count = wp_count_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => true ] );
            if ( is_wp_error( $term_count ) || (int) $term_count < 1 ) continue;
            $xml .= $this->sitemap_index_entry( 'tax-' . $taxonomy );
        }
        return $xml . '</sitemapindex>';
    }

    private function sitemap_index_entry( $type ) {
        return "  <sitemap>\n    <loc>" . esc_url( home_url( '/gml-sitemap-' . $type . '.xml' ) ) . "</loc>\n    <lastmod>" . esc_html( gmdate( 'c' ) ) . "</lastmod>\n  </sitemap>\n";
    }

    private function generate_sitemap( $type ) {
        $entries = strpos( $type, 'tax-' ) === 0
            ? $this->taxonomy_entries( substr( $type, 4 ) )
            : $this->post_entries( $type );
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ( $entries as $entry ) {
            $xml .= "  <url>\n    <loc>" . esc_url( $entry['loc'] ) . "</loc>\n";
            if ( ! empty( $entry['lastmod'] ) ) $xml .= '    <lastmod>' . esc_html( $entry['lastmod'] ) . "</lastmod>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';
        return $this->transformer->transform( $xml, [ 'entrypoint' => 'gml_sitemap' ] );
    }

    private function post_entries( $post_type ) {
        $valid = get_post_types( [ 'public' => true ], 'names' );
        if ( ! isset( $valid[ $post_type ] ) || $post_type === 'attachment' ) return [];
        $entries = [];
        $posts = get_posts( [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 1000,
            'orderby' => 'modified',
            'order' => 'DESC',
        ] );
        foreach ( $posts as $post ) {
            $entries[] = [
                'loc' => get_permalink( $post ),
                'lastmod' => get_post_modified_time( 'c', true, $post ),
            ];
        }
        return $entries;
    }

    private function taxonomy_entries( $taxonomy ) {
        $object = get_taxonomy( $taxonomy );
        if ( ! $object || empty( $object->public ) ) return [];
        $entries = [];
        $terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => true, 'number' => 1000 ] );
        if ( is_wp_error( $terms ) ) return [];
        foreach ( $terms as $term ) {
            $url = get_term_link( $term );
            if ( ! is_wp_error( $url ) ) $entries[] = [ 'loc' => $url, 'lastmod' => '' ];
        }
        return $entries;
    }

    private function has_local_targets() {
        return class_exists( 'GML_Language_Utils' ) && (bool) GML_Language_Utils::enabled_local_target_codes();
    }
}
