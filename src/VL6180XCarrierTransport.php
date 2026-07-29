<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Concerns\VL6180XIO;
use GeneralPurposeIO\I2C\I2CSlave;

class VL6180XCarrierTransport
{
    use VL6180XIO;

    public readonly string $active_transport;

    /**
     * @throws VL6180XException
     */
    public function __construct(
        protected ?I2CSlave $i2c = null,
    ) {
        $this->active_transport = $this->detectTransport();
    }

    /**
     * @param  array<int, int>  $data
     *
     * @throws VL6180XException
     */
    public function write(array $data): int
    {
        $method = "{$this->active_transport}Write";

        return $this->{$method}($data);
    }

    /**
     * @param  array<int, int>  $bytes_to_write
     * @return array<int, int>
     *
     * @throws VL6180XException
     */
    public function writeRead(array $bytes_to_write, int $length): array
    {
        $method = "{$this->active_transport}WriteRead";

        return $this->{$method}($bytes_to_write, $length);
    }

    /**
     * @throws VL6180XException
     */
    protected function detectTransport(): string
    {
        if (! is_null($this->i2c)) {
            return 'i2c';
        }

        throw VL6180XException::transportMissingProtocol();
    }

    public function close(): void
    {
        $this->i2c?->close();
    }
}
