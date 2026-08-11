<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Providers;

use DeptOfScrapyardRobotics\Sensors\VL6180X\VL6180X;
use Fabricate\NutsAndBolts\ServiceProvider;
use GeneralPurposeIO\Core\MagicAliases\Circuit;

class VL6180XServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Circuit::addCircuit('vl6180x', VL6180X::class);
    }
}
