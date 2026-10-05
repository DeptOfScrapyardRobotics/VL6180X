# Agent guidelines — dept-of-scrapyard-robotics/vl6180x

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s), bump `generated.at`, and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them. The bundle documents the package, never a session.

Do **not** create `.okf` folders under `src/*` — knowledge for this package lives at the package root only. Catalog, transport and adapter semantics belong to `scrapyard-io/framework`'s bundle; point there, do not restate them here.

## Where this package sits

`ext-posi` / `ext-ftdi` → `microscrap/*` → `scrapyard-io/framework` (protocol managers, transports, the circuit catalog) → **`dept-of-scrapyard-robotics/vl6180x`** (chip driver) → apps.

## Package rules (quick) — 0.10.x

- Composer: `dept-of-scrapyard-robotics/vl6180x` **0.10.0**. PHP `^8.4|^8.5|^8.6`. Namespace `DeptOfScrapyardRobotics\Sensors\VL6180X\` → `src/`.
- **Requires split components only**: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-voyager/nuts-and-bolts`, `venusian-voyager/vessel`. Never `scrapyard-io/framework` or `venusian/framework`. Protocol components and adapters are `suggest`.
- **Chip = `Bootable` + `Sensor`.** Boot: XSHUT pulse when wired, model ID `0xB4`, ST AN4545 private writes only when fresh out of reset (flag then cleared), recommended public settings from the configuration, one temperature calibration. `close()` releases GPIO1 and XSHUT; the bus belongs to its driver.
- **The factory is the config shape.** `ConjuresVL6180X::i2c()` parameters are exactly a `circuits.vl6180x.configs.*` entry's keys; the provider catalogs `vl6180x`. A new config key = a new factory parameter, and the reverse. Bus first, GPIO1 and XSHUT after.
- **Ranging**: `readRange()` = start → wait → value → clear. Waits on GPIO1's edge only while `interruptLine()` is non-null (pin wired, interrupt output, new-sample-ready routing); otherwise polls status every 1 ms. Polarity comes from `gpio1_mode`, never from the pin's `active_low`.
- **Transport**: 16-bit register index, high byte first; reads through `writeRead()` and throw on refused or short reads.
- **Reach the framework through the container.** 0.10 has no protocol aliases or facades. The factory resolves `gpio.i2c` / `gpio.digital` from `ControlPanel::getInstance()`; apps call `app('circuit')->conjure()`.
- **Exceptions** descend from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException` → `GPIOLevelException`.
- Enums int-backed, FULLY UPPERCASE cases. No class constants. `is_null($x)` over `$x === null`.

## Verification

```bash
vendor/bin/pest            # recording fake bus and pins; no hardware
```

Run it under NTS and ZTS PHP before every commit. Suites stay hardware-free: chips are for scratch smoke scripts, never committed and never in `tests/`.

Hardware truth: a VL6180X at `0x29` on an FT232H's I2C (board I2C switch on), XSHUT on GPIO0 (D4, pin 0), GPIO1 on GPIO1 (D5, pin 1); proven by moving a hand over it.
