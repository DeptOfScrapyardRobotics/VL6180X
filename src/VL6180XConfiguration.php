<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XGPIO1Mode;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XInterruptConfig;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XALSGain;

/**
 * The settings boot writes, then the last value written or read for each.
 * Defaults are ST's recommended public register values (AN4545).
 */
class VL6180XConfiguration
{
    /**
     * @param  int  $averaging_sample_period  READOUT__AVERAGING_SAMPLE_PERIOD, raw count
     * @param  int  $vhv_repeat_rate  ranging measurements between automatic temperature recalibrations; 0 turns it off
     * @param  int  $als_integration_period  ms − 1, 0 to 463
     * @param  int  $range_intermeasurement_period  (count + 1) × 10 ms in continuous ranging, 0 to 254
     * @param  int  $als_intermeasurement_period  (count + 1) × 10 ms in continuous ALS, 0 to 254
     */
    public function __construct(
        protected VL6180XGPIO1Mode $gpio1_mode = new VL6180XGPIO1Mode,
        protected int $averaging_sample_period = 0x30,
        protected VL6180XALSGain $als_gain = VL6180XALSGain::GAIN_1,
        protected int $vhv_repeat_rate = 0xFF,
        protected int $als_integration_period = 0x63,
        protected int $range_intermeasurement_period = 0x09,
        protected int $als_intermeasurement_period = 0x31,
        protected VL6180XInterruptConfig $interrupt_config = new VL6180XInterruptConfig,
    ) {}

    public function get(string $var): mixed
    {
        if (isset($this->$var)) {
            return $this->$var;
        }

        throw VL6180XException::invalidProperty($var, static::class);
    }

    public function set(string $var, mixed $value): void
    {
        if (isset($this->$var)) {
            $this->$var = $value;

            return;
        }

        throw VL6180XException::invalidProperty($var, static::class);
    }
}
