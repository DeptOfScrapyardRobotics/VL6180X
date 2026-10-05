<?php

/*
| Proven against a recording fake I2C transport and fake GPIO1 / XSHUT pins:
| every register byte the chip would see, every byte it would answer.
| Nothing here touches a bus. The live check is a VL6180X on an FT232H at 0x29.
*/

/*
| A Venusian app's core defines config() over the container's config repository;
| CircuitRegistry::conjure() calls it. A package suite has no core, so this
| stands in for it the same way.
*/
if (! function_exists('config')) {
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        $config = \Voyager\Vessel\ControlPanel::getInstance()->make('config');

        return match (true) {
            is_null($key) => $config,
            is_array($key) => $config->set($key),
            default => $config->get($key, $default),
        };
    }
}

/**
 * The shared container as an app sets it up: config, the circuit catalog, and the I2C and digital managers, each
 * with a 'fake' driver.
 *
 * @return array{i2c: \DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\FakeI2CConnectionDriver, digital: \DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\FakeDigitalIOConnectionDriver, app: \DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\ConfigPathVessel}
 */
function fakeBench(array $config = []): array
{
    $app = new \DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\ConfigPathVessel;
    $app->registerInstance('config', new \Voyager\Config\Repository($config));
    $app->registerInstance('circuit', new \GeneralPurposeIO\IntegratedCircuits\CircuitRegistry);
    \Voyager\Vessel\ControlPanel::setInstance($app);

    $bench = [
        'i2c' => new \DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\FakeI2CConnectionDriver,
        'digital' => new \DeptOfScrapyardRobotics\Sensors\VL6180X\Tests\Support\FakeDigitalIOConnectionDriver,
        'app' => $app,
    ];

    $app->registerInstance('gpio.i2c', (new \GeneralPurposeIO\I2C\I2CConnectionManager($app))->extend('fake', fn () => $bench['i2c']));
    $app->registerInstance('gpio.digital', (new \GeneralPurposeIO\Digital\DigitalOConnectionManager($app))->extend('fake', fn () => $bench['digital']));

    return $bench;
}

pest()->afterEach(function (): void {
    \Voyager\Vessel\ControlPanel::setInstance(null);
})->in(__DIR__);
