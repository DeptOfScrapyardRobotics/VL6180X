<?php

use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XGPIO1Mode;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Breakouts\VL6180XInterruptConfig;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XALSGain;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XInterruptError;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XInterruptMode;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XRangeError;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\FakeI2CTransport;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\FakeInterruptPin;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\FakeOutputPin;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Transports\VL6180XI2CTransport;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180X;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XConfiguration;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180XException;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;

/** ST AN4545 private settings, loaded once per reset. */
const VL6180X_PRIVATE_WRITES = [
    [0x02, 0x07, 0x01], [0x02, 0x08, 0x01], [0x00, 0x96, 0x00], [0x00, 0x97, 0xFD],
    [0x00, 0xE3, 0x00], [0x00, 0xE4, 0x04], [0x00, 0xE5, 0x02], [0x00, 0xE6, 0x01],
    [0x00, 0xE7, 0x03], [0x00, 0xF5, 0x02], [0x00, 0xD9, 0x05], [0x00, 0xDB, 0xCE],
    [0x00, 0xDC, 0x03], [0x00, 0xDD, 0xF8], [0x00, 0x9F, 0x00], [0x00, 0xA3, 0x3C],
    [0x00, 0xB7, 0x00], [0x00, 0xBB, 0x3C], [0x00, 0xB2, 0x09], [0x00, 0xCA, 0x09],
    [0x01, 0x98, 0x01], [0x01, 0xB0, 0x17], [0x01, 0xAD, 0x00], [0x00, 0xFF, 0x05],
    [0x01, 0x00, 0x05], [0x01, 0x99, 0x05], [0x01, 0xA6, 0x1B], [0x01, 0xAC, 0x3E],
    [0x01, 0xA7, 0x1F], [0x00, 0x30, 0x00],
];

/** ST AN4545 recommended public settings, in order. */
const VL6180X_PUBLIC_WRITES = [
    [0x00, 0x11, 0x10],        // GPIO1: interrupt output, active low
    [0x01, 0x0A, 0x30],        // averaging sample period
    [0x00, 0x3F, 0x46],        // ALS gain 1
    [0x00, 0x31, 0xFF],        // VHV repeat rate
    [0x00, 0x40, 0x00, 0x63],  // ALS integration 100 ms
    [0x00, 0x2E, 0x01],        // VHV recalibrate
    [0x00, 0x1B, 0x09],        // ranging every 100 ms
    [0x00, 0x3E, 0x31],        // ALS every 500 ms
    [0x00, 0x14, 0x24],        // new sample ready for ranging and ALS
];

/** @return array{0: VL6180X, 1: FakeI2CTransport} */
function vl6180x(array $replies = [], ?VL6180XI2CTransport $transport = null, ?FakeI2CTransport $bus = null, ?VL6180XConfiguration $config = null, bool $boot = true, bool $fresh = true): array
{
    $bus ??= new FakeI2CTransport;
    $bus->replies = $boot ? [[0xB4], [$fresh ? 0x01 : 0x00], ...$replies] : $replies;
    $chip = new VL6180X($transport ?? new VL6180XI2CTransport($bus), $config ?? new VL6180XConfiguration, boot_now: $boot);
    $bus->writes = $boot ? [] : $bus->writes;
    $bus->write_reads = [];

    return [$chip, $bus];
}

/** @return list<int> the 16-bit register of every read */
function registersRead(FakeI2CTransport $bus): array
{
    return array_map(fn (array $wr): int => ($wr[0][0] << 8) | $wr[0][1], $bus->write_reads);
}

// --- boot ---------------------------------------------------------------------

it('boots fresh out of reset with the private and recommended settings, byte for byte', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [[0xB4], [0x01]];

    $chip = new VL6180X(new VL6180XI2CTransport($bus), boot_now: true);

    expect($chip->hasBooted())->toBeTrue()
        ->and($bus->write_reads)->toBe([[[0x00, 0x00], 1], [[0x00, 0x16], 1]])
        ->and($bus->writes)->toBe([...VL6180X_PRIVATE_WRITES, [0x00, 0x16, 0x00], ...VL6180X_PUBLIC_WRITES]);
});

it('skips the private settings when the chip is not fresh out of reset', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [[0xB4], [0x00]];

    new VL6180X(new VL6180XI2CTransport($bus), boot_now: true);

    expect($bus->writes)->toBe(VL6180X_PUBLIC_WRITES);
});

it('refuses a chip that does not answer 0xB4 and writes nothing', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [[0xE5]];

    expect(fn () => new VL6180X(new VL6180XI2CTransport($bus), boot_now: true))
        ->toThrow(VL6180XException::class, 'expected 0xB4, got 0xE5')
        ->and($bus->writes)->toBe([]);
});

