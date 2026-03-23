<?php

// HSGQ GPON ONU Status Mapping
// OID: .1.3.6.1.4.1.3320.10.3.3.1.4

return [
    '0' => 'offline',      // off-line (generic)
    '1' => 'inactive',     // inactive
    '2' => 'disable',      // disable (admin down)
    '3' => 'working',      // active (online)
    '4' => 'los',          // LOS - Laser out (no signal from ONU)
    '5' => 'powerdown',    // Power down (ONU powered off)
];
