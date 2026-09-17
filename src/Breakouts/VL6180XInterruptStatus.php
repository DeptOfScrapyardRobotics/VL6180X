<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XInterruptError;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XInterruptMode;
use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/** RESULT__INTERRUPT_STATUS_GPIO: what has fired, ranging in bits 2:0, ALS in bits 5:3, error in bits 7:6. */
readonly class VL6180XInterruptStatus extends DataRegister
{
    public function __construct(
        public VL6180XInterruptMode $range = VL6180XInterruptMode::DISABLED,
        public VL6180XInterruptMode $als = VL6180XInterruptMode::DISABLED,
        public VL6180XInterruptError $error = VL6180XInterruptError::NONE,
    ) {}

    public function rangeReady(): bool
    {
        return $this->range === VL6180XInterruptMode::NEW_SAMPLE_READY;
    }

    public function toBits(): string
    {
        return sprintf('%02b', $this->error->value).sprintf('%03b', $this->als->value).sprintf('%03b', $this->range->value);
    }

    public static function fromByte(int $byte): static
    {
        return new static(
            VL6180XInterruptMode::from($byte & 0x07),
            VL6180XInterruptMode::from(($byte >> 3) & 0x07),
            VL6180XInterruptError::from(($byte >> 6) & 0x03),
        );
    }

    public static function none(): static
    {
        return new static;
    }
}
