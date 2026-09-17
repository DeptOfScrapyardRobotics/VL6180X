<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Enums;

/** Light gain, bits 2:0 of SYSALS__ANALOGUE_GAIN. The upper nibble is the dark gain and stays 0x4. */
enum VL6180XALSGain: int
{
    case GAIN_20 = 0;
    case GAIN_10 = 1;
    case GAIN_5 = 2;
    case GAIN_2_5 = 3;
    case GAIN_1_67 = 4;
    case GAIN_1_25 = 5;
    case GAIN_1 = 6;
    case GAIN_40 = 7;

    public function factor(): float
    {
        return match ($this) {
            self::GAIN_20 => 20.0,
            self::GAIN_10 => 10.0,
            self::GAIN_5 => 5.0,
            self::GAIN_2_5 => 2.5,
            self::GAIN_1_67 => 1.67,
            self::GAIN_1_25 => 1.25,
            self::GAIN_1 => 1.0,
            self::GAIN_40 => 40.0,
        };
    }
}
