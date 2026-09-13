# rc.32 Scheduler Correction and Bounded Acceptance

Scope: GML Translate only, Core 0.9.10. No GML SEO, production write,
provider switch, global queue recovery, tag, release or main merge.

## Findings and ownership

| Observation | Source / correction | Remaining production proof |
| --- | --- | --- |
| 91 historical wakes around 300 seconds apart | Host wake origin is not established by activity alone. Plugin recurrence is 60 seconds; capture original due and actual start separately. | Read current system/managed cron, DISABLE_WP_CRON and loopback using an authorized read-only access path. Do not assume a configuration change is necessary. |
| One short item in each wake | Old worker executes one context-filtered batch. The 30-text fixture fails on exact Core 0.9.9 and passes on 0.9.10 across three separate contexts. | Compare current provider call/save deltas after approved install. |
| Page window expires between delayed wakes | 180 seconds becomes a fixed 900 seconds; urgent priority preempts and aging remains. Smallest pending count no longer wins ties. | Observe selected resource and exit reasons; no production schedule was changed. |
| Current missing text without pending work | Explicit Page Progress page/language action performs authoritative discovery plus missing-set enqueue. Inventory-only discovery remains non-paying. | Choose an explicit page/language; stopped scans, unselected pages and old failed rows stay unchanged. |
| Seven failure actions for one exact asset | Group by hash, source/target languages, context and exact source; keep timeline. One successful official save reconciles exact failed rows without erasing their text/history. Current normal schema also has a unique tuple index. | Inspect actual production identities first: identical screen previews do not prove identical tuples. |
| Deterministic protected-content error repeats | Isolate the known bad item, retain other pending work, require explicit recovery instead of three identical calls. | Source/candidate evidence for historical queue 142259 remains unverified. |
| Six old percentage failures | Both exact source paragraphs times ES/DE/RU pass offline checks with 0.9.9 and 0.9.10. | LIVE_RETRY_NOT_RUN: no new production provider call or candidate write. |
| Wrong technical application translation | Context prompt distinguishes equipment use from employment; no global replacement. | Existing Bewerbung TM is unchanged and requires targeted approved review. |
| Spanish OG locale en_US | Transform the existing SEOPress locale markup through its supported filter, not a second OG emitter. | Ozon disabled OG/X remains disabled. Validate CNXHE actual cached head. |
| English progress notice | Fixed target-language notices for DE/ES/FR/RU/IT/PT/ZH; excluded from translation. | Verify actual target pages after asset installation. Menu remains navigation-only. |

## Limits and data semantics

- Normal worker: maximum eight batches, eight external-request reservations,
  262144 input bytes, 32768 reserved output tokens, 45 seconds, reduced for a
  shorter PHP execution limit. Transport timeout is capped by remaining time.
- Daily account billing is NOT implemented here. An existing site budget may deny
  each call through gml_translation_worker_can_request. Tests simulate denial.
- One fixed-argument continuation event is eligible five seconds later.
  WordPress removes a due event before its callback; wp_next_scheduled prevents
  duplicate events. This is not an independent daemon or a guaranteed five-second
  service when the host invokes WP-Cron only every five minutes.
- No global pause is removed by install, connection test, explicit page enqueue
  or single-item recovery. In-flight paid results can finish safely; subsequent
  calls recheck scope, pause, lease, circuit and budget.
- No new DB schema/table/option renames. No overwrite of existing auto/manual/
  held assets; existing assets receive candidates under the existing review path.
- Page/locale readiness remains the existing 98% count-and-length policy with
  critical-field, source noindex and hold protections. Incomplete valid routes
  remain progressively accessible. Switcher percentages remain removed.
- Page authorization is explicit and bounded to one current resource/language.
  It is not inferred from arbitrary visits or from a site-wide not-queued total.
  Unavailable AI, stale/render-error manifest or enqueue failure returns a
  blocking result; existing failed/held items are shown for separate review.
- Demand statistics remain opt-in. Tests cover disabled fallback priority and
  enabled aggregate demand; neither production site is enabled by this update.

## Local verification

