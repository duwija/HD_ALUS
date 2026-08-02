<?php

// CDATA GPON OID configuration — REVERSE-ENGINEERED FROM A LIVE PRODUCTION DEVICE.
//
// Unlike config/cdata_gpon_oid.php (which follows CDATA's own officially registered
// enterprise 34592, per the public LibreNMS MIB dump), real CDATA GPON units in this
// fleet report sysObjectID = enterprises.17409 and use an OEM proprietary tree for all
// ONU-level data. This file documents that 17409 tree, verified live against:
//   - OLT id 8 ("OLT CDATA PESAGI 03", model FD1608Y-B1, 172.30.10.13:1611, kencana tenant)
//     firmware V3.3.45_250430, 8x GPON ports, 389 real registered ONUs.
//
// CORRECTION: this firmware is a HYBRID, not pure-17409. The ONU-level 34592 table
// (enterprises.34592.1.5.1.1.2.18.x, CDATA's official CDATA-GPON-MIB) genuinely does NOT
// exist here — confirmed "No Such Object" for name/SN/status/RX. But the OLT-level
// per-port SFP diagnostics table one node up, enterprises.34592.1.5.1.1.2.17.2.1.x
// (gponOltObjects, same 34592 tree), DOES exist and returns real per-port readings — see
// oidOltPort* below. So: ONU data → 17409 OEM tree; OLT/port diagnostics → official 34592
// tree. Always verify per-table on new firmware rather than assuming one enterprise wins.
//
// Index encoding (verified empirically, not from any MIB doc): every ONU-level OID in
// this tree is indexed by a single integer built as:
//     index = 0x480000 + ((port - 1) << 12) + onuId
// i.e. base tag 0x480000 (4718592), 12 bits of ONU id space per port (up to 4096 ONUs/
// port, though real capacity is far lower), port counted from 1. Confirmed by decoding
// 389 real index values against known per-port ONU counts (8 GPON ports, up to ~80
// ONUs/port) with zero mismatches. See encode_cdata17409_index() / decode_cdata17409_index()
// below.
//
// ONU main info table: enterprises.17409.2.8.4.1.1.<column>.<index>
//   column 2   = ONU description/label (usually the customer name, admin-set)
//   column 3   = ONU serial number, Hex-STRING, vendor-prefixed (e.g. "ZTEG"+hex) —
//                same format already handled by OltController::normalizeOnuSerial()
//   column 5   = ONU vendor ID string (e.g. "ZTEG")
//   column 6   = ONU model string (e.g. "F609V10.0")
//   column 14  = ONU firmware version string
//   column 101 = LIVE run state, INTEGER 1=online / 0=offline. Cross-checked against the
//                device's own "show ont info all" CLI output: the one ONU CLI reported as
//                Offline (port 1 onu 11, "Pesagi_LPD") was the exact ONU whose column 101
//                read 0 here — the only 3 such ONUs out of 389 total, matching a healthy
//                network snapshot (NOT column 103 below, which looked like a status field
//                but isn't — see the warning under it).
//   column 102 = last state-change timestamp, STRING "YYYY-MM-DD HH:MM:SS"
//   column 103 = "last down-cause" STRING (e.g. "dying-gasp", "LOS", "--" = none yet).
//                IMPORTANT: this is a HISTORY field, not current status — it stays set to
//                the last disconnect reason even after the ONU reconnects and column 101
//                goes back to 1. The CLI's own output labels this column "Last down-cause"
//                for exactly this reason. An earlier version of this integration used
//                column 103 as the live status and got it wrong (e.g. reported ~94% of
//                ONUs as "down" when only 3 actually were) — do not repeat that mistake.
//   column 9   = LIKELY distance in meters. Not CLI-cross-checked like column 101 was
//                (no distance field in "show ont info all"), but: reads exactly 0 for all
//                3 offline ONUs and never 0 for any online ONU; stable across two SNMP
//                walks taken ~1 day apart for the same ONU (149 both times); range across
//                389 ONUs is 0–16979, plausible for a rural multi-village GPON deployment
//                (this OLT's ONU descriptions span several distinct village names). Treat
//                as a reasonable inference, not a confirmed fact — flag to the user if a
//                displayed distance looks physically implausible for a given customer.
//   Other columns (4,7,8,10,11,12,13,15,16,100,104,105,106) were observed but their exact
//   meaning wasn't conclusively determined — left out of the map below.
//
// ONU optical power table (the REAL RX/TX pair — supersedes the RX-only table below):
// enterprises.17409.2.8.4.4.1.4.<index>.0.0 = RX power, enterprises.17409.2.8.4.4.1.5.<index>.0.0 = TX power
//   INTEGER, value / 100 = dBm. Verified against the 3 known-offline ONUs (cross-checked
//   via column 7/101 run-state, itself CLI-confirmed): RX reads exactly -1 and TX reads
//   exactly -3000 on ALL offline ONUs and only offline ONUs — clearly fixed "no reading"
//   sentinels, not real measurements (treat raw -1 / -3000 as null, not -0.01 / -30.00 dBm).
//   Live TX range on online ONUs: 1.96–3.01 dBm, squarely inside GPON ONU launch-power spec
//   (typ. +0.5 to +5 dBm) — this is genuinely the ONU's own upstream TX power.
//   Live RX range here: roughly -15 to -23 dBm.
//   Column 7 (enterprises.17409.2.8.4.1.1.7) is a second, independent run-state field for
//   this same table: INTEGER 1=up / 2=down — agrees 100% with column 101 (1/0) on every
//   ONU checked, including the CLI-confirmed offline one. Not currently used since 101 is
//   already wired and CLI-verified, but documented here as a cross-check / fallback.
//
// ONU RX optical power table (OLDER find, keep for reference): enterprises.17409.2.3.3.6.1.2.<index>
//   INTEGER, value / 100 = dBm (range -22.xx to -33.xx dBm across 389 real ONUs). Gives
//   DIFFERENT numbers than oidOnuRxPower above for the same ONU (e.g. ONU 1: -25.08 here vs
//   -19.13 above) — likely a different measurement point (OLT's received power FROM this
//   ONU's upstream burst, vs. this ONU's own reported RX of the OLT's downstream signal —
//   two legitimately different GPON optical readings, not a bug). No paired TX exists for
//   this table. Kept in oidOnuRxPowerOltSide below in case it's ever needed for comparison;
//   the primary oidOnuRxPower now points at the .2.8.4.4 table since it comes as a real
//   RX/TX pair with confirmed sentinel behavior.
//
// This mapping was NOT found on the EPON-family unit tested (enterprise 17409 exists
// there too for basic system info, but the .2.8.4.1.1 ONU table returned
// "No Such Object" — EPON firmware in this line does not expose it the same way).
// Treat this file as GPON-only.
//
// OLT-side per-PORT SFP diagnostics (official 34592 tree, gponOltObjects — see the
// hybrid-firmware note above): enterprises.34592.1.5.1.1.2.17.2.1.<column>.1.0.<ifIndex>
// Index is the PLAIN IF-MIB ifIndex (the same values ifDescr walk returns, e.g. 1310721
// for "gpon 0/0/1") — no port encoding needed. Verified live, all 8 GPON ports populated
// (the GE/XGE ports return "-", presumably no DDM-capable SFP fitted there):
//   column 1 = temperature, °C            (observed 54.79–59.93, normal SFP range)
//   column 2 = supply voltage, V          (observed 3.24–3.36, standard 3.3V SFP)
//   column 3 = TX bias current, mA        (observed 26.52–34.80)
//   column 4 = TX power, dBm              (observed 5.90–5.98 on 7 ports; port 6 read
//              9.33 — a clear outlier vs. its siblings, worth flagging as a possible
//              mismatched/out-of-spec SFP rather than assuming it's a parsing error)
//   column 5 = RX power, dBm              (observed -24.44 to -33.01 — aggregate upstream
//              burst power received at the OLT from ALL ONUs on that port combined; this
//              is NOT the same figure as any single ONU's oidOnuRxPower above)
// This is OLT/port-level health data, not tied to a specific ONU — useful for a per-port
// SFP health view, not for the per-ONU listing this file's other OIDs feed.

