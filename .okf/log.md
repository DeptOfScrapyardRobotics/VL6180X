# Directory Update Log

## 2026-08-11

* **Update (draft)**: VL6180X implements `Waveforms\Distance\MeasuresDistance`; units are `Waveforms\Contracts\Sensors\Enums\DistanceUnit` (deleted package-local DistanceUnit). Composer requires `scrapyard-io/waveforms ^0.7`. Amended traps / AGENTS / README.
* **Backfill**: Initial `.okf` v0.2 for `dept-of-scrapyard-robotics/vl6180x` 0.7 — package orientation, VL6180X SensorIC surface (I2C factories, BootScaffolding + Nab `Splices16Bits`, ranging), Circuits catalog `vl6180x`, traps for Fabricate leftovers / XSHUT recommended / unfinished `interrupt_pin`, lean `AGENTS.md`. Corrective after 0.7 promotion shipped without the bundle (`.gitattributes` already `export-ignore`d `.okf` + `AGENTS.md`).
