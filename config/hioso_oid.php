<?php

/**
 * HIOSO EPON OLT OID map — enterprise 25355, branch .3.2.6, covering the
 * HA7304V / HA7304C / HA7304VX model family (internally referred to as
 * profiles HIOSO_C / HIOSO_B2 / HIOSO_VX). These three profiles share this
 * exact same MIB tree — they only differ by SNMP community string and how
 * many PON ports are exposed live, both of which are already per-OLT-record
 * concerns (`community_ro`, admin-entered `type`), not something this file
 * needs to branch on.
 *
 * OIDs below were originally transcribed from a user-supplied reference doc
 * (OLT_OID_REFERENCE.md) for a sibling billing system, then live-verified
 * (2026-08-03) against a real unit: OLT id=10 "OLT HIOSO BLAHBATUH" on the
 * cnetgoup tenant, 172.30.10.29:1612, community "public", sysDescr "Epon
 * 7304 1.0.0.1 20191122", type "HIOSO4port" — all 4 PON ports exposed
 * separately (no PON2/3/4 merge on this particular unit), 173 ONUs total.
 * status/sn/name/model/distance/rx/tx below all matched live output.
 *
 * NOT verified / deliberately excluded after live testing: an "ONU uptime"
 * OID was requested but neither candidate branch actually contains it —
 * `.25355.3.2.6.1.1.1.1` is a 4-row per-PON table of constant INTEGER 1
 * (looks like a port admin-status flag, not Timeticks), and `.25355.3.2.6.3`
 * is OLT-level system/network info (IpAddress, hex-encoded strings like
 * "Unknown"/"C242"/"8022"), not per-ONU data. Do not wire either in.
 * Last-online/last-offline timestamps also have no native OID on this
 * profile (confirmed, matches source doc) — would require a separate
 * polling-based tracker (scheduled job + stored previous status), not a
 * config addition, so that's intentionally not implemented here either.
 *
 * Index shape: every OID below is a table indexed by "{board}.{pon}.{onu}"
 * (3 levels — unlike VSOL's plain "{port}.{onu}"). Every documented example
 * uses board=1; multi-board HIOSO chassis are not evidenced in the source
 * doc, so board is assumed constant, not derived.
 *
 * Known hardware quirk (HIOSO_C / HA7304V only, per source doc): this
 * model's SNMP agent merges PON ports 2/3/4 into a single group (index
 * "1.2.X"), i.e. the true PON3/PON4 ONUs are indistinguishable from PON2's
 * via SNMP alone. The source project works around this with a Telnet scan,
 * but no Telnet CLI syntax for HIOSO was available when this file was
 * written, so that workaround is NOT implemented here — on HIOSO_C hardware
 * specifically, PON3/4 ONUs will simply list under PON "2".
 *
 * ONU model/type OID confirmed separately by the user (not in the original
 * source doc, which claimed model wasn't exposed on this profile — it is,
 * at column .9 of the same .3.2.1 table as SN/status/distance).
 *
 * Still NOT exposed via SNMP on this profile (per source doc): ONU vendor
 * string, ONU firmware version. Left null below; the collector already
 * treats a null OID as "skip this field" (same convention as
 * config/vsol_oid.php).
 *
 * oidOnuName is an admin-settable label, not a hardware identity field —
 * per source doc it reads back the literal string "NA" when never set, so
 * the collector treats "NA" as empty and falls back to the serial number
 * (same "alias to description; falls back to SN if empty" convention as
 * config/cdata_gpon_oid.php's oidOnuName).
 */

return [
    // Standard MIB-2 system group — every SNMP agent answers these regardless of
    // vendor, used by getOltInfo()'s generic OLT-name/uptime/version/desc card.
    'oidOltName'    => '.1.3.6.1.2.1.1.5.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltUptime'  => '.1.3.6.1.2.1.1.3.0',
    'oidOltDesc'    => '.1.3.6.1.2.1.1.6.0',
    // No "unconfigured/unregistered ONU" table documented for this profile.
    'oidOnuUncfgSn'   => null,
    'oidOnuUncfgType' => null,

    'oidOnuStatus'      => '.1.3.6.1.4.1.25355.3.2.6.3.2.1.39', // 1=online, 2=offline
    'oidOnuSn'          => '.1.3.6.1.4.1.25355.3.2.6.3.2.1.11', // hex string -> normalizeOnuSerial()
    'oidOnuName'        => '.1.3.6.1.4.1.25355.3.2.6.3.2.1.37', // admin-set label, or literal "NA" if unset
    'oidOnuModel'       => '.1.3.6.1.4.1.25355.3.2.6.3.2.1.9',  // ONU hardware model/type, STRING
    'oidOnuVendor'      => null,
    'oidOnuVersion'     => null,
    'oidOnuDistance'    => '.1.3.6.1.4.1.25355.3.2.6.3.2.1.25', // meters, integer
    'oidOnuTemperature' => '.1.3.6.1.4.1.25355.3.2.6.14.2.1.7', // STRING "47.74" (degrees C, 2dp) — confirmed live, not INTEGER
    'oidOnuTxPowerOnu'  => '.1.3.6.1.4.1.25355.3.2.6.14.2.1.4', // already a final dBm float string, e.g. "-2.08"
    'oidOnuRxPower'     => '.1.3.6.1.4.1.25355.3.2.6.14.2.1.8', // already a final dBm float string, e.g. "-18.50"
];
