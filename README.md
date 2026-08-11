# dept-of-scrapyard-robotics/vl6180x (0.7)

I2C driver for the ST VL6180X time-of-flight / ambient-light sensor. Extends `GeneralPurposeIO\Circuits\SensorIC`.

## Register

Provider registers catalog slug `vl6180x`.

```php
Circuit::ic('vl6180x')->i2c(/* … */)->make();
```

XSHUT (`shutdown_pin`) is strongly recommended for a clean boot. The `interrupt_pin` path remains unfinished — leave it unset unless you are extending the driver.

## Distance units

`Waveforms\Contracts\Sensors\Enums\DistanceUnit`. Implements `Waveforms\Distance\MeasuresDistance` for `Rangefinder::circuit($profile)`. Raw ranging is millimetres; `distance()` converts via `DistanceUnit::convertFromMm()`.
