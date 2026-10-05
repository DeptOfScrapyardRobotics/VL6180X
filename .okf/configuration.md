---
type: Reference
title: Wiring config
description: circuits.vl6180x keys merged by the provider and the vl6180x-config publish tag.
tags: [config, provider, publish, circuits]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T01:30:00Z }
sources:
  - id: provider
    resource: src/Providers/VL6180XServiceProvider.php
    title: VL6180XServiceProvider
  - id: config
    resource: config/vl6180x.php
    title: config/vl6180x.php
---

# Merge + publish

`register()` merges `config/vl6180x.php` under `circuits.vl6180x`; app values win.[^provider][^config] `boot()` publishes to `config/circuits/vl6180x.php`, tag `vl6180x-config`; `circuit` bound → `addCircuit('vl6180x')`. `conjure()` passes the keys to `i2c()`.

# Keys

| Key | Default |
|---|---|
| `default_config` | `'i2c'` |
| `configs.i2c.driver` / `device` | `'none'` / `''` |
| `configs.i2c.slave` | 0x29 |
| `configs.i2c.gpio1` | `enabled` false, `driver` none, `device` '', `pin` 0 |
| `configs.i2c.xshut` | same, `pin` 1 |
| `configs.i2c.boot_now` | true (factory default) |

[^provider]: VL6180XServiceProvider
[^config]: config/vl6180x.php
