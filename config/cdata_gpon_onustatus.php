<?php

// CDATA GPON ONU status mapping (gponOnuRunState, CDATA-GPON-MIB)
// Only two states exist for this object — online(1) / offline(2).
// Keep both "INTEGER: n" and plain numeric keys for parser compatibility.

return [
    'INTEGER: 1' => 'online',
    'INTEGER: 2' => 'offline',

    '1' => 'online',
    '2' => 'offline',

    1 => 'online',
    2 => 'offline',
];
