<?php

/*
| Proven against a recording fake I2C transport and fake GPIO1 / XSHUT pins:
| every register byte the chip would see, every byte it would answer.
| Nothing here touches a bus. The live check is a VL6180X on an FT232H at 0x29.
*/
