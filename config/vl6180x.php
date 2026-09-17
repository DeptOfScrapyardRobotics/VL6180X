<?php

use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XI2CAddress;

return [
    'default_config' => 'i2c',
    'configs' => [
        'i2c' => [
            'driver' => 'none',
            'device' => '',
            'slave' => VL6180XI2CAddress::DEFAULT->value,
            'gpio1' => [
                'enabled' => false,
                'driver' => 'none',
                'device' => '',
                'pin' => 0,
            ],
            'xshut' => [
                'enabled' => false,
                'driver' => 'none',
                'device' => '',
                'pin' => 1,
            ],
        ],
    ],
];
