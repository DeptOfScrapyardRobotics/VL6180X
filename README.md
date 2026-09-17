# vl6180x

Drive VL6180X time-of-flight range sensors from PHP over I2C, using the ScrapyardIO GPIO framework.

`dept-of-scrapyard-robotics/vl6180x` boots the sensor with ST's required and recommended settings, then measures distance in millimetres. It can wait for each measurement on the GPIO1 interrupt pin, or poll the chip when that pin isn't wired.

## Requirements

- PHP 8.4 or newer
- A Venusian application with `scrapyard-io/framework` 0.8
- An adapter for your hardware:
  - `microscrap/scrapyard-usb` for FTDI MPSSE boards such as the FT232H (needs `ext-ftdi`)
  - `microscrap/scrapyard-linux` for native `i2c-dev` and `libgpiod` (needs `ext-posi`)

## Installation

```bash
composer require dept-of-scrapyard-robotics/vl6180x
```

The service provider is discovered automatically. It merges the package's wiring config under `circuits.vl6180x`. To publish that config into your app, run:

```bash
php computer vendor:publish --tag=vl6180x-config
```

That writes `config/circuits/vl6180x.php`.

## Quick start

A VL6180X at `0x29` on an FT232H:

```php
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XI2CAddress;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Transports\VL6180XI2CTransport;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180X;
use GeneralPurposeIO\I2C\I2C;

$slave = I2C::driver('usb')
    ->connectTo('ft232h')
    ->register()
    ->device('ft232h', VL6180XI2CAddress::DEFAULT->value);

$tof = new VL6180X(new VL6180XI2CTransport($slave), boot_now: true);

echo $tof->readRange();   // 42 (millimetres)
```

Booting checks the model ID (`0xB4`) and writes the sensor's settings. Each `readRange()` takes one single-shot measurement, about 50 ms on an FT232H.

## Connecting

The sensor takes a `VL6180XI2CTransport`. It wraps an I2C connection from the framework, plus two optional pins:

| Pin | Direction | Use |
|---|---|---|
| GPIO1 | input | the sensor pulls it when a measurement is ready |
| XSHUT | output | shutdown; boot pulses it to reset the sensor |

```php
use GeneralPurposeIO\Digital\DigitalIO;
use GeneralPurposeIO\I2C\I2C;

// FTDI MPSSE
$slave = I2C::driver('usb')->connectTo('ft232h')->register()->device('ft232h', 0x29);
$gpio1 = DigitalIO::driver('usb')->input('ft232h', 1);   // GPIOL1 (D5)
$xshut = DigitalIO::driver('usb')->output('ft232h', 2);  // GPIOL2 (D6)

// Linux i2c-dev and gpiochip0
$slave = I2C::driver('native')->connectTo(1)->register()->device(1, 0x29);
$pins = DigitalIO::driver('native')->connectTo(0)->register();
$gpio1 = $pins->input(0, 17);
$xshut = $pins->output(0, 27);

$tof = new VL6180X(new VL6180XI2CTransport($slave, $gpio1, $xshut), boot_now: true);
```

The address is fixed at `0x29` (`VL6180XI2CAddress::DEFAULT`). Registers are 16-bit, sent high byte first.

Leave the GPIO1 input's `active_low` argument at its default. The sensor's own polarity lives in its configuration, so the driver expects the raw line level.

### XSHUT

With XSHUT wired, boot drives it low for 2 ms, then high for 2 ms, so the sensor starts from reset every time. Without it, boot starts from whatever state the sensor is in.

ST requires a set of private register values after every reset. Boot writes them only when the sensor reports it is fresh out of reset, then clears that flag.

### From the published config

The config file holds your wiring. The package merges it but does not open connections from it, so read it where you build the sensor:

```php
$name = config('circuits.vl6180x.default_config');      // 'i2c'
$wiring = config("circuits.vl6180x.configs.{$name}");

$slave = I2C::driver($wiring['driver'])
    ->connectTo($wiring['device'])
    ->register()
    ->device($wiring['device'], $wiring['slave']);

$gpio1 = $wiring['gpio1']['enabled']
    ? DigitalIO::driver($wiring['gpio1']['driver'])->input($wiring['gpio1']['device'], $wiring['gpio1']['pin'])
    : null;
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

GPIO1 counts as set up for ranging when `gpio1_mode` makes it an interrupt output and `interrupt_config` raises it on a new range sample. Both are true after boot. Change either one and the driver falls back to polling on its own.

Measured on an FT232H, a range costs one status read with GPIO1 and about seven without it. Both paths take under 40 ms. The MPSSE adapter samples the pin over USB, so the gain there is less bus traffic rather than lower latency.

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

Check `error` before trusting a range. When it isn't `NONE`, the value that came with it isn't a valid distance.

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

Failures throw `DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XException`, which extends the framework's `GPIOLevelException`:

- The sensor answers a model ID other than `0xB4`.
- The bus refuses a read, or returns fewer bytes than asked for.
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
| `default_config` | `'i2c'` | which entry under `configs` to use |
| `configs.i2c.driver` | `'none'` | I2C adapter: `usb` or `native` |
| `configs.i2c.device` | `''` | `ft232h`, or a bus number |
| `configs.i2c.slave` | `0x29` | sensor address |
| `configs.i2c.gpio1` | disabled, pin 0 | `enabled`, `driver`, `device` and `pin` for GPIO1 |
| `configs.i2c.xshut` | disabled, pin 1 | the same keys for XSHUT |

## Testing

```bash
composer install
vendor/bin/pest
```

The suite runs against recording fakes of the I2C bus and both pins, so it needs no hardware. The boot sequence is checked byte for byte against ST's application note values.

## License

MIT. See [LICENSE](LICENSE).
