---
type: Trap
title: XSHUT recommended
description: Pass DigitalOutputPin as shutdown_pin so boot can pulse XSHUT; without it, resetDevice is a no-op.
tags: [traps, xshut, shutdown, boot, digital]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T02:00:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: internal-api
    resource: src/Concerns/VL6180XInternalAPI.php
    title: resetDevice / _boot
  - id: ic
    resource: src/VL6180X.php
    title: shutdown_pin factory args
  - id: readme
    resource: README.md
    title: README XSHUT guidance
---

# Trap

`shutdown_pin` (XSHUT) is **optional in the type signature** but **strongly recommended** for a clean boot. When it is null, `resetDevice()` does nothing and boot continues straight to model-id confirm + settings.[^internal-api][^readme]

# Correct use

Pass a `GeneralPurposeIO\Digital\DigitalOutputPin` into `i2c()` / `fromI2CBus()` / the constructor as `$shutdown_pin`. Boot then pulses low → high (~2 ms each) before `confirmDeviceId()`.[^internal-api][^ic]

XSHUT is **not** a `#[Pinout]` key — wire it in application code (or profile extras), separate from I2C `driver` / `device` / `slave`.

On shared-context USB I2C (MPSSE), closing the sensor closes digital pins then the transport — do not keep using a pin after `close()`.

# Related

* [VL6180X IC](../core/vl6180x.md)
* [Interrupt pin unfinished](interrupt-pin-unfinished.md)

[^internal-api]: resetDevice / _boot
[^ic]: shutdown_pin factory args
[^readme]: README XSHUT guidance
