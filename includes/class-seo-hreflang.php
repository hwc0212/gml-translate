<?php
/**
 * Standalone multilingual SEO output backed by Translation Core data.
 *
 * This adapter owns only the minimum SEO required by a multilingual site.
 * Full SEO settings remain the responsibility of a dedicated SEO plugin.
 *
 * @package GML_Translate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GML_SEO_Hreflang {

    /** @var GML_Translation_Provider_Interface */
    private $provider;

    /** @var bool */
    private $external_seo_authority;

    /** Explicit noindex observed from the current request's SEO owner. */
    private $request_noindex = false;

    public function __construct( GML_Translation_Provider_Interface $provider = null ) {
        $this->provider               = $provider ?: new GML_Translation_Provider();
        $this->external_seo_authority = $this->has_external_seo_authority();

		// GML SEO consumes this plugin's read-only TranslationProvider data and
		// remains the sole owner of canonical, hreflang, OG locale, and sitemap.
		if ( defined( 'GML_SEO_VER' ) ) {
			return;
		}

        // Observe robots as produced by the SEO owner, then render alternates.
        // Never call that owner's rendering functions twice or change its policy.
        foreach ( [ 'wp_robots', 'wpseo_robots', 'wpseo_robots_array', 'rank_math/frontend/robots', 'seopress_titles_robots' ] as $hook ) {
            add_filter( $hook, [ $this, 'observe_robots' ], PHP_INT_MAX );
        }
        add_action( 'wp_head', [ $this, 'inject_multilingual_meta' ], PHP_INT_MAX );
        add_filter( 'language_attributes', [ $this->provider, 'filter_language_attributes' ], 10, 2 );

        // Keep canonical URLs supplied by common SEO plugins self-referencing
        // on translated routes without adding a second canonical tag.
        add_filter( 'get_canonical_url', [ $this, 'filter_canonical_url' ], 10, 2 );
        add_filter( 'wpseo_canonical', [ $this, 'filter_canonical_url' ], 10, 1 );
        add_filter( 'rank_math/frontend/canonical', [ $this, 'filter_canonical_url' ], 10, 1 );
        add_filter( 'seopress_titles_canonical', [ $this, 'filter_seopress_canonical' ], 10, 1 );
        add_filter('seopress_social_og_locale',[$this,'filter_seopress_locale']);

        if ( ! $this->external_seo_authority ) {
            remove_action( 'wp_head', 'rel_canonical' );
        }
    }

    public function inject_multilingual_meta() {
        if ( is_admin() || $this->is_not_found_request() ) {
            return;
        }

        $canonical = $this->current_canonical_url();
        $alternates = GML_SEO_Router::is_paginated_archive()
            ? $this->pagination_alternates()
            : $this->provider->get_alternate_urls( $canonical );
        foreach ( $alternates as $hreflang => $url ) {
            echo '<link rel="alternate" hreflang="' . esc_attr( $hreflang ) . '" href="' . esc_url( $url ) . '" />' . "\n";
        }

        if ( ! $this->external_seo_authority ) {
            $current = $this->provider->get_current_language();
            echo '<meta property="og:locale" content="' . esc_attr( $this->provider->get_og_locale( $current ) ) . '" />' . "\n";
            $seen = [];
            foreach ( $alternates as $hreflang => $url ) {
                unset( $url );
                if ( $hreflang === 'x-default' || $hreflang === $this->provider->get_hreflang_code( $current ) ) continue;
                $locale = $this->provider->get_og_locale( $hreflang );
                if ( ! isset( $seen[ $locale ] ) ) {
                    echo '<meta property="og:locale:alternate" content="' . esc_attr( $locale ) . '" />' . "\n";
                    $seen[ $locale ] = true;
                }
            }
        }

        if ( ! $this->external_seo_authority && $canonical ) {
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
        }
    }

    public function filter_canonical_url( $canonical_url, $post = null ) {
        unset( $post );
        // SEO owners use false/empty to deliberately suppress the canonical.
        if ( ! is_string($canonical_url) || trim($canonical_url) === '' ) return $canonical_url;
        if ( $this->is_not_found_request() ) {
            return $canonical_url;
        }
        $source = $this->current_source_url() ?: $canonical_url;
        $current = $this->provider->get_current_language();
        if ( $current === $this->provider->get_source_language() ) return $source;

        $resource = class_exists( 'GML_Resource_Identity' ) ? GML_Resource_Identity::current_public() : null;
        $status = $resource instanceof GML_Resource_Identity
            ? $this->provider->get_public_status( $resource, $current, [ 'entrypoint' => 'canonical' ] )
            : [];
        // Progressive 200 pages keep their own identity even while noindex.
        return ! empty( $status['route_valid'] ) && ! empty( $status['url'] )
            ? $this->provider->get_translated_url( $source, $current ) : $source;
    }

    public function filter_seopress_canonical( $canonical_tag ) {
        if ( trim( (string) $canonical_tag ) === '' ) return $canonical_tag;
        $canonical = $this->filter_canonical_url( $this->canonical_from_markup( $canonical_tag ) );
        return $canonical
            ? '<link rel="canonical" href="' . esc_url( $canonical ) . '">'
            : $canonical_tag;
    }

    public function filter_seopress_locale($markup) {
        if(is_admin() || $this->is_not_found_request()) return $markup;
        $current=$this->provider->get_current_language();
        if($current===$this->provider->get_source_language()) return $markup;
        $locale=$this->provider->get_og_locale($current);
        return preg_replace_callback('/(<meta\\b[^>]*property=["\x27]og:locale["\x27][^>]*content=)["\x27][^"\x27]*["\x27]/i',
            static function($match) use($locale) {return $match[1].'"'.esc_attr($locale).'"';},$markup);
    }

    /** Read-only observation: return the exact robots value to its owner. */
    public function observe_robots( $robots ) {
        if ( $this->robots_disallow_index( $robots ) ) $this->request_noindex = true;
        return $robots;
    }

    private function robots_disallow_index( $robots ) {
        foreach ( (array) $robots as $key => $value ) {
            if ( is_string( $key ) ) {
                if ( in_array( strtolower( $key ), [ 'noindex', 'none' ], true ) && $value ) return true;
                if ( ! in_array( $key, [ 'index', 'follow' ], true ) ) continue;
            }
            if ( ! is_scalar( $value ) ) continue;
            $value = (string) $value;
            if ( preg_match( '~<meta\b[^>]*\bcontent\s*=\s*([\"\'])(.*?)\1~is', $value, $match ) ) $value = $match[2];
            $tokens = preg_split( '/[,\s]+/', strtolower( $value ) );
            if ( array_intersect( [ 'noindex', 'none' ], $tokens ) ) return true;
        }
        return false;
    }

    private function pagination_alternates() {
        if ( $this->request_noindex ) return [];
        foreach ( headers_list() as $header ) {
            if ( stripos( $header, 'X-Robots-Tag:' ) === 0 && $this->robots_disallow_index( $header ) ) return [];
        }
        $resource = GML_Resource_Identity::current_public();
        if ( ! $resource instanceof GML_Resource_Identity ) return [];
        // Core still owns base-resource readiness and review eligibility.
        // Adapt only eligible local members to the same current archive page.
        $eligible = $this->provider->get_alternate_urls( $resource->get_source_url() );
        $urls = GML_SEO_Router::get_language_urls();
        $result = [];
        foreach ( $this->provider->get_languages() as $language ) {
            $code = $this->provider->get_hreflang_code( $language );
            if ( isset( $eligible[$code], $urls[$language] ) ) $result[$code] = $urls[$language];
        }
        $source = $this->provider->get_source_language();
        if ( isset( $eligible['x-default'], $urls[$source] ) ) $result['x-default'] = $urls[$source];
        return $result;
    }

    private function current_canonical_url() {
        return $this->filter_canonical_url( $this->current_source_url() );
    }

    private function current_source_url() {
        if ( GML_SEO_Router::is_paginated_archive() ) {
            return GML_SEO_Router::get_current_source_url( GML_Resource_Identity::current_public() );
        }
        $request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        $path    = wp_parse_url( $request, PHP_URL_PATH );
        $path    = $path ?: '/';
        if ( class_exists( 'GML_URL_Helper' ) ) {
            $path = GML_URL_Helper::strip_home_path( $path );
        }
        return $this->provider->get_translated_url(
            home_url( $path ),
            $this->provider->get_source_language()
        );
    }

    private function canonical_from_markup( $value ) {
        if ( preg_match( '/href=["\']([^"\']+)["\']/', (string) $value, $match ) ) {
            return html_entity_decode( $match[1], ENT_QUOTES, 'UTF-8' );
        }
        return filter_var( $value, FILTER_VALIDATE_URL ) ? $value : '';
    }

    private function has_external_seo_authority() {
        return defined( 'GML_SEO_VER' )
            || defined( 'WPSEO_VERSION' )
            || defined( 'RANK_MATH_VERSION' )
            || defined( 'SEOPRESS_VERSION' )
            || defined( 'THE_SEO_FRAMEWORK_VERSION' )
            || class_exists( 'WPSEO_Options' )
            || class_exists( 'RankMath' );
    }

    private function is_not_found_request() {
        return function_exists( 'is_404' ) && is_404();
    }
}
