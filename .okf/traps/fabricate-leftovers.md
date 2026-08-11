---
type: Trap
title: Fabricate leftovers
description: VL6180X 0.7 uses GeneralPurposeIO Circuits + waveforms MeasuresDistance — not Fabricate Circuits or local DistanceUnit. Nab Splices16Bits is intentional.
tags: [traps, fabricate, circuits, sensors, waveforms, nab]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T16:20:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: ic
    resource: src/VL6180X.php
    title: VL6180X imports
  - id: internal-api
    resource: src/Concerns/VL6180XInternalAPI.php
    title: BootScaffolding + Nab Splices16Bits
  - id: provider
    resource: src/Providers/VL6180XServiceProvider.php
    title: Circuit MagicAlias import
  - id: exception
    resource: src/VL6180XException.php
    title: CircuitException base
---

# Trap

Do **not** import or revive:

- `Fabricate\Contracts\Circuits\*`
- `Fabricate\Circuits\*` (including any old Fabricate boot / DataRegister paths)
- Package-local `DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\DistanceUnit` (removed)

# Use instead

| Concern | Correct FQCN |
|---------|----------------|
| Taxonomy base | `GeneralPurposeIO\Circuits\Types\SensorIC` |
| Attributes / BootSequence / BootScaffolding | `GeneralPurposeIO\Contracts\Circuits\Attributes\*`, `BootSequence`, `BootScaffolding` |
| Circuit alias | `GeneralPurposeIO\Core\MagicAliases\Circuit` |
| Exception base | `GeneralPurposeIO\Contracts\Circuits\CircuitException` |
| Bit helpers | `Fabricate\NutsAndBolts\Concerns\Splices16Bits` (Nab — **intentional**; opcode addresses are 16-bit) |
| Rangefinder qualify | `Waveforms\Distance\MeasuresDistance` |
| Units | `Waveforms\Contracts\Sensors\Enums\DistanceUnit` |

`VL6180X` implements `MeasuresDistance` so `Rangefinder::circuit($profile)` accepts it.[^ic]

Keep chip methods (`distance` / `readRange` / status helpers). Raw ranging is millimetres.

This package depends on `scrapyard-io/waveforms` (not tubes).

# Related

* [VL6180X IC](../core/vl6180x.md)
* [Circuits integration](../core/circuits.md)

[^ic]: VL6180X imports
[^internal-api]: BootScaffolding + Nab Splices16Bits
[^provider]: Circuit MagicAlias import
[^exception]: CircuitException base
