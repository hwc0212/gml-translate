# GML Translate 2.11.2-rc.11 — pagination review candidate

2026-11-24 (Asia/Shanghai). Development branch: codex/2.11.2-rc.5-review.
Core remains 0.9.22, pinned to f0e86e26fe1c82615d4945a9cb77c41459f1cc9d.

- [Installable candidate ZIP](gml-translate-2.11.2-rc.11.zip) · [SHA-256](gml-translate-2.11.2-rc.11.zip.sha256)
- [分页排查与验收报告](PAGINATION_REVIEW.md) · [机器可读验收结果](review-results.json) · [原始有效证据](evidence)
- [Translate PR #3](https://github.com/hwc0212/gml-translate/pull/3) · [Core PR #1](https://github.com/hwc0212/gml-translation-core/pull/1)
- [此前 rc.10 累计审查报告](../v2.11.2-rc.10/FULL_REVIEW.md)

434,920 bytes; 103 ZIP entries; all 48 Core files, source and lock verified.
SHA-256: 8b29584999fb63db9b8c89060a4db2e02f030430b47699371b9e85c4bc92344c.

## Result

Archive language navigation retains the actual page number. Explicitly noindex pagination consistently omits hreflang, while navigation remains available; indexable test archives use same-page alternates and self canonicals. SEOPress empty canonical output and pagination indexing policy are preserved.

The native WordPress/WooCommerce/SEOPress browser test followed all 16 source-to-target page-2 transitions and checked actual product IDs. Expanded native matrices passed 120 cases / 1,559 assertions. The existing database regression passed 122 scenarios / 2,331 assertions; 31 integration scripts passed, including 869 pagination assertions. The exact packaged runtime passed the same 869 assertions.

Normal language-preference Set-Cookie requests correctly bypass cache. A separate disposable fixture disabled only that cookie writer and verified 6 real cache-backed responses without changing product cache safety guards. No production cache or SEO policy was changed.

## Delivery and limits

This candidate and its acceptance evidence are supplied through PR #3 on the development branch for review. No merge, tag, GitHub Release or production deployment is part of this delivery. Core has no new changes this round. Exact GitHub CI status is recorded in the PR discussion.

review-results.json is the frozen local acceptance snapshot taken before commit/push. Its Git delivery flags describe that snapshot, not the later delivery state.

The native test stack was WordPress 7.1.4 / PHP 8.3.33 / WooCommerce 10.5.3 / SEOPress 9.7.4 / MariaDB 10.6. Production's reported WordPress 7.1.5 and unknown SEOPress version, production theme, LiteSpeed, Cloudflare and OPcache were not independently retested. Other SEO owners were not all reproduced natively. Dedicated NAS test containers were stopped. No paid AI, email or real inquiry was sent.
