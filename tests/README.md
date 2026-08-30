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