return [
    'oidOltName'    => '.1.3.6.1.2.1.1.5.0',
    'oidOltUptime'  => '.1.3.6.1.2.1.1.3.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltDesc'    => '.1.3.6.1.2.1.1.1.0',

    // ONU main info table (index: 0x480000 + (port-1)*4096 + onuId)
    'oidOnuName'        => '.1.3.6.1.4.1.17409.2.8.4.1.1.2',
    'oidOnuSn'          => '.1.3.6.1.4.1.17409.2.8.4.1.1.3',
    'oidOnuVendor'      => '.1.3.6.1.4.1.17409.2.8.4.1.1.5',
    'oidOnuModel'       => '.1.3.6.1.4.1.17409.2.8.4.1.1.6',
    'oidOnuVersion'     => '.1.3.6.1.4.1.17409.2.8.4.1.1.14',
    'oidOnuLastChange'  => '.1.3.6.1.4.1.17409.2.8.4.1.1.102', // last run-state transition time
    'oidOnuRunState'    => '.1.3.6.1.4.1.17409.2.8.4.1.1.101', // LIVE status: 1=online, 0=offline
    'oidOnuLastDownCause' => '.1.3.6.1.4.1.17409.2.8.4.1.1.103', // history only, see header
    'oidOnuDistance'    => '.1.3.6.1.4.1.17409.2.8.4.1.1.9', // likely meters, see header

    // ONU optical power — real RX/TX pair (index: <onu_index>.0.0, see header)
    'oidOnuRxPower'    => '.1.3.6.1.4.1.17409.2.8.4.4.1.4',
    'oidOnuTxPowerOnu' => '.1.3.6.1.4.1.17409.2.8.4.4.1.5',
    'optical_index_suffix' => '.0.0',
    'optical_sentinel_rx' => -1,
    'optical_sentinel_tx' => -3000,

    // Older RX-only find, kept for reference/comparison — see header note.
    'oidOnuRxPowerOltSide' => '.1.3.6.1.4.1.17409.2.3.3.6.1.2',

    // Not found in this tree for this firmware (per-ONU):
    'oidOnuVoltage'    => null,
    'oidOnuTemperature'=> null,

    'onu_index_base' => 0x480000,
    'onu_index_step' => 4096, // bits reserved per port for onu id
    'rx_power_divisor' => 100,

    // OLT-side per-port SFP diagnostics (official 34592 tree — see header note on why
    // this coexists with the 17409 ONU tables). Index: plain ifIndex, e.g. 1310721.
    'oidOltPortTemperature' => '.1.3.6.1.4.1.34592.1.5.1.1.2.17.2.1.1.1.0',
    'oidOltPortVoltage'     => '.1.3.6.1.4.1.34592.1.5.1.1.2.17.2.1.2.1.0',
    'oidOltPortTxBias'      => '.1.3.6.1.4.1.34592.1.5.1.1.2.17.2.1.3.1.0',
    'oidOltPortTxPower'     => '.1.3.6.1.4.1.34592.1.5.1.1.2.17.2.1.4.1.0',
    'oidOltPortRxPower'     => '.1.3.6.1.4.1.34592.1.5.1.1.2.17.2.1.5.1.0',
];
