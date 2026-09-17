<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Providers;

use Voyager\NutsAndBolts\ServiceProvider;

/**
 * The sensor's wiring config lives under the circuits tree: config('circuits.vl6180x'),
 * published to config/circuits/vl6180x.php, which the config loader keys the same way.
 */
class VL6180XServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/vl6180x.php', 'circuits.vl6180x');
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/vl6180x.php' => $this->app->configPath('circuits/vl6180x.php'),
        ], 'vl6180x-config');
    }
}
