<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XRangeError;
use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/** RESULT__RANGE_STATUS: error code in bits 7:4, device ready in bit 0. */
readonly class VL6180XRangeStatus extends DataRegister
{
    public function __construct(
        public VL6180XRangeError $error = VL6180XRangeError::NONE,
        public bool $device_ready = false,
    ) {}

    public function toBits(): string
    {
        return sprintf('%04b', $this->error->value).'000'.($this->device_ready ? '1' : '0');
    }

    public static function fromByte(int $byte): static
    {
        return new static(
            VL6180XRangeError::from(($byte >> 4) & 0x0F),
            (bool) ($byte & 0x01),
        );
    }

    public static function none(): static
    {
        return new static;
    }
}
