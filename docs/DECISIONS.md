# GML Decisions

Last updated: 2026-09-07

## Active Decisions

### D-001: GML Translate Is The Primary Product

GML Translate receives new product development. GML SEO is LTS and no third optimizer is created. This keeps engineering effort on multilingual reliability instead of duplicating established SEO and performance products.

### D-002: Core Is Source-First And Build-Vendored

`gml-translation-core` is the only Core source. Each product ZIP vendors an exact committed Core through the official sync tool and records the commit in `translation-core.lock.json`. Runtime dependencies and a separate Core plugin are prohibited.

### D-003: Multilingual Output Does Not Depend On AI Availability

Multilingual Site controls routes and stored translations. AI Translation controls only new machine work. Provider failure, quota exhaustion, missing credentials, or a paused queue must not delete saved translation data.

### D-004: One SEO Authority

SEOPress, Yoast, and Rank Math remain authoritative for general SEO. GML Translate supplies multilingual resource mapping, exact publication state, canonical adaptation where required, hreflang relationships, and Sitemap cluster data. It does not duplicate a complete SEO suite.

### D-005: Publication Is Derived

There is no mutable `published = true` flag. A local translated resource is public only when machine completeness, exact current Human Review approval, source indexability, enabled local language, and route validity are all true.

### D-006: Fail Closed Without Destroying Review Access

Anonymous visitors to an ineligible local target receive a 302 source-language redirect. Authorized reviewers may preview the target under forced noindex protection and a visible status banner. A 404 is not used merely because translation is incomplete.

### D-007: Human Approval Is Per Resource And Language

Bulk approval is prohibited. Review decisions use POST, capability checks, nonce validation, exact-snapshot compare-and-swap, InnoDB transactions, and append-only audit records.

### D-008: Historical Data Is Retained

Legacy queue failures, Translation Memory, manual translations, glossary, language options, and audit records stay compatible. Historical errors may be excluded from current readiness and retry counts without being deleted.

### D-009: Cross-Server Publication Requires Proof

An external URL alone does not prove equivalent content, current translation, reciprocal hreflang, or ownership. External targets remain `external_unverified` until a signed manifest exchange is designed and separately approved.

### D-010: Release Safety

Normal development commits and development-branch pushes are allowed. Force push, main merges, stable tags, GitHub Releases, production deployment, production database mutation, risky cache purge, and destructive migration require a separate Stop Gate.
