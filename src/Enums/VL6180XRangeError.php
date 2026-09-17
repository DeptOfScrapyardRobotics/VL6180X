<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X\Enums;

/** Bits 7:4 of RESULT__RANGE_STATUS. */
enum VL6180XRangeError: int
{
    case NONE = 0;
    case VCSEL_CONTINUITY_TEST = 1;
    case VCSEL_WATCHDOG_TEST = 2;
    case VCSEL_WATCHDOG = 3;
    case PLL1_LOCK = 4;
    case PLL2_LOCK = 5;
    case EARLY_CONVERGENCE_ESTIMATE = 6;
    case MAX_CONVERGENCE = 7;
    case NO_TARGET_IGNORE = 8;
    case NOT_USED_9 = 9;
    case NOT_USED_10 = 10;
    case MAX_SIGNAL_TO_NOISE_RATIO = 11;
    case RAW_RANGING_ALGO_UNDERFLOW = 12;
    case RAW_RANGING_ALGO_OVERFLOW = 13;
    case RANGING_ALGO_UNDERFLOW = 14;
    case RANGING_ALGO_OVERFLOW = 15;
}
