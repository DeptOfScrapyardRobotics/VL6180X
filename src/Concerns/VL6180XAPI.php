<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Concerns;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XGPIO1Mode;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XInterruptConfig;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XInterruptStatus;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XRangeStatus;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XALSGain;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XInterruptMode;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XOpCode;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Transports\VL6180XI2CTransport;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XConfiguration;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XException;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;

/**
 * Getters read the chip and record what they read. Setters write the chip,
 * then the configuration.
 */
trait VL6180XAPI
{
    abstract public function transport(): VL6180XI2CTransport;

    abstract public function config(): VL6180XConfiguration;

    protected function sendCommand(VL6180XOpCode $register, array $command_data = []): int
    {
        return $this->transport()->write($register->value, $command_data);
    }

    protected function readData(VL6180XOpCode $register, int $length): array
    {
        return $this->transport()->read($register->value, $length);
    }

    protected function readByte(VL6180XOpCode $register): int
    {
        return $this->readData($register, 1)[0];
    }

    protected function writeByte(VL6180XOpCode $register, int $value, string $name, int $max = 0xFF): void
    {
        if ($value < 0 || $value > $max) {
            throw VL6180XException::registerOutOfRange($name, $value, $max);
        }

        $this->sendCommand($register, [$value]);
    }

    public function getDeviceId(): int
    {
        return $this->readByte(VL6180XOpCode::MODEL_ID);
    }

    /** true until boot has loaded the settings a reset clears. */
    public function getFreshOutOfReset(): bool
    {
        return ($this->readByte(VL6180XOpCode::SYSTEM_FRESH_OUT_OF_RESET) & 0x01) === 0x01;
    }

    public function setFreshOutOfReset(bool $fresh): void
    {
        $this->sendCommand(VL6180XOpCode::SYSTEM_FRESH_OUT_OF_RESET, [$fresh ? 0x01 : 0x00]);
    }

    public function getGPIO1Mode(): VL6180XGPIO1Mode
    {
        $mode = VL6180XGPIO1Mode::fromByte($this->readByte(VL6180XOpCode::SYSTEM_MODE_GPIO1));
        $this->config()->set('gpio1_mode', $mode);

        return $mode;
    }

    public function setGPIO1Mode(VL6180XGPIO1Mode $mode): void
    {
        $this->sendCommand(VL6180XOpCode::SYSTEM_MODE_GPIO1, [$mode->toByte()]);
        $this->config()->set('gpio1_mode', $mode);
    }

    public function getInterruptConfig(): VL6180XInterruptConfig
    {
        $config = VL6180XInterruptConfig::fromByte($this->readByte(VL6180XOpCode::SYSTEM_INTERRUPT_CONFIG_GPIO));
        $this->config()->set('interrupt_config', $config);

        return $config;
    }

    public function setInterruptConfig(VL6180XInterruptConfig $config): void
    {
        $this->sendCommand(VL6180XOpCode::SYSTEM_INTERRUPT_CONFIG_GPIO, [$config->toByte()]);
        $this->config()->set('interrupt_config', $config);
    }

    public function getAveragingSamplePeriod(): int
    {
        $period = $this->readByte(VL6180XOpCode::READOUT_AVERAGING_SAMPLE_PERIOD);
        $this->config()->set('averaging_sample_period', $period);

        return $period;
    }

    public function setAveragingSamplePeriod(int $period): void
    {
        $this->writeByte(VL6180XOpCode::READOUT_AVERAGING_SAMPLE_PERIOD, $period, 'averaging_sample_period');
        $this->config()->set('averaging_sample_period', $period);
    }

    public function getALSGain(): VL6180XALSGain
    {
        $gain = VL6180XALSGain::from($this->readByte(VL6180XOpCode::SYSALS_ANALOGUE_GAIN) & 0x07);
        $this->config()->set('als_gain', $gain);

        return $gain;
    }

    /** Writes the light gain under the fixed dark-gain nibble 0x4. */
    public function setALSGain(VL6180XALSGain $gain): void
    {
        $this->sendCommand(VL6180XOpCode::SYSALS_ANALOGUE_GAIN, [0x40 | $gain->value]);
        $this->config()->set('als_gain', $gain);
    }

    public function getVHVRepeatRate(): int
    {
        $rate = $this->readByte(VL6180XOpCode::SYSRANGE_VHV_REPEAT_RATE);
        $this->config()->set('vhv_repeat_rate', $rate);

        return $rate;
    }

    public function setVHVRepeatRate(int $rate): void
    {
        $this->writeByte(VL6180XOpCode::SYSRANGE_VHV_REPEAT_RATE, $rate, 'vhv_repeat_rate');
        $this->config()->set('vhv_repeat_rate', $rate);
    }

    /** ms − 1. */
    public function getALSIntegrationPeriod(): int
    {
        [$high, $low] = $this->readData(VL6180XOpCode::SYSALS_INTEGRATION_PERIOD, 2);
        $period = (($high & 0x01) << 8) | $low;
        $this->config()->set('als_integration_period', $period);

        return $period;
    }

