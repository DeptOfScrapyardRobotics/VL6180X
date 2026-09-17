<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts;

use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/** SYSTEM__MODE_GPIO1: bits 4:1 select interrupt output (1000) or off (0000), bit 5 sets polarity. */
readonly class VL6180XGPIO1Mode extends DataRegister
{
    public function __construct(
        public bool $interrupt_output = true,
        public bool $active_high = false,
    ) {}

    public function toBits(): string
    {
        $bit5 = $this->active_high ? '1' : '0';
        $bits41 = $this->interrupt_output ? '1000' : '0000';

        return "00{$bit5}{$bits41}0";
    }

    public static function fromByte(int $byte): static
    {
        return new static(
            (($byte >> 1) & 0x0F) === 0b1000,
            (bool) (($byte >> 5) & 0x01),
        );
    }

    public static function none(): static
    {
        return new static(false, false);
    }
}
