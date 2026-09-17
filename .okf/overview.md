---
type: Package
title: dept-of-scrapyard-robotics/vl6180x
description: VL6180X time-of-flight driver for scrapyard-io/framework 0.8 — identity, requires, classes, boot, errors.
resource: composer.json
tags: [vl6180x, tof, range, i2c, package]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: composer
    resource: composer.json
    title: Package manifest
  - id: chip
    resource: src/VL6180X.php
    title: VL6180X
  - id: bootstrap
    resource: src/Concerns/VL6180XBootstrap.php
    title: VL6180XBootstrap
  - id: exception
    resource: src/VL6180XException.php
    title: VL6180XException
---

# Identity

`dept-of-scrapyard-robotics/vl6180x` 0.8.0, namespace `DeptOfScrapyardRobotics\Sensors\VL6180X\`.[^composer] Requires split components only: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-voyager/nuts-and-bolts`. No waveforms; distance = `int` mm.

# Classes

| Class | Role |
|---|---|
| `VL6180X` | `Bootable` + `Sensor`; `transport()`, `config()`, `close()`[^chip] |
| `VL6180XConfiguration` | boot values + last value read/written |
| `Transports\VL6180XI2CTransport` | 16-bit register I/O, GPIO1 + XSHUT pins |
| `Concerns\VL6180XAPI` | getters, setters, ranging |
| `Concerns\VL6180XBootstrap` | `__get` / `__set`, `_boot` |
| `Breakouts\*` | `GPIO1Mode`, `InterruptConfig`, `InterruptStatus`, `RangeStatus` |
| `Providers\VL6180XServiceProvider` | config merge + publish |

# Boot

`new VL6180X($transport, $config, boot_now: true)` or `boot()`; runs once.[^bootstrap]

1. XSHUT low 2 ms → high 2 ms, when wired.
2. Model ID `0x000` must read `0xB4`.
3. `SYSTEM__FRESH_OUT_OF_RESET` = 1 → 30 private ST AN4545 writes, then flag ← 0.
4. Public settings from configuration: GPIO1 mode, averaging, ALS gain, VHV repeat, ALS integration, VHV recalibrate, range period, ALS period, interrupt config.

# Errors

`VL6180XException` → `CircuitException` → `GPIOLevelException`.[^exception] Wrong model ID, refused/short read, measurement timeout, setting out of range, unknown property or configuration key.

[^composer]: Package manifest
[^chip]: VL6180X
[^bootstrap]: VL6180XBootstrap
[^exception]: VL6180XException
