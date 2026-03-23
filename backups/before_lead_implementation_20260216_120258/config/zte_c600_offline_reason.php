<?php

// ZTE C600/C620/C650 ONU Last Offline Reason Mapping
// OID: .1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.7
// Returns INTEGER value

return [
    1 => 'unknown',
    2 => 'LOS',           // Loss of Signal
    3 => 'LOSi',          // Loss of Signal indication
    4 => 'LOFi',          // Loss of Frame indication
    5 => 'sfi',           // Signal Fail indication
    6 => 'loai',          // Loss of Acknowledgement indication
    7 => 'loami',         // Loss of Acknowledgement Message indication
    8 => 'AuthFail',      // Authentication Failed
    9 => 'PowerOff',      // ONU Powered Off
    10 => 'deactiveSucc', // Deactivation Success
    11 => 'deactiveFail', // Deactivation Failed
    12 => 'Reboot',       // ONU Rebooted
    13 => 'Shutdown',     // ONU Shutdown
];
