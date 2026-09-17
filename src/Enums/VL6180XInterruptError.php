<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Enums;

/** Bits 7:6 of RESULT__INTERRUPT_STATUS_GPIO. */
enum VL6180XInterruptError: int
{
    case NONE = 0;
    case LASER_SAFETY = 1;
    case PLL = 2;
    case RESERVED = 3;
}
