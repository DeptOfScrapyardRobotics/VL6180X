---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/vl6180x Knowledge Bundle

Package knowledge for `dept-of-scrapyard-robotics/vl6180x` (ST VL6180X ToF / ALS sensor, v0.7.x).
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. New agent-written concepts stay `status: draft` until a human verifies them.
**Placement:** Package-root `.okf/` only — never under `src/`.
**Links:** Concept cross-links use paths relative to each file.
**Scope:** This package’s IC surface and Circuits catalog registration. Registry semantics live in `scrapyard-io/gpio-framework` — do not duplicate that bundle here. Sibling ToF packages (`vl53lxx`, `vl53l5cx`) keep their own `.okf`. No tubes dependency.
**Dist note:** `.okf/` and root `AGENTS.md` are `export-ignore` in `.gitattributes`.

# Orientation

* [Package (0.7)](orientation/package.md) - Composer identity, namespace, provider, dependencies.

# Core

* [VL6180X IC](core/vl6180x.md) - SensorIC class, attributes, I2C factories, boot, ranging API.
* [Circuits integration](core/circuits.md) - Catalog slug `vl6180x`, `Circuit::addCircuit`.

# Traps

* [Fabricate leftovers](traps/fabricate-leftovers.md) - GeneralPurposeIO Circuits + waveforms MeasuresDistance / DistanceUnit; Nab `Splices16Bits` intentional.
* [XSHUT recommended](traps/xshut-recommended.md) - Pass `shutdown_pin` for a clean boot pulse.
* [Interrupt pin unfinished](traps/interrupt-pin-unfinished.md) - Leave `interrupt_pin` unset; GPIO INT path returns -1.

# Log

* [Directory update log](log.md)
