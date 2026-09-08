# GML Translate Development Status

Last updated: 2026-09-08

## Current State

- Product version: `2.11.1-rc.18`
- Development branch: `codex/upgrade-lock-hotfix`
- Vendored Core version: `0.9.1`
- Vendored Core commit: `14e573d8e2997ca355a303f0c8b779cd46d4075a`
- Last committed Translate baseline before Phase 2D: `a2b51262eb9724ab4a32c6b06fc6f5a3c1fa5d61`
- Phase 2D Translate implementation commit: pending final local commit; record it here in the post-implementation status commit.

Phase 2C.1 is complete. Phase 2D implementation is feature-complete locally and is undergoing clean-room and release-package verification before the first user-operated RC Stop Gate.

## Phase 2D Scope

- Derived public eligibility in Core.
- Anonymous 302 source redirect for ineligible local translated routes.
- Authorized, visibly marked, noindex administrator preview.
- One decision shared by route, switcher, canonical, hreflang, and Sitemap.
- SEOPress-first integration, followed by Yoast and Rank Math.
- Independently listed and reciprocal eligible Sitemap clusters.
- Bounded URL/resource/status reads without URL x language N+1.

## Verification Completed

- Translation Core database suite: 92 scenarios on WordPress 7.1, PHP 8.3, MariaDB 10.11, root and `/ygnaglul` installations.
- Retention fixture: 130,000 historical queue rows and 52,000 Translation Memory rows preserved.
- Review and public-cluster query metrics: one bulk query per tested batch and installation context.
- GML Translate PHP lint and all integration tests, including runtime anonymous/admin Gate behavior and Sitemap namespace/eligibility behavior.
- Core vendor lock verification against the exact Core commit.
- Disposable Apache HTTP assertions: approved source/target 200, one canonical, reciprocal `en`/`es`/`x-default`, ineligible targets 302 to source, and two approved Sitemap entries.
- Gettext-to-Output-Buffer request-local readiness regression without additional database reads.
- No real AI request or external provider call was made.

## Current Blockers

- GitHub push and remote CI confirmation remain pending because the execution environment requires a direct in-chat authorization before networked Git writes, even though the project brief authorizes normal development-branch pushes.
- Actual browser acceptance on a cloned WordPress site is intentionally deferred to the first RC Stop Gate.

## Remaining Risks

- External-domain and cross-server language targets remain `external_unverified`; signed remote manifest exchange is not part of Phase 2D.
- The standalone fallback currently emits up to 1,000 resources per post type or taxonomy sitemap. Larger standalone sites need pagination before stable release; sites using SEOPress, Yoast, or Rank Math use their pagination.
- SEOPress contracts were checked against local source. Yoast and Rank Math contracts are covered by adapter tests and their public source contracts, but still require user-site browser validation.
- Rolling back to a pre-Phase-2D build removes the public Gate and can expose previously stored stale translations again. Disable Multilingual Site before downgrade, then verify routes and caches.

## Next Stage

Build and verify `2.11.1-rc.18`, then stop for user acceptance. After that acceptance, Phase 2E can design a reversible GML SEO to GML Translate migration using a clone, never the production database first.
