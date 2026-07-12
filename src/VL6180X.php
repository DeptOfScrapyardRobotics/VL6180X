<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use BareMetal\Sensors\Sensor;
use BareMetal\Contracts\Circuits\BootSequence;
use GPIO\Contracts\I2C\I2CCompatibleTransport;
use BareMetal\Contracts\Sensors\Distance\DistanceUnit;
use BareMetal\Contracts\Sensors\Distance\DistanceMeasurable;
use GPIO\Contracts\Digital\DigitalInputPin as DigitalInputInterface;
use GPIO\Contracts\Digital\DigitalOutputPin as DigitalOutputInterface;

/**
 * @property-read int $device_id
 * @property bool $system_fresh_out_of_reset
 * @property int $int_pin_mode
 * @property int $avg_sample_period_readout
 * @property int $analog_gain
 * @property int $vhv_repeat_rate
 * @property array $sys_als_integration_period
 * @property int $vhv_recalibrate
 * @property int $sys_range_integration_period
 * @property int $intermeasurement_period
 * @property int $interrupt_pin_config
 */
class VL6180X extends Sensor implements DistanceMeasurable, BootSequence
{
    use VL6180XAPI;

    protected int $_int_pin_mode = 0x10;
    protected int $_avg_sample_period_readout = 0x30;
    protected int $_analog_gain = 0x46;
    protected int $_vhv_repeat_rate = 0xFF;
    protected array $_sys_als_integration_period = [0x00, 0x63];
    protected int $_vhv_recalibrate = 0x01;
    protected int $_sys_range_integration_period = 0x09;
    protected int $_intermeasurement_period = 0x31;
    protected int $_interrupt_pin_config = 0x24;

    public function __construct(
        protected I2CCompatibleTransport $i2c,
        protected ?DigitalInputInterface $interrupt_pin = null,
        protected ?DigitalOutputInterface $shutdown_pin = null,
        bool $boot_now = false,
    ) {
        if($boot_now) {
            $this->boot();
        }
    }

    /**
     * @throws VL6180XException
     */
    public function __get(string $name): mixed
    {
        return match($name) {
            'device_id' => $this->getDeviceId(),
            'system_fresh_out_of_reset' => $this->getSystemFreshOutOfReset() == 0x01,
            'int_pin_mode' => $this->getInterruptPinMode(),
            'avg_sample_period_readout' => $this->getAvgSamplePeriodReadout(),
            'analog_gain' => $this->getAnalogGain(),
            'vhv_repeat_rate' => $this->getVHVRepeatRate(),
            'sys_als_integration_period' => $this->getSysAlsIntegrationPeriod(),
            'vhv_recalibrate' => $this->getVHvRecalibrate(),
            'sys_range_integration_period' => $this->getSysRangeIntegrationPeriod(),
            'intermeasurement_period' => $this->getIntermeasurementPeriod(),
            'interrupt_pin_config' => $this->getInterruptPinConfig(),
            default => throw VL6180XException::invalidProperty($name, static::class)
        };
    }

    /**
     * @throws VL6180XException
     */
    public function __set(string $name, mixed $value): void
    {
        match($name) {
            'system_fresh_out_of_reset' => $this->setSystemFreshOutOfReset((bool) $value),
            'int_pin_mode' => $this->setInterruptPinMode((int) $value),
            'avg_sample_period_readout' => $this->setAvgSamplePeriodReadout((int) $value),
            'analog_gain' => $this->setAnalogGain((int) $value),
            'vhv_repeat_rate' => $this->setVHVRepeatRate((int) $value),
            'sys_als_integration_period' => $this->setSysAlsIntegrationPeriod($value),
            'vhv_recalibrate' => $this->setVHvRecalibrate((int) $value),
            'sys_range_integration_period' => $this->setSysRangeIntegrationPeriod((int) $value),
            'intermeasurement_period' => $this->setIntermeasurementPeriod((int) $value),
            'interrupt_pin_config' => $this->setInterruptPinConfig((int) $value),
            default => throw VL6180XException::invalidProperty($name, static::class)
        };
    }

    public function distance(DistanceUnit $unit = DistanceUnit::MM): float
    {
        $mm = $this->readRange();

        return match ($unit) {
            DistanceUnit::CM => $mm / 10.0,
            DistanceUnit::M => $mm / 1000.0,
            DistanceUnit::IN => $mm / 25.4,
            DistanceUnit::FT => $mm / 304.8,
            DistanceUnit::YD => $mm / 914.4,
            DistanceUnit::uM => $mm * 1_000.0,
            DistanceUnit::nM => $mm * 1_000_000.0,
            default => (float) $mm,
        };
    }
}
