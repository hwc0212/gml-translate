<?php
/** Expand sitemap resources into reciprocal, independently listed language URLs. */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class GML_Sitemap_Publication_Transformer {
    const SITEMAP_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    const XHTML_NS = 'http://www.w3.org/1999/xhtml';
    const IMAGE_NS = 'http://www.google.com/schemas/sitemap-image/1.1';
    const VIDEO_NS = 'http://www.google.com/schemas/sitemap-video/1.1';
    const NEWS_NS = 'http://www.google.com/schemas/sitemap-news/0.9';
    const MAX_XML_BYTES = 26214400;
    const BATCH_SIZE = 1000;

    /** @var GML_Translation_Provider */
    private $provider;

    public function __construct( GML_Translation_Provider $provider ) {
        $this->provider = $provider;
    }

    public function transform( $xml, array $context = [] ) {
        $xml = (string) $xml;
        if ( $xml === '' || strlen( $xml ) > self::MAX_XML_BYTES || ! class_exists( 'DOMDocument' ) ) return $xml;
        if ( ! class_exists( 'GML_Resource_Identity' ) || ! method_exists( $this->provider, 'get_public_clusters_bulk' ) ) return $xml;

        $previous = libxml_use_internal_errors( true );
        $document = new DOMDocument();
        $loaded = $document->loadXML( $xml, LIBXML_NONET );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        if ( ! $loaded || ! $document->documentElement || $document->documentElement->localName !== 'urlset' ) return $xml;

        $url_nodes = [];
        $urls = [];
        foreach ( iterator_to_array( $document->documentElement->childNodes ) as $child ) {
            if ( ! $child instanceof DOMElement || $child->localName !== 'url' ) continue;
            $loc = $this->child_element( $child, 'loc' );
            if ( ! $loc ) continue;
            $url = html_entity_decode( trim( $loc->textContent ), ENT_QUOTES | ENT_XML1, 'UTF-8' );
            if ( $url === '' ) continue;
            $url_nodes[] = [ 'node' => $child, 'url' => $url ];
            $urls[] = $url;
        }
        if ( ! $urls ) return $xml;

        $resolved = [];
        foreach ( array_chunk( $urls, self::BATCH_SIZE ) as $url_batch ) {
            $resolved += GML_Resource_Identity::resolve_urls( $url_batch );
        }
        if ( ! $resolved ) return $xml;

        $resources = [];
        $templates = [];
        $nodes_by_key = [];
        $source_listed = [];
        $trust_source_indexability = ! empty( $context['source_sitemap_authority'] );
        foreach ( $url_nodes as $entry ) {
            $resource = $resolved[ $entry['url'] ] ?? null;
            if ( ! $resource || ! method_exists( $resource, 'get_key' ) ) continue;
            $key = $resource->get_key();
            $resources[ $key ] = $resource;
            $nodes_by_key[ $key ][] = $entry['node'];
            if ( ! isset( $templates[ $key ] ) || ! $this->is_target_url( $entry['url'] ) ) {
                $templates[ $key ] = $entry['node'];
            }
            if ( $trust_source_indexability && ! $this->is_target_url( $entry['url'] ) ) $source_listed[ $key ] = true;
        }
        if ( ! $resources ) return $xml;

        $context['entrypoint'] = $context['entrypoint'] ?? 'sitemap';
        $context['source_listed_resource_keys'] = $source_listed;
        $clusters = [];
        foreach ( array_chunk( array_values( $resources ), self::BATCH_SIZE ) as $resource_batch ) {
            $batch_clusters = $this->provider->get_public_clusters_bulk( $resource_batch, $context );
            if ( ! is_array( $batch_clusters ) ) return $xml;
            $clusters += $batch_clusters;
        }

        $document->documentElement->setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:xhtml', self::XHTML_NS );
        foreach ( $resources as $key => $resource ) {
            $nodes = $nodes_by_key[ $key ] ?? [];
            if ( ! $nodes ) continue;

            $cluster = $clusters[ $key ] ?? [];
            $public_urls = is_array( $cluster['eligible_urls'] ?? null ) ? $cluster['eligible_urls'] : [];
            $anchor = $nodes[0];
            if ( $public_urls && $anchor->parentNode ) {
                $template = $templates[ $key ] ?? $anchor;
                foreach ( $public_urls as $lang => $url ) {
                    $clone = $template->cloneNode( true );
                    $this->set_location( $clone, $url );
                    $this->replace_alternates( $document, $clone, $public_urls, $cluster['source_lang'] ?? $this->provider->get_source_language() );
                    $anchor->parentNode->insertBefore( $clone, $anchor );
                }
            }

            // The original entry is replaced by the exact eligible cluster. If
            // the cluster is empty, removing it prevents a noindex or invalid
            // resource from surviving in the standalone sitemap.
            foreach ( $nodes as $node ) {
                if ( $node->parentNode ) $node->parentNode->removeChild( $node );
            }
        }

        return $document->saveXML();
    }

    public function transform_fragment( $fragment, $loc, array $context = [] ) {
        unset( $loc );
        $wrapped = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="' . self::SITEMAP_NS
            . '" xmlns:xhtml="' . self::XHTML_NS
            . '" xmlns:image="' . self::IMAGE_NS
            . '" xmlns:video="' . self::VIDEO_NS
            . '" xmlns:news="' . self::NEWS_NS
            . '">' . (string) $fragment . '</urlset>';
        $transformed = $this->transform( $wrapped, $context );
        if ( $transformed === $wrapped || ! class_exists( 'DOMDocument' ) ) return $fragment;

        $previous = libxml_use_internal_errors( true );
        $document = new DOMDocument();
        $loaded = $document->loadXML( $transformed, LIBXML_NONET );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        if ( ! $loaded ) return $fragment;

        $output = '';
        foreach ( $document->documentElement->childNodes as $child ) {
            if ( $child instanceof DOMElement && $child->localName === 'url' ) $output .= $document->saveXML( $child );
        }
        return $output !== '' ? $output : $fragment;
    }

    private function replace_alternates( DOMDocument $document, DOMElement $url_node, array $urls, $source_lang ) {
        foreach ( iterator_to_array( $url_node->childNodes ) as $child ) {
            if ( $child instanceof DOMElement && $child->localName === 'link' && $child->namespaceURI === self::XHTML_NS ) {
                $url_node->removeChild( $child );
            }
        }
        foreach ( $urls as $lang => $url ) {
            $link = $document->createElementNS( self::XHTML_NS, 'xhtml:link' );
            $link->setAttribute( 'rel', 'alternate' );
            $link->setAttribute( 'hreflang', $this->provider->get_hreflang_code( $lang ) );
            $link->setAttribute( 'href', $url );
            $url_node->appendChild( $link );
        }
        if ( isset( $urls[ $source_lang ] ) ) {
            $default = $document->createElementNS( self::XHTML_NS, 'xhtml:link' );
            $default->setAttribute( 'rel', 'alternate' );
            $default->setAttribute( 'hreflang', 'x-default' );
            $default->setAttribute( 'href', $urls[ $source_lang ] );
            $url_node->appendChild( $default );
        }
    }

    private function set_location( DOMElement $url_node, $url ) {
        $loc = $this->child_element( $url_node, 'loc' );
        if ( ! $loc ) return;
        while ( $loc->firstChild ) $loc->removeChild( $loc->firstChild );
        $loc->appendChild( $loc->ownerDocument->createTextNode( esc_url_raw( $url ) ) );
    }

    private function child_element( DOMElement $parent, $local_name ) {
        foreach ( $parent->childNodes as $child ) {
            if ( $child instanceof DOMElement && $child->localName === $local_name ) return $child;
        }
        return null;
    }

    private function is_target_url( $url ) {
        if ( ! class_exists( 'GML_URL_Helper' ) || ! class_exists( 'GML_Language_Utils' ) ) return false;
        $path = wp_parse_url( $url, PHP_URL_PATH ) ?: '/';
        return (bool) GML_URL_Helper::detect_language( $path, GML_Language_Utils::enabled_local_target_codes() );
    }
}
