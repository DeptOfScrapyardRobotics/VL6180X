---
type: Trap
title: Wait-path fallback
description: A wired GPIO1 is ignored unless gpio1_mode is an interrupt output and interrupt_config raises ranging on NEW_SAMPLE_READY.
tags: [trap, gpio1, polling, interrupts]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: api
    resource: src/Concerns/VL6180XAPI.php
    title: VL6180XAPI::interruptLine()
---

# Trap

`interruptLine()` null → polling, no error.[^api] E.g. `interrupt_config` range `OUT_OF_WINDOW` → still polls status for new samples.

# Check

`$tof->interruptLine()` non-null → edge path active.

[^api]: VL6180XAPI::interruptLine()
