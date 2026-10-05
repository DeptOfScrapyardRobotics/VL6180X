---
type: Guide
title: Connecting a VL6180X
description: conjure() and the i2c() factory, the optional GPIO1 and XSHUT pins, and building the transport by hand.
tags: [i2c, transport, wiring, mpsse, gpio1, xshut]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T01:30:00Z }
sources:
  - id: transport
    resource: src/Transports/VL6180XI2CTransport.php
    title: VL6180XI2CTransport
  - id: address
    resource: src/Enums/VL6180XI2CAddress.php
    title: VL6180XI2CAddress
  - id: factory
    resource: src/Concerns/ConjuresVL6180X.php
    title: ConjuresVL6180X
---

# conjure

`app('circuit')->conjure('vl6180x')` → `VL6180X::i2c(driver, device, slave = 0x29, gpio1 = [], xshut = [], boot_now = true)`.[^factory] Bus from `gpio.i2c` (shared if the app connected it); then GPIO1 input and XSHUT output from `gpio.digital` when their `enabled` is true, so FT232H pins ride the bus's context.

Address fixed `0x29` (`VL6180XI2CAddress::DEFAULT`).[^address] Register index = 16-bit, high byte first; read = `writeRead([hi, lo], n)`, write = `write([hi, lo, ...data])`.[^transport]

# Pins

| Pin | Transport | Use |
|---|---|---|
| GPIO1 | `DigitalInTransport` | ready signal; see [ranging](/ranging.md) |
| XSHUT | `DigitalOutTransport` | boot reset pulse |

Both optional. `close()` releases both; bus connection stays with its driver.[^transport] Open GPIO1 with the driver's default polarity (`active_low` false): the driver decides asserted from `gpio1_mode->active_high`, and an inverted line would look asserted while idle. Change polarity through `gpio1_mode`.

Bench: FT232H, I2C switch on, XSHUT D4 (pin 0), GPIO1 D5 (pin 1).

# By hand

```php
$slave = app('gpio.i2c')->driver('usb')->connectTo('ft232h')->register()->device('ft232h', 0x29);
$pins = app('gpio.digital')->driver('usb');
$tof = new VL6180X(new VL6180XI2CTransport($slave, $pins->input('ft232h', 1), $pins->output('ft232h', 0)), boot_now: true);
```

[^transport]: VL6180XI2CTransport
[^address]: VL6180XI2CAddress
[^factory]: ConjuresVL6180X
