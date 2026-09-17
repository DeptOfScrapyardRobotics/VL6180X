<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support;

use GeneralPurposeIO\Digital\DigitalOutputTransport;

final class FakeOutputPin extends DigitalOutputTransport
{
    public bool $state = false;

    /** @var list<bool> */
    public array $levels = [];

    public bool $closed = false;

    public function read(): bool { return $this->state; }

    public function write(bool $state): bool
    {
        $this->levels[] = $state;

        return $this->state = $state;
    }

    public function close(): void { $this->closed = true; }
}
