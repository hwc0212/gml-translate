# GML Translate 2.11.1

This Stable release promotes the accepted 2.11.1-rc.35 functional baseline. The
promotion changes only version and release documentation. Shared Translation
Core remains 0.9.13 at `63efe6bb5f49a7850cf5df9addbc6feb4591a388`.

## Highlights

- Page-by-language progress and a default 98% readiness policy keep valid local
  language URLs available while incomplete content falls back to the source
  language. Search publication remains separately gated by current critical
  content, review state and source indexability.
- Page Progress and Needs Attention provide explicit one-item AI requests,
  manual editing, deferral and revocable Keep source decisions for the current
  resource/language snapshot. Existing auto/manual/held translations are not
  discarded or silently replaced.
- Protected-term and format checks preserve placeholders, links, dimensions,
  percentages and configured terminology while accepting validated equivalent
  localized forms. Rejected candidates remain reviewable rather than entering
  Translation Memory automatically.
- Queue and scheduler work is bounded and prioritizes explicit page requests,
  homepage/navigation and demand. Failure diagnostics, pause controls and
  provider handling reduce repeated paid failures without hiding all valid
  language pages when an AI provider is unavailable.
- The Translation Editor rejects a stale delete if its displayed row snapshot
  changed before saving. The newer translation remains intact.

## Upgrade from rc.35

Back up the site, then install `gml-translate-2.11.1.zip` over rc.35. This
promotion adds no database migration, restarts no paused queue and makes no
provider request. Confirm the active version, language routes, current page
readiness and queue state. Keep a tested backup and the rc.35 ZIP for rollback.
The exact Core is bundled in the ZIP; Composer, npm and a separate Core plugin
are not required.

## Integration limits

CNXHE's external full-page cache requires a site-specific permanent cache
adapter. Historical mistranslations and terminology need content review;
site SEO settings and provider/API availability remain separate site or
operations concerns. This release does not assert that every historical
translation is semantically perfect, every CDN/server cache is integrated, or
every commercial theme and SEO plugin was directly certified.
