---
type: Guide
title: Ranging
description: readRange() and its step helpers, the GPIO1 edge wait versus status-register polling, and result status breakouts.
tags: [range, measurement, interrupts, gpio1, polling]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: api
    resource: src/Concerns/VL6180XAPI.php
    title: VL6180XAPI
  - id: status
    resource: src/Breakouts/VL6180XInterruptStatus.php
    title: VL6180XInterruptStatus
  - id: range-status
    resource: src/Breakouts/VL6180XRangeStatus.php
    title: VL6180XRangeStatus
---

# readRange

`readRange(int $timeout_ms = 1000): int` → mm. Steps: `startMeasurement()` (`SYSRANGE__START` ← 0x01) → `statusWait()` → `readRangeValue()` (`RESULT__RANGE_VAL`) → `clearInterrupt()` (`SYSTEM__INTERRUPT_CLEAR` ← 0x07).[^api] Timeout → `measurementTimedOut`. `$tof->range` = `readRange()`. ~50 ms each on FT232H.

# Wait path

`interruptLine()` → GPIO1 input only when all hold:[^api]

* transport has GPIO1
* `gpio1_mode->interrupt_output`
* `interrupt_config->range === NEW_SAMPLE_READY`

else null → poll.

| | `statusReady()` | `statusWait()` |
|---|---|---|
| line | idle → false, no bus read; asserted → one `RESULT__INTERRUPT_STATUS_GPIO` read | `listen(remaining, rising=active_high, falling=!active_high)`, recheck |
| no line | status read | status read every 1 ms |

FT232H measured: line path 1 status read / range, poll path ~7; both < 40 ms (MPSSE samples pin over USB → less traffic, not less latency).

Asserted = `read() === gpio1_mode->active_high`. Level checked before listening → edge before listen not lost. Polarity + routing come from configuration, not bus.

# Status

`interrupt_status` → `VL6180XInterruptStatus` (`range`, `als`, `error`, `rangeReady()`).[^status] `range_status` → `VL6180XRangeStatus` (`error: VL6180XRangeError`, `device_ready`).[^range-status]

[^api]: VL6180XAPI
[^status]: VL6180XInterruptStatus
[^range-status]: VL6180XRangeStatus
