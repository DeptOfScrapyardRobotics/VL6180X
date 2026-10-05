<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support;

use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalIOConnectionDriver;
use GeneralPurposeIO\Digital\DigitalIOConnectionFactory;

/** Hands out one FakeInterruptPin or FakeOutputPin per device and pin. */
final class FakeDigitalIOConnectionDriver extends DigitalIOConnectionDriver
{
    /** @var list<string|int> every device connectTo() opened */
    public array $opened = [];

    /** @var array<string, FakeInterruptPin> */
    public array $inputs = [];

    /** @var array<string, FakeOutputPin> */
    public array $outputs = [];

    protected function newConnection(int|string $device): DigitalIOConnectionFactory
    {
        $this->opened[] = $device;

        return new FakeDigitalIOConnectionFactory($device, $this);
    }

    protected function getInputTransport(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): FakeInterruptPin
    {
        return $this->inputs["{$device}:{$pin}"] ??= new FakeInterruptPin($pin);
    }

    protected function getOutputTransport(string|int $device, int $pin): FakeOutputPin
    {
        return $this->outputs["{$device}:{$pin}"] ??= new FakeOutputPin($pin);
    }

    protected function closeConnection(mixed $handle): void {}
}
