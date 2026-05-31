<?php

// HSGQ-G02ID OID Configuration
// Branch observed on this model: .1.3.6.1.4.1.3320
// Keep keys aligned with OltController expectations for HSGQ paths.

return [
    // System
    'oidOltName' => '.1.3.6.1.2.1.1.5.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltUptime' => '.1.3.6.1.2.1.1.3.0',
    'oidOltDesc' => '.1.3.6.1.2.1.1.6.0',

    // Health (as referenced)
    'oidOltCpuUsage' => '.1.3.6.1.4.1.3320.9.109.1.1.1.1',
    'oidOltMemoryUsage' => '.1.3.6.1.4.1.3320.9.48.1',
    'oidOltTemperature' => '.1.3.6.1.4.1.3320.9.181.4.1',
    'oidOltFan' => '.1.3.6.1.4.1.3320.9.187.3',

    // IF-MIB aliases used by existing HSGQ logic
    'oidIfDescr' => '.1.3.6.1.2.1.2.2.1.2',
    'oidIfOperStatus' => '.1.3.6.1.2.1.2.2.1.8',
    'oidIfAdminStatus' => '.1.3.6.1.2.1.2.2.1.7',

    // PON optics
    'oidPonPortTxPower' => '.1.3.6.1.4.1.3320.101.107.1.3',
    'oidPonPortRxPower' => '.1.3.6.1.4.1.3320.101.108.1.3',
    'oidOltRxPower' => '.1.3.6.1.4.1.3320.101.108.1.3',

    // ONU core
    'oidOnuStatus' => '.1.3.6.1.4.1.3320.101.11.1.1.6',
    'oidOnuName' => '.1.3.6.1.2.1.31.1.1.1.18',
    'oidOnuModel' => '.1.3.6.1.4.1.3320.101.10.1.1.2',
    'oidOnuVendor' => '.1.3.6.1.4.1.3320.101.10.1.1.2',
    'oidOnuSn' => '.1.3.6.1.4.1.3320.101.10.1.1.3',
    'oidOnuDistance' => '.1.3.6.1.4.1.3320.101.10.1.1.27',
    'oidOnuUptime' => '.1.3.6.1.2.1.2.2.1.9',

    // ONU optics
    'oidOnuRxPower' => '.1.3.6.1.4.1.3320.101.108.1',
    'oidOnuTxPower' => '.1.3.6.1.4.1.3320.101.10.5.1.6',
    'oidOnuTxPowerOnu' => '.1.3.6.1.4.1.3320.101.10.5.1.6',

    // Optional fields used in some views
    'oidOnuCtcIpAdd' => '.1.3.6.1.4.1.3320.101.10.29.1',
    'oidOnuOfflineReason' => '.1.3.6.1.4.1.3320.101.11.1.1.11',

    // Keep unsupported keys explicit
    'oidOnuLastOffline' => null,
    'oidOnuLastOnline' => null,
    'oidOnuUncfgSn' => null,
    'oidOnuUncfgSnG' => null,
    'oidOnuUncfgType' => null,

    // Power normalization
    'powerDivisor' => 100,
];
