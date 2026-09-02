=== GML Translate ===
Contributors: huwencai
Tags: translate, multilingual, ai, hreflang, language switcher
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.11.1-rc.13
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI multilingual translation for WordPress with stable language URLs, editable translations, glossary, queue controls, hreflang, and sitemap integration.

== Description ==

GML Translate is a standalone multilingual plugin focused on stable WordPress language routing and maintainable AI-assisted translation.

Multilingual Site and AI Translation are separate states. Removing a provider key, exhausting quota, pausing the queue, or disabling AI Translation only stops new AI work. Existing translated pages, saved translations, language URLs, switchers, manual translations, hreflang, and sitemap variants remain available.

= Features =

* Language-prefixed WordPress routes, subdirectory-safe URL handling, and external domain/subdomain language links.
* Google Gemini, DeepSeek, Qwen, and OpenAI translation providers.
* Bounded, resumable queue with locks, failure states, circuit breaking, and per-language manual recovery.
* Translation memory, current-page lookups, manual translation editor, glossary, protected terms, and exclusion rules.
* Content crawler that runs only in admin/WP-Cron and uses signed same-site requests.
* Menu, shortcode, widget, and automatic language switcher positions with theme, outline, and solid appearance presets.
* Browser-language detection.
* Translated metadata, self-referencing canonical behavior, hreflang, and multilingual sitemap relationships.
* Redis/Memcached-safe cache generation invalidation without whole-site cache flushes.

= Relationship with GML AI SEO =

If you only need multilingual translation, use GML Translate.

If you want the complete GML SEO suite with technical SEO, search data and AI SEO workflow, use GML SEO.

GML SEO already includes multilingual translation, so installing both is normally unnecessary.

When both are active, GML Translate keeps the multilingual runtime until an administrator confirms handover, while GML SEO owns final canonical, meta, Open Graph, Schema, hreflang, and sitemap output. Existing data is never silently deleted.

== Installation ==

1. Upload and activate GML Translate.
2. Select source and destination languages.
3. Enable Multilingual Site and verify language routes before using AI.
4. Save a key for Gemini, DeepSeek, Qwen, or OpenAI, then enable AI Translation if new translations are needed.
5. Translate one language and a small page sample first.
6. Review glossary, links, layout, forms, canonical, hreflang, and sitemap before crawling the full site.

== Frequently Asked Questions ==

= Do translated pages disappear when the API key is removed? =

No. Existing multilingual output remains available. Only new AI translation work stops.

= Does the frontend call Gemini or DeepSeek? =

No. Ordinary frontend requests only perform routing, cache and translation lookups, lightweight rendering, and minimum multilingual SEO output.

= Can I edit AI translations? =

Yes. Manual translations are stored separately and take priority over normal automatic work.

= Is GML Translate a complete SEO plugin? =

No. It provides only the minimum multilingual SEO output. Use GML AI SEO or another single SEO authority for complete SEO management.

= What happens to my translations if I delete the plugin? =

All data is retained by default, including saved and manual translations, settings, glossary, queue, and encrypted provider credentials. To remove everything, first select permanent removal under Settings > Uninstall Data Retention and type DELETE exactly. Deactivation and normal updates never delete stored data.

== Changelog ==

= 2.11.1-rc.13 =
* Updates Translation Core to 0.7.0 with additive resource manifests, source-hash relationships, and per-resource/language machine-readiness snapshots.
* Requires all critical SEO hashes and at least 95% of the current resource manifest without treating manual Translation Memory as human publication approval.
* Uses bounded, signed, cookie-free, same-install HTTP 200 HTML renders and fails closed as render_error.
* Invalidates only an edited resource; global presentation changes advance one generation and schedule five-resource background batches.
* Keeps discovery and readiness operational with AI disabled and never starts paid AI work during shadow backfill.
* Adds object-aware single and bulk provider APIs without changing public canonical, hreflang, Sitemap, switcher, or anonymous access.
* Verifies one query for one resource and two indexed bulk queries for 1000 resources across root and subdirectory installations.

