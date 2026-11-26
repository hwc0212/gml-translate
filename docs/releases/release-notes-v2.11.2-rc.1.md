# GML Translate 2.11.2-rc.1 — local review candidate

This candidate fixes static form UI discovery and resource-inventory recovery.
It is not a published release or a production deployment.

## Behavior

- Textarea placeholder/ARIA attributes and unnamed input submit captions enter
  normal discovery, Translation Memory lookup and resource manifests.
- Textarea bodies, user/hidden input values, form destinations and named submit
  business values remain protected. Existing exclusion rules still apply.
- Failed discovery remains pending. Atomic claims preserve edits arriving while
  rendering; stale/unknown/render-error manifests can recover even when the
  dirty hint or paid queue is empty. Existing maintenance events own recovery.
- Partial readiness writes keep the resource private and retryable; a committed
  manifest is not proof that every language snapshot was saved.
- Backfill checkpoints cannot overwrite a newer pause/reset. Failed resources
  remain retryable and a wakeup is persisted before rendering.
- Review detail includes inventory state, phase/cursor, pending count, lock expiry,
  safe error codes and concrete readiness mismatch codes.

No schema migration, provider call, queue resume, translation deletion, threshold
change or forced approval is part of the upgrade. GML SEO is not upgraded.

## Validation and deployment boundary

See the dated local delivery report for actual run results. The new required
database scenario covers metadata-only edits, transient failures, concurrent
edits and empty-hint recovery. The optional CF7 scenario uses native rendering
and manual TM, with no submission/mail/provider request.

Discovery of additional form UI adds required hashes. Existing automatically
enabled translation workflows may enqueue these when a site later rescans.
Before production rollout, preserve the current plugin and data, inspect the
site's queue/AI controls, prepare the intended UI translations and perform
scoped discovery. Do not infer production recovery from CLI or logged-in preview.

Rollback restores the previous plugin files and existing data; do not uninstall
or delete translations. The new diagnostic option and empty dirty hint are
backward compatible. Production cache and anonymous canonical/hreflang/indexing
checks remain site-specific rollout work.
