<?php

// CDATA EPON ONU status mapping (linkOnLineStatus, SYNTAX DeviceStatus, FD-OLT-MIB
// imported from EPON-EOC-MIB). Official enum (1-based, NOT 0-based):
//   notPresent(1), offline(2), online(3), normal(4), abnormal(5)
//
// Previous version of this file copied ZTE's 0-based oidOnuStatus enum
// (logging/los/syncMib/working/dyinggasp/authFailed/offline, values 0-6) which does not
// match this MIB at all — CDATA EPON status was being misread (e.g. a real "online"
// value of 3 was displayed as "syncMib"). Corrected to the real DeviceStatus enum below,
// re-mapped onto this app's existing status vocabulary so counting/coloring keeps working:
//   online/normal  -> 'online' / 'working' (healthy)
//   abnormal       -> 'los'    (flagged as a problem state, same bucket as loss-of-signal)
//   notPresent/offline -> 'offline'
// Keep both "INTEGER: n" and plain numeric keys for parser compatibility.

return [
    'INTEGER: 1' => 'offline',   // notPresent
    'INTEGER: 2' => 'offline',
    'INTEGER: 3' => 'online',
    'INTEGER: 4' => 'working',   // normal
    'INTEGER: 5' => 'los',       // abnormal

    '1' => 'offline',
    '2' => 'offline',
    '3' => 'online',
    '4' => 'working',
    '5' => 'los',

    1 => 'offline',
    2 => 'offline',
    3 => 'online',
    4 => 'working',
    5 => 'los',
];
