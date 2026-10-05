<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Concerns;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XI2CAddress;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Transports\VL6180XI2CTransport;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XException;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport;
use Voyager\Vessel\ControlPanel;

/**
 * The i2c() protocol factory the circuit catalog calls. Its parameters are the keys of a config/circuits/vl6180x.php
 * entry, so app('circuit')->conjure('vl6180x') builds a wired, booted sensor from the app's config alone. A bus or
 * pin device that is not connected yet is connected here; one the app already connected is shared as it is. The bus
 * is connected before GPIO1 and XSHUT, so pins on an FT232H ride the bus's own context.
 */
trait ConjuresVL6180X
{
    /**
     * @param  array{enabled?: bool, driver?: string, device?: string|int, pin?: int}  $gpio1
     * @param  array{enabled?: bool, driver?: string, device?: string|int, pin?: int}  $xshut
     */
    public static function i2c(
        string $driver,
        string|int $device,
        int $slave = VL6180XI2CAddress::DEFAULT->value,
        array $gpio1 = [],
        array $xshut = [],
        bool $boot_now = true,
    ): static {
        $bus = static::gpio('gpio.i2c')->driver($driver);
        $i2c = $bus->device($device, $slave) ?? $bus->connectTo($device)->register()->device($device, $slave);

        if (is_null($i2c)) {
            throw VL6180XException::notConnected('I2C', $driver, $device);
        }

        return new static(new VL6180XI2CTransport($i2c, static::inputLine($gpio1), static::outputLine($xshut)), boot_now: $boot_now);
    }

    /** @param  array{enabled?: bool, driver?: string, device?: string|int, pin?: int}  $line */
    protected static function inputLine(array $line): ?DigitalInTransport
    {
        if (! ($line['enabled'] ?? false)) {
            return null;
        }

        $pins = static::gpio('gpio.digital')->driver($line['driver']);
        $pin = $pins->input($line['device'], $line['pin'])
            ?? $pins->connectTo($line['device'])->register()->input($line['device'], $line['pin']);

        return $pin ?? throw VL6180XException::notConnected('DigitalIO', $line['driver'], $line['device']);
    }

    /** @param  array{enabled?: bool, driver?: string, device?: string|int, pin?: int}  $line */
    protected static function outputLine(array $line): ?DigitalOutTransport
    {
        if (! ($line['enabled'] ?? false)) {
            return null;
        }

        $pins = static::gpio('gpio.digital')->driver($line['driver']);
        $pin = $pins->output($line['device'], $line['pin'])
            ?? $pins->connectTo($line['device'])->register()->output($line['device'], $line['pin']);

        return $pin ?? throw VL6180XException::notConnected('DigitalIO', $line['driver'], $line['device']);
    }

    /** A protocol manager from the app's container: gpio.i2c or gpio.digital. */
    protected static function gpio(string $manager): mixed
    {
        return ControlPanel::getInstance()->make($manager);
    }
}
