# dept-of-scrapyard-robotics/vl6180x Update Log

## 2026-10-04
* **Update**: 0.10 port. [overview](/overview.md): 0.10 split requires, catalog slug, `notConnected`. [connecting](/connecting.md) rewritten for `conjure()` and `VL6180X::i2c()`. [configuration](/configuration.md): catalog, `boot_now`.
* **Update**: [ranging](/ranging.md): live reference on the FT232H with GPIO1 and XSHUT.
* **Removal**: the four 0.8 warning notes (fresh out of reset, GPIO1 polarity, range error first, wait-path fallback), folded into [overview](/overview.md), [connecting](/connecting.md) and [ranging](/ranging.md).

## 2026-09-16
* **Update**: [connecting](/connecting.md) FT232H pins GPIO1 → GPIOL1, XSHUT → GPIOL2; [ranging](/ranging.md) measured status-read counts per path.
* **Creation**: bundle seeded for 0.8.0 — [overview](/overview.md), [connecting](/connecting.md), [ranging](/ranging.md), [settings](/settings.md), [configuration](/configuration.md), four [traps](/traps/index.md).