it('pulses XSHUT low then high before it reads the chip', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [[0xB4], [0x00]];
    $xshut = new FakeOutputPin(3);

    new VL6180X(new VL6180XI2CTransport($bus, xshut: $xshut), boot_now: true);

    expect($xshut->levels)->toBe([false, true])
        ->and($xshut->state)->toBeTrue();
});

it('boots once, and only when asked', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [[0xB4], [0x00]];
    $chip = new VL6180X(new VL6180XI2CTransport($bus));

    expect($bus->writes)->toBe([])->and($chip->hasBooted())->toBeFalse();

    $chip->boot();
    $chip->boot();

    expect($bus->write_reads)->toHaveCount(2)
        ->and($bus->writes)->toBe(VL6180X_PUBLIC_WRITES);
});

it('boots with the configuration it was given', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [[0xB4], [0x00]];

    new VL6180X(new VL6180XI2CTransport($bus), new VL6180XConfiguration(
        gpio1_mode: new VL6180XGPIO1Mode(active_high: true),
        averaging_sample_period: 0x10,
        als_gain: VL6180XALSGain::GAIN_20,
        vhv_repeat_rate: 0x00,
        als_integration_period: 0x12B,
        range_intermeasurement_period: 0x00,
        als_intermeasurement_period: 0x09,
        interrupt_config: new VL6180XInterruptConfig(als: VL6180XInterruptMode::DISABLED),
    ), boot_now: true);

    expect($bus->writes)->toBe([
        [0x00, 0x11, 0x30],
        [0x01, 0x0A, 0x10],
        [0x00, 0x3F, 0x40],
        [0x00, 0x31, 0x00],
        [0x00, 0x40, 0x01, 0x2B],
        [0x00, 0x2E, 0x01],
        [0x00, 0x1B, 0x00],
        [0x00, 0x3E, 0x09],
        [0x00, 0x14, 0x04],
    ]);
});

// --- ranging by polling ----------------------------------------------------------

it('ranges by polling the status register when GPIO1 is not wired', function (): void {
    [$chip, $bus] = vl6180x([[0x00], [0x40], [0x04], [0x64]]);

    expect($chip->readRange())->toBe(100)
        ->and($bus->writes)->toBe([[0x00, 0x18, 0x01], [0x00, 0x15, 0x07]])
        ->and(registersRead($bus))->toBe([0x04F, 0x04F, 0x04F, 0x062]);
});

it('reads the range as a property', function (): void {
    [$chip] = vl6180x([[0x04], [0x2A]]);

    expect($chip->range)->toBe(42);
});

it('throws when no sample is ready in time, without reading the range', function (): void {
    [$chip, $bus] = vl6180x(array_fill(0, 500, [0x00]));

    expect(fn () => $chip->readRange(5))->toThrow(VL6180XException::class, 'not ready after 5 ms')
        ->and(registersRead($bus))->not->toContain(0x062)
        ->and($bus->writes)->toBe([[0x00, 0x18, 0x01]]);
});

it('keeps the status helpers usable on their own', function (): void {
    [$chip, $bus] = vl6180x([[0x00], [0x04], [0x7B]]);

    $chip->startMeasurement();

    expect($chip->statusReady())->toBeFalse()
        ->and($chip->statusWait(50))->toBeTrue()
        ->and($chip->readRangeValue())->toBe(123);

    $chip->clearInterrupt();

    expect($bus->writes)->toBe([[0x00, 0x18, 0x01], [0x00, 0x15, 0x07]]);
});

// --- ranging on GPIO1 ------------------------------------------------------------

it('answers not ready from an idle GPIO1 without touching the bus', function (): void {
    $gpio1 = new FakeInterruptPin(4);
    [$chip, $bus] = vl6180x(transport: new VL6180XI2CTransport($b = new FakeI2CTransport, $gpio1), bus: $b);

    expect($chip->statusReady())->toBeFalse()
        ->and($gpio1->reads)->toBe(1)
        ->and($bus->write_reads)->toBe([]);
});

it('confirms an asserted GPIO1 against the status register', function (): void {
    $gpio1 = new FakeInterruptPin(4);
    $gpio1->level = false;
    [$chip, $bus] = vl6180x([[0x20]], transport: new VL6180XI2CTransport($b = new FakeI2CTransport, $gpio1), bus: $b);

    expect($chip->statusReady())->toBeFalse()
        ->and(registersRead($bus))->toBe([0x04F]);
});

