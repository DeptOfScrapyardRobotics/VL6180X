<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Enums;

/** What raises GPIO1 for one measurement kind: the 3-bit field in SYSTEM__INTERRUPT_CONFIG_GPIO and RESULT__INTERRUPT_STATUS_GPIO. */
enum VL6180XInterruptMode: int
{
    case DISABLED = 0;
    case LEVEL_LOW = 1;
    case LEVEL_HIGH = 2;
    case OUT_OF_WINDOW = 3;
    case NEW_SAMPLE_READY = 4;
}
