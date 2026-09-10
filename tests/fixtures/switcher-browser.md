# Switcher UI Regression

Run from the repository root, optionally in a read-only PHP Docker container:

```sh
php -S 127.0.0.1:18824 tests/fixtures/switcher-browser.php
```

The fixture uses real renderer, URL helpers, CSS and JS. It has no database, credentials or provider calls. Its header deliberately uses `overflow:hidden` and a fixed height. A local script binds focus/blur/click handlers before GML initialization and asserts the navigation ancestor contract used by GeneratePress. All default cases must produce no console errors. Optional `&theme=gp` loads the actual CNXHE GeneratePress 3.6.1 script for live compatibility verification; this optional case requires network access.

At widths 1440, 768 and 390, open `/?case=all` and `/staging/?case=all`. Verify collapsed/expanded ARIA, five alternatives (four local plus one external), body-level portal placement, hit-testing every link, no header height change, no horizontal overflow, and outside-click closing. The portal must retain a `nav.main-nav` ancestor without inheriting the header's dimensions or clipping. The external example is a fixture destination, not a live site.

Keyboard: Enter/Down opens and focuses the first link; Up opens at the last; arrows move between links; Escape closes and returns to the trigger. Shift+Tab from the first returns to the trigger; Tab from the last reaches Contact after the trigger.

Open at 390px, then resize to 320px. The fixture hides the trigger at this breakpoint; the teleported panel must close instead of remaining at the top-left of the page. Resize back to 390px and confirm normal opening still works.

`?case=none` has no configured targets and must show a static EN label without a button or empty panel. `?case=incomplete` shows four unavailable local items plus a clickable external destination. `?case=unavailable` shows four unavailable local items and no anchors, but must still open. Click or Enter on an unavailable item must not navigate; arrow keys, Tab and Escape must work across both links and unavailable statuses.

`/ru/?case=complete` shows current RU, clickable EN/external, and three unavailable local items. PHP integration tests cover disabled/invalid external configuration, local eligibility, button-list parity, 404 and root/subdirectory URLs. The SEO public URL list must remain unchanged.

Use `?case=incomplete&low=1` at 390x640 to test a trigger near the bottom: the panel should fit above or scroll within the available viewport. All rows must remain readable and reachable.
