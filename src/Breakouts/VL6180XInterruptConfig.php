<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XInterruptMode;
use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/** SYSTEM__INTERRUPT_CONFIG_GPIO: ranging in bits 2:0, ALS in bits 5:3. */
readonly class VL6180XInterruptConfig extends DataRegister
{
    public function __construct(
        public VL6180XInterruptMode $range = VL6180XInterruptMode::NEW_SAMPLE_READY,
        public VL6180XInterruptMode $als = VL6180XInterruptMode::NEW_SAMPLE_READY,
    ) {}

    public function toBits(): string
    {
        return '00'.sprintf('%03b', $this->als->value).sprintf('%03b', $this->range->value);
    }

    public static function fromByte(int $byte): static
    {
        return new static(
            VL6180XInterruptMode::from($byte & 0x07),
            VL6180XInterruptMode::from(($byte >> 3) & 0x07),
        );
    }

    public static function none(): static
    {
        return new static(VL6180XInterruptMode::DISABLED, VL6180XInterruptMode::DISABLED);
    }
}
