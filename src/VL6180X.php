<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Concerns\VL6180XAPI;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XI2CAddress;
use Exception;
use GeneralPurposeIO\Circuits\Types\SensorIC;
use GeneralPurposeIO\Contracts\Circuits\Attributes\IntegratedCircuit;
use GeneralPurposeIO\Contracts\Circuits\Attributes\Pinout;
use GeneralPurposeIO\Contracts\Circuits\BootSequence;
use GeneralPurposeIO\Digital\DigitalInputPin;
use GeneralPurposeIO\Digital\DigitalOutputPin;
use GeneralPurposeIO\I2C\I2C;
use GeneralPurposeIO\I2C\I2CSlave;
use Waveforms\Contracts\Distance\DistanceUnit;
use Waveforms\Contracts\Distance\MaxDistance;
use Waveforms\Contracts\Distance\MeasuresDistance;
use Waveforms\Contracts\Distance\MinDistance;

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
#[IntegratedCircuit('I2C')]
#[Pinout(['I2C' => ['driver', 'device', 'slave']])]
class VL6180X extends SensorIC implements BootSequence, MeasuresDistance
{
    use VL6180XAPI;

    #[MaxDistance] protected $max_distance = 175;
    #[MinDistance] protected $min_distance = 5;

    /**
     * @param  array{0: int, 1: int}  $_sys_als_integration_period
     *
     * @throws Exception
     */
    public function __construct(
        protected readonly VL6180XCarrierTransport $transport,
        protected int $_int_pin_mode,
        protected int $_avg_sample_period_readout,
        protected int $_analog_gain,
        protected int $_vhv_repeat_rate,
        protected array $_sys_als_integration_period,
        protected int $_vhv_recalibrate,
        protected int $_sys_range_integration_period,
        protected int $_intermeasurement_period,
        protected int $_interrupt_pin_config,
        protected ?DigitalInputPin $interrupt_pin = null,
        protected ?DigitalOutputPin $shutdown_pin = null,
        bool $boot_now = false,
    ) {
        if ($boot_now) {
            $this->boot();
        }
    }

    /**
     * @throws VL6180XException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'device_id' => $this->getDeviceId(),
            'system_fresh_out_of_reset' => $this->getSystemFreshOutOfReset() == 0x01,
            'int_pin_mode' => $this->getInterruptPinMode(),
            'avg_sample_period_readout' => $this->getAvgSamplePeriodReadout(),
            'analog_gain' => $this->getAnalogGain(),
            'vhv_repeat_rate' => $this->getVHVRepeatRate(),
            'sys_als_integration_period' => $this->getSysALSIntegrationPeriod(),
            'vhv_recalibrate' => $this->getVHVRecalibrate(),
            'sys_range_integration_period' => $this->getSysRangeIntegrationPeriod(),
            'intermeasurement_period' => $this->getIntermeasurementPeriod(),
            'interrupt_pin_config' => $this->getInterruptPinConfig(),
            default => throw VL6180XException::invalidProperty($name, static::class),
        };
    }

    /**
     * @throws VL6180XException
     */
    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'system_fresh_out_of_reset' => $this->setSystemFreshOutOfReset((bool) $value),
            'int_pin_mode' => $this->setInterruptPinMode((int) $value),
            'avg_sample_period_readout' => $this->setAvgSamplePeriodReadout((int) $value),
            'analog_gain' => $this->setAnalogGain((int) $value),
            'vhv_repeat_rate' => $this->setVHVRepeatRate((int) $value),
            'sys_als_integration_period' => $this->setSysALSIntegrationPeriod($value),
            'vhv_recalibrate' => $this->setVHVRecalibrate((int) $value),
            'sys_range_integration_period' => $this->setSysRangeIntegrationPeriod((int) $value),
            'intermeasurement_period' => $this->setIntermeasurementPeriod((int) $value),
            'interrupt_pin_config' => $this->setInterruptPinConfig((int) $value),
            default => throw VL6180XException::invalidProperty($name, static::class),
        };
    }

    public function distance(DistanceUnit $unit = DistanceUnit::MM): float
    {
        return $unit->convertFromMm((float) $this->readRange());
    }

    public function close(): void
    {
        $this->interrupt_pin?->close();
        $this->shutdown_pin?->close();
        $this->transport->close();
    }

    /**
     * @param  array{0: int, 1: int}  $sys_als_integration_period
     *
     * @throws Exception
     */
    public static function i2c(
        string|int $device,
        ?string $adapter = null,
        int $slave = VL6180XI2CAddress::DEFAULT->value,
        int $int_pin_mode = 0x10,
        int $avg_sample_period_readout = 0x30,
        int $analog_gain = 0x46,
        int $vhv_repeat_rate = 0xFF,
        array $sys_als_integration_period = [0x00, 0x63],
        int $vhv_recalibrate = 0x01,
        int $sys_range_integration_period = 0x09,
        int $intermeasurement_period = 0x31,
        int $interrupt_pin_config = 0x24,
        ?DigitalInputPin $interrupt_pin = null,
        ?DigitalOutputPin $shutdown_pin = null,
        bool $boot_now = true,
    ): static {
        $i2c = I2C::adapter($adapter)
            ->device($device)
            ->bus()
            ->slave($slave);

        return static::fromI2CBus(
            $i2c,
            $int_pin_mode,
            $avg_sample_period_readout,
            $analog_gain,
            $vhv_repeat_rate,
            $sys_als_integration_period,
            $vhv_recalibrate,
            $sys_range_integration_period,
            $intermeasurement_period,
            $interrupt_pin_config,
            $interrupt_pin,
            $shutdown_pin,
            $boot_now,
        );
    }

    /**
     * @param  array{0: int, 1: int}  $sys_als_integration_period
     *
     * @throws Exception
     */
    public static function fromI2CBus(
        I2CSlave $i2c,
        int $int_pin_mode = 0x10,
        int $avg_sample_period_readout = 0x30,
        int $analog_gain = 0x46,
        int $vhv_repeat_rate = 0xFF,
        array $sys_als_integration_period = [0x00, 0x63],
        int $vhv_recalibrate = 0x01,
        int $sys_range_integration_period = 0x09,
        int $intermeasurement_period = 0x31,
        int $interrupt_pin_config = 0x24,
        ?DigitalInputPin $interrupt_pin = null,
        ?DigitalOutputPin $shutdown_pin = null,
        bool $boot_now = true,
    ): static {
        $transport = new VL6180XCarrierTransport(i2c: $i2c);

        return new static(
            $transport,
            $int_pin_mode,
            $avg_sample_period_readout,
            $analog_gain,
            $vhv_repeat_rate,
            $sys_als_integration_period,
            $vhv_recalibrate,
            $sys_range_integration_period,
            $intermeasurement_period,
            $interrupt_pin_config,
            $interrupt_pin,
            $shutdown_pin,
            $boot_now,
        );
    }
}
