<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use BareMetal\Contracts\Sensors\SensorException;

class VL6180XException extends SensorException
{
    public static function invalidModelId(int $id): static
    {
        return new static(sprintf('Invalid VL6180X model ID - 0x%02X (expected 0xB4)', $id));
    }

    public static function i2cReadFailed(int $register, int $length): static
    {
        return new static(sprintf('I2C read failed - register 0x%04X, expected %d byte(s).', $register, $length));
    }
}
