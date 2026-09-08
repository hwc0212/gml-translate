# GML Translate 2.11.1-rc.18

Release type: user-test release candidate

Date: 2026-09-08

## Purpose

This RC activates Phase 2D Publication Gate for local language routes. A translated page is no longer considered public merely because its URL exists or some Translation Memory rows are present.

## Public Eligibility

A local target is public only when all conditions are true:

```text
machine_complete
AND human approval matches exact current snapshot
AND source resource is SEO-indexable
AND local target language is enabled
AND route is valid
```

The result is derived on read. No second `published` state is stored.

## Visitor And Reviewer Behavior

- Anonymous visitor, eligible target: receives the translated page.
- Anonymous visitor, ineligible target: receives a temporary 302 redirect to the matching source URL.
- Authorized administrator, ineligible target: may preview it with HTTP and HTML `noindex, nofollow`, disabled caching, a visible warning, and the exact blocking reason.
- 404, search, feed, REST/AJAX, account, checkout, and unsupported personalized resources are not advertised as language alternatives.
- Theme and plugin strings translated earlier by WordPress Gettext remain eligible in the later HTML buffer; the handoff is request-local and adds no Translation Memory scan or database query.

## SEO And Sitemap

- Route, switcher, canonical, hreflang, and Sitemap share the same public cluster.
- SEOPress is the first compatibility target; Yoast and Rank Math adapters use their published Sitemap filters.
- Every eligible source or translated URL has its own Sitemap `<url>` entry.
- Every entry in a cluster carries the same reciprocal alternate set plus `x-default`.
- Resources with no eligible URL are removed instead of leaking through a source-only fallback.
- Image, video, and news Sitemap child nodes are preserved.
- Without a supported SEO plugin, GML owns one fallback Sitemap and disables the non-multilingual WordPress Core Sitemap.

## Data And Upgrade Safety

The upgrade is additive. It does not call an AI provider, start or resume Queue work, approve translations, delete historical failures, clear Translation Memory, change glossary data, or remove manual translations.

External-domain mappings remain stored but are not public-eligible in this RC because their remote content and reciprocal relationship cannot yet be verified locally.

## Rollback

Before downgrading to a pre-Phase-2D build, disable Multilingual Site and purge only the affected page cache through the normal plugin controls. Older builds do not understand the Publication Gate and may expose stale stored translations. The Core schema additions are retained and are not destructively rolled back.

## Required User Test

1. Install on a clone or staging site and keep AI Translation paused.
2. Open a machine-complete but unapproved target while logged out; confirm one 302 to the matching English page.
3. Open the same target as an administrator; confirm the warning banner and `noindex, nofollow` without a redirect.
4. Approve that exact resource/language in Human Review and reopen logged out; confirm 200 translated content and self canonical.
5. Confirm the source and target both show the same reciprocal hreflang set with `x-default` to the source.
6. Confirm the language switcher lists only approved current targets and never appears on a real 404.
7. Inspect the active SEO plugin Sitemap: source and approved target must each have an independent URL entry; rejected/stale/unreviewed targets must be absent.
8. Edit the source page, let the manifest refresh without starting paid AI work, and confirm the prior approval becomes stale and anonymous access returns to 302.
9. Pause AI or remove the API key and confirm already approved/current pages remain accessible.
10. Test root and subdirectory staging paths for exactly one base directory in route, canonical, hreflang, and Sitemap URLs.
