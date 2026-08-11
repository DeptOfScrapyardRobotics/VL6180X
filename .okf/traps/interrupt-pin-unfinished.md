---
type: Trap
title: Interrupt pin unfinished
description: Setting interrupt_pin makes readRange() return -1 — leave it null and use I2C status polling.
tags: [traps, interrupt, gpio, unfinished]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T02:00:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: api
    resource: src/Concerns/VL6180XAPI.php
    title: readRange interrupt branch
  - id: ic
    resource: src/VL6180X.php
    title: interrupt_pin ctor arg
  - id: readme
    resource: README.md
    title: README interrupt guidance
---

# Trap

Do **not** pass `$interrupt_pin` expecting hardware INT-driven ranging. When `interrupt_pin` is non-null, `readRange()` skips the I2C poll path and returns `-1` (stub `@todo` — blocked until register values are broken out).[^api][^readme]

# Correct use

Leave `interrupt_pin` **null** (default). Hot path: `startMeasurement()` → `statusWait()` (I2C `RESULT_INTERRUPT_STATUS_GPIO`) → `readRangeValue()` → `clearInterrupt()`.[^api]

`interrupt_pin_config` (register knob) is separate — that configures the chip’s GPIO interrupt register over I2C and is applied at boot; it does not enable the unfinished PHP INT-pin branch.[^ic]

# Related

* [VL6180X IC](../core/vl6180x.md)
* [XSHUT recommended](xshut-recommended.md)

[^api]: readRange interrupt branch
[^ic]: interrupt_pin ctor arg
[^readme]: README interrupt guidance
