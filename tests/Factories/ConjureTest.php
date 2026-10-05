<?php

use DeptOfScrapyardRobotics\Sensors\VL6180X\Providers\VL6180XServiceProvider;
use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180X;

/** The provider's config with the app's wiring over it, booted onto the bench's catalog, every slave answering boot. */
function vl6180xBench(array $i2c): array
{
    $bench = fakeBench(['circuits' => ['vl6180x' => ['default_config' => 'i2c', 'configs' => ['i2c' => [
        'driver' => 'fake', 'device' => 'ft232h', ...$i2c,
    ]]]]]);
    $bench['i2c']->replies = [[0xB4], [0x00]];
    $provider = new VL6180XServiceProvider($bench['app']);
    $provider->register();
    $provider->boot();

    return $bench;
}

it('conjures a booted sensor over I2C at 0x29, connecting the bus', function (): void {
    $bench = vl6180xBench([]);

    $chip = $bench['app']->make('circuit')->conjure('vl6180x');

    expect($chip)->toBeInstanceOf(VL6180X::class)
        ->and($chip->hasBooted())->toBeTrue()
        ->and($bench['i2c']->opened)->toBe(['ft232h'])
        ->and($bench['i2c']->slaves)->toHaveKey('ft232h:41')
        ->and($chip->transport()->interruptPin())->toBeNull()
        ->and($chip->transport()->shutdownPin())->toBeNull();
});

it('shares an I2C bus the app already connected', function (): void {
    $bench = vl6180xBench([]);
    $bench['app']->make('gpio.i2c')->driver('fake')->connectTo('ft232h')->register();

    $bench['app']->make('circuit')->conjure('vl6180x');

    expect($bench['i2c']->opened)->toBe(['ft232h']);
});

it('wires XSHUT and GPIO1 after the bus and pulses XSHUT at boot', function (): void {
    $bench = vl6180xBench([
        'xshut' => ['enabled' => true, 'driver' => 'fake', 'device' => 'ft232h', 'pin' => 0],
        'gpio1' => ['enabled' => true, 'driver' => 'fake', 'device' => 'ft232h', 'pin' => 1],
    ]);

    $chip = $bench['app']->make('circuit')->conjure('vl6180x');

    expect($chip->transport()->shutdownPin())->toBe($bench['digital']->outputs['ft232h:0'])
        ->and($chip->transport()->interruptPin())->toBe($bench['digital']->inputs['ft232h:1'])
        ->and($bench['digital']->outputs['ft232h:0']->levels)->toBe([false, true])
        ->and($bench['digital']->opened)->toBe(['ft232h']);
});

it('builds without booting when boot_now is false', function (): void {
    $bench = vl6180xBench(['boot_now' => false]);

    $chip = $bench['app']->make('circuit')->conjure('vl6180x');

    expect($chip->hasBooted())->toBeFalse()
        ->and($bench['i2c']->slaves['ft232h:41']->writes)->toBe([]);
});
