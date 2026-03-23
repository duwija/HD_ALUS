<?php

// ZTE C600/C620/C650 Series OID Configuration
// Tested on: ZTE C620 V1.2.1 @ 103.156.74.17
// Base OID: .1.3.6.1.4.1.3902.1082 (different from C300/C320)
// MIB References: ZTE-AN-PON-BASE-MIB, ZTE-AN-OPTICAL-MODULE-MIB
//
// Index format differences:
// - Branch .10.1.2.4.1: shelf.slot.onuid (e.g., 1.1.3 for shelf 1, slot 1, ONU 3)
// - Branch .500.10.2.3: encoded ID (e.g., 285278721 = frame*16777216 + slot*65536 + port*256)
//
// Limitation: Optical power (RX/TX) not available via SNMP with public_ro community
// Solution: Use SSH/Telnet CLI commands for detailed optical metrics

return [

	'oidOltName' => '.1.3.6.1.2.1.1.5.0',
	'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
	'oidOltUptime' => '.1.3.6.1.2.1.1.3.0',
	'oidOltDesc' => '.1.3.6.1.2.1.1.6.0',
	'oidOltRxPower' => '.1.3.6.1.4.1.3902.1082.500.1.2.4.2.1.2', //Olt Rx Power (requires encoded index)
	
	// ONU Basic Info (Branch: .1082.10.1.2.4.1)
	// Index format: shelf.slot.onuid
	// NOTE: Branch .10.1.2.4.1.4 returns CARD TYPE (HFTH/FCSDA), NOT ONU names!
	// NOTE: Branch .10.1.2.4.1.5 returns CARD STATUS, NOT ONU status!
	// C620 doesn't have user-defined ONU names in this branch
	'oidOnuName' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.1', // ONU Type (branch .500) - Use this for ONU list ✓
	'oidOnuStatus' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.4', // ONU Phase State (branch .500): 4=working, 7=offline ✓
	'oidOnuId' => '.1.3.6.1.4.1.3902.1082.10.1.2.4.1.2', // ONU ID: INTEGER (e.g., 662529) ✓
	'oidOnuVersion' => '.1.3.6.1.4.1.3902.1082.10.1.2.4.1.23', // Firmware version (e.g., "V1.0.0") ✓
	'oidOnuDeviceId' => '.1.3.6.1.4.1.3902.1082.10.1.2.4.1.14', // Device ID (partial SN, numeric) ✓
	
	// ONU Management Info (Branch: .1082.500.10.2.3.3.1)
	// Index format: encoded.onuid
	'oidOnuSn' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.6', // Full Serial Number ✓ NEW! (Hex-STRING)
	'oidOnuName' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.2', // ONU Name/Description ✓ NEW! (STRING)
	'oidOnuModel' => '.1.3.6.1.4.1.3902.1082.500.20.2.1.2.1.8', // ONU Model ✓ NEW! (STRING: "F670LV9.0", "FD512XW-R460")
	'oidOnuType' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.1', // ONU Type (STRING: "ZTE_ALL") ✓
	'oidOnuRegMode' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.5', // Registration Mode (INTEGER: 1=regModeSn) ✓
	
	// Advanced ONU Info (Branch: .1082.500.10.2.3 and .1082.500.20.2.1.2.1)
	// Index format: encoded.onuid (e.g., 285278721.3 for ONU 3)
	// Base index: (frame*16777216 + slot*65536 + port*256 + 1)
	// Full index: baseIndex.{onuid}
	'oidOnuDistance' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.10.1.2', // Distance in meters ✓
	'oidOnuLastOnline' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.5', // Octet String → DateTime ✓ (index: encoded.onuid)
	'oidOnuLastOffline' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.6', // Octet String → DateTime ✓ (index: encoded.onuid)
	'oidOnuLastOfflineReason' => '.1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.7', // INTEGER: 1=unknown, 2=LOS, etc ✓ (index: encoded.onuid)
	'oidOnuUptime' => '.1.3.6.1.4.1.3902.1082.500.20.2.1.2.1.18', // Uptime ✓ NEW! (TimeTicks: "3 days, 2:01:28.00")
	
	// Optical Power OIDs (VERIFIED on C620 @ 103.156.74.17)
	// Index format varies by metric:
	// - OLT RX: encoded.{onuid} (e.g., 285278721.3)
	// - ONU TX: encoded.{onuid}.1 (e.g., 285278721.3.1)
	
	'oidOltRxFromOnu' => '.1.3.6.1.4.1.3902.1082.500.1.2.4.2.1.2', // OLT RX Power ✓
	// Formula: value / 1000 = dBm (e.g., -21662 → -21.662 dBm)
	// Index: {encoded}.{onuid}
	// Invalid: -80000 (offline)
	
	'oidOnuTxPower' => '.1.3.6.1.4.1.3902.1082.500.20.2.2.2.1.14', // ONU TX Power ✓ NEW!
	// Formula: (value - 13000) / 1000 = dBm (e.g., 16179 → 3.179 dBm)
	// Index: {encoded}.{onuid}.1
	// Invalid: 65535 (offline)
	
	// Not available via SNMP (requires CLI parsing):
	'oidOnuRxPower' => null, // ONU RX Power - CLI only
	'oidOltTxPower' => null, // OLT TX Power - CLI only
	
	// Note: All ONU details (Model, SN, Name, Uptime, etc.) are defined above in branch .500
	
	// Unconfigured ONU Detection
	// Note: OID .1082.10.1.2.3.1.5 returns stale/historical data, not actual unconfigured ONUs
	// CLI 'show pon onu uncfg' is the accurate source. Disable SNMP query for C620.
	'oidOnuUncfgSn'=> null, // Disabled - returns historical data
	'oidOnuUncfgSnG'=> null, // Disabled - returns historical data
	'oidOnuUncfgType' => null, // Disabled

	// VLAN & Profile Configuration
	'oidOltVlanId' => '.1.3.6.1.4.1.3902.1015.20.2.1.2',
	'oidOltVlanName'  => '.1.3.6.1.4.1.3902.1082.10.1.2.2.1.2',
	'oidOltGmportProfile' => '.1.3.6.1.4.1.3902.1082.10.1.2.2.1.2',
	'oidOltTconProfile' => '.1.3.6.1.4.1.3902.1082.10.1.2.1.1.2',
	
	// Helper functions
	'encodeIndex' => function($frame, $slot, $port, $onuid = 0) {
		// Encode frame/slot/port/onuid to index for .500 branch
		// Example: frame=1, slot=1, port=1, onuid=3 
		// Result: 16777216 + 65536 + 256 + 3 = 16843011
		// Actual tested: 285278721 for slot 1 port 1 (17*16777216 + 1*65536 + 1*256 + 1)
		return ($frame * 16777216) + ($slot * 65536) + ($port * 256) + $onuid;
	},
	
	'convertOpticalPower' => function($snmpValue, $type = 'olt_rx') {
		// Convert SNMP integer value to dBm
		// Different formulas for different power types
		
		// Check for invalid/offline values first
		if ($type === 'onu_tx') {
			if ($snmpValue >= 65535 || $snmpValue <= 0) {
				return null; // Invalid or offline
			}
			// Formula: (value - 13000) / 1000
			// Example: 16179 → (16179 - 13000) / 1000 = 3.179 dBm
			return ($snmpValue - 13000) / 1000;
		}
		
		// Default: OLT RX Power
		if ($snmpValue <= -80000 || $snmpValue >= 80000) {
			return null; // Invalid or no signal
		}
		// Formula: value / 1000
		// Example: -21662 → -21.662 dBm
		return $snmpValue / 1000;
	},
	
	'convertOctetStringToDateTime' => function($octetString) {
		// Convert SNMP Octet String to DateTime
		// Format: Hex-STRING: 07 E5 01 18 0A 1E 2D 00 2B 00 00
		// Means: Year 2021 (0x07E5), Month 01, Day 18, Hour 10, Min 30, Sec 45
		// Timezone: +00:00
		
		// Remove prefix if exists
		$hex = str_replace(['Hex-STRING: ', ' '], '', $octetString);
		
		// Parse hex pairs
		if (strlen($hex) < 16) {
			return null; // Invalid format
		}
		
		$year = hexdec(substr($hex, 0, 4));
		$month = hexdec(substr($hex, 4, 2));
		$day = hexdec(substr($hex, 6, 2));
		$hour = hexdec(substr($hex, 8, 2));
		$min = hexdec(substr($hex, 10, 2));
		$sec = hexdec(substr($hex, 12, 2));
		
		try {
			return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $min, $sec);
		} catch (\Exception $e) {
			return null;
		}
	},
	
	'decodeOnuSerialNumber' => function($hexString) {
		// Decode ONU Serial Number from Hex-STRING
		// Format: "5A 54 45 47 D5 D1 BB 96" → "ZTEGD5D1BB96"
		// First 4 bytes: Vendor ID (ASCII: ZTEG, CDTC, etc.)
		// Last 4 bytes: Serial Number (Hex uppercase)
		
		$hex = str_replace([' ', 'Hex-STRING:', 'STRING:'], '', $hexString);
		$hex = trim($hex);
		
		if (strlen($hex) < 16) {
			return null; // Invalid format
		}
		
		// First 4 bytes: Vendor ID (ASCII)
		$vendor = '';
		for ($i = 0; $i < 8; $i += 2) {
			$vendor .= chr(hexdec(substr($hex, $i, 2)));
		}
		
		// Last 4 bytes: Serial (Hex uppercase)
		$serial = strtoupper(substr($hex, 8, 8));
		
		return $vendor . $serial;
	},
	
	// CLI Commands for unavailable SNMP data:
	// - Serial Number: show gpon onu detail-info gpon_onu-{frame}/{slot}/{port}:{onuid}
	// - Optical Power: show pon power attenuation gpon_onu-{frame}/{slot}/{port}:{onuid}
	//   Returns: OLT Rx, ONU Tx, OLT Tx, ONU Rx in dBm format
	// - ONU List: show gpon onu state gpon-olt_{frame}/{slot}/{port}
];

?>
