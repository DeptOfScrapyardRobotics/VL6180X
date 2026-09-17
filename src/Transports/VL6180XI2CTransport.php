<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Transports;

use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XException;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport;
use GeneralPurposeIO\Contracts\I2C\I2CTransport;
use GeneralPurposeIO\Contracts\IntegratedCircuits\ReadWriter;
use GeneralPurposeIO\Contracts\NutsAndBolts\Splices16Bits;

/**
 * Registers are 16-bit indexes, high byte first. GPIO1 (interrupt) and
 * XSHUT (shutdown) are optional pins.
 */
class VL6180XI2CTransport implements ReadWriter
{
    use Splices16Bits;

    public function __construct(
        protected I2CTransport $transport,
        protected ?DigitalInTransport $gpio1 = null,
        protected ?DigitalOutTransport $xshut = null,
    ) {}

    public function read(int $register, int $length): array
    {
        $bytes = $this->transport->writeRead(array_values($this->splitBytes($register)), $length);

        if ($bytes === false) {
            throw VL6180XException::readFailed($register, $length);
        }

        if (count($bytes) < $length) {
            throw VL6180XException::shortRead($register, $length, count($bytes));
        }

        return array_values($bytes);
    }

    public function write(int $register, array $data): int
    {
        return $this->transport->write([...array_values($this->splitBytes($register)), ...$data]);
    }

    /** The input wired to GPIO1, or null. */
    public function interruptPin(): ?DigitalInTransport
    {
        return $this->gpio1;
    }

    /** The output wired to XSHUT, or null. */
    public function shutdownPin(): ?DigitalOutTransport
    {
        return $this->xshut;
    }

    /** Release GPIO1 and XSHUT. The bus connection belongs to its driver and stays open. */
    public function close(): void
    {
        $this->gpio1?->close();
        $this->xshut?->close();
    }
}