    /** ms − 1, 0 to 463. */
    public function setALSIntegrationPeriod(int $period): void
    {
        if ($period < 0 || $period > 463) {
            throw VL6180XException::registerOutOfRange('als_integration_period', $period, 463);
        }

        $this->sendCommand(VL6180XOpCode::SYSALS_INTEGRATION_PERIOD, array_values($this->transport()->splitBytes($period)));
        $this->config()->set('als_integration_period', $period);
    }

    public function getRangeIntermeasurementPeriod(): int
    {
        $period = $this->readByte(VL6180XOpCode::SYSRANGE_INTERMEASUREMENT_PERIOD);
        $this->config()->set('range_intermeasurement_period', $period);

        return $period;
    }

    /** (count + 1) × 10 ms between continuous ranging measurements, 0 to 254. */
    public function setRangeIntermeasurementPeriod(int $period): void
    {
        $this->writeByte(VL6180XOpCode::SYSRANGE_INTERMEASUREMENT_PERIOD, $period, 'range_intermeasurement_period', 254);
        $this->config()->set('range_intermeasurement_period', $period);
    }

    public function getALSIntermeasurementPeriod(): int
    {
        $period = $this->readByte(VL6180XOpCode::SYSALS_INTERMEASUREMENT_PERIOD);
        $this->config()->set('als_intermeasurement_period', $period);

        return $period;
    }

    /** (count + 1) × 10 ms between continuous ALS measurements, 0 to 254. */
    public function setALSIntermeasurementPeriod(int $period): void
    {
        $this->writeByte(VL6180XOpCode::SYSALS_INTERMEASUREMENT_PERIOD, $period, 'als_intermeasurement_period', 254);
        $this->config()->set('als_intermeasurement_period', $period);
    }

    /** One temperature (VHV) calibration now. */
    public function recalibrate(): void
    {
        $this->sendCommand(VL6180XOpCode::SYSRANGE_VHV_RECALIBRATE, [0x01]);
    }

    public function getInterruptStatus(): VL6180XInterruptStatus
    {
        return VL6180XInterruptStatus::fromByte($this->readByte(VL6180XOpCode::RESULT_INTERRUPT_STATUS_GPIO));
    }

    public function getRangeStatus(): VL6180XRangeStatus
    {
        return VL6180XRangeStatus::fromByte($this->readByte(VL6180XOpCode::RESULT_RANGE_STATUS));
    }

    /**
     * One single-shot range in millimetres: start, wait, read, clear.
     *
     * @throws VL6180XException when no sample is ready within $timeout_ms
     */
    public function readRange(int $timeout_ms = 1000): int
    {
        $this->startMeasurement();

        if (! $this->statusWait($timeout_ms)) {
            throw VL6180XException::measurementTimedOut($timeout_ms);
        }

        $range = $this->readRangeValue();
        $this->clearInterrupt();

        return $range;
    }

    /** Start one single-shot range measurement. */
    public function startMeasurement(): void
    {
        $this->sendCommand(VL6180XOpCode::SYSRANGE_START, [0x01]);
    }

    /**
     * Is a range sample ready? Never waits. With GPIO1 wired and raising on a
     * new range sample, an idle line answers without a bus read; an asserted
     * line is confirmed against RESULT__INTERRUPT_STATUS_GPIO.
     */
    public function statusReady(): bool
    {
        $line = $this->interruptLine();

        if (! is_null($line) && ! $this->lineAsserted($line)) {
            return false;
        }

        return $this->getInterruptStatus()->rangeReady();
    }

    /**
     * Block until a range sample is ready or the timeout passes. Waits on
     * GPIO1 edges when the line serves ranging, otherwise polls the status
     * register every millisecond.
     */
    public function statusWait(int $timeout_ms = 1000): bool
    {
        $deadline = hrtime(true) + ($timeout_ms * 1_000_000);

        while (! $this->statusReady()) {
            $remaining_ns = $deadline - hrtime(true);

            if ($remaining_ns <= 0) {
                return false;
            }

            $line = $this->interruptLine();

            if (is_null($line)) {
                usleep((int) min(1_000, ceil($remaining_ns / 1_000)));

                continue;
            }

            $active_high = $this->config()->get('gpio1_mode')->active_high;
            $line->listen((int) ceil($remaining_ns / 1_000_000), $active_high, ! $active_high);
        }

        return true;
    }

    /** Clear the range, ALS and error interrupts. */
    public function clearInterrupt(): void
    {
        $this->sendCommand(VL6180XOpCode::SYSTEM_INTERRUPT_CLEAR, [0x07]);
    }

    /** The last range result, in millimetres. */
    public function readRangeValue(): int
    {
        return $this->readByte(VL6180XOpCode::RESULT_RANGE_VAL);
    }

    /**
     * GPIO1's input when the chip drives it as an interrupt output that
     * raises on a new range sample; null means poll the status register.
     */
    public function interruptLine(): ?DigitalInTransport
    {
        $line = $this->transport()->interruptPin();

        if (is_null($line)
            || ! $this->config()->get('gpio1_mode')->interrupt_output
            || $this->config()->get('interrupt_config')->range !== VL6180XInterruptMode::NEW_SAMPLE_READY) {
            return null;
        }

        return $line;
    }

    protected function lineAsserted(DigitalInTransport $line): bool
    {
        return $line->read() === $this->config()->get('gpio1_mode')->active_high;
    }
}
