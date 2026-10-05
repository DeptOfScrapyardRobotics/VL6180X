<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;

class VL6180XException extends CircuitException
{
    public static function invalidChipId(int $chip_id, int $expected_id): static
    {
        return new static(sprintf('Invalid VL6180X model ID: expected 0x%02X, got 0x%02X.', $expected_id, $chip_id));
    }

    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property '{$name}' on {$class}.");
    }

    public static function readFailed(int $register, int $length): static
    {
        return new static(sprintf('VL6180X register 0x%03X: the bus refused a %d byte read.', $register, $length));
    }

    public static function shortRead(int $register, int $wanted, int $got): static
    {
        return new static(sprintf('VL6180X register 0x%03X: wanted %d bytes, got %d.', $register, $wanted, $got));
    }

    public static function registerOutOfRange(string $name, int $value, int $max): static
    {
        return new static("VL6180X {$name} takes 0 to {$max}; got {$value}.");
    }

    public static function measurementTimedOut(int $timeout_ms): static
    {
        return new static("VL6180X range measurement not ready after {$timeout_ms} ms.");
    }

    public static function notConnected(string $protocol, string $driver, string|int $device): static
    {
        return new static("VL6180X could not get a {$protocol} connection from driver [{$driver}] on device [{$device}].");
    }
}
