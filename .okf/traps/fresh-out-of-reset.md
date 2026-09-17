---
type: Trap
title: Fresh-out-of-reset gate
description: Boot loads ST's private settings only when SYSTEM__FRESH_OUT_OF_RESET reads 1; they are lost on power loss and reloaded only after a reset.
tags: [trap, boot, xshut, reset]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: bootstrap
    resource: src/Concerns/VL6180XBootstrap.php
    title: VL6180XBootstrap::_boot()
---

# Trap

Boot clears the flag after private writes.[^bootstrap] Later boots on same power cycle skip private writes — intended. Setting `fresh_out_of_reset = true` by hand then booting a new instance rewrites them.

# Use

Wire XSHUT → every boot resets chip → flag 1 → full sequence. Without XSHUT, boot trusts current chip state.

[^bootstrap]: VL6180XBootstrap::_boot()
