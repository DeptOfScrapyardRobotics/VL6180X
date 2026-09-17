---
type: Guide
title: Connecting a VL6180X
description: Build the sensor over I2C from the framework's protocol managers, with optional GPIO1 and XSHUT pins, or from the published wiring config.
tags: [i2c, transport, wiring, mpsse, gpio1, xshut]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: transport
    resource: src/Transports/VL6180XI2CTransport.php
    title: VL6180XI2CTransport
  - id: address
    resource: src/Enums/VL6180XI2CAddress.php
    title: VL6180XI2CAddress
---

# Shape

`I2C::driver()` → `connectTo()` → `register()` → `device(bus, 0x29)` → `new VL6180XI2CTransport($slave, $gpio1, $xshut)` → `new VL6180X(...)`.

Address fixed `0x29` (`VL6180XI2CAddress::DEFAULT`).[^address] Register index = 16-bit, high byte first; read = `writeRead([hi, lo], n)`, write = `write([hi, lo, ...data])`.[^transport]

```php
$slave = I2C::driver('usb')->connectTo('ft232h')->register()->device('ft232h', 0x29);
$slave = I2C::driver('native')->connectTo(1)->register()->device(1, 0x29);
```

# Pins

| Pin | Transport | Use |
|---|---|---|
| GPIO1 | `DigitalInTransport` | ready signal; see [ranging](/ranging.md) |
| XSHUT | `DigitalOutTransport` | boot reset pulse |

```php
$gpio1 = DigitalIO::driver('usb')->input('ft232h', 1);   // GPIOL1 (D5)
$xshut = DigitalIO::driver('usb')->output('ft232h', 2);  // GPIOL2 (D6)
```

Both optional. `close()` releases both; bus connection stays with its driver.[^transport]

# From config

Package merges `circuits.vl6180x`, never opens connections from it. App reads `configs.<name>` → `driver`, `device`, `slave`, `gpio1.*`, `xshut.*`. See [configuration](/configuration.md).

[^transport]: VL6180XI2CTransport
[^address]: VL6180XI2CAddress
