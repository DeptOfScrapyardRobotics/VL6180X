# Agent guidelines — dept-of-scrapyard-robotics/vl6180x

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s) and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them.

Do **not** create `.okf` folders under `src/*` — knowledge for this package lives at the package root only. Transport and dock semantics belong to `scrapyard-io/framework`; point there, do not restate them here.

## Where this package sits

`ext-posi` / `ext-ftdi` → `microscrap/*` → `scrapyard-io/framework` (protocol managers, transports) → **`dept-of-scrapyard-robotics/vl6180x`** (time-of-flight range sensor driver).

## Package rules (quick) — 0.8.x

- Composer: `dept-of-scrapyard-robotics/vl6180x` **0.8.0**. PHP `^8.4|^8.5|^8.6`. Namespace `DeptOfScrapyardRobotics\Sensors\VL6180X\` → `src/`.
- **Requires split components only**: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-voyager/nuts-and-bolts`. Never `scrapyard-io/framework` or `venusian/framework`. No waveforms dependency; ranges are plain `int` millimetres. Protocol components and adapters are `suggest`.
- **Sensor = `Bootable` + `Sensor`.** Boot: XSHUT pulse (if wired) → model ID `0xB4` → private settings only when fresh out of reset, then clear the flag → ST recommended public settings from `VL6180XConfiguration` → one VHV recalibration.
- **The chip is readable.** Getters read the chip and record the value in the configuration; setters write the chip, then the configuration. `__get`/`__set` map properties to those methods.
- **The configuration drives the wait path.** `interruptLine()` returns GPIO1 only when `gpio1_mode` is an interrupt output and `interrupt_config->range` is `NEW_SAMPLE_READY`; otherwise ranging polls `RESULT__INTERRUPT_STATUS_GPIO`. An idle line answers not-ready without a bus read; an asserted line is confirmed by one status read.
- **Transport**: `VL6180XI2CTransport` — 16-bit register high byte first; throws on refused or short reads; optional GPIO1 `DigitalInTransport` and XSHUT `DigitalOutTransport`; `close()` releases the pins only.
- **Register breakouts** are `readonly` `DataRegister`s from `gpio/integrated-circuits`.
- **Boot bytes are pinned** in tests to ST AN4545 values. Changing a configuration default changes those bytes; update the test only with a reason.
- **Reach the framework through MagicAliases** (`I2C::`, `DigitalIO::`), never `app('gpio.*')`.
- **Config** merges under `circuits.vl6180x`; publish tag `vl6180x-config` → `config/circuits/vl6180x.php`. The package reads none of it.
- **Exceptions** descend from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException` → `GPIOLevelException`.
- Enums int- or string-backed, FULLY UPPERCASE cases. No class constants. `is_null($x)` over `$x === null`.

## Verification

```bash
vendor/bin/pest            # recording fakes; no hardware
```

Hardware truth is a VL6180X at `0x29` on an FT232H (MPSSE I2C), GPIO1 on GPIOL1, XSHUT on GPIOL2. On MPSSE, pin `close()` closes the shared context; close the sensor last.
