# Security Policy

## Supported versions

vl6180x is pre-1.0. No 0.x release receives security fixes or advisories; fixes land in the next
release line. Security support starts with 1.0.

| Version | Security fixes |
|---------|----------------|
| < 1.0   | No             |

## Reporting a vulnerability

Please don't open a public issue for a security problem.

Report it privately through GitHub: the **Report a vulnerability** button on this repository's
**Security** tab. If that isn't available, email **info@projectsaturnstudios.com**.

Include what you found, the affected version, the adapter and hardware in use, and steps to
reproduce. Reports are read and weighed for the release line in development; before 1.0 there is
no response-time commitment.

## Security model

vl6180x is plain PHP. It reads and writes VL6180X registers through the bus and pin transports
`scrapyard-io/framework` hands it, and holds no native code of its own. What a PHP process may
touch is decided below it: the adapter (`microscrap/scrapyard-linux` over ext-posi,
`microscrap/scrapyard-usb` over ext-ftdi) and the operating system's permissions on the I2C,
GPIO or USB device. Grant those through device groups or udev rules scoped to the hardware, not by
running PHP as root.

- **Configuration is trusted input.** `conjure()` connects whatever bus, GPIO1 and XSHUT pins the
  `circuits.vl6180x` config names, and drives XSHUT as an output. Keep that config under the app's
  control: a wrong pin number drives another device's line.
- **Settings are range-checked.** Register settings outside their ranges throw before anything is
  written, and a refused or short bus read throws instead of returning partial data.
- **Measurements are data.** A range value is only a distance when its status reports no error.

A report is in scope when this package writes a register it was not asked to, drives a pin other
than its configured XSHUT, or lets a well-formed call leave the sensor in a state its
configuration does not describe. Weaknesses in an adapter or extension belong to that package's
own policy.
