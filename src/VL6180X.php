<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use DeptOfScrapyardRobotics\Sensors\VL6180X\Concerns\VL6180XBootstrap;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Transports\VL6180XI2CTransport;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Sensor;
use GeneralPurposeIO\IntegratedCircuits\Bootable;

class VL6180X extends Bootable implements Sensor
{
    use VL6180XBootstrap;

    protected int $hardwired_device_id = 0xB4;

    public function __construct(
        protected readonly VL6180XI2CTransport $transport,
        protected readonly VL6180XConfiguration $config = new VL6180XConfiguration,
        bool $boot_now = false,
    ) {
        parent::__construct($boot_now);
    }

    public function transport(): VL6180XI2CTransport
    {
        return $this->transport;
    }

    public function config(): VL6180XConfiguration
    {
        return $this->config;
    }

    public function close(): void
    {
        $this->transport->close();
    }
}
