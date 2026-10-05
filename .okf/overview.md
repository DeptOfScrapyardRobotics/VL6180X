---
type: Package
title: dept-of-scrapyard-robotics/vl6180x
description: VL6180X time-of-flight driver for scrapyard-io/framework 0.10 — identity, requires, classes, conjure, boot, errors.
resource: composer.json
tags: [vl6180x, tof, range, i2c, package]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T01:30:00Z }
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

`dept-of-scrapyard-robotics/vl6180x` 0.10.0, alias `dev-main` → `0.10.x-dev`, namespace `DeptOfScrapyardRobotics\Sensors\VL6180X\`.[^composer] Requires split components only: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-voyager/nuts-and-bolts`, `venusian-voyager/vessel` (`ControlPanel`). Catalog slug `vl6180x`. No waveforms; distance = `int` mm.

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

`conjure('vl6180x')` / `VL6180X::i2c(...)`, or `new VL6180X($transport, $config, boot_now: true)` / `boot()`; runs once.[^bootstrap]

1. XSHUT low 2 ms → high 2 ms, when wired.
2. Model ID `0x000` must read `0xB4`.
3. `SYSTEM__FRESH_OUT_OF_RESET` = 1 → 30 private ST AN4545 writes, then flag ← 0. A later boot on the same power cycle skips them, as intended; with XSHUT wired every boot resets the chip, so the flag reads 1 and the full sequence runs. Setting `fresh_out_of_reset = true` by hand makes the next boot rewrite them.
4. Public settings from configuration: GPIO1 mode, averaging, ALS gain, VHV repeat, ALS integration, VHV recalibrate, range period, ALS period, interrupt config.

# Errors

`VL6180XException` → `CircuitException` → `GPIOLevelException`.[^exception] Wrong model ID, refused/short read, measurement timeout, setting out of range, no bus or pin from the driver (`notConnected`), unknown property or configuration key. Bench, FT232H with XSHUT and GPIO1: boot 181–191 ms, model `0xB4`.

[^composer]: Package manifest
[^chip]: VL6180X
[^bootstrap]: VL6180XBootstrap
[^exception]: VL6180XException
