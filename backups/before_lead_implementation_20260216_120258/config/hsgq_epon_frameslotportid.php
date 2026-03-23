<?php

// HSGQ EPON Frame/Slot/Port ID Mapping
// EPON uses different index format than GPON
// Typically PON port index format

return [
    // EPON PON 1-8 format (adjust based on your hardware)
    '1' => '0.1',  // EPON PON 1
    '2' => '0.2',  // EPON PON 2
    '3' => '0.3',  // EPON PON 3
    '4' => '0.4',  // EPON PON 4
    '5' => '0.5',  // EPON PON 5
    '6' => '0.6',  // EPON PON 6
    '7' => '0.7',  // EPON PON 7
    '8' => '0.8',  // EPON PON 8
    
    // If your HSGQ EPON has 16 PON ports
    '9' => '0.9',
    '10' => '0.10',
    '11' => '0.11',
    '12' => '0.12',
    '13' => '0.13',
    '14' => '0.14',
    '15' => '0.15',
    '16' => '0.16',
];