it('waits on a falling GPIO1 edge, then reads the range', function (): void {
    $gpio1 = new FakeInterruptPin(4);
    $gpio1->listen_edges = [new DigitalEdgeEvent(device: 0, pin: 1, edge: SignalEdge::FALLING, timestamp: 10, seqno: 1)];
    $gpio1->level_on_edge = false;
    [$chip, $bus] = vl6180x([[0x04], [0x37]], transport: new VL6180XI2CTransport($b = new FakeI2CTransport, $gpio1), bus: $b);

    expect($chip->readRange(250))->toBe(55)
        ->and($gpio1->listens)->toHaveCount(1)
        ->and($gpio1->listens[0][0])->toBeGreaterThan(0)->toBeLessThanOrEqual(250)
        ->and(array_slice($gpio1->listens[0], 1))->toBe([false, true])
        ->and(registersRead($bus))->toBe([0x04F, 0x062])
        ->and($bus->writes)->toBe([[0x00, 0x18, 0x01], [0x00, 0x15, 0x07]]);
});

it('listens for rising edges and treats high as asserted when GPIO1 is active high', function (): void {
    $gpio1 = new FakeInterruptPin(4);
    $gpio1->level = false;
    $gpio1->listen_edges = [new DigitalEdgeEvent(device: 0, pin: 1, edge: SignalEdge::RISING, timestamp: 10, seqno: 1)];
    $gpio1->level_on_edge = true;
    [$chip] = vl6180x([[0x04]],
        transport: new VL6180XI2CTransport($b = new FakeI2CTransport, $gpio1),
        bus: $b,
        config: new VL6180XConfiguration(gpio1_mode: new VL6180XGPIO1Mode(active_high: true)),
    );

    expect($chip->statusWait(250))->toBeTrue()
        ->and(array_slice($gpio1->listens[0], 1))->toBe([true, false]);
});

it('gives up on GPIO1 when the timeout passes without an edge', function (): void {
    $gpio1 = new FakeInterruptPin(4);
    [$chip, $bus] = vl6180x(transport: new VL6180XI2CTransport($b = new FakeI2CTransport, $gpio1), bus: $b);

    expect($chip->statusWait(3))->toBeFalse()
        ->and($gpio1->listens)->not->toBeEmpty()
        ->and($bus->write_reads)->toBe([]);
});

it('polls instead when GPIO1 is wired but not an interrupt output', function (): void {
    $gpio1 = new FakeInterruptPin(4);
    [$chip, $bus] = vl6180x([[0x04], [0x10]], transport: new VL6180XI2CTransport($b = new FakeI2CTransport, $gpio1), bus: $b);

    $chip->gpio1_mode = VL6180XGPIO1Mode::none();

    expect($chip->interruptLine())->toBeNull()
        ->and($chip->readRange())->toBe(16)
        ->and($gpio1->reads)->toBe(0)
        ->and($gpio1->listens)->toBe([]);
});

it('polls instead when ranging does not raise GPIO1 on a new sample', function (): void {
    $gpio1 = new FakeInterruptPin(4);
    [$chip] = vl6180x(transport: new VL6180XI2CTransport($b = new FakeI2CTransport, $gpio1), bus: $b);

    $chip->interrupt_config = new VL6180XInterruptConfig(range: VL6180XInterruptMode::OUT_OF_WINDOW);

    expect($chip->interruptLine())->toBeNull();

    $chip->interrupt_config = new VL6180XInterruptConfig;

    expect($chip->interruptLine())->toBe($gpio1);
});

// --- settings --------------------------------------------------------------------

it('reads settings from the chip and records them in the configuration', function (): void {
    [$chip, $bus] = vl6180x([[0x30], [0x04], [0x47], [0x01, 0x2B], [0x20], [0x01], [0x13], [0x05]]);

    $gpio1 = $chip->gpio1_mode;
    $interrupt_config = $chip->interrupt_config;

    expect($gpio1->interrupt_output)->toBeTrue()
        ->and($gpio1->active_high)->toBeTrue()
        ->and($interrupt_config->range)->toBe(VL6180XInterruptMode::NEW_SAMPLE_READY)
        ->and($interrupt_config->als)->toBe(VL6180XInterruptMode::DISABLED)
        ->and($chip->als_gain)->toBe(VL6180XALSGain::GAIN_40)
        ->and($chip->als_integration_period)->toBe(299)
        ->and($chip->averaging_sample_period)->toBe(0x20)
        ->and($chip->vhv_repeat_rate)->toBe(0x01)
        ->and($chip->range_intermeasurement_period)->toBe(0x13)
        ->and($chip->als_intermeasurement_period)->toBe(0x05)
        ->and(registersRead($bus))->toBe([0x011, 0x014, 0x03F, 0x040, 0x10A, 0x031, 0x01B, 0x03E])
        ->and($chip->config()->get('gpio1_mode'))->toBe($gpio1)
        ->and($chip->config()->get('als_gain'))->toBe(VL6180XALSGain::GAIN_40)
        ->and($chip->config()->get('als_integration_period'))->toBe(299);
});

