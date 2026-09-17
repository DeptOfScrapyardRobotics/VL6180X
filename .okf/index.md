---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/vl6180x — knowledge bundle

VL6180X time-of-flight range sensor driver for `scrapyard-io/framework` 0.8. I2C, 16-bit registers, ST boot settings, single-shot ranging in mm, GPIO1 edge wait or status polling, optional XSHUT.

Read this index first, open only concepts task needs. Every concept `status: draft` until human verifies.

# Concepts

* [overview.md](/overview.md) - package identity, requires, classes, boot, errors
* [connecting.md](/connecting.md) - I2C transport, GPIO1 + XSHUT pins, usb / native adapters, wiring from config
* [ranging.md](/ranging.md) - readRange(), step helpers, GPIO1 vs polling wait path, result status
* [settings.md](/settings.md) - VL6180XConfiguration, getters/setters, property table, breakouts
* [configuration.md](/configuration.md) - circuits.vl6180x keys, publish tag

# Traps

* [traps/](/traps/index.md) - GPIO1 active_low flag, fresh-out-of-reset gate, range error before value, wait-path fallback

# Log

* [log.md](/log.md)
