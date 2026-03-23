<?php

// HSGQ GPON Frame/Slot/Port ID Mapping
// HSGQ uses hex index format: 0x0100XXYY
// Where: 01 = fixed, 00 = reserved, XX = PON number, YY = ONU ID
// 
// Example:
// - PON01 ONU01 = 0x01000101 = 16777473
// - PON02 ONU01 = 0x01000201 = 16777729
// - PON08 ONU01 = 0x01000801 = 16779265

return [
    // PON 1-8 format
    '1' => '0.1',  // PON 1
    '2' => '0.2',  // PON 2
    '3' => '0.3',  // PON 3
    '4' => '0.4',  // PON 4
    '5' => '0.5',  // PON 5
    '6' => '0.6',  // PON 6
    '7' => '0.7',  // PON 7
    '8' => '0.8',  // PON 8
    
    // If your HSGQ has 16 PON ports
    '9' => '0.9',
    '10' => '0.10',
    '11' => '0.11',
    '12' => '0.12',
    '13' => '0.13',
    '14' => '0.14',
    '15' => '0.15',
    '16' => '0.16',
];
