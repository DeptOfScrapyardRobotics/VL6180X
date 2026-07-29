<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Concerns;

use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XException;

trait VL6180XIO
{
    /**
     * @param  array<int, int>  $data
     *
     * @throws VL6180XException
     */
    protected function i2cWrite(array $data): int
    {
        if (! is_null($this->i2c)) {
            return $this->i2c->write($data);
        }

        throw VL6180XException::transportMissingProtocol();
    }

    /**
     * @param  array<int, int>  $bytes_to_write
     * @return array<int, int>
     *
     * @throws VL6180XException
     */
    protected function i2cWriteRead(array $bytes_to_write, int $length): array
    {
        if (! is_null($this->i2c)) {
            $data = $this->i2c->writeRead($bytes_to_write, $length);

            if ($data === false) {
                throw VL6180XException::i2cReadFailed(
                    (($bytes_to_write[0] ?? 0) << 8) | ($bytes_to_write[1] ?? 0),
                    $length,
                );
            }

            return array_values($data);
        }

        throw VL6180XException::transportMissingProtocol();
    }
}
