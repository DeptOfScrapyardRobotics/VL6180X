<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180OpCode;

trait VL6180XAPI
{
    use VL6180XInternalAPI;

    /**
     * @throws VL6180XException
     */
    public function getDeviceId(): int
    {
        [$id] = $this->readData(VL6180OpCode::DEVICE_ID_REGISTER, 1);

        return $id;
    }

    /**
     * @throws VL6180XException
     */
    public function getSystemFreshOutOfReset(): int
    {
        [$flag] = $this->readData(VL6180OpCode::SYSTEM_FRESH_OUT_OF_RESET, 1);

        return $flag;
    }

    /**
     * @throws VL6180XException
     */
    public function getInterruptPinMode(): int
    {
        [$mode] = $this->readData(VL6180OpCode::SYSTEM_MODE_GPIO1, 1);

        return $mode;
    }

    /**
     * @return int
     * @throws VL6180XException
     */
    public function getAvgSamplePeriodReadout(): int
    {
        [$period] = $this->readData(VL6180OpCode::READOUT_AVERAGING_SAMPLE_PERIOD, 1);

        return $period;
    }

    /**
     * @return int
     * @throws VL6180XException
     */
    public function getAnalogGain(): int {
        [$gain] = $this->readData(VL6180OpCode::SYSALS_ANALOGUE_GAIN, 1);

        return $gain;
    }

    /**
     * @return int
     * @throws VL6180XException
     */
    public function getVHVRepeatRate(): int {
        [$rate] = $this->readData(VL6180OpCode::SYSRANGE_VHV_REPEAT_RATE, 1);

        return $rate;
    }

    /**
     * @return int
     * @throws VL6180XException
     */
    public function getSysALSIntegrationPeriod(): array {
        [$high, $low] = $this->readData(VL6180OpCode::SYSALS_INTEGRATION_PERIOD, 2);

        return [$high, $low];
    }

    /**
     * @return int
     * @throws VL6180XException
     */
    public function getVHVRecalibrate(): int {
        [$value] = $this->readData(VL6180OpCode::SYSRANGE_VHV_RECALIBRATE, 1);

        return $value;
    }

    /**
     * @return int
     * @throws VL6180XException
     */
    public function getSysRangeIntegrationPeriod(): int {
        [$period] = $this->readData(VL6180OpCode::SYSRANGE_INTERMEASUREMENT_PERIOD, 1);

        return $period;
    }

    /**
     * @return int
     * @throws VL6180XException
     */
    public function getIntermeasurementPeriod(): int {
        [$period] = $this->readData(VL6180OpCode::SYSALS_INTERMEASUREMENT_PERIOD, 1);

        return $period;
    }

    /**
     * @return int
     * @throws VL6180XException
     */
    public function getInterruptPinConfig(): int {
        [$config] = $this->readData(VL6180OpCode::SYSTEM_INTERRUPT_CONFIG_GPIO, 1);

        return $config;
    }

    public function setSystemFreshOutOfReset(bool $value): void
    {
        $this->sendCommand(VL6180OpCode::SYSTEM_FRESH_OUT_OF_RESET, [$value ? 0x01 : 0x00]);
    }

    public function setInterruptPinMode(int $mode): void
    {
        $this->sendCommand(VL6180OpCode::SYSTEM_MODE_GPIO1, [$mode]);
    }

    public function setAvgSamplePeriodReadout(int $period): void
    {
        $this->sendCommand(VL6180OpCode::READOUT_AVERAGING_SAMPLE_PERIOD, [$period]);
    }

    public function setAnalogGain(int $gain): void
    {
        $this->sendCommand(VL6180OpCode::SYSALS_ANALOGUE_GAIN, [$gain]);
    }

    public function setVHVRepeatRate(int $rate): void
    {
        $this->sendCommand(VL6180OpCode::SYSRANGE_VHV_REPEAT_RATE, [$rate]);
    }

    public function setSysALSIntegrationPeriod(array $period): void
    {
        $this->sendCommand(VL6180OpCode::SYSALS_INTEGRATION_PERIOD, $period);
    }

    public function setVHVRecalibrate(int $period): void
    {
        $this->sendCommand(VL6180OpCode::SYSRANGE_VHV_RECALIBRATE, [$period]);
    }

    public function setSysRangeIntegrationPeriod(int $period): void
    {
        $this->sendCommand(VL6180OpCode::SYSRANGE_INTERMEASUREMENT_PERIOD, [$period]);
    }

    public function setIntermeasurementPeriod(int $period): void
    {
        $this->sendCommand(VL6180OpCode::SYSALS_INTERMEASUREMENT_PERIOD, [$period]);
    }

    public function setInterruptPinConfig(int $config): void
    {
        $this->sendCommand(VL6180OpCode::SYSTEM_INTERRUPT_CONFIG_GPIO, [$config]);
    }

    /**
     * @throws VL6180XException
     */
    public function readRange(): int
    {
        if(is_null($this->interrupt_pin))
        {
            $this->startMeasurement();
            $ready = $this->statusWait();

            if (! $ready) {
                return -1;
            }

            $raw = $this->readRangeValue();
            $this->clearInterrupt();

            return $raw;
        }
        else
        {
            // @todo - use the int pin to read the measurement [blocked until the register values gets broken out]
        }
    }

    public function startMeasurement(): void
    {
        $this->sendCommand(VL6180OpCode::SYSRANGE_START, [0x01]);
    }

    public function statusReady(): bool
    {
        try {
            [$status] = $this->readData(VL6180OpCode::RESULT_INTERRUPT_STATUS_GPIO, 1);
            return ($status & 0x07) === 0x04;
        } catch (VL6180XException) {
            return false;
        }
    }

    public function statusWait(int $timeoutMs = 1000): bool
    {
        $startTime = microtime(true);
        while (! $this->statusReady()) {
            usleep(1_000);
            if ($timeoutMs > 0 && ((microtime(true) - $startTime) * 1000.0) >= $timeoutMs) {
                return false;
            }
        }

        return true;
    }

    public function clearInterrupt(): void
    {
        $this->sendCommand(VL6180OpCode::SYSTEM_INTERRUPT_CLEAR, [0x07]);
    }

    /**
     * @throws VL6180XException
     */
    public function readRangeValue(): int
    {
        [$val] = $this->readData(VL6180OpCode::RESULT_RANGE_VAL, 1);

        return $val;
    }
}
