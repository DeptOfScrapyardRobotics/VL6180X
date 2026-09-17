<?php

use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XGPIO1Mode;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XInterruptConfig;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XInterruptStatus;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XRangeStatus;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XInterruptError;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XInterruptMode;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XRangeError;

it('packs SYSTEM__MODE_GPIO1', function (): void {
    expect((new VL6180XGPIO1Mode)->toByte())->toBe(0x10)
        ->and((new VL6180XGPIO1Mode(active_high: true))->toByte())->toBe(0x30)
        ->and(VL6180XGPIO1Mode::none()->toByte())->toBe(0x00)
        ->and(VL6180XGPIO1Mode::fromByte(0x30))->toEqual(new VL6180XGPIO1Mode(true, true))
        ->and(VL6180XGPIO1Mode::fromByte(0x20))->toEqual(new VL6180XGPIO1Mode(false, true));
});

it('packs SYSTEM__INTERRUPT_CONFIG_GPIO with ranging low and ALS high', function (): void {
    expect((new VL6180XInterruptConfig)->toByte())->toBe(0x24)
        ->and((new VL6180XInterruptConfig(VL6180XInterruptMode::OUT_OF_WINDOW, VL6180XInterruptMode::LEVEL_LOW))->toByte())->toBe(0x0B)
        ->and(VL6180XInterruptConfig::none()->toByte())->toBe(0x00)
        ->and(VL6180XInterruptConfig::fromByte(0x0B))->toEqual(new VL6180XInterruptConfig(VL6180XInterruptMode::OUT_OF_WINDOW, VL6180XInterruptMode::LEVEL_LOW));
});

it('unpacks RESULT__INTERRUPT_STATUS_GPIO', function (): void {
    $status = VL6180XInterruptStatus::fromByte(0x84);

    expect($status->range)->toBe(VL6180XInterruptMode::NEW_SAMPLE_READY)
        ->and($status->als)->toBe(VL6180XInterruptMode::DISABLED)
        ->and($status->error)->toBe(VL6180XInterruptError::PLL)
        ->and($status->rangeReady())->toBeTrue()
        ->and($status->toByte())->toBe(0x84)
        ->and(VL6180XInterruptStatus::fromByte(0x20)->rangeReady())->toBeFalse()
        ->and(VL6180XInterruptStatus::none()->toByte())->toBe(0x00);
});

it('unpacks RESULT__RANGE_STATUS', function (): void {
    $status = VL6180XRangeStatus::fromByte(0xB1);

    expect($status->error)->toBe(VL6180XRangeError::MAX_SIGNAL_TO_NOISE_RATIO)
        ->and($status->device_ready)->toBeTrue()
        ->and($status->toByte())->toBe(0xB1)
        ->and(VL6180XRangeStatus::fromByte(0x00))->toEqual(VL6180XRangeStatus::none());
});
