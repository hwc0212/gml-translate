<?php
/**
 * GML Sitemap — Multilingual hreflang injection for SEO plugin sitemaps
 *
 * Instead of generating a standalone sitemap, this class hooks into popular
 * SEO plugins (SEOPress, Yoast SEO, Rank Math, The SEO Framework) and
 * WordPress core sitemaps to inject xhtml:link hreflang annotations for
 * every language version of each URL.
 *
 * If no SEO plugin is detected, falls back to a standalone /gml-sitemap.xml.
 *
 * @package GML_Translate
 * @since 2.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GML_Sitemap {

    /** @var GML_Translation_Provider_Interface */
    private $provider;

    /** @var string Source language code */
    private $source_lang;

    /** @var array Enabled target languages */
    private $languages = [];

    /** @var bool Whether an SEO plugin sitemap was detected */
    private $seo_plugin_detected = false;

    public function __construct( GML_Translation_Provider_Interface $provider = null ) {
        $this->provider    = $provider ?: new GML_Translation_Provider();
        $this->source_lang = $this->provider->get_source_language();
        $this->languages   = $this->get_enabled_languages();

		// GML SEO reads alternate URLs from TranslationProvider and owns the
		// final sitemap. Do not register competing sitemap hooks in dual mode.
		if ( defined( 'GML_SEO_VER' ) ) {
			return;
		}

        if ( empty( $this->languages ) ) {
            return;
        }

        // ── SEOPress ──────────────────────────────────────────────────────────
        // Inject xmlns:xhtml into <urlset> and append hreflang to each <url>
        add_filter( 'seopress_sitemaps_urlset', [ $this, 'seopress_add_xmlns' ] );
        add_filter( 'seopress_sitemaps_url',    [ $this, 'seopress_inject_hreflang' ], 10, 2 );

        // ── Yoast SEO ─────────────────────────────────────────────────────────
        add_filter( 'wpseo_sitemap_urlset', [ $this, 'yoast_add_xmlns' ], 1 );
        add_filter( 'wpseo_sitemap_url', [ $this, 'yoast_inject_hreflang' ], 10, 2 );

        // ── Rank Math ─────────────────────────────────────────────────────────
        // Rank Math's entry filter passes an array, not XML.
        // We use the content filter to inject hreflang into the final XML.
        add_filter( 'rank_math/sitemap/post/content',    [ $this, 'rankmath_inject_content' ], 10 );
        add_filter( 'rank_math/sitemap/page/content',    [ $this, 'rankmath_inject_content' ], 10 );
        add_filter( 'rank_math/sitemap/product/content', [ $this, 'rankmath_inject_content' ], 10 );

        // ── WordPress Core Sitemaps (5.5+) ────────────────────────────────────
        add_filter( 'wp_sitemaps_posts_entry',      [ $this, 'wp_core_add_hreflang' ], 10, 3 );
        add_filter( 'wp_sitemaps_taxonomies_entry',  [ $this, 'wp_core_add_hreflang' ], 10, 3 );

        // ── Detect SEO plugin on init ─────────────────────────────────────────
        add_action( 'wp_loaded', [ $this, 'detect_seo_plugin' ] );

        // ── Fallback: standalone sitemap if no SEO plugin ─────────────────────
        add_action( 'init',              [ $this, 'add_rewrite_rules' ] );
        add_action( 'template_redirect', [ $this, 'serve_sitemap' ] );
        add_filter( 'robots_txt',        [ $this, 'add_to_robots_txt' ], 10, 2 );
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  SEO Plugin Detection
    // ══════════════════════════════════════════════════════════════════════════

    public function detect_seo_plugin() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->seo_plugin_detected = (
            is_plugin_active( 'wp-seopress/seopress.php' ) ||
            is_plugin_active( 'wp-seopress-pro/seopress-pro.php' ) ||
            is_plugin_active( 'wordpress-seo/wp-seo.php' ) ||
            is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ||
            is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ||
            is_plugin_active( 'seo-by-rank-math-pro/rank-math-pro.php' ) ||
            is_plugin_active( 'autodescription/autodescription.php' ) ||
            is_plugin_active( 'gml-seo/gml-seo.php' )
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  SEOPress Integration
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Add xmlns:xhtml namespace to SEOPress <urlset> tag.
     */
    public function seopress_add_xmlns( $urlset ) {
        if ( strpos( $urlset, 'xmlns:xhtml' ) === false ) {
            $urlset = str_replace(
                'xmlns:image=',
                'xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image=',
                $urlset
            );
            // If no image namespace, add before closing >
            if ( strpos( $urlset, 'xmlns:xhtml' ) === false ) {
                $urlset = preg_replace( '/>$/', ' xmlns:xhtml="http://www.w3.org/1999/xhtml">', $urlset );
            }
        }
        return $urlset;
    }

    /**
     * Inject hreflang <xhtml:link> into each SEOPress <url> block.
     *
     * @param string $sitemap_data  The XML string for this <url> entry.
     * @param array  $seopress_url  Array with 'loc', 'mod', 'images'.
     */
    public function seopress_inject_hreflang( $sitemap_data, $seopress_url ) {
        if ( empty( $seopress_url['loc'] ) ) {
            return $sitemap_data;
        }

        $hreflang_xml = $this->build_hreflang_xml( $seopress_url['loc'] );
        if ( empty( $hreflang_xml ) ) {
            return $sitemap_data;
        }

        // Insert hreflang links before </url>
        $sitemap_data = str_replace( '</url>', $hreflang_xml . '</url>', $sitemap_data );
        return $sitemap_data;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Yoast SEO Integration
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Add xmlns:xhtml namespace to Yoast SEO <urlset> tag.
     * @see https://developer.yoast.com/features/xml-sitemaps/api/#filter-the-urlset-element
     */
    public function yoast_add_xmlns( $urlset ) {
        if ( strpos( $urlset, 'xmlns:xhtml' ) === false ) {
            $urlset = str_replace( '>', ' xmlns:xhtml="http://www.w3.org/1999/xhtml">', $urlset );
        }
        return $urlset;
    }

    /**
     * Inject hreflang into Yoast SEO sitemap <url> output.
     *
     * @param string $output  The XML string for this <url> entry.
     * @param array  $url     Array with 'loc', 'mod', 'images', etc.
     */
    public function yoast_inject_hreflang( $output, $url ) {
        $loc = $url['loc'] ?? '';
        if ( empty( $loc ) ) {
            return $output;
        }

        $hreflang_xml = $this->build_hreflang_xml( $loc );
        if ( empty( $hreflang_xml ) ) {
            return $output;
        }

        $output = str_replace( '</url>', $hreflang_xml . '</url>', $output );
        return $output;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Rank Math Integration
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Inject hreflang XML into Rank Math sitemap content output.
     * Rank Math's {$type}_content action fires with the full XML string.
     * We use output buffering to modify the XML before it's sent.
     *
     * Since Rank Math doesn't provide a per-URL XML filter like Yoast/SEOPress,
     * we post-process the complete sitemap XML to inject hreflang links.
     *
     * @param string $content The sitemap XML content.
     * @return string Modified XML with hreflang links.
     */
    public function rankmath_inject_content( $content ) {
        if ( empty( $content ) || strpos( $content, '<urlset' ) === false ) {
            return $content;
        }

        // Add xmlns:xhtml namespace if missing
        if ( strpos( $content, 'xmlns:xhtml' ) === false ) {
            $content = preg_replace(
                '/(<urlset\s[^>]*)>/',
                '$1 xmlns:xhtml="http://www.w3.org/1999/xhtml">',
                $content,
                1
            );
        }

        // Inject hreflang into each <url> block
        if ( preg_match_all( '#<url>\s*<loc>([^<]+)</loc>#', $content, $matches, PREG_SET_ORDER ) ) {
            foreach ( $matches as $m ) {
                $loc = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
                $hreflang_xml = $this->build_hreflang_xml( $loc );
                if ( ! empty( $hreflang_xml ) ) {
                    $content = str_replace(
                        '<loc>' . $m[1] . '</loc>',
                        '<loc>' . $m[1] . '</loc>' . $hreflang_xml,
                        $content
                    );
                }
            }
        }

        return $content;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  WordPress Core Sitemaps (5.5+)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Add language URLs to WordPress core sitemap entries.
     * Core sitemaps don't support xhtml:link natively, but we can add
     * extra entries for each language URL.
     */
    public function wp_core_add_hreflang( $entry, $post_or_term, $post_type_or_taxonomy = '' ) {
        // WordPress core sitemaps don't support xhtml:link injection.
        // The best we can do is ensure language URLs are discoverable
        // via the standalone GML sitemap or hreflang in <head>.
        return $entry;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Shared: Build hreflang XML
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Build xhtml:link hreflang XML for a given source URL.
     *
     * @param string $loc  The original (source language) URL.
     * @return string      XML fragment with hreflang links, or empty string.
     */
    private function build_hreflang_xml( $loc ) {
        $loc = html_entity_decode( $loc, ENT_QUOTES, 'UTF-8' );
        $xml  = "\n";

        foreach ( $this->provider->get_alternate_urls( $loc ) as $hreflang => $url ) {
            $xml .= sprintf(
                '<xhtml:link rel="alternate" hreflang="%s" href="%s" />' . "\n",
                esc_attr( $hreflang ),
                esc_url( $url )
            );
        }

        return $xml;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Fallback: Standalone Sitemap (when no SEO plugin is active)
    // ══════════════════════════════════════════════════════════════════════════

    public function add_rewrite_rules() {
        add_rewrite_rule( '^gml-sitemap\.xml$', 'index.php?gml_sitemap=1', 'top' );
        add_rewrite_rule( '^gml-sitemap-([a-z_]+)\.xml$', 'index.php?gml_sitemap=1&gml_sitemap_type=$matches[1]', 'top' );
        add_filter( 'query_vars', function( $vars ) {
            $vars[] = 'gml_sitemap';
            $vars[] = 'gml_sitemap_type';
            return $vars;
        } );
    }

    public function serve_sitemap() {
        if ( ! get_query_var( 'gml_sitemap' ) ) {
            return;
        }

        // If an SEO plugin is handling sitemaps, don't serve standalone
        if ( $this->seo_plugin_detected ) {
            return;
        }

        $type = get_query_var( 'gml_sitemap_type', '' );

        header( 'Content-Type: application/xml; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex' );

        if ( empty( $type ) ) {
            echo $this->generate_sitemap_index();
        } else {
            echo $this->generate_sitemap( $type );
        }
        exit;
    }

    /**
     * Only add to robots.txt if no SEO plugin is active.
     */
    public function add_to_robots_txt( $output, $public ) {
        if ( $public && ! $this->seo_plugin_detected ) {
            $output .= "\nSitemap: " . home_url( '/gml-sitemap.xml' ) . "\n";
        }
        return $output;
    }

    // ── Standalone sitemap generation (fallback) ──────────────────────────────

    private function generate_sitemap_index() {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $post_types = get_post_types( [ 'public' => true ], 'names' );
        unset( $post_types['attachment'] );

        foreach ( $post_types as $pt ) {
            $count = (int) wp_count_posts( $pt )->publish;
            if ( $count > 0 ) {
                $xml .= '  <sitemap>' . "\n";
                $xml .= '    <loc>' . esc_url( home_url( '/gml-sitemap-' . $pt . '.xml' ) ) . '</loc>' . "\n";
                $xml .= '    <lastmod>' . date( 'c' ) . '</lastmod>' . "\n";
                $xml .= '  </sitemap>' . "\n";
            }
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    private function generate_sitemap( $post_type ) {
        $valid_types = get_post_types( [ 'public' => true ], 'names' );
        unset( $valid_types['attachment'] );
        if ( ! isset( $valid_types[ $post_type ] ) ) {
            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        $posts = get_posts( [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ] );

        foreach ( $posts as $post ) {
            $permalink = get_permalink( $post );
            $modified  = get_post_modified_time( 'c', true, $post );

            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . esc_url( $permalink ) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $modified . '</lastmod>' . "\n";

            foreach ( $this->provider->get_alternate_urls( $permalink ) as $hreflang => $url ) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . esc_attr( $hreflang ) . '" href="' . esc_url( $url ) . '" />' . "\n";
            }

            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    private function get_enabled_languages() {
        $langs = [];
        foreach ( $this->provider->get_languages() as $code ) {
            if ( $code !== $this->source_lang && $this->provider->get_translation_status( 0, $code ) === 'complete' ) {
                $langs[] = [ 'code' => $code, 'enabled' => true ];
            }
        }
        return $langs;
    }
}
