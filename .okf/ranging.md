---
type: Guide
title: Ranging
description: readRange() and its step helpers, the GPIO1 edge wait versus status-register polling, and result status breakouts.
tags: [range, measurement, interrupts, gpio1, polling]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T01:30:00Z }
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

`readRange(int $timeout_ms = 1000): int` → mm. Steps: `startMeasurement()` (`SYSRANGE__START` ← 0x01) → `statusWait()` → `readRangeValue()` (`RESULT__RANGE_VAL`) → `clearInterrupt()` (`SYSTEM__INTERRUPT_CLEAR` ← 0x07).[^api] Timeout → `measurementTimedOut`; any other result is returned, so check `range_status->error === VL6180XRangeError::NONE` before trusting the value (past the sensor's reach it reads 255 with `RAW_RANGING_ALGO_OVERFLOW`). `$tof->range` = `readRange()`. FT232H, 0.10, GPIO1 path: 28–81 ms each; hand tracked 35–172 mm.

# Wait path

`interruptLine()` → GPIO1 input only when all hold:[^api]

* transport has GPIO1
* `gpio1_mode->interrupt_output`
* `interrupt_config->range === NEW_SAMPLE_READY`

else null → poll, without an error: check `interruptLine()` is non-null to know the edge path is active.

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
