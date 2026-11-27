---
name: filament-qr-code-collecting
description: Collect many scanned codes in a Filament v5 form or table action with mmuqiitf/filament-qr-code. Use for QrCollector, QrCollectAction, quantity, CSV import, and maxItems.
---

# Collecting

Read `vendor/mmuqiitf/filament-qr-code/docs/collecting.md`.

- `QrCollector` stores a list on the form. `QrCollectAction` opens the same camera in a table action and does not run `itemRules()` or `gs1()`.
- `->quantity()` switches state to `{code, qty}`. `->maxItems()` caps the list in the browser and on submit.
- `->csv()` imports and exports rows through the same normalizer, GS1 check, and item rules as a scan.
- `->gs1(require: [...])` stores the bracketed label. There is no `fill` on the collector.
