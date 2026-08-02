<?php

// CDATA GPON OLT OID Configuration
// MIB: CDATA-GPON-MIB (enterprise 1.3.6.1.4.1.34592, node: vendor.ipProduct(1).gpon(5).gponMIB(1)
//      .gponObjects(1).gponControlObjects(2).gponOnuObjects(18))
// Base OID for ONU tables: .1.3.6.1.4.1.34592.1.5.1.1.2.18
// Reference: https://github.com/librenms/librenms/blob/master/mibs/cdata/CDATA-GPON-MIB
//
// Index format: all ONU tables are indexed by {gponOltDeviceId}.{gponOltCardId}.{gponOltPortId}.{gponOnuId}
// (four plain small integers, e.g. 1.1.1.5 for device 1, card 1, port 1, ONU 5) — no bit-shift
// encoding needed, unlike ZTE C600's .500 branch.
//
// NOT YET VERIFIED against live hardware: SNMP on the reference device (OLT id 11,
// 172.30.10.29:1613) did not respond at the time this file was written (host pings fine,
// but SNMP timed out on both port 1613 and 161, community 'public'/'private', v1/v2c).
// Re-check against a reachable CDATA GPON unit before relying on this in production.

return [
    'oidOltName'    => '.1.3.6.1.2.1.1.5.0',
    'oidOltUptime'  => '.1.3.6.1.2.1.1.3.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltDesc'    => '.1.3.6.1.2.1.1.1.0',

    // gponOnuConfigTable (.18.1) — index: device.card.port.onuId
    'oidOnuSn'          => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.1.1.4', // gponOnuSn
    'oidOnuDescription' => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.1.1.8', // gponOnuDescription (admin label)
    'oidOnuName'        => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.1.1.8', // alias to description; falls back to SN if empty

    // gponOnuInfoTable (.18.2) — index: device.card.port.onuId
    'oidOnuStatus'        => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.2.1.1', // gponOnuRunState: online(1)/offline(2)
    'oidOnuConfigState'   => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.2.1.2', // initial/success/failed/configing
    'oidOnuDistance'      => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.2.1.4', // meters
    'oidOnuLastOnline'    => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.2.1.6', // gponOnuInfoLastUpTime
    'oidOnuLastOffline'   => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.2.1.7', // gponOnuInfoLastDownTime

    // gponOnuAutofindInfoTable (.18.5) — unregistered/auto-discovered ONUs.
    // Index: device.card.port.autofindId (NOT onuId — separate id space for undiscovered units)
    'oidOnuUncfgSn'     => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.5.1.3', // gponOnuAutofindSn
    'oidOnuUncfgVendor' => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.5.1.5', // gponOnuAutofindVendorId (4-byte ASCII)
    'oidOnuUncfgType'   => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.5.1.7', // gponOnuAutofindEquipmentID (model)
    'oidOnuUncfgTime'   => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.5.1.9', // gponOnuAutofindTime

    // gponOnuOpticalInfoTable (.18.6) — index: device.card.port.onuId. All values are
    // DisplayString (already human-readable dBm/V/celsius, e.g. "-19.55"), not raw integers
    // needing a conversion formula — parse the numeric substring directly.
    'oidOnuVoltage'         => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.6.1.1', // gponOnuOpticalVoltage
    'oidOnuTxPowerOnu'      => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.6.1.2', // gponOnuOpticalTxPower
    'oidOnuRxPower'         => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.6.1.4', // gponOnuOpticalRxPower
    'oidOnuLaserBiasCurrent'=> '.1.3.6.1.4.1.34592.1.5.1.1.2.18.6.1.5', // gponOnuOpticalLaserBiasCurrent
    'oidOnuTemperature'     => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.6.1.6', // gponOnuOpticalTemperature

    // gponOnuVersionTable (.18.7) — index: device.card.port.onuId
    'oidOnuVersion' => '.1.3.6.1.4.1.34592.1.5.1.1.2.18.7.1.1', // gponOnuMainSoftwareVersion

    // Not exposed by this MIB branch (would require CLI):
    'oidOnuModel' => null,

    'power_divisor' => 1, // optical values are already plain dBm/V strings, no scaling needed
];
