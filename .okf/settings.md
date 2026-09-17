---
type: Reference
title: Settings
description: VL6180XConfiguration defaults, read-then-record getters, write-then-record setters, property table and register breakouts.
tags: [settings, configuration, registers, breakouts, als]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: configuration
    resource: src/VL6180XConfiguration.php
    title: VL6180XConfiguration
  - id: api
    resource: src/Concerns/VL6180XAPI.php
    title: VL6180XAPI
  - id: bootstrap
    resource: src/Concerns/VL6180XBootstrap.php
    title: VL6180XBootstrap
---

# Configuration

Defaults = ST AN4545 public values.[^configuration] `get()` / `set()`; unknown key → `invalidProperty`.

| Key | Default | Register |
|---|---|---|
| `gpio1_mode` | `VL6180XGPIO1Mode` → 0x10 | 0x011 |
| `averaging_sample_period` | 0x30 | 0x10A |
| `als_gain` | `GAIN_1` → 0x46 | 0x03F |
| `vhv_repeat_rate` | 0xFF | 0x031 |
| `als_integration_period` | 0x63 (ms − 1) | 0x040, 2 bytes |
| `range_intermeasurement_period` | 0x09 ((n+1)×10 ms) | 0x01B |
| `als_intermeasurement_period` | 0x31 | 0x03E |
| `interrupt_config` | `VL6180XInterruptConfig` → 0x24 | 0x014 |

# Read / write

Chip readable → getter reads chip, records value in configuration. Setter writes chip, then configuration.[^api] Ranges: bytes 0–255, periods 0–254, integration 0–463; out of range throws before any write. ALS gain written `0x40 | gain` (dark-gain nibble fixed). `recalibrate()` → 0x02E ← 0x01.

Properties:[^bootstrap] read-write `fresh_out_of_reset` + every configuration key; read-only `device_id`, `interrupt_status`, `range_status`, `range`.

# Breakouts

| Breakout | Bits |
|---|---|
| `VL6180XGPIO1Mode` | 5 polarity, 4:1 = 1000 interrupt output |
| `VL6180XInterruptConfig` | 5:3 ALS, 2:0 range (`VL6180XInterruptMode`) |
| `VL6180XInterruptStatus` | 7:6 error, 5:3 ALS, 2:0 range |
| `VL6180XRangeStatus` | 7:4 `VL6180XRangeError`, 0 device ready |

[^configuration]: VL6180XConfiguration
[^api]: VL6180XAPI
[^bootstrap]: VL6180XBootstrap