Use the committed Core tests with WordPress 7.1, PHP 8.3, MariaDB 10.11 and
the official Redis Object Cache drop-in. Each disposable database has a distinct
cache prefix. The full database runner includes 111 required scenarios.
Product checks also run on the exact vendored source and CI-supported PHP versions.

Additional fixed fixtures:

1. worker-continuation.php: 30 short texts, seo_title/seo_meta/text contracts,
   three provider batches in one wake, 30 committed inserts.
2. worker-recovery.php: pause, AI-off, site budget, lease loss, protected failure,
   seven legacy rows/one action/one generation, page missing-set enqueue,
   inventory-only/no-op, existing pause, request/time caps and technical context.
3. cron-http.php plus cron-fixture-mu.php: actual local wp-cron.php in three
   windows, 100 synthetic texts saved 40/40/20, five mock calls, no admin refresh.
   This is HTTP Cron entry testing, not repeated direct process_batch calls.
4. page-scheduling-cache.php: accelerated 300-second delay, navigation, urgency,
   fairness and exact resource-cache token/race/cursor regression.
5. test-format-quality.php: 36 positive/negative fixtures, including the six
   Ozon source/candidate pairs. Candidate format checking is not live quality QA.

Never install the test MU fixture or synthetic configuration in production.

## One consolidated production validation gate (approval required)

This plan is NOT permission to execute production writes.

1. Preserve the currently installed ZIP/files and record runtime version, exact
   RC ZIP SHA256, Core lock, configured languages/provider, user pause, current
   queue/TM counts and selected resource manifests. Back up only the affected
   settings and tuple snapshots using the approved access path.
2. Install the verified exact rc.32 ZIP. Confirm file ownership/writability first,
   plugin/Core hashes, frontend/admin health and unchanged provider/pause/TM.
   No automatic scan, pending start or purge follows installation.
3. Check host wake evidence read-only. If a real five-minute host task is found,
   propose one bounded change to its approved scheduler separately. Do not label
   SERVER_CONFIG_CHANGE_REQUIRED without that evidence.
4. Select CNXHE source home / DE / ES and quote / DE for page-scoped discovery.
   Review current seo_title/seo_meta differences before enqueue/recovery. Only
   current requested missing tuples are admitted; no historical bulk retry.
5. For Ozon, review the two exact public source paragraphs times ES/DE/RU.
   Allow at most six current candidate calls after snapshot checks; preserve
   the configured provider. Existing TM/held/manual rows are not overwritten.
6. Combined canary ceiling: 12 provider requests, 65536 reserved output tokens,
   524288 input bytes, two worker windows and five minutes wall-clock.
   Abort before the next call when any ceiling is reached. Treat this as a
   controlled canary, not Start All Pending; unrelated pending work stays paused.
7. Stop on source/manifest drift, unexpected scope, new provider-wide 400/401/403/
   404, cooldown/quota, content/brand/number/URL damage, lock conflict, repeated
   identical failure, unexpected TM overwrite, 5xx or uncontrolled event growth.
   Retain current good translations; do not clear history or reset attempts.
8. After approved safe writes, consume only the exact affected resource-cluster
   invalidation tokens. Candidate local CNXHE URLs are /, /de/, /es/, /quote/,
   /de/quote/ plus only actual affected local alternates from the fresh cluster.
   Ozon paths must be resolved from its six tuples before approval; do not guess
   them from the text. Outer cache purge uses only that recorded exact URL list.
9. Verify source/target content, title/description, locale, self-canonical,
   robots, reciprocal hreflang and /sitemaps.xml plus bounded child maps.
   Readiness/SEO must reflect the current snapshot and source noindex setting.
   Check desktop/mobile/keyboard navigation and Request Quote without progress
   text in the switcher. Stop and report client-side blocked fetches distinctly.
10. Rollback restores the preserved plugin files and exact pre-change settings;
    approved insertions are handled through the saved official mutation ledger,
    not a whole-TM restore or blanket delete. Preserve user pause throughout.

The local package is a candidate for READY_FOR_BOUNDED_LIVE_VALIDATION only
after its exact ZIP fresh-install/upgrade/HTTP and CI gates pass. No claim of
production recovery or real-provider throughput follows from mock tests.
