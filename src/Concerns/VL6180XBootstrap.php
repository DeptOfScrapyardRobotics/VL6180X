<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Concerns;

use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XException;

trait VL6180XBootstrap
{
    use VL6180XAPI;

    /**
     * Private registers ST requires after every reset (AN4545, SR03 settings).
     *
     * @var list<array{int, int}>
     */
    protected array $private_settings = [
        [0x0207, 0x01], [0x0208, 0x01], [0x0096, 0x00], [0x0097, 0xFD],
        [0x00E3, 0x00], [0x00E4, 0x04], [0x00E5, 0x02], [0x00E6, 0x01],
        [0x00E7, 0x03], [0x00F5, 0x02], [0x00D9, 0x05], [0x00DB, 0xCE],
        [0x00DC, 0x03], [0x00DD, 0xF8], [0x009F, 0x00], [0x00A3, 0x3C],
        [0x00B7, 0x00], [0x00BB, 0x3C], [0x00B2, 0x09], [0x00CA, 0x09],
        [0x0198, 0x01], [0x01B0, 0x17], [0x01AD, 0x00], [0x00FF, 0x05],
        [0x0100, 0x05], [0x0199, 0x05], [0x01A6, 0x1B], [0x01AC, 0x3E],
        [0x01A7, 0x1F], [0x0030, 0x00],
    ];

    /**
     * @throws VL6180XException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'device_id' => $this->getDeviceId(),
            'fresh_out_of_reset' => $this->getFreshOutOfReset(),
            'gpio1_mode' => $this->getGPIO1Mode(),
            'interrupt_config' => $this->getInterruptConfig(),
            'averaging_sample_period' => $this->getAveragingSamplePeriod(),
            'als_gain' => $this->getALSGain(),
            'vhv_repeat_rate' => $this->getVHVRepeatRate(),
            'als_integration_period' => $this->getALSIntegrationPeriod(),
            'range_intermeasurement_period' => $this->getRangeIntermeasurementPeriod(),
            'als_intermeasurement_period' => $this->getALSIntermeasurementPeriod(),
            'interrupt_status' => $this->getInterruptStatus(),
            'range_status' => $this->getRangeStatus(),
            'range' => $this->readRange(),
            default => throw VL6180XException::invalidProperty($name, static::class),
        };
    }

    /**
     * @throws VL6180XException
     */
    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'fresh_out_of_reset' => $this->setFreshOutOfReset($value),
            'gpio1_mode' => $this->setGPIO1Mode($value),
            'interrupt_config' => $this->setInterruptConfig($value),
            'averaging_sample_period' => $this->setAveragingSamplePeriod($value),
            'als_gain' => $this->setALSGain($value),
            'vhv_repeat_rate' => $this->setVHVRepeatRate($value),
            'als_integration_period' => $this->setALSIntegrationPeriod($value),
            'range_intermeasurement_period' => $this->setRangeIntermeasurementPeriod($value),
            'als_intermeasurement_period' => $this->setALSIntermeasurementPeriod($value),
            default => throw VL6180XException::invalidProperty($name, static::class),
        };
    }

    protected function _boot(): void
    {
        $this->resetDevice();
        $this->confirmDeviceId();

        if ($this->getFreshOutOfReset()) {
            $this->loadPrivateSettings();
            $this->setFreshOutOfReset(false);
        }

        $this->loadRecommendedSettings();
    }

    /** Pulse XSHUT low then high, 2 ms each, when it is wired. */
    protected function resetDevice(): void
    {
        $xshut = $this->transport()->shutdownPin();

        if (is_null($xshut)) {
            return;
        }

        $xshut->low();
        usleep(2_000);
        $xshut->high();
        usleep(2_000);
    }

    protected function confirmDeviceId(): void
    {
        $device_id = $this->getDeviceId();

        if ($device_id !== $this->hardwired_device_id) {
            throw VL6180XException::invalidChipId($device_id, $this->hardwired_device_id);
        }
    }

    protected function loadPrivateSettings(): void
    {
        foreach ($this->private_settings as [$register, $value]) {
            $this->transport()->write($register, [$value]);
        }
    }

    /** ST's recommended public settings, in AN4545 order, from the configuration. */
    protected function loadRecommendedSettings(): void
    {
        $this->setGPIO1Mode($this->config()->get('gpio1_mode'));
        $this->setAveragingSamplePeriod($this->config()->get('averaging_sample_period'));
        $this->setALSGain($this->config()->get('als_gain'));
        $this->setVHVRepeatRate($this->config()->get('vhv_repeat_rate'));
        $this->setALSIntegrationPeriod($this->config()->get('als_integration_period'));
        $this->recalibrate();
        $this->setRangeIntermeasurementPeriod($this->config()->get('range_intermeasurement_period'));
        $this->setALSIntermeasurementPeriod($this->config()->get('als_intermeasurement_period'));
        $this->setInterruptConfig($this->config()->get('interrupt_config'));
    }
}