= 2.11.1-rc.12 =
* Updates Translation Core to 0.6.2 with atomic database compare-and-swap leases and one deferred rewrite refresh after routing imports.
* Prevents competing stale takeovers and prevents an expired or wrong owner from releasing a newer lock.
* Restricts processing-row recovery and late provider-result writes to the current queue owner.
* Preserves legacy lock values and makes no schema, translation, provider, URL, readiness, or multilingual SEO changes.
* Makes the language-switcher shortcode safe inside another plugin's output-buffer callback through a shared string renderer.

= 2.11.1-rc.11 =
* Retains all plugin data by default when the plugin is deleted, allowing a later reinstall to reuse it.
* Adds an explicit permanent-removal preference protected by an exact typed DELETE confirmation.
* Removes translation tables, legacy tables, settings, credentials, glossary, jobs, and dedicated cache only during confirmed WordPress uninstall.
* Protects shared translation data while GML AI SEO remains installed and handles multisite preferences per site.
* Hides every switcher surface and multilingual discovery tag on real 404 responses instead of manufacturing translated 404 links.
* Verifies reciprocal hreflang for index-ready pages while incomplete translations remain noindex and unadvertised.

= 2.11.1-rc.10 =
* Adds per-language local or external-site delivery for independent domains and subdomains.
* Supports same-path mapping or a safe external-homepage fallback without copying query strings across domains.
* Keeps external languages out of local rewrite rules, crawling, glossary work, and AI translation queues.
* Adds switcher appearance presets and automatic, left, or right dropdown alignment.
* Emits external hreflang only where a valid equivalent can be represented; remote sites are never modified.

= 2.11.1-rc.9 =
* Tracks published source changes incrementally without starting AI or resuming a paused queue.
* Adds Qwen and OpenAI alongside Gemini and DeepSeek with separate encrypted credentials.
* Reduces token use through duplicate removal and relevant-only glossary and protected-term context.
* Preserves and locally reuses technical-only values such as comparison limits and dimensions.

= 2.11.1-rc.8 =
* Classifies historical and current translation failures without changing existing tables.
* Uses bounded automatic cooldown for HTTP 429, 5xx, network and timeout failures without consuming item attempts.
* Keeps configuration failures safety-paused and failed retries limited to 25 items per language.
* Adds redacted recent-failure details and prevents a small historical tail from suppressing an otherwise complete language.

= 2.11.1-rc.3 =
* Fail locally on unreadable saved credentials and verify encrypted writes by reading them back.
* Separate local Save Changes from Test Saved AI Connection; neither resumes AI work.
* Preserve legacy encrypted keys, translation tables, multilingual routes and existing translations.
* Candidate only: real provider access and production recovery still require verification.

= 2.11.1-rc.2 =
* Separate content discovery from AI processing and report scheduled versus active batches accurately.
* Refresh rendered-page cache without deleting translation memory, manual edits or queued work.
* Preserve technical dimensions, operators and SKU symbols in translated text, titles, metadata and alt text.
* Release candidate only. Missing site translations still require bounded validation and completion.

= 2.11.1-rc.1 =
* Release candidate for staging verification, not a production-verified stable release.
* Prevent blocking translation upgrades: no frontend migration, no legacy-table ALTER or automatic deduplication, nonblocking setup lock and failure recovery.
* Restore explicit Auto-Translate start from an ordinary pause while retaining provider, sample, permission and scheduling safeguards.
* Preserve existing translation data and add real WordPress 7.1 / MariaDB large-queue and concurrency regression tests.

= 2.11.0 =

* Renamed the public product to GML Translate while preserving its folder, text domain, options, tables, and URLs.
* Adopted a locked, build-time vendored shared Translation Core with no runtime dependency.
* Separated multilingual availability from AI translation.
* Added safe dual-plugin behavior and deferred final SEO output to GML AI SEO when both are active.
* Centralized secure provider transport, bounded queue/crawler work, current-page lookups, and Redis-safe invalidation.
* Expanded regression coverage for state, routes, subdirectories, output ownership, transport, queue/cache, parser, crawler, installer, rules, and admin registration.

See CHANGELOG.md and GitHub Releases for complete release history.
