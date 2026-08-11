---
type: Module
title: Package (0.7)
description: dept-of-scrapyard-robotics/vl6180x Composer identity, namespace, and discovery.
resource: composer.json
tags: [orientation, package, 0.7, vl6180x]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T02:00:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: Package composer.json
  - id: provider
    resource: src/Providers/VL6180XServiceProvider.php
    title: VL6180XServiceProvider
  - id: gitattributes
    resource: .gitattributes
    title: Dist export-ignore
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `dept-of-scrapyard-robotics/vl6180x` **0.7.0** |
| PHP | `^8.4\|^8.5\|^8.6` |
| Namespace | `DeptOfScrapyardRobotics\Sensors\VL6180X\` → `src/` |
| Provider | `DeptOfScrapyardRobotics\Sensors\VL6180X\Providers\VL6180XServiceProvider` |
| Catalog slug | `vl6180x` |
| Branch alias | `dev-master` → `0.7.x-dev` |

# Requires

| Package | Constraint |
|---------|------------|
| `scrapyard-io/gpio-framework` | `^0.7.0` |

**No** `scrapyard-io/tubes` — this is a sensor package, not a display driver.[^composer]

Suggested (optional): `microscrap/i2c`, `microscrap/mpsse` at `^0.7.0`.[^composer]

# Discovery

`extra.scrapyard-io.providers` lists `Providers\VL6180XServiceProvider`. That provider registers catalog IC `vl6180x` only (no package-local make-profile / smoke sketch).[^provider]

# Dist

`.okf/` and `AGENTS.md` are `export-ignore` — Composer dist tarballs omit them.[^gitattributes]

# Related

* [VL6180X IC](../core/vl6180x.md)
* [Circuits integration](../core/circuits.md)

[^composer]: Package composer.json
[^provider]: VL6180XServiceProvider
[^gitattributes]: Dist export-ignore
