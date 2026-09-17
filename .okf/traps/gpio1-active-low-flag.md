---
type: Trap
title: GPIO1 active_low flag
description: Requesting the GPIO1 input with active_low true inverts the level the driver compares against gpio1_mode polarity.
tags: [trap, gpio1, digital, polarity]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: api
    resource: src/Concerns/VL6180XAPI.php
    title: VL6180XAPI::lineAsserted()
---

# Trap

Driver treats line asserted when `read() === gpio1_mode->active_high`, edges chosen same way.[^api] `DigitalIO::...->input(..., active_low: true)` → libgpiod inverts → idle looks asserted, wait path wrong.

# Use instead

`input($device, $pin)` with default `active_low` false. Change polarity via `gpio1_mode = new VL6180XGPIO1Mode(active_high: true)`.

[^api]: VL6180XAPI::lineAsserted()
