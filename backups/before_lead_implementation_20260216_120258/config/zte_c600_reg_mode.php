<?php

// ZTE C600/C620/C650 ONU Registration Mode Mapping
// OID: .1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.5
// Returns INTEGER value

return [
    1 => 'regModeSn',                      // Register by Serial Number
    2 => 'regModePwd',                     // Register by Password
    3 => 'regModeSnPlusPwd',               // Register by SN + Password
    4 => 'regModeRegisterId',              // Register by Register ID
    5 => 'regModeRegisterIdPlus8021x',     // Register ID + 802.1x
    6 => 'regModeRegisterIdPlusMutual',    // Register ID + Mutual
    7 => 'regModeHexPwd',                  // Hex Password
    8 => 'regModeSnPlusHexPwd',            // SN + Hex Password
    9 => 'regModeLoid',                    // LOID
    10 => 'regModeLoidPlusPwd',            // LOID + Password
    11 => 'regModeSnPlusRegisterId',       // SN + Register ID
    12 => 'regModePsk',                    // Pre-Shared Key
];
