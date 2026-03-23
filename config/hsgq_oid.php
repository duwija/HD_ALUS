<?php

/**
 * HSGQ GPON OLT OID Configuration
 * 
 * Enterprise OID: .1.3.6.1.4.1.50224 (HSGQ Technology)
 * Device: HSGQ GPON G008
 * Tested @ 103.153.149.200
 * 
 * BREAKTHROUGH: Full SNMP support via IF-MIB + Enterprise OIDs!
 * ================================================================
 * - IF-MIB: Standard interface enumeration (ifDescr shows "ONU{PON}/{ID}")
 * - Enterprise .50224.3.12.2: ONU details (name, vendor, model, serial)
 * - Enterprise .50224.3.12.3: ONU optics (power, distance, temperature)
 * 
 * Index Format: 0x0100XXYY where XX=PON (01-16), YY=ONU (00-FF)
 * Example: PON01 ONU01 = 16777473 (0x01000101)
 *          PON02 ONU00 = 16777728 (0x01000200)
 * 
 * Discovered Fields via snmpwalk 25,570 OIDs:
 * - .12.2.1.2 = ONU Description/Name
 * - .12.2.1.8 = ONU Vendor (ZTEG, RTEG, etc.)
 * - .12.2.1.9 = ONU Model (F609V5.3, F670LV9.0, etc.)
 * - .12.2.1.15 = Serial Number (ZTEGcf82eeda, etc.)
 * - .12.3.1.4 = RX Power (dBm*100, e.g., -2100 = -21.00 dBm)
 * - .12.3.1.6 = Distance (meters?)
 */

return [
    // ==========================================
    // OLT System Information - WORKING
    // ==========================================
    'oidOltName' => '.1.3.6.1.2.1.1.5.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltUptime' => '.1.3.6.1.2.1.1.3.0',
    'oidOltDesc' => '.1.3.6.1.2.1.1.6.0',
    
    // ==========================================
    // PON Port Information - WORKING
    // ==========================================
    // PON Port Names: Hex-indexed "PON01", "PON02", etc.
    // Index: 16777472=PON01, 16777728=PON02, 16777984=PON03...
    'oidPonPortName' => '.1.3.6.1.4.1.50224.3.2.1.1.2',
    
    // ==========================================
    // IF-MIB Standard (ONU Enumeration) - WORKING
    // ==========================================
    'oidIfDescr' => '.1.3.6.1.2.1.2.2.1.2',        // Shows "ONU{PON}/{ID}"
    'oidIfOperStatus' => '.1.3.6.1.2.1.2.2.1.8',   // 1=up, 2=down
    'oidIfAdminStatus' => '.1.3.6.1.2.1.2.2.1.7',  // 1=up, 2=down, 3=testing
    
    // ==========================================
    // ONU Details - Enterprise .50224.3.12.2.1.{field}.{hexIndex}
    // ==========================================
    'oidOnuName' => '.1.3.6.1.4.1.50224.3.12.2.1.2',   // Field 2: Description
    'oidOnuVendor' => '.1.3.6.1.4.1.50224.3.12.2.1.8', // Field 8: Vendor
    'oidOnuModel' => '.1.3.6.1.4.1.50224.3.12.2.1.9',  // Field 9: Model
    'oidOnuSn' => '.1.3.6.1.4.1.50224.3.12.2.1.15',    // Field 15: Serial Number
    'oidOnuUptime' => '.1.3.6.1.4.1.50224.3.12.2.1.21', // Field 21: Uptime (Timeticks)
    'oidOnuLastReg' => '.1.3.6.1.4.1.50224.3.12.2.1.20', // Field 20: Last Registration Time
    
    // NOTE: Field 3 (.12.2.1.3) accepts SNMP SET but doesn't actually trigger reboot/reset
    // Use CLI/Telnet method instead (see reboot_hsgq_ont.py, reset_hsgq_ont.py)
    
    // Unknown fields (mostly 0/empty in current data)
    'oidOnuField4' => '.1.3.6.1.4.1.50224.3.12.2.1.4',
    'oidOnuField5' => '.1.3.6.1.4.1.50224.3.12.2.1.5',
    
    // ==========================================
    // ONU Optics - Enterprise .50224.3.12.3.1.{field}.{hexIndex}.{subIndex}
    // SubIndex: .0.0 for active, .65535.65535 for inactive
    // ==========================================
    'oidOnuRxPower' => '.1.3.6.1.4.1.50224.3.12.3.1.4',    // RX Power (dBm*100)
    'oidOnuTxPowerOnu' => '.1.3.6.1.4.1.50224.3.12.3.1.5', // TX Power ONU (dBm*100)
    'oidOnuDistance' => '.1.3.6.1.4.1.50224.3.12.3.1.6',   // Distance
    'oidOnuVoltage' => '.1.3.6.1.4.1.50224.3.12.3.1.7',    // Voltage
    'oidOnuTemperature' => '.1.3.6.1.4.1.50224.3.12.3.1.8', // Temperature
    
    // ==========================================
    // Power Calculation
    // ==========================================
    'powerDivisor' => 100, // RX Power format: dBm * 100
    
    // ==========================================
    // NOT AVAILABLE (No OIDs found for these)
    // ==========================================
    'oidOnuStatus' => null,      // Use ifOperStatus instead
    'oidOnuLastOffline' => null, // NOT AVAILABLE
    'oidOnuLastOnline' => null,  // NOT AVAILABLE
    'oidOltRxPower' => null,     // NOT AVAILABLE (might be in .12.3.1.5)
    'oidOnuTxPower' => null,     // NOT AVAILABLE (ONU TX power)
    
    // ==========================================
    // Unconfigured ONU Detection - NOT AVAILABLE
    // ==========================================
    'oidOnuUncfgSn' => null,     // NOT AVAILABLE
    'oidOnuUncfgSnG' => null,    // NOT AVAILABLE
    'oidOnuUncfgType' => null,   // NOT AVAILABLE
    
    // ==========================================
    // VLAN & Service Profile - NOT AVAILABLE
    // ==========================================
    'oidOltVlanId' => null,
    'oidOltVlanName' => null,
    'oidOltGmportProfile' => null,
    'oidOltTconProfile' => null,
    
    // ==========================================
    // Gemport - NOT AVAILABLE
    // ==========================================
    'oidOnuGmUp' => null,
    'oidOnuGm' => null,
];
