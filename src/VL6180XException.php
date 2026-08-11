<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use GeneralPurposeIO\Contracts\Circuits\CircuitException;

class VL6180XException extends CircuitException
{
    public static function transportMissingProtocol(): static
    {
        return new static('VL6180X devices require an I2C capable connection.');
    }

    public static function invalidModelId(int $id): static
    {
        return new static(sprintf('Invalid VL6180X model ID - 0x%02X (expected 0xB4)', $id));
    }

    public static function i2cReadFailed(int $register, int $length): static
    {
        return new static(sprintf('I2C read failed - register 0x%04X, expected %d byte(s).', $register, $length));
    }
}
