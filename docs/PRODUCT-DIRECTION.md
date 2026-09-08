# GML Product Direction

Last updated: 2026-09-07

## Product Portfolio

GML Translate is the primary maintained product. It is a standalone WordPress multilingual system for controlled AI-assisted translation, stable language routing, Translation Memory, glossary, manual editing, queue operations, human review, publication safety, hreflang, and multilingual sitemap relationships.

GML SEO is in LTS. It receives security fixes, data-integrity fixes, WordPress/PHP compatibility work, critical regressions, migration support, and the minimum changes required to consume an officially vendored Translation Core. It does not receive new general SEO features.

No third GML performance or WordPress optimizer product is planned. Mature SEO and performance products already cover that market, while multilingual reliability remains the differentiated problem GML should solve.

## Product Boundary

GML Translate owns multilingual routing, local translation production, queue and failure recovery, Translation Memory, glossary, manual review, translated URL mapping, publication eligibility, language switchers, and the minimum multilingual SEO relationship layer.

GML Translate does not own a full title/meta system, general Schema, redirects, 404 management, GSC, GA4, Google Ads, generic SEO audits, or performance optimization. When SEOPress, Yoast, or Rank Math is active, that plugin remains the SEO authority.

Multilingual Site and AI Translation are independent states. Disabling AI, deleting a key, exhausting quota, or pausing the queue must never remove already stored translations or destroy existing language data.

## Architecture

`gml-translation-core` is product-neutral source. Product repositories never become independent forks of Core.

The required flow is:

```text
Core source change
-> Core tests
-> Core commit
-> official product vendor sync
-> translation-core.lock.json verification
-> product tests
-> product commit
```

Release ZIP files remain self-contained. Users never install Composer, npm, a Git submodule, or a separate Core plugin.

## Safety And Performance

- Public translation status is derived, never a manually stored `published` boolean.
- Paid AI work is never started by an update or by viewing a public page.
- Large recovery starts with a 10-25 item sample and explicit administrator action.
- Historical failures remain auditable but do not occupy current retry/readiness counts.
- Human decisions bind to an exact snapshot and fail closed after source or translation changes.
- Frontend reads stay bounded and avoid URL x language query patterns.
- Database changes are additive, versioned, and tested against retained production-scale data.

## Roadmap

1. Reliability: stale detection, queue recovery, backoff, historical-error separation, and API-off continuity.
2. Migration and compatibility: GML SEO, SEOPress, Yoast, Rank Math, Oxygen, GeneratePress, Elementor, and WooCommerce.
3. Cost control: Translation Memory first, deduplication, token/cost estimates, and sampled paid batches.
4. Operations: audit, import/export, per-language controls, backup, and safe destructive confirmation.
5. Later only: signed cross-server manifests for subdomains and independent domains.

Cross-server publication is deliberately not inferred from a configured URL. A remote target requires verifiable readiness and reciprocal ownership before it can be advertised as an alternate.
