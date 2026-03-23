<?php

// ZTE C600/C620/C650 ONT Status Configuration
// Based on testing with C620 V1.2.1 @ 103.156.74.17
// OID: .1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.4 (ONU Phase State)
// Note: This is DIFFERENT from C300/C320!

return [
	'INTEGER: 0' => 'unknown',      // Unknown state
	'INTEGER: 1' => 'logging',      // Logging in
	'INTEGER: 2' => 'los',          // Loss of signal
	'INTEGER: 3' => 'syncMib',      // Synchronizing MIB
	'INTEGER: 4' => 'working',      // Working/Online - VERIFIED ✓
	'INTEGER: 5' => 'dyinggasp',    // Dying gasp
	'INTEGER: 6' => 'authFailed',   // Authentication failed
	'INTEGER: 7' => 'offline',      // Offline - VERIFIED ✓
];

?>
