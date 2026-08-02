<?php

/**
 * V-SOL GPON OLT OID map — empirically reverse-engineered live against a real
 * VSOL-G004 unit (172.30.10.23:1611, community "public", OLT id 8 on tenant
 * db_nadinet — "OLT Payangan"). No public MIB documents this enterprise tree
 * (enterprise 37950), so every OID below was found by walking the live device
 * and cross-checking values against known ONU counts/serials, not from a spec.
 *
 * Index shape: every table under 37950.1.1.6.1.1.<table> is a genuine two-level
 * SNMP table indexed by {ponPort}.{onuId} — e.g. "...1.7.2.48" = table 3
 * (optical), column 7 (RX), port 2, onu 48. No bit-packed/encoded index is
 * needed here (unlike CDATA's enterprise-17409 tree or ZTE's C600 .500 branch)
 * — just append ".<port>.<onuId>" to any OID below.
 *
 * Tables found under 37950.1.1.6.1.1:
 *   table 1 (.1.1.6.1.1.1.1.<col>.<port>.<onu>) — ONU config
 *     col1 = port number (echoes the index, not independently useful)
 *     col2 = onu id (echoes the index)
 *     col3 = 1 (constant, unknown/reserved)
 *     col4 = **status: 1=online, 2=offline** — confirmed against 5 real
 *            offline ONUs (RX/TX show "N/A" and model shows "unknown" only
 *            for these same onus — three independent signals agree).
 *     col5 = onu type/capability enum (3=most common, 4 and 6 also seen on a
 *            handful of units — not status-related, left unused)
 *   table 2 (.1.1.6.1.1.2.1.<col>.<port>.<onu>) — ONU registration/identity
 *     col1 = port, col2 = onu id (both echo the index)
 *     col3 = line/service profile name, STRING (always "default" on this unit)
 *     col4 = 1 (constant, unknown/reserved)
 *     col5 = serial number, STRING, e.g. "ZTEGD392AF2E" (uppercase)
 *     col6 = model name, STRING, e.g. "F663NV3a" / "F609V10.0" — reads back
 *            literally "unknown" while the ONU is offline
 *   table 3 (.1.1.6.1.1.3.1.<col>.<port>.<onu>) — ONU optical/DDM diagnostics
 *     col1 = port, col2 = onu id (echo the index)
 *     col3 = temperature,   STRING "38.750(C)"
 *     col4 = voltage,       STRING "3.20(V)"
 *     col5 = bias current,  STRING "9.600(mA)"
 *     col6 = TX power,      STRING "2.228(dBm)" or "N/A" when offline
 *     col7 = RX power,      STRING "-18.602(dBm)" or "N/A" when offline
 *   table 4 (.1.1.6.1.1.4.1.<col>.<port>.<onu>) — ONU vendor/version
 *     col1 = port, col2 = onu id (echo the index)
 *     col3 = vendor ID (OUI-style 4-letter code), STRING e.g. "ZTEG"
 *     col4 = hardware/firmware version, STRING e.g. "V10.0"
 *     col5 = serial number again, STRING but lowercase (e.g. "ZTEGd392af2e")
 *            — table 2 col5's uppercase value is used as the canonical SN
 *     col6, col7 = INTEGER 0 (constant on this unit, unknown/reserved)
 *     col8 = INTEGER 1 (constant, unknown/reserved)
 *
 * NOT found anywhere on this device/firmware: GPON ranging distance, and any
 * last-online/last-offline/uptime timestamp — the collector reports these as null.
 * Ethernet uplink ports (GE1-6) live under a completely separate, unrelated
 * branch (.1.1.5.10) and are not part of this ONU map.
 *
 * Also checked and ruled out (2026-08-02): standard IF-MIB. This device DOES expose
 * every ONU as a virtual sub-interface (ifDescr "GPON0/<port>:<onu>", ifIndex offsets
 * sequentially after the 4 physical GPON port interfaces), and ifOperStatus on those
 * correctly mirrors up(1)/down(2) — but ifLastChange (.1.3.6.1.2.1.2.2.1.9) reads a
 * constant Timeticks(0) for every single ONU sub-interface regardless of online/
 * offline state, confirmed against both online and offline ONUs live. ifLastChange
 * DOES work normally for the physical uplink ports (e.g. ifIndex 4 showed a real
 * multi-day value), so this isn't a general SNMP-agent bug — the firmware just never
 * updates it for the virtual GPON channel interfaces. No GPON ranging/distance OID
 * exists under IF-MIB either. Conclusion: distance/last-online/last-offline/uptime
 * are not retrievable via SNMP on this hardware at all, by any known path.
 */

return [
    // Standard MIB-2 system group — every SNMP agent answers these regardless of
    // vendor, used by getOltInfo()'s generic OLT-name/uptime/version/desc card.
    'oidOltName'    => '.1.3.6.1.2.1.1.5.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltUptime'  => '.1.3.6.1.2.1.1.3.0',
    'oidOltDesc'    => '.1.3.6.1.2.1.1.6.0',
    // No "unconfigured/unregistered ONU" table found on this device — leave null,
    // getOltInfo()/table_onu_unconfig() already treat a null/empty oidOnuUncfgSn as
    // "0 unconfigured" / return an empty DataTables response.
    'oidOnuUncfgSn' => null,
    'oidOnuUncfgType' => null,

    'oidOnuStatus'      => '.1.3.6.1.4.1.37950.1.1.6.1.1.1.1.4',
    'oidOnuProfile'     => '.1.3.6.1.4.1.37950.1.1.6.1.1.2.1.3',
    'oidOnuSn'          => '.1.3.6.1.4.1.37950.1.1.6.1.1.2.1.5',
    'oidOnuModel'       => '.1.3.6.1.4.1.37950.1.1.6.1.1.2.1.6',
    'oidOnuVendor'      => '.1.3.6.1.4.1.37950.1.1.6.1.1.4.1.3',
    'oidOnuVersion'     => '.1.3.6.1.4.1.37950.1.1.6.1.1.4.1.4',
    'oidOnuTemperature' => '.1.3.6.1.4.1.37950.1.1.6.1.1.3.1.3',
    'oidOnuVoltage'     => '.1.3.6.1.4.1.37950.1.1.6.1.1.3.1.4',
    'oidOnuBiasCurrent' => '.1.3.6.1.4.1.37950.1.1.6.1.1.3.1.5',
    'oidOnuTxPowerOnu'  => '.1.3.6.1.4.1.37950.1.1.6.1.1.3.1.6',
    'oidOnuRxPower'     => '.1.3.6.1.4.1.37950.1.1.6.1.1.3.1.7',
];
