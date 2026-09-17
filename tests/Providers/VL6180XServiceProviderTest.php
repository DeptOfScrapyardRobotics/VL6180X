<?php

use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180XI2CAddress;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Providers\VL6180XServiceProvider;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\ConfigPathVessel;
use Voyager\Config\Repository;
use Voyager\NutsAndBolts\ServiceProvider;
use Voyager\Vessel\Vessel;

it('registers the config under circuits.vl6180x, keeping anything the app already set', function (): void {
    $vessel = new Vessel;
    $vessel->instance('config', new Repository(['circuits' => ['vl6180x' => ['default_config' => 'bench']]]));

    (new VL6180XServiceProvider($vessel))->register();

    $config = $vessel->make('config');

    expect($config->get('circuits.vl6180x.default_config'))->toBe('bench')
        ->and($config->get('circuits.vl6180x.configs.i2c.slave'))->toBe(VL6180XI2CAddress::DEFAULT->value)
        ->and($config->get('circuits.vl6180x.configs.i2c.gpio1.enabled'))->toBeFalse()
        ->and($config->get('circuits.vl6180x.configs.i2c.xshut.pin'))->toBe(1)
        ->and($config->has('vl6180x'))->toBeFalse();
});

it('leaves other circuits config beside its own key untouched', function (): void {
    $vessel = new Vessel;
    $vessel->instance('config', new Repository(['circuits' => ['adxl345' => ['default_config' => 'spi']]]));

    (new VL6180XServiceProvider($vessel))->register();

    $config = $vessel->make('config');

    expect($config->get('circuits.adxl345'))->toBe(['default_config' => 'spi'])
        ->and($config->get('circuits.vl6180x.default_config'))->toBe('i2c');
});

it('publishes the config file into config/circuits under the vl6180x-config tag', function (): void {
    $app = new ConfigPathVessel('/app/config');
    $app->instance('config', new Repository);

    $provider = new VL6180XServiceProvider($app);
    $provider->register();
    $provider->boot();

    $root = dirname(__DIR__, 2);

    expect(ServiceProvider::pathsToPublish(VL6180XServiceProvider::class, 'vl6180x-config'))->toBe([
        "{$root}/config/vl6180x.php" => '/app/config/circuits/vl6180x.php',
    ]);
});
