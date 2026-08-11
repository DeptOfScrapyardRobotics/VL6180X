# Agent guidelines — dept-of-scrapyard-robotics/vl6180x

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from Composer dist via `.gitattributes` `export-ignore`).

Before changing this package or advising on VL6180X architecture:

1. Read [`.okf/index.md`](.okf/index.md) first (progressive disclosure).
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable` concepts; treat `deprecated` as historical only. New/changed concepts stay `status: draft` until a human verifies them.
4. When you learn something durable about **this package**, update the affected `.okf` concept(s) and append `.okf/log.md`.
5. Keep the `.okf` bundle at the **package root** only — do not nest extra `.okf` folders under `src/`.
6. Circuits registry semantics belong in `scrapyard-io/gpio-framework`’s `.okf`. Sibling ToF packages (`vl53lxx`, `vl53l5cx`) keep their own bundles. Do not pull tubes/display knowledge into this sensor package.

## Package rules (quick) — 0.7.x

- Composer: `dept-of-scrapyard-robotics/vl6180x` **0.7.0**. Namespace `DeptOfScrapyardRobotics\Sensors\VL6180X\`.
- Provider: `Providers\VL6180XServiceProvider`. Catalog slug `vl6180x` only (no package-local make-profile / smoke).
- Requires `scrapyard-io/gpio-framework ^0.7` + `scrapyard-io/waveforms ^0.7` — **no tubes**.
- IC extends `GeneralPurposeIO\Circuits\Types\SensorIC`, implements `BootSequence` + `Waveforms\Distance\MeasuresDistance`; factories `i2c(...)` / `fromI2CBus(...)`.
- Attributes: `#[IntegratedCircuit('I2C')]` + `#[Pinout(['I2C' => ['driver', 'device', 'slave']])]`. Optional `interrupt_pin` / `shutdown_pin` are ctor args, not Pinout keys.
- Boot uses `BootScaffolding`; 16-bit opcode helpers use Nab `Splices16Bits` (intentional). Units: `Waveforms\Contracts\Sensors\Enums\DistanceUnit` — no package-local DistanceUnit.
- XSHUT (`shutdown_pin`) strongly recommended for clean boot. Leave `interrupt_pin` unset — INT path unfinished (`readRange` returns `-1`).
- Never import `Fabricate\Contracts\Circuits\*` or `Fabricate\Circuits\*`.
