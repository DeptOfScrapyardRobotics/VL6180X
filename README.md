# vl6180x

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dept-of-scrapyard-robotics/vl6180x.svg)](https://packagist.org/packages/dept-of-scrapyard-robotics/vl6180x)
[![License](https://img.shields.io/packagist/l/dept-of-scrapyard-robotics/vl6180x.svg)](LICENSE)

Drive VL6180X time-of-flight range sensors from PHP over I2C, using the ScrapyardIO GPIO framework.

`dept-of-scrapyard-robotics/vl6180x` boots the sensor with ST's required and recommended settings, then measures distance in millimetres. It can wait for each measurement on the GPIO1 interrupt pin, or poll the chip when that pin isn't wired.

```
ext-posi / ext-ftdi            1:1 system and libftdi calls
  → microscrap/*               libgpiod, i2c-dev, libmpsse in PHP
    → microscrap/scrapyard-*   adapters: the `native` and `usb` drivers
      → scrapyard-io/framework protocol managers, transports, the circuit catalog
        → dept-of-scrapyard-robotics/vl6180x   ← this package
```

## Requirements

- PHP 8.4 or newer
- A Venusian 0.10 application with the `scrapyard-io/framework` 0.10 components (`gpio/i2c`, `gpio/digital`, `gpio/integrated-circuits`)
- An adapter for your hardware:
  - `microscrap/scrapyard-usb` (driver `usb`) for FTDI MPSSE boards such as the FT232H, needs `ext-ftdi`
  - `microscrap/scrapyard-linux` (driver `native`) for native `i2c-dev` and `libgpiod`, needs `ext-posi`

## Installation

```bash
composer require dept-of-scrapyard-robotics/vl6180x
```

The service provider is discovered automatically. It merges the package's wiring config under `circuits.vl6180x` and registers the sensor with the circuit catalog. To publish that config into your app, run:

```bash
php computer vendor:publish --tag=vl6180x-config
```

That writes `config/circuits/vl6180x.php`, with `driver => 'none'` until you fill in your bench.

## Quick start

A VL6180X on an FT232H's I2C, XSHUT on GPIO0 (D4) and GPIO1 on GPIO1 (D5):

```php
// config/circuits/vl6180x.php
return [
    'default_config' => 'i2c',
    'configs' => [
        'i2c' => [
            'driver' => 'usb',
            'device' => 'ft232h',
            'slave' => 0x29,
            'xshut' => ['enabled' => true, 'driver' => 'usb', 'device' => 'ft232h', 'pin' => 0],
            'gpio1' => ['enabled' => true, 'driver' => 'usb', 'device' => 'ft232h', 'pin' => 1],
        ],
    ],
];
```

```php
$tof = app('circuit')->conjure('vl6180x');   // connected, reset and booted

echo $tof->readRange();   // 42 (millimetres)
```

Booting checks the model ID (`0xB4`) and writes the sensor's settings, in about 185 ms on an FT232H with XSHUT wired. Each `readRange()` takes one single-shot measurement, 30–80 ms on an FT232H.

## Connecting

`conjure('vl6180x')` reads `circuits.vl6180x`, picks `default_config`, and calls the sensor's `i2c()` factory with that entry's keys. You can call the factory directly too:

```php
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180X;

$tof = VL6180X::i2c('usb', 'ft232h');

$tof = VL6180X::i2c(
    'native', 1,
    gpio1: ['enabled' => true, 'driver' => 'native', 'device' => 0, 'pin' => 17],
    xshut: ['enabled' => true, 'driver' => 'native', 'device' => 0, 'pin' => 27],
);
```

A bus or pin device that isn't connected yet is connected by the factory. One your app already connected is shared as it is. GPIO1 and XSHUT are opened after the bus, so on an FT232H they ride the same USB context as its I2C engine. Pass `boot_now: false` to build without booting.

| Pin | Direction | Use |
|---|---|---|
| GPIO1 | input | the sensor pulls it when a measurement is ready |
| XSHUT | output | shutdown; boot pulses it to reset the sensor |

The address is fixed at `0x29` (`VL6180XI2CAddress::DEFAULT`). Registers are 16-bit, sent high byte first.

Leave the GPIO1 input's `active_low` argument at its default. The sensor's own polarity lives in its configuration, so the driver expects the raw line level.

### XSHUT

With XSHUT wired, boot drives it low for 2 ms, then high for 2 ms, so the sensor starts from reset every time. Without it, boot starts from whatever state the sensor is in.

ST requires a set of private register values after every reset. Boot writes them only when the sensor reports it is fresh out of reset, then clears that flag, so a second boot on the same power cycle without XSHUT skips them.

### Building the transport yourself

```php
use DeptOfScrapyardRobotics\Sensors\VL6180X\Transports\VL6180XI2CTransport;

$slave = app('gpio.i2c')->driver('usb')->connectTo('ft232h')->register()->device('ft232h', 0x29);
$pins = app('gpio.digital')->driver('usb');

$tof = new VL6180X(new VL6180XI2CTransport($slave, $pins->input('ft232h', 1), $pins->output('ft232h', 0)), boot_now: true);
```

## Measuring distance

```php
$mm = $tof->readRange();          // wait up to 1000 ms
$mm = $tof->readRange(200);       // wait up to 200 ms
$mm = $tof->range;                // same as readRange()
```

`readRange()` starts a measurement, waits for it, reads the result and clears the interrupt. If nothing is ready in time, it throws.

How it waits depends on GPIO1:

- **GPIO1 wired and set up for ranging.** The driver blocks on the pin's edge, then confirms the result with one status read. While the pin is idle, a readiness check costs no bus traffic.
- **Otherwise.** The driver reads the status register every millisecond.

GPIO1 counts as set up for ranging when `gpio1_mode` makes it an interrupt output and `interrupt_config` raises it on a new range sample. Both are true after boot. Change either one and the driver falls back to polling on its own; `interruptLine()` returns the pin while the edge path is in use and `null` otherwise.

The same steps are available one at a time, for code that shouldn't block:

```php
$tof->startMeasurement();

while (! $tof->statusReady()) {
    // other work
}

$mm = $tof->readRangeValue();
$tof->clearInterrupt();
```

`statusWait($timeout_ms)` blocks the same way `readRange()` does and returns `false` on timeout.

### Result status

```php
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XRangeError;

$status = $tof->range_status;
$status->error;          // VL6180XRangeError::NONE, ::MAX_SIGNAL_TO_NOISE_RATIO, …
$status->device_ready;   // bool

$tof->interrupt_status->rangeReady();   // bool
```

Check `error` before trusting a range. When it isn't `NONE`, the value that came with it isn't a valid distance: past the sensor's reach it reads 255 with `RAW_RANGING_ALGO_OVERFLOW`.

## Configuration object

`VL6180XConfiguration` holds the values boot writes. Every argument is optional, and the defaults are ST's recommended settings.

| Argument | Default | Meaning |
|---|---|---|
| `gpio1_mode` | `new VL6180XGPIO1Mode` | interrupt output, active low (`0x10`) |
| `averaging_sample_period` | `0x30` | readout averaging period |
| `als_gain` | `VL6180XALSGain::GAIN_1` | ambient light gain |
| `vhv_repeat_rate` | `0xFF` | range measurements between automatic temperature recalibrations; `0` turns it off |
| `als_integration_period` | `0x63` | ambient light integration, in ms minus one (100 ms) |
| `range_intermeasurement_period` | `0x09` | continuous ranging period, (count + 1) × 10 ms |
| `als_intermeasurement_period` | `0x31` | continuous ambient light period, (count + 1) × 10 ms |
| `interrupt_config` | `new VL6180XInterruptConfig` | new-sample-ready for ranging and ambient light (`0x24`) |

```php
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XGPIO1Mode;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XConfiguration;

$tof = new VL6180X(
    new VL6180XI2CTransport($slave, $gpio1),
    new VL6180XConfiguration(gpio1_mode: new VL6180XGPIO1Mode(active_high: true)),
    boot_now: true,
);
```

Boot also runs one temperature calibration.

## Settings

Every setting reads from the sensor and writes to it straight away:

```php
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XInterruptConfig;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XALSGain;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XInterruptMode;

$tof->als_gain = VL6180XALSGain::GAIN_10;
$tof->als_integration_period = 199;               // 200 ms
$tof->range_intermeasurement_period = 4;          // 50 ms
$tof->interrupt_config = new VL6180XInterruptConfig(range: VL6180XInterruptMode::OUT_OF_WINDOW);

$tof->recalibrate();                              // one temperature calibration now
```

Reads and writes both update the configuration, so `$tof->config()->get('als_gain')` always holds the last value seen.

| Property | Read | Write | Type |
|---|---|---|---|
| `device_id` | yes | | `int`, `0xB4` |
| `fresh_out_of_reset` | yes | yes | `bool` |
| `gpio1_mode` | yes | yes | `VL6180XGPIO1Mode` |
| `interrupt_config` | yes | yes | `VL6180XInterruptConfig` |
| `averaging_sample_period` | yes | yes | `int` 0–255 |
| `als_gain` | yes | yes | `VL6180XALSGain` |
| `vhv_repeat_rate` | yes | yes | `int` 0–255 |
| `als_integration_period` | yes | yes | `int` 0–463 |
| `range_intermeasurement_period` | yes | yes | `int` 0–254 |
| `als_intermeasurement_period` | yes | yes | `int` 0–254 |
| `interrupt_status` | yes | | `VL6180XInterruptStatus` |
| `range_status` | yes | | `VL6180XRangeStatus` |
| `range` | yes | | `int` mm |

Each property has a matching method, such as `getALSGain()` and `setALSGain()`.

## Errors

Failures throw `VL6180XException`, which descends from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException`:

- The sensor answers a model ID other than `0xB4`.
- The bus refuses a read, or returns fewer bytes than asked for.
- The protocol driver hands back no bus or pin (`notConnected`).
- A measurement isn't ready before the timeout.
- A setting is out of range.
- Your code reads or writes a property or configuration key that doesn't exist.

## Closing

```php
$tof->close();
```

`close()` releases GPIO1 and XSHUT. The I2C connection belongs to the protocol driver and stays open for other devices on the bus.

## Configuration file

`config/circuits/vl6180x.php`:

| Key | Default | Meaning |
|---|---|---|
| `default_config` | `'i2c'` | which entry under `configs` `conjure()` uses |
| `configs.i2c.driver` | `'none'` | I2C adapter: `usb` or `native` |
| `configs.i2c.device` | `''` | `ft232h`, or a bus number |
| `configs.i2c.slave` | `0x29` | sensor address |
| `configs.i2c.gpio1` | disabled, pin 0 | `enabled`, `driver`, `device` and `pin` for GPIO1 |
| `configs.i2c.xshut` | disabled, pin 1 | the same keys for XSHUT |
| `configs.i2c.boot_now` | `true` | boot during `conjure()` |

## Upgrading from 0.8

| 0.8 | 0.10 |
|---|---|
| `scrapyard-io/framework` 0.8 components | the 0.10 components |
| `I2C::driver(...)`, `DigitalIO::driver(...)` | `app('circuit')->conjure('vl6180x')`, `VL6180X::i2c()`, or `app('gpio.i2c')->driver(...)` |
| the package merged config but never read it | `conjure()` builds the sensor from it |

## Testing

```bash
composer install
vendor/bin/pest
```

The suite runs against recording fakes of the I2C bus and both pins, so it needs no hardware. The boot sequence is checked byte for byte against ST's application note values. The sensor was also exercised on an FT232H for this release, with XSHUT and GPIO1 wired: it booted in about 185 ms and tracked a hand between 35 and 172 mm, waiting on GPIO1 for each reading.

## Security

The driver reads and writes registers on hardware the PHP process can open. See [SECURITY.md](SECURITY.md) for the support policy and how to report a vulnerability.

## License

MIT. See [LICENSE](LICENSE).
