---
type: Module
title: Circuits integration
description: Catalog registration for VL6180X via Circuit::addCircuit.
resource: src/Providers/VL6180XServiceProvider.php
tags: [circuits, catalog, vl6180x]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T02:00:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: provider
    resource: src/Providers/VL6180XServiceProvider.php
    title: VL6180XServiceProvider
  - id: ic
    resource: src/VL6180X.php
    title: VL6180X Pinout / factories
---

# Role

This package **owns the VL6180X chip driver** and registers it with gpio-framework Circuits. Registry / fluent / profile **semantics** live in `scrapyard-io/gpio-framework` — open that package’s `.okf` for `CircuitRegistry`, `PendingCircuit`, and `circuit:make-profile` behavior.

# Catalog

On `boot()`:[^provider]

```php
Circuit::addCircuit('vl6180x', VL6180X::class);
```

Slug: **`vl6180x`** (string literal — no package catalog enum).

# Profiles

Use gpio-framework’s generic scaffolder after publishing Circuits config:

```bash
workshop vendor:publish --tag=gpio-circuits-config
workshop circuit:make-profile   # pick vl6180x when installed
```

`#[Pinout]` prompts I2C `driver` / `device` / `slave` only. Wire optional `shutdown_pin` / `interrupt_pin` in application code or profile extras — they are not Pinout keys.[^ic]

```php
Circuit::ic('vl6180x')->i2c(/* … */)->make();
// or Circuit::profile('…') when a recipe ic => vl6180x exists
```

This package does **not** ship a package-local make-profile command or smoke sketch.

# Related

* [VL6180X IC](vl6180x.md)
* [Package (0.7)](../orientation/package.md)

[^provider]: VL6180XServiceProvider
[^ic]: VL6180X Pinout / factories
