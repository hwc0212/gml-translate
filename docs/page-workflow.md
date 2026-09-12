# Page Workflow and Upgrade Guide

## Current scope

GML Translate is the primary product; GML SEO remains LTS and is not updated by
this workflow. Core 0.9.8 is bundled at build time. End users install one complete
ZIP and do not run Composer/npm or install another Core plugin.

This is development/testing work, not production acceptance or a directory listing.

## Page readiness

Page Progress reports each current resource and target language independently.
The default 98% threshold requires both exact unique-segment coverage and source
byte-length coverage. It prevents many short navigation labels from outweighing
missing body paragraphs. This is a transparent conservative length check, not an
AI quality score. 97.9% is below 98%; 100% means all required segments are present.

Critical title/description omissions, held text, stale inventory and current review
rejection cannot be bypassed by the percentage. Source noindex remains respected.
Changing the threshold records resource-cluster invalidation; an invalidation-write
failure restores the previous threshold. 98% is a product policy, not a search
engine indexing guarantee.

A valid incomplete route stays 200, keeps its language canonical and uses current
source text for missing/held segments. It shows a small incomplete notice; if AI
or background translation is paused, the notice says so. Enabled local languages
remain links. The switcher shows configured language labels/flags, not progress
percentages or translation diagnostics; use admin Page Progress for those details.
External sites use configured mappings,
not invented equivalents. 404, private and permanent-redirect resources retain
their existing protections. Below-policy routes are noindex and excluded from the
page's SEO alternate set; ready language versions use reciprocal hreflang/sitemap.

## What is processed first

Explicit single-item requests have the highest priority at the next safe worker
boundary. An in-flight provider request is not interrupted. A busy worker returns
a clear retry message, and a second active one-item request is not silently merged.

Home and top-level primary-navigation pages are discovered before older long-tail
posts. The navigation location and priority resource keys can be changed in Page
Progress. The queue works on one resource/language for a bounded three-minute
window, then re-evaluates. Shared source identities remain deduplicated. Demand
and waiting age influence selection; sufficiently old low-traffic work can outrank
fresh navigation work. Language preference uses the configured target order.

Selecting Prioritize This Page raises its currently pending text. It does not
discover an unscanned page, retry historical failures or resume paused background
translation. Scan current content first when there is no current manifest.

## Needs Attention

Filter by resource ID, language, error text and current/history. Rows show the exact
source, context, affected resources, recorded error/time and attempts. Legacy rows
may only contain an older timestamp or generic provider error; the plugin does not
invent missing historical response details.

- AI Translate This Item: accepts one identified item, then reports waiting,
  generating, saved, candidate, failed or expired. It can run while background work
  is paused, without unpausing other work. AI-off, credentials, circuit, cooldown
  and existing provider budgets still apply. Requests are rate-limited.
- Missing TM: a validated result uses the official missing-only transactional API.
- Existing auto/manual/held TM: AI creates a candidate. Explicit Save Manual
  Translation uses current source/manifest/TM snapshots; stale forms return conflict.
  Held text requires an explicit release checkbox. Candidate generation is not approval.
- Defer: leaves the source required and preserves the error. It cannot inflate the
  page percentage. Exclusion rules are separate and must be intentional.

Successful manual writes mark TM manual, update readiness and invalidate the local
resource cluster. Resolved rows leave current failures but retain historical error
information. Candidate storage is bounded at 100 items; review saved candidates
before requesting more. The original translation is not removed automatically.

A one-item job expires after 30 minutes. The UI stops polling after three minutes
and reports Cron/cooldown status instead of spinning forever. Configure reliable
WordPress Cron for actual background progress; opening the UI is not the executor.
Activity retains the latest 100 redacted metadata events, not keys/prompts/raw
provider responses. Historical failure totals do not themselves trip the circuit.

## Privacy and demand

First-party demand is disabled by default. When enabled, it only records a known
resource, source/target language, day and aggregate view count. English source
views also raise target-page demand; target-language requests have extra weight.
No query strings, payment data or raw IP addresses are stored. A rotating HMAC of
the connection address is used transiently for abuse limits, not visitor profiling.

The browser event works with cached HTML. It waits for `wp_has_consent('statistics')`
or explicit `window.gmlPageDemandConsent = true`, then a
`document.dispatchEvent(new Event('gml:consent-granted'))` event. The site owner must
connect this to the site's actual consent policy; absence of consent means no counts.
Visitors retain the same navigation when counters fail or are disabled.

Default retention is 7 days, configurable 1-30. Cleanup is bounded and scheduled.
Client/session repeat views, obvious bots/headless agents, admin previews, invalid
resource proofs and request floods are rejected or deduplicated. No public input
can create a new resource or invoke AI. Cached proof tokens last seven days; stale
HTML older than that needs ordinary page-cache refresh before counting resumes.

Already-authorized analytics can supply read-only aggregate scores through
`gml_page_demand_scores($scores, $known_resource_ids)`. Keep the provider-specific
source ledger in that adapter; GML does not open a new GSC/GA OAuth connection or
pretend unconnected metrics exist.

## Cache adapters

GML's own generation invalidation is active. External server/CDN caches require
explicit `gml_resource_cluster_cache_adapters` callbacks. Each callback receives
one exact local URL and its resource-cluster plan; return true only after checking
the purge result. Set a bounded transport timeout (recommended <=10 seconds).
Do not put credentials or site-specific shell commands in plugin source.

The scheduled consumer handles one resource and at most 20 URLs per run, checks
its lease, persists token-bound progress and acknowledges only the same token.
A failed callback retains pending work; a concurrent invalidation invalidates the
old acknowledgement. Without adapters, Page Progress clearly reports that outer
cache purge is unconfirmed. There is no Purge Everything or Redis-wide flush.

## Upgrade and rollback

Back up the current plugin ZIP and relevant database/options before testing. Keep
the current queue/provider settings. Upgrade adds only the daily aggregate table;
existing TM, manual/held records, glossary and queue identities remain unchanged.
The new policy narrows rc.28's partial SEO publication to default 98%, so after
upgrading review Page Progress and exact page caches before using it publicly.

A code rollback to rc.28 preserves new data and the additive table, but restores
rc.28's looser partial-SEO policy. Do not delete/reinstall with erase-data enabled
to roll back. Do not restore an old whole-site database over new orders/inquiries.
Pause new AI work before rollback, keep the new TM assets, and verify affected
language routes and SEO with the restored version. There is no automatic production
deployment, provider switch, queue resume, historical retry or translation-library reset.
