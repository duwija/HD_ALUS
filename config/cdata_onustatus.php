<?php

// CDATA FD-OLT-MIB status mapping (DeviceStatus)
// Keep both "INTEGER: n" and plain numeric keys for parser compatibility.

return [
    'INTEGER: 0' => 'logging',
    'INTEGER: 1' => 'los',
    'INTEGER: 2' => 'syncMib',
    'INTEGER: 3' => 'working',
    'INTEGER: 4' => 'dyinggasp',
    'INTEGER: 5' => 'authFailed',
    'INTEGER: 6' => 'offline',

    '0' => 'logging',
    '1' => 'los',
    '2' => 'syncMib',
    '3' => 'working',
    '4' => 'dyinggasp',
    '5' => 'authFailed',
    '6' => 'offline',

    0 => 'logging',
    1 => 'los',
    2 => 'syncMib',
    3 => 'working',
    4 => 'dyinggasp',
    5 => 'authFailed',
    6 => 'offline',
];
