# Traps

* [gpio1-active-low-flag.md](/traps/gpio1-active-low-flag.md) - leave the GPIO1 input's active_low false; polarity lives in gpio1_mode
* [fresh-out-of-reset.md](/traps/fresh-out-of-reset.md) - private settings load only when the flag reads 1; without XSHUT a warm chip skips them
* [range-error-first.md](/traps/range-error-first.md) - check range_status error before trusting a range
* [wait-path-fallback.md](/traps/wait-path-fallback.md) - GPIO1 silently not used unless gpio1_mode + interrupt_config route ranging to it
