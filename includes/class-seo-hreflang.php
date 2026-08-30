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

    public function __construct( GML_Translation_Provider_Interface $provider = null ) {
        $this->provider               = $provider ?: new GML_Translation_Provider();
        $this->external_seo_authority = $this->has_external_seo_authority();

		// GML SEO consumes this plugin's read-only TranslationProvider data and
		// remains the sole owner of canonical, hreflang, OG locale, and sitemap.
		if ( defined( 'GML_SEO_VER' ) ) {
			return;
		}

        add_action( 'wp_head', [ $this, 'inject_multilingual_meta' ], 1 );
        add_filter( 'language_attributes', [ $this->provider, 'filter_language_attributes' ], 10, 2 );

        // Keep canonical URLs supplied by common SEO plugins self-referencing
        // on translated routes without adding a second canonical tag.
        add_filter( 'get_canonical_url', [ $this, 'filter_canonical_url' ], 10, 2 );
        add_filter( 'wpseo_canonical', [ $this, 'filter_canonical_url' ], 10, 1 );
        add_filter( 'rank_math/frontend/canonical', [ $this, 'filter_canonical_url' ], 10, 1 );
        add_filter( 'seopress_titles_canonical', [ $this, 'filter_seopress_canonical' ], 10, 1 );

        if ( ! $this->external_seo_authority ) {
            remove_action( 'wp_head', 'rel_canonical' );
        }
    }

    public function inject_multilingual_meta() {
        if ( is_admin() ) {
            return;
        }

        $canonical = $this->current_canonical_url();
        foreach ( $this->provider->get_alternate_urls( $canonical ) as $hreflang => $url ) {
            echo '<link rel="alternate" hreflang="' . esc_attr( $hreflang ) . '" href="' . esc_url( $url ) . '" />' . "\n";
        }

        $current = $this->provider->get_current_language();
        echo '<meta property="og:locale" content="' . esc_attr( $this->provider->get_og_locale( $current ) ) . '" />' . "\n";
        $seen = [];
        foreach ( $this->provider->get_alternate_urls( $canonical ) as $hreflang => $url ) {
            unset( $url );
            if ( $hreflang === 'x-default' || $hreflang === $this->provider->get_hreflang_code( $current ) ) {
                continue;
            }
            $locale = $this->provider->get_og_locale( $hreflang );
            if ( ! isset( $seen[ $locale ] ) ) {
                echo '<meta property="og:locale:alternate" content="' . esc_attr( $locale ) . '" />' . "\n";
                $seen[ $locale ] = true;
            }
        }

        if ( ! $this->external_seo_authority && $canonical ) {
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
        }
    }

    public function filter_canonical_url( $canonical_url, $post = null ) {
        unset( $post );
        $source = $canonical_url ?: $this->current_source_url();
        $url    = $this->provider->get_translated_url( $source, $this->provider->get_current_language() );
        return $url ?: $canonical_url;
    }

    public function filter_seopress_canonical( $canonical_tag ) {
        $canonical = $this->filter_canonical_url( $this->canonical_from_markup( $canonical_tag ) );
        return $canonical
            ? '<link rel="canonical" href="' . esc_url( $canonical ) . '">'
            : $canonical_tag;
    }

    private function current_canonical_url() {
        return $this->filter_canonical_url( $this->current_source_url() );
    }

    private function current_source_url() {
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
}
