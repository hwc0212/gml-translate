# rc.34 protected-content validation

This candidate is development-only until the owner performs bounded site validation. Do not automatically deploy it, resume a queue, retry history, purge shared caches or change provider credentials.

## Changed behavior

Core 0.9.12 accepts explicit representation aliases: ASCII/fullwidth/Arabic percent signs; mm/cm/m and their Cyrillic equivalents; multiplication symbols between dimension values. Decimal comma normalization is retained. Numeric values, dimension order and unit scale must remain unchanged. No semantic guessing or unit conversions are performed.

Real printf directives retain type, positional identity, flags, width, precision, custom padding and escaped percent. Named positional directives may move with their identities; unnumbered argument order may not change. URLs, template names, HTML tokens and configured protected terms remain protected. This is a deterministic format guard, not a general semantic translator or an exhaustive product-model recognizer.

On a protected-content failure the existing worker marks the item failed once. Other items continue. Dedupe/split recovery reports the original input index rather than a local sub-batch index. The final rejected candidate never enters Translation Memory.

Needs Attention displays a source-bound private diagnostic: failed rule, source/candidate token and rejected candidate excerpt. URLs containing query/fragment/userinfo are represented by SHA256 identity digests; credential patterns are redacted. Only administrators can read it; candidate markup is escaped. Retention is bounded to 100 non-autoloaded records and 16,384 characters per candidate. This is diagnostic evidence, not durable translation storage. Old failures cannot be reconstructed when no candidate was retained.

## Owner validation after installation

1. Preserve the current ZIP and existing backup; install the exact candidate ZIP and verify rc.34/Core 0.9.12. Upgrade must not alter queue pause, provider settings, TM, glossary or publication policy.
2. Review one existing protected failure. An older attempt without candidate evidence must explicitly say so, not pretend to show its provider response.
3. If separately authorizing one paid retry, choose only one current source/language and inspect the resulting tokens. A truly changed protected value must remain Needs Attention after one attempt; no automatic three-call burn.
4. Verify the existing manual/AI/Keep source actions on a designated safe fixture. Do not approve critical production content merely to pass a test.
5. Verify source and target page output separately from CDN cache freshness. Never clear all translation assets to test this change.

## Rollback and limitations

No schema migration or renamed options/tables. Roll back to the retained rc.33 ZIP; older code ignores private diagnostic options. Translation assets and source decisions remain in their existing stores.

The local mock tests prove deterministic validation and workflow safety, not real-provider translation quality. Production legacy failures still need a faithful manual edit, an explicit source decision where appropriate, or a separately authorized current-item retry. They are not automatically rewritten or silently reclassified.

## External cache boundary (not changed)

The durable outbox is GML_Page_Cache::pending_clusters(). GML_Resource_Cache_Worker::run() consumes it only through the gml_resource_cluster_cache_adapters filter. No configured callable adapter yields adapter_required; a false/throwing adapter leaves retry_pending. acknowledge_cluster() requires successful exact-URL work at every configured layer. An internal generation bump cannot itself purge Cloudflare/Nginx.

For a production stale-cache case, inspect the worker status and exact adapter receipt first. Configure or repair only the missing exact-URL adapter and acknowledge only confirmed URLs. This round does not establish which external adapter is installed on a live server and performs no cache purge.
