---
type: Trap
title: Range error before value
description: readRange() returns RESULT__RANGE_VAL even when RESULT__RANGE_STATUS reports an error; the value is then not a distance.
tags: [trap, range, status]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: api
    resource: src/Concerns/VL6180XAPI.php
    title: VL6180XAPI::readRange()
---

# Trap

`readRange()` throws only on timeout.[^api] Error code (no target, signal-to-noise, overflow…) not checked.

# Use

`$tof->range_status->error === VL6180XRangeError::NONE` before trusting value.

[^api]: VL6180XAPI::readRange()