it('writes each setting to the chip, then to the configuration', function (): void {
    [$chip, $bus] = vl6180x();

    $chip->als_gain = VL6180XALSGain::GAIN_2_5;
    $chip->als_integration_period = 463;
    $chip->averaging_sample_period = 0x40;
    $chip->vhv_repeat_rate = 0x80;
    $chip->range_intermeasurement_period = 254;
    $chip->als_intermeasurement_period = 0;
    $chip->interrupt_config = VL6180XInterruptConfig::none();
    $chip->gpio1_mode = new VL6180XGPIO1Mode(active_high: true);
    $chip->fresh_out_of_reset = true;
    $chip->recalibrate();

    expect($bus->writes)->toBe([
        [0x00, 0x3F, 0x43],
        [0x00, 0x40, 0x01, 0xCF],
        [0x01, 0x0A, 0x40],
        [0x00, 0x31, 0x80],
        [0x00, 0x1B, 0xFE],
        [0x00, 0x3E, 0x00],
        [0x00, 0x14, 0x00],
        [0x00, 0x11, 0x30],
        [0x00, 0x16, 0x01],
        [0x00, 0x2E, 0x01],
    ])
        ->and($chip->config()->get('als_gain'))->toBe(VL6180XALSGain::GAIN_2_5)
        ->and($chip->config()->get('als_integration_period'))->toBe(463)
        ->and($chip->config()->get('range_intermeasurement_period'))->toBe(254)
        ->and($chip->config()->get('gpio1_mode')->active_high)->toBeTrue();
});

it('refuses out-of-range values before writing', function (string $name, int $value, string $message): void {
    [$chip, $bus] = vl6180x();

    expect(fn () => $chip->$name = $value)->toThrow(VL6180XException::class, $message)
        ->and($bus->writes)->toBe([]);
})->with([
    ['als_integration_period', 464, 'als_integration_period takes 0 to 463; got 464'],
    ['als_integration_period', -1, 'als_integration_period takes 0 to 463; got -1'],
    ['range_intermeasurement_period', 255, 'range_intermeasurement_period takes 0 to 254; got 255'],
    ['als_intermeasurement_period', 255, 'als_intermeasurement_period takes 0 to 254; got 255'],
    ['averaging_sample_period', 256, 'averaging_sample_period takes 0 to 255; got 256'],
    ['vhv_repeat_rate', -1, 'vhv_repeat_rate takes 0 to 255; got -1'],
]);

it('reads identity, reset flag and result status', function (): void {
    [$chip] = vl6180x([[0xB4], [0x01], [0x44], [0xB1]]);

    expect($chip->device_id)->toBe(0xB4)
        ->and($chip->fresh_out_of_reset)->toBeTrue();

    $interrupt = $chip->interrupt_status;
    $range = $chip->range_status;

    expect($interrupt->rangeReady())->toBeTrue()
        ->and($interrupt->error)->toBe(VL6180XInterruptError::LASER_SAFETY)
        ->and($range->error)->toBe(VL6180XRangeError::MAX_SIGNAL_TO_NOISE_RATIO)
        ->and($range->device_ready)->toBeTrue();
});

it('rejects unknown properties and configuration keys', function (): void {
    [$chip] = vl6180x();

    expect(fn () => $chip->distance)->toThrow(VL6180XException::class, "Invalid property 'distance'")
        ->and(fn () => $chip->device_id = 1)->toThrow(VL6180XException::class, "Invalid property 'device_id'")
        ->and(fn () => $chip->config()->get('slave'))->toThrow(VL6180XException::class, "Invalid property 'slave'")
        ->and(fn () => $chip->config()->set('slave', 0x29))->toThrow(VL6180XException::class, "Invalid property 'slave'");
});

// --- transport -------------------------------------------------------------------

it('throws when the bus refuses a read or answers short', function (): void {
    [$chip] = vl6180x([false, [0x01]]);

    expect(fn () => $chip->readRangeValue())->toThrow(VL6180XException::class, 'register 0x062: the bus refused a 1 byte read')
        ->and(fn () => $chip->getALSIntegrationPeriod())->toThrow(VL6180XException::class, 'register 0x040: wanted 2 bytes, got 1');
});

it('releases GPIO1 and XSHUT on close and leaves the bus to its driver', function (): void {
    $gpio1 = new FakeInterruptPin(4);
    $xshut = new FakeOutputPin(3);
    [$chip, $bus] = vl6180x(transport: new VL6180XI2CTransport($b = new FakeI2CTransport, $gpio1, $xshut), bus: $b);

    $chip->close();

    expect($gpio1->closed)->toBeTrue()
        ->and($xshut->closed)->toBeTrue()
        ->and($bus->closed)->toBeFalse();
});
