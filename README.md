# VL6180X
### Drive ST VL6180X time-of-flight distance sensors over I²C with PHP

PHP package for the [ST VL6180X](https://www.st.com/en/imaging-and-photonics-solutions/vl6180x.html) proximity / ranging sensor (up to ~100 mm absolute ranging). It sits on the ScrapyardIO I²C stack and implements BareMetal's `DistanceMeasurable` + `BootSequence` contracts so you can use it directly or wrap it in `BareMetal\Sensors\Distance\DistanceSensor`.

Compatible I2C Interfaces
===============

The VL6180X communicates over I²C at the default 7-bit address `0x29` (`VL6180XI2CAddress::DEFAULT`).

You can interface with it the following ways:

* A Linux single-board computer's exposed I²C bus using the POSIX carrier (`posix`) — typically `/dev/i2c-1`
* An MPSSE-enabled USB-to-serial device such as an FT232H using the USB carrier (`usb`)

Confirm the host exposes an I²C bus before opening a session:

```bash
ls /dev/i2c-*
```

Optional GPIO (digital) pins:

* **Shutdown / XSHUT** — digital output; **strongly recommended on real hardware**. When provided, `boot()` pulses it low→high before the identity check. Many breakouts fail to come up cleanly on I²C alone.
* **Interrupt (GPIO1)** — reserved. Do **not** pass `$interrupt_pin` yet — `readRange()`'s interrupt branch is an empty `@todo` and will TypeError on the declared `int` return. Ranging today polls the status register over I²C with `$interrupt_pin = null`.

Dependencies
=============

This package makes use of modules within:

* [The ScrapyardIO Framework](https://github.com/ScrapyardIO/framework)

This package also requires one of the following extensions in order to interface with I²C:

* [POSI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/posi)
* [FTDI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/ftdi)

In addition, an extension wrapper package is needed.

For ext-posi:

* [Microscrap POSIX Package](https://github.com/microscrap/posix)
* [Microscrap Native I2C Package](https://github.com/microscrap/i2c)
* [Microscrap POSIX Drivers](https://github.com/microscrap/scrapyard-posix-drivers) (`posix` I²C carrier)

For ext-ftdi:

* [Microscrap FTDI Package](https://github.com/microscrap/ftdi)
* [Microscrap MPSSE Package](https://github.com/microscrap/mpsse)
* [Microscrap USB Drivers](https://github.com/microscrap/scrapyard-usb-drivers) (`usb` I²C carrier)

Optional (XSHUT / future interrupt pins):

* [Microscrap Native GPIO Package](https://github.com/microscrap/gpio) / digital carriers via `waveforms/digital`

Installing from Composer
====================

```bash
composer require dept-of-scrapyard-robotics/vl6180x
```

Also install the carrier stack for your transport:

```bash
# Native SBC (posix I²C)
composer require microscrap/posix-drivers microscrap/i2c

# USB FT232H (usb I²C)
composer require microscrap/usb-drivers microscrap/mpsse
```

Basic Usage
============

Build an I²C transport with the GPIO facade, hand it to `VL6180X`, then call `boot()` (or pass `boot_now: true`) so the driver confirms model ID `0xB4` and loads settings. BareMetal wrappers do **not** expose `boot()` — boot the IC before wrapping, or pass `boot_now: true`.

### Native (POSIX) I²C — single-board computers

```php
<?php

use GPIO\Common\GPIO;
use BareMetal\Contracts\Sensors\Distance\DistanceUnit;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180X;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XI2CAddress;

$i2c = GPIO::i2c('posix')
    ->device(1)                                    // /dev/i2c-1
    ->slave(VL6180XI2CAddress::DEFAULT->value)     // 0x29
    ->create();

$sensor = new VL6180X($i2c, boot_now: true);

$mm = $sensor->distance();                         // DistanceUnit::MM by default
$cm = $sensor->distance(DistanceUnit::CM);

echo $mm . " mm\n";

$i2c->close();
```

### USB (MPSSE) I²C — Linux and macOS via FT232H

```php
<?php

use GPIO\Common\GPIO;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180X;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XI2CAddress;

$i2c = GPIO::i2c('usb')
    ->device('ft232h')
    ->slave(VL6180XI2CAddress::DEFAULT->value)
    ->create();

$sensor = new VL6180X($i2c, boot_now: true);

echo $sensor->distance() . " mm\n";

$i2c->close();
```

### Preferred: with XSHUT (shutdown) pin

Wire XSHUT to a digital output and pass it as `$shutdown_pin`. Boot pulses it low for 2 ms, then high for 2 ms, before reading the device ID — this is the reliable path on real hardware.

```php
<?php

use GPIO\Common\GPIO;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180X;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XI2CAddress;

$i2c = GPIO::i2c('posix')
    ->device(1)
    ->slave(VL6180XI2CAddress::DEFAULT->value)
    ->create();

$xshut = GPIO::digitalOut('posix')
    ->device(4)
    ->pin(17)
    ->name('vl6180-xshut')
    ->defaultState(true)
    ->create();

$sensor = new VL6180X($i2c, shutdown_pin: $xshut, boot_now: true);

echo $sensor->distance() . " mm\n";

$i2c->close();
$xshut->close();
```

Constructor knobs on `new VL6180X(...)`:

* `i2c` — required `I2CCompatibleTransport`
* `interrupt_pin` — reserved; leave `null` until interrupt-driven ranging is implemented
* `shutdown_pin` — optional digital out for XSHUT (recommended)
* `boot_now: true` — call `boot()` inside the constructor

Ranging helpers on the IC (beyond `distance()`):

* `readRange()` — single-shot mm (or `-1` on timeout)
* `startMeasurement()` / `statusReady()` / `statusWait($timeoutMs = 1000)` / `clearInterrupt()` / `readRangeValue()`

Alternative Usage
============

### Using Through the Distance Library (as a DistanceSensor)

Boot the IC first (or use `boot_now: true`). `DistanceSensor` does not expose `boot()`.

```php
<?php

use GPIO\Common\GPIO;
use BareMetal\Sensors\Distance\DistanceSensor;
use BareMetal\Contracts\Sensors\Distance\DistanceUnit;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180X;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XI2CAddress;

$i2c = GPIO::i2c('posix')
    ->device(1)
    ->slave(VL6180XI2CAddress::DEFAULT->value)
    ->create();

$chip = new VL6180X($i2c, boot_now: true);
$sensor = new DistanceSensor($chip);

echo $sensor->distance(DistanceUnit::MM) . " mm\n";

// Average several single-shots yourself (ProximityData has no public distance getter yet)
$sum = 0.0;
for ($i = 0; $i < 5; $i++) {
    $sum += $sensor->distance(DistanceUnit::MM);
}
echo ($sum / 5) . " mm (avg)\n";

// Block until a reading falls inside [low, high]; return false from the callback to stop
$sensor->within([10, 80], DistanceUnit::MM, function (float $mm): bool {
    echo $mm . " mm in range\n";
    return false;
});

$i2c->close();
```

Notes
=====

* Always `boot()` (or `boot_now: true`) before ranging — boot verifies model ID `0xB4`. If `system_fresh_out_of_reset` is set, it also applies ST's proprietary init table; recommended GPIO / ALS / VHV / intermeasurement defaults are always written afterward. Wrong ID throws `VL6180XException::invalidModelId()`.
* Prefer wiring XSHUT and passing `$shutdown_pin` — a real low→high pulse is often required for a clean boot on breakouts.
* `distance()` converts from a raw millimeter single-shot via `readRange()`. Supported units: `MM`, `CM`, `M`, `IN`, `FT`, `YD`, `uM`, `nM`.
* Without an interrupt pin, ranging starts a measurement, polls `RESULT_INTERRUPT_STATUS_GPIO` (up to 1 s), reads `RESULT_RANGE_VAL`, then clears interrupts. Timeout returns `-1`.
* Do **not** pass `$interrupt_pin` until interrupt-driven ranging is implemented — the non-null branch currently returns nothing and will TypeError.
* Default I²C address is `VL6180XI2CAddress::DEFAULT` (`0x29`). Multi-sensor buses typically need unique addresses via XSHUT sequencing on the bus (not automated by this package yet).
* **Wiring:** VIN → 2.8–3.3 V (check your breakout), GND common, SDA/SCL to the I²C bus (pull-ups required), XSHUT to a GPIO out (recommended), optional GPIO1 reserved for a future interrupt path.
