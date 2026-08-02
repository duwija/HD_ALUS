<?php

// CDATA EPON OID Configuration (enterprise: 1.3.6.1.4.1.34592)
// MIB: FD-OLT-MIB (llid table), for CDATA EPON-family OLTs (e.g. FD11xx/FD12xx/FD13xx).
// Based on FD-OLT-MIB indexes: {ponCardSlotId, oltId, linkId}
// For CDATA GPON-family OLTs, see config/cdata_gpon_oid.php (CDATA-GPON-MIB) instead —
// selection is automatic via get_olt_oid_config() based on the OLT's type/name field.

return [
    // OLT system information
    'oidOltName' => '.1.3.6.1.2.1.1.5.0',
    'oidOltUptime' => '.1.3.6.1.2.1.1.3.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltDesc' => '.1.3.6.1.2.1.1.1.0',

    // LLID/ONU base information (index: ponCardSlotId.oltId.linkId)
    'oidOnuName' => '.1.3.6.1.4.1.34592.1.3.3.12.1.1.4',       // llidMac (used as stable identity)
    'oidOnuStatus' => '.1.3.6.1.4.1.34592.1.3.3.12.1.1.5',     // linkOnLineStatus (DeviceStatus)
    'oidOnuId' => '.1.3.6.1.4.1.34592.1.3.3.12.1.1.3',         // associatedOnuId
    'oidOnuSn' => '.1.3.6.1.4.1.34592.1.3.3.12.1.1.4',         // fallback to llidMac
    'oidOnuModel' => null,

    // Not exposed in this MIB branch
    'oidOnuRxPower' => null,
    'oidOnuTxPower' => null,
    'oidOnuTxPowerOnu' => null,
    'oidOnuDistance' => null,
    'oidOnuVoltage' => null,
    'oidOnuTemperature' => null,
    'oidOnuUptime' => null,
    'oidOnuLastOffline' => null,
    'oidOnuLastOnline' => null,
    'oidOnuOfflineReason' => null,

    // Unconfigured ONU table not mapped yet for FD-OLT-MIB branch in this integration
    'oidOnuUncfgSn' => null,
    'oidOnuUncfgSnG' => null,
    'oidOnuUncfgType' => null,

    // Optional IF-MIB compatibility keys
    'oidIfDescr' => '.1.3.6.1.2.1.2.2.1.2',
    'oidIfOperStatus' => '.1.3.6.1.2.1.2.2.1.8',

    // Power divisor placeholder to keep existing conversion paths safe
    'power_divisor' => 100,
];
