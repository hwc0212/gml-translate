# Shared Translation Core

GML SEO and GML Translate use one build-time source package:
`https://github.com/hwc0212/gml-translation-core`.

The released WordPress ZIPs remain fully self-contained. Users do not install
Core and do not need Composer, npm, a submodule, or another WordPress plugin.

## Source And Vendor Rules

- Core source contains product-neutral translation logic only.
- WordPress menus, product settings, lifecycle hooks, and final SEO rendering
  remain in each product adapter.
- Vendored files are committed so GitHub source ZIPs can be installed directly.
- Do not edit files below the lock's `vendorRoot`.
- `translation-core.lock.json` records the exact Core version, commit, and
  SHA-256 hash of every vendored file.

## Verify

```bash
php bin/translation-core.php verify
php bin/translation-core.php verify --source=/path/to/gml-translation-core
```

The first command detects local vendor drift. The second also proves that the
checked-out Core source is the exact locked commit.

## Update

Commit the Core change first, then run in each product:

```bash
php bin/translation-core.php sync \
  --source=/path/to/gml-translation-core \
  --update-lock
bash tests/run-all.sh
```

Review the lock and generated diff together. A product release must never update
only one vendored copy.
