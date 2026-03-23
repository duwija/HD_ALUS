<?php

// HSGQ EPON OLT OID Configuration
// Enterprise OID: .1.3.6.1.4.1.3320 (HSGQ Technology)
// EPON Branch: .1.3.6.1.4.1.3320.101.* (Blue color in documentation)
// Note: HSGQ EPON uses different index format than GPON

return [
    // ==========================================
    // OLT System Information (Same as GPON)
    // ==========================================
    'oidOltName' => '.1.3.6.1.2.1.1.5.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltUptime' => '.1.3.6.1.2.1.1.3.0',
    'oidOltDesc' => '.1.3.6.1.2.1.1.6.0',
    'oidOltContact' => '.1.3.6.1.2.1.1.4.0',
    'oidOltLocation' => '.1.3.6.1.2.1.1.6.0',
    'oidOltSystemTime' => '.1.3.6.1.4.1.3320.9.225.1.4.0',
    
    // System Resources
    'oidOltCpuUsage' => '.1.3.6.1.4.1.3320.9.109.1.1.1.1',
    'oidOltMemoryUsage' => '.1.3.6.1.4.1.3320.9.48.1',
    'oidOltTemperature' => '.1.3.6.1.4.1.3320.9.181.1.1.7',
    'oidOltFan' => '.1.3.6.1.4.1.3320.9.187.3',
    
    // ==========================================
    // PON Port Information (EPON)
    // ==========================================
    // PON Port Optical Power
    'oidPonPortTxPower' => '.1.3.6.1.4.1.3320.101.107.1.3', // EPON
    'oidPonPortRxPower' => '.1.3.6.1.4.1.3320.101.108.1.3', // EPON
    'oidOltRxPower' => '.1.3.6.1.4.1.3320.101.108.1.3', // Alias for compatibility
    
    // Interface Status & Description
    'oidInterfaceStatus' => '.1.3.6.1.2.1.2.2.1.8',
    'oidInterfaceDesc' => '.1.3.6.1.2.1.2.2.1.2',
    'oidInterfaceAlias' => '.1.3.6.1.2.1.31.1.1.1.18',
    
    // Interface Traffic/Bandwidth
    'oidIfInBitRate' => '.1.3.6.1.4.1.3320.9.64.4.1.1.6', // ifIn5MinBitRate
    'oidIfOutBitRate' => '.1.3.6.1.4.1.3320.9.64.4.1.1.8', // ifOut5MinBitRate
    'oidIfInOctets' => '.1.3.6.1.2.1.31.1.1.1.6',
    'oidIfOutOctets' => '.1.3.6.1.2.1.31.1.1.1.10',
    
    // Interface PPS (Packets Per Second)
    'oidIfInPktRate' => '.1.3.6.1.4.1.3320.9.64.4.1.1.7', // ifIn5MinPktRate
    'oidIfOutPktRate' => '.1.3.6.1.4.1.3320.9.64.4.1.1.9', // ifOut5MinPktRate
    
    // ==========================================
    // ONU Information (EPON)
    // ==========================================
    // ONU Serial Number & MAC
    'oidOnuSn' => '.1.3.6.1.4.1.3320.101.10.1.1.3', // EPON - from ONU
    'oidOnuSnBind' => '.1.3.6.1.4.1.3320.101.11.1.1.2', // EPON - bind in config (no epon bind-onu-sequence)
    'oidOnuMac' => '.1.3.6.1.4.1.3320.101.11.1.1.2', // EPON ONU MAC (same as bind)
    
    // ONU Status
    'oidOnuStatus' => '.1.3.6.1.4.1.3320.101.11.1.1.6', // EPON
    // Status values: off-line(0), inactive(1), disable(2), active(3)
    
    // ONU Optical Power
    'oidOnuRxPower' => '.1.3.6.1.4.1.3320.101.10.5.1.5', // EPON - ONU RX Power
    'oidOnuTxPower' => '.1.3.6.1.4.1.3320.101.10.5.1.6', // EPON - ONU TX Power
    
    // ONU Details
    'oidOnuName' => '.1.3.6.1.2.1.31.1.1.1.18', // Interface alias
    'oidOnuModel' => '.1.3.6.1.4.1.3320.101.10.1.1.2', // ONU Type/Model (EPON)
    'oidOnuVendor' => '.1.3.6.1.4.1.3320.101.10.1.1.2', // Same as model for EPON
    'oidOnuDistance' => '.1.3.6.1.4.1.3320.101.10.1.1.27', // EPON
    'oidOnuUptime' => '.1.3.6.1.2.1.2.2.1.9', // ifLastChange
    
    // ONU Software Version
    'oidOnuSwVersion' => null, // Not available in EPON via SNMP
    'oidOnuSwVersion2' => null,
    
    // ONU Offline Reason
    'oidOnuOfflineReason' => '.1.3.6.1.4.1.3320.101.11.1.1.11', // EPON (llidOnuBindLastDeregReason)
    // Reasons: none(0), dying-gasp(1), laser-always-on(2), admin-down(3), 
    // omcc-down(4), unknown(5), pon-los(6), lcdg(7), wire-down(8), 
    // omci-mismatch(9), password-mismatch(10), reboot(11), ranging-failed(12)
    
    // ONU LAN Port Status
    'oidOnuLanStatus' => '.1.3.6.1.4.1.3320.101.12.1.1.8', // EPON
    
    // ONU CTC IP Address (EPON specific)
    'oidOnuCtcIpAdd' => '.1.3.6.1.4.1.3320.101.10.29.1',
    
    // ==========================================
    // ONU Traffic (EPON)
    // ==========================================
    // ONU Bandwidth Download (OLT to ONU)
    'oidOnuDownloadBw' => '.1.3.6.1.4.1.3320.9.64.4.1.1.8', // ifOut5MinBitRate
    'oidOnuDownloadOctets' => '.1.3.6.1.2.1.31.1.1.1.10',
    
    // ONU Bandwidth Upload (ONU to OLT)
    'oidOnuUploadBw' => '.1.3.6.1.4.1.3320.9.64.4.1.1.6', // ifIn5MinBitRate
    'oidOnuUploadOctets' => '.1.3.6.1.2.1.31.1.1.1.6',
    
    // ONU PPS Download
    'oidOnuDownloadPps' => '.1.3.6.1.4.1.3320.9.64.4.1.1.9', // ifOut5MinPktRate
    
    // ONU PPS Upload
    'oidOnuUploadPps' => '.1.3.6.1.4.1.3320.9.64.4.1.1.7', // ifIn5MinPktRate
    
    // ==========================================
    // Alarms & Events (EPON)
    // ==========================================
    'oidOnuDyingGasp' => '.1.3.6.1.4.1.3320.101.11.1.1.11', // Same as offline reason
    'oidOnuLogicLinkDown' => null, // Not separate in EPON
    'oidOnuLogicLinkUp' => null, // Not separate in EPON
    'oidOnuLoopDetect' => null, // Not available in EPON via SNMP
    
    // ==========================================
    // Unconfigured ONU (EPON)
    // ==========================================
    'oidOnuUncfgSn' => null, // EPON doesn't have separate uncfg table
    'oidOnuUncfgSnG' => null,
    'oidOnuUncfgType' => null,
    
    // ==========================================
    // Date/Time Fields
    // ==========================================
    'oidOnuLastOffline' => null, // Not available via SNMP (only reason code)
    'oidOnuLastOnline' => null, // Not available via SNMP
    
    // ==========================================
    // VLAN Configuration
    // ==========================================
    'oidOltVlanId' => '.1.3.6.1.2.1.17.7.1.4.3.1.1',
    'oidOltVlanName' => '.1.3.6.1.2.1.17.7.1.4.3.1.2',
    
    // ==========================================
    // DDM (Digital Diagnostics Monitoring)
    // ==========================================
    'oidDdmStatus' => '.1.3.6.1.4.1.3320.9.63.1.7',
    'oidDdmTxPower' => '.1.3.6.1.4.1.3320.9.63.1.7.1.2',
    'oidDdmRxPower' => '.1.3.6.1.4.1.3320.9.63.1.7.1.3',
    
    // ==========================================
    // MAC Address Table (EPON)
    // ==========================================
    'oidMacTable' => '.1.3.6.1.2.1.17.7.1.2.2', // dot1qTpFdbTable (standard)
    
    // ==========================================
    // Management
    // ==========================================
    'oidRebootSw' => '.1.3.6.1.4.1.3320.9.1847',
];
