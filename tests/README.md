# GML Translate Tests

Run the complete deterministic CLI suite from the repository root:

```bash
bash tests/run-all.sh
```

The runner lints every PHP file, runs each `tests/integration/test-*.php`
script, parses every JavaScript asset with Node, and exits non-zero on the
first failure. The mock tests are intentionally dependency-free; real
WordPress routing, subdirectory, cache, WooCommerce, and plugin-coexistence
coverage is maintained as a separate integration layer.

Every regression test must print `OK <name>` on success and use
`gml_test_assert()` (or an equivalent helper) so failures produce a non-zero
exit code.

Form UI regressions cover textarea raw/default content, exclusions, named submit
values, quoted/unquoted attributes and byte-preserving CF7-shaped HTML.

Core 0.9.14 adds a real-database inventory recovery scenario to its required suite.
Its optional `tests/database/form-ui-cf7.php` must be run separately against a
verified CF7 package in a disposable WordPress installation. It exercises native
form generation, manual TM lookup, manifest discovery and four-language rebuilds;
it does not submit forms, send mail, or call translation providers.
