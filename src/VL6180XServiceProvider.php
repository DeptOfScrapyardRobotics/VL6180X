<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use Fabricate\NutsAndBolts\MagicAliases\Circuit;
use Fabricate\NutsAndBolts\ServiceProvider;

class VL6180XServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Circuit::addCircuit('vl6180x', VL6180X::class);
    }
}
