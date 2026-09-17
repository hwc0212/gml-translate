# rc.33 Human Resolution and Bounded Validation

Issue: https://github.com/hwc0212/gml-translate/issues/1

## Runtime changes

- Needs Attention groups exact current queue assets, including failed/pending/held
  work, with per-item AI/manual actions and resource-local Keep source decisions.
- Current actionable, Deferred, Kept source decisions and History are distinct.
  A source decision is not TM, does not delete a failure and does not mark shared
  untranslated work complete for other pages.
- One resource/language/current manifest item may be explicitly kept in the
  source language. Critical items require an additional confirmation and record
  actor/time. Held text and a current rejected page cannot be bypassed.
- Revoke returns an item to unresolved. Changed source/manifest/generation makes
  an old decision ineffective; a refresh is required before a new decision.
- Translation coverage = auto + manual. Resolved coverage additionally counts
  explicit source decisions. Count and byte-length coverage must satisfy the
  existing threshold (default 98%); unresolved critical/held/rejected content
  still prevents SEO readiness. Navigation remains independent of these checks.
- Manual save uses the existing reviewed-TM mutation and preserves manual
  protection; shared assets update all current references. AI uses the existing
  one-item fast lane and never silently resumes the global queue.
- Successful actions reload current list/progress. Durable cache work commits
  with the decision. External CDN/server invalidation still requires the site's
  configured exact-URL adapter; no universal purge is assumed.

## Upgrade and rollback

Core 0.9.11 uses additive schema 3.6.0. The only new table is
`{prefix}gml_item_resolutions`, with unique active scope and retained revoked
rows. Existing tables/options, glossary, TM states and queue are not renamed.

The first normal administrator request after replacement performs the existing
schema-upgrade checkpoint. Do not assume an ordinary frontend/CLI request runs
that administrator migration. Database errors leave a visible upgrade notice.

Back up the previous ZIP, database and relevant configuration. Upgrade with the
exact rc.33 ZIP, visit its admin page, verify version/schema and paused state.
Do not use uninstall/delete to roll back. Reinstall rc.32 if needed: it retains
the additive table but ignores source decisions. Raw stored machine readiness
still counts genuine translations only. Thus rollback may reduce publication
readiness, but cannot misinterpret a kept source as a translated record.
Refresh only affected rendered resource clusters using the site's normal
cache adapter; retain decision audit and all translations.

## Development verification

Local WordPress 7.1 / PHP 8.3 / MariaDB 10.11 / real Redis:
- 112 full database scenarios, including root/subdirectory routing, resource
  readiness, publication, cache races, concurrency, scheduler and uninstall.
- Extra decision tests: snapshot conflict, critical confirmation, permission,
  revoke/source drift, page-local shared hash, optional human review, protected
  manual save, held/rejected protection, and transactional cache failure.
- Policy boundaries: 97.9%, 98%, 100%, long-text coverage and current rejection;
  92 auto + 6 manual + 2 source = 98 translated / 100 resolved.
- Real AJAX/browser forms at 1440 and 390 px, Chinese UI, invalid nonce,
  anonymous writes, stale token, save/revoke/reload and unchanged pause state.
- WordPress Plugin_Upgrader: rc.32 -> rc.33 -> rc.32 -> rc.33, with unchanged
  TM/queue checksums and settings, retained decision audit, fail-safe rollback.
- Fresh rc.33 installation and installed-file/vendor hash comparison.
- Product suite retains navigation-only switcher and root/subdirectory fixtures.
  Final GitHub CI must pass PHP 7.4, PHP 8.3 and the real database job.

These checks use synthetic data and blocked external provider requests. They
are not production or real-provider quality acceptance. Evidence and final
package SHA are delivered separately with the exact final commit.

## Bounded live validation plan (requires separate authorization)

1. On each site, record installed version, queue pause state, current page
   counts and backups. Keep global AI paused and default provider unchanged.
2. Choose at most two existing resources per site and one local target language:
   one ordinary unresolved item and one critical field. Record exact current
   source/manifest/TM state; stop on drift. Include a shared header/footer asset
   only when its affected-page list is understood.
3. Review one ordinary item and explicitly Keep source. Verify it leaves
   Current actionable for that page, appears in Kept source decisions, counts
   as resolved but not translated, and does not resolve another shared page.
4. Revoke that decision. Save one reviewed manual translation using the official
   editor. Verify manual status, affected-page progress and exact cache refresh;
   do not release held content or overwrite existing assets without separate
   explicit review.
5. For the critical field, verify missing confirmation is rejected. Only after
   checking its exact text choose confirmed Keep source or a verified manual
   translation. Check truthful resolved/translated counts and the 98% policy.
6. If separately authorized, run at most one explicit AI item per site with the
   existing provider. Inspect candidate/error and activity log; do not retry all,
   resume background AI, change provider or start a crawl.
7. Check the selected source/target pages, language navigation, canonical,
   hreflang and sitemap inclusion against actual readiness/noindex policy.
   CNXHE's sitemap is https://www.cnxhe.com/sitemaps.xml. Verify only those URLs
   and exact cache actions; no global purge.
8. Record pass/fail, data/count deltas and queue state. Stop on conflict, 5xx,
   unauthorized changes or wrong content. Use the tested ZIP rollback path
   when needed and report before expanding the batch.

## Explicit exclusions

No production deployment/database/TM mutation, AI request, queue resume,
historical bulk retry, provider switch, global cache purge, GML SEO change,
Work Hub work, WordPress.org submission, main merge, force push, tag or Release.

