# GML Translate 2.11.2-rc.12 — comparison text and breadcrumb review candidate

2026-11-25 (Asia/Shanghai). Development branch: codex/2.11.2-rc.5-review.
Core 0.9.23 is pinned to 83e5d9fda37113cf4249313e467452f6a420506a.

- [Installable candidate ZIP](gml-translate-2.11.2-rc.12.zip) · [SHA-256](gml-translate-2.11.2-rc.12.zip.sha256)
- [本轮修复与验收报告](BREADCRUMB_COMPARISON_REVIEW.md) · [验收 JSON](review-results.json) · [原始有效证据](evidence)
- [最小测试主题截图](evidence/native-de-manual.png)
- [Translate PR #3](https://github.com/hwc0212/gml-translate/pull/3) · [Core PR #1](https://github.com/hwc0212/gml-translation-core/pull/1)

438,149 bytes; 104 ZIP entries; all 49 Core files, source and lock verified.
SHA-256: b3208778cf010c260133ad366c6ba71494b816c1f4ed451025d491121a275609.

The candidate preserves whole text nodes containing raw comparison signs and localizes only allowlisted BreadcrumbList name/page-URL string spans. Other schema nodes, graph identifiers, numeric literal precision and ordinary scripts remain untouched. Existing manual translation protection, readiness gates, pagination policy and URL exclusions remain in place.

Local acceptance passed 250 new focused assertions, 32 integration scripts, 133 PHP syntax checks, 122 database scenarios / 2,342 assertions and 14 state scenarios / 106 assertions. Native WordPress/WooCommerce tests passed 12 anonymous pages plus 3 logged-in pages / 360 checks; all 16 pagination transitions retained the actual second-page product IDs. Missing, restored and second-manual-title states each passed two HTTP reads. The exact ZIP runtime passed the 250 focused and 869 pagination assertions.

Native WooCommerce generated its real schema. The SEOPress modern breadcrumb shape was a contract fixture, not a full SEOPress Pro runtime test. Test stack: WordPress 7.1.4 / PHP 8.3.33 / WooCommerce 10.5.3 / SEOPress Free 9.7.4 / MariaDB 10.6. Production theme, TM records, reported WordPress 7.1.5, external caches and all third-party schema builders were not independently retested. The dedicated NAS containers were stopped.

review-results.json retains the local acceptance snapshot taken before commit/push. Its push flags describe that frozen snapshot; GitHub delivery and exact current CI status are recorded in the two PRs. Earlier rc.11 field retesting is historical, limited evidence and is not rc.12 production acceptance.

This package and report are supplied on the development branch for review. No merge, tag, GitHub Release or deployment is part of this delivery. No production access, paid AI, email or real inquiry occurred. No schema/TM migration, all-site retranslation/rebuild or cache purge was performed. Previously unseen breadcrumb labels follow existing translation and pause settings; no missing translation is fabricated or allowed to bypass manual protection.
