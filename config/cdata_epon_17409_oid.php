<?php

// CDATA EPON OID configuration — REVERSE-ENGINEERED FROM A LIVE PRODUCTION DEVICE.
//
// config/cdata_oid.php targets CDATA's officially registered enterprise 34592
// (FD-OLT-MIB, llid table indexed {ponCardSlotId, oltId, linkId}). Real CDATA EPON
// units in this fleet do NOT implement that tree at all (confirmed "No Such Object"
// on every OID under enterprises.34592.1.3.3.12.1.1.x). Instead, like the CDATA GPON
// units documented in config/cdata_gpon_17409_oid.php, they report sysObjectID =
// enterprises.17409 and expose ONU data on an OEM proprietary tree — but at a
// DIFFERENT branch than the GPON one (.2.3.4 here vs .2.8.4 for GPON; GPON's
// .2.8.4.1.1 table returns "No Such Object" on this EPON unit, confirming the two
// product lines use genuinely separate sub-trees, not just different indexing).
//
// Verified live against: 103.156.75.196:1612, community "public", sysDescr "olt",
// sysName "olt" (both generic/unhelpful), 91+ days uptime, 2x EPON PON cards
// (ifDescr: "epon 0/1/1".."epon 0/1/4" populated with 181 registered ONUs;
// "epon 0/2/1".."epon 0/2/4" exist as interfaces but have zero registered ONUs —
// confirmed by probing the predicted card-2 base index and getting "No Such
// Instance" (right branch, empty row) rather than "No Such Object" (wrong branch)).
//
// Index encoding (verified empirically against 181 real ONUs across 4 active EPON
// ports with zero mismatches, cross-checked against the human-readable port/onu
// numbers embedded in the name string itself — column 2 literally reads e.g.
// "epon 0/1/1 onu 5"):
//     index = (card * 0x1000000) + 0x480000 + ((portInCard - 1) * 0x1000) + onuId
// i.e. the same 0x480000 base + 4096-per-port step as CDATA-GPON's enterprise-17409
// tree (see config/cdata_gpon_17409_oid.php), PLUS a 0x1000000-per-card component
// for devices with multiple PON cards (GPON units in this fleet so far are all
// single-card, so their formula never needed this term — it was hiding at card=0).
// card and portInCard are both 1-based, matching the "epon <frame>/<card>/<port>"
// naming CDATA uses in ifDescr (frame is always 0 on every unit seen so far).
// See encode_cdata_epon17409_index() / decode_cdata_epon17409_index() in
// config/zteoid.php.
//
// ONU main info table: enterprises.17409.2.3.4.1.1.<column>.<index>
//   column 2   = ONU name/label. Default is "epon <card>/<card>/<port> onu <n>"
//                (yes, the device repeats the card number as both frame and slot);
//                admin-set labels get APPENDED after this default string rather
//                than replacing it (observed: 'epon 0/1/1 onu 19 I MADE SANGGA').
//   column 3   = INTEGER, constant 1 across all 181 ONUs observed — NOT a useful
//                status field (no variance to correlate against anything). Left
//                unmapped.
//   column 7   = ONU MAC address, Hex-STRING (6 bytes, e.g. "50 5B 1D DD E4 5A").
//                EPON has no separate GPON-style serial number in this tree — the
//                MAC is the natural stable identity, used here as oidOnuSn.
//   column 8   = LIVE run state candidate: INTEGER, 175/181 ONUs read 1, 6/181 read
//                2 — plausible online/offline split (~3.3% down, consistent with a
//                normal access network) but NOT cross-checked against this device's
//                own CLI output (no "show onu info" transcript available at time of
//                writing — see the memory note on never inventing status meanings).
//                Mapped here as 1=online, 2=offline; treat with more skepticism than
//                the GPON column 101 mapping (which WAS CLI-verified) — BUT corroborated
//                by an independent signal: all 6 ONUs column 8 marks offline have ZERO
//                entry in the RX optical table below (no reading at all, not a sentinel
//                value), while all 175 "online" ONUs have a real RX reading — exactly the
//                pattern you'd expect from a genuinely down ONU with no active upstream
//                burst to measure.
//   column 9   = INTEGER, constant 1 across all ONUs observed. Left unmapped.
//   column 10  = STRING "0x007f" on every ONU checked — looks like a capability/
//                feature bitmask, not per-ONU state. Left unmapped.
//   column 12  = STRING, format unclear — observed "21.10.21", "00.00.03",
//                "12.06.14", "06.09.27" across different ONUs. Could be a hardware
//                revision (x.y.z) for some vendors and a build date (D.M.Y or
//                Y.M.D) for others — formats aren't consistent enough across the
//                4 ONU vendors seen live to commit to one interpretation. Left
//                unmapped; do not guess a label for the UI.
//   column 13  = ONU firmware/software version STRING, e.g. "V3.2.22",
//                "ZL_V2.2.1.7", "V5R019C00S105" — format varies by vendor but is
//                unambiguously a version string in every sample. Mapped as
//                oidOnuVersion.
//   column 15  = INTEGER, current registration-session uptime in SECONDS. Reads 0
//                on every ONU column 8 marks offline, and a real (small, single-
//                digit-hours) value on every online ONU — this is uptime SINCE
//                LAST MPCP RE-REGISTRATION, not since the OLT itself booted (OLT
//                uptime is 91+ days; every ONU sampled read under 4 hours), which
//                is normal/expected EPON semantics, not a bug. Mapped as
//                oidOnuUptimeSeconds; the collector turns this into a synthetic
//                'last_online_at' (now() - these seconds) so the existing
//                Carbon::diffForHumans() uptime display in OltController::getOltOnu()
//                works without any changes there.
//   column 18  = Counter32, monotonically increasing, wildly different magnitudes
//                between ONUs (189-895405 observed) with no clean correlation to
//                column 15's uptime or column 8's online/offline split found in
//                this session's sample — possibly a cumulative error/retry/
//                register-attempt counter. Left unmapped.
//   column 25  = ONU vendor short-code STRING, e.g. "CDTC" (C-Data), "HWTC"
//                (Huawei-compatible), read empty ("") on offline ONUs alongside
//                columns 11-14 (further corroboration that column 8 = live run
//                state — an offline ONU simply has no live telemetry to report).
//                Mapped as oidOnuVendor AND used as the 'model' row value shown in
//                the UI's Model column, since no separate full model-number string
//                (e.g. GPON's "F609V10.0") was found anywhere on this tree — the
//                vendor short-code is the best available identifier, not a true
//                model number. Flag to the user if precise model matters.
//   Columns 100-103 (the GPON tree's lastChange/runState/lastDownCause fields) all
//   return "No Such Object" here — this EPON tree does not expose transition
//   history OR a down-cause/reason field (no LOS vs dying-gasp distinction is
//   available via SNMP on this tree; status can only report generic
//   online/offline, unlike CDATA GPON's normalizeCdata17409DownCause()). Dying-gasp
//   on EPON hardware is typically only ever announced via an async SNMP trap at
//   the moment of power loss, not a polled table cell — if this OLT sends such
//   traps, they'd need a trap receiver, not more GET/walk probing, to capture.
//
// ONU optical table: enterprises.17409.2.3.4.2.1.<column>.<index>.1.<portInCard>
//   IMPORTANT: the trailing suffix is NOT a fixed ".1.1" — the last component is
//   the ONU's own port-within-card number (port 1 -> ".1.1", port 2 -> ".1.2", port
//   3 -> ".1.3", etc — confirmed live across all 4 active ports). An earlier
//   version of this file assumed a fixed ".1.1" suffix and manual spot-checks on
//   ports 2-4 came back empty as a result — that was a probing artifact, NOT a
//   sign the table is empty for those ports. The collector handles this correctly
//   already: it re-keys the walk by the last 3 OID components and takes only the
//   first (the ONU index) via strtok(), so the varying trailing port number never
//   needs to be predicted — only re-verify this if a future refactor stops using
//   reindexSnmpWalkByTail() for this table. Also unlike GPON's ".0.0" optical
//   suffix, only "own port" is honest for a 2-part suffix regex; the middle "1" is
//   otherwise unexplained here.
//   column 4 = RX power, INTEGER, value/100 = dBm. Range observed: -1684 to -2602
//              raw (-16.84 to -26.02 dBm) — squarely inside normal EPON OLT-side
//              upstream burst RX range. This is the OLT's received power from each
//              ONU, same measurement point as CDATA-GPON's oidOnuRxPowerOltSide.
//   column 5 = TX power, INTEGER, value/100 = dBm. Range across all 175 online
//              ONUs: 1.64 to 2.70 dBm, tightly clustered — squarely inside normal
//              EPON ONU upstream launch-power spec (IEEE 802.3ah 1000BASE-PX,
//              roughly 0.5 to +5 dBm depending on class) and far too consistent
//              across many different ONU vendors/firmwares (see column 25) to be
//              anything but a real optical reading. Present only for ONUs with a
//              live optical link (absent for offline ONUs, same corroboration
//              pattern as column 4/RX and column 8/run-state) — NOT CLI-verified,
//              but the value-range + presence-correlates-with-status evidence is
//              strong. Mapped as oidOnuTxPowerOnu.
//   column 6 = INTEGER, range 400-1955 (raw) across the same 175 ONUs — much wider
//              spread than column 5, inconsistent with a shared optical spec value
//              (TX power or bias current shouldn't vary 5x across similar ONU
//              hardware). Possibly TX bias current in some scaling, but not
//              confident enough to map. Left unmapped.
//   column 7 = INTEGER, extremely tight cluster 317000-345000 (~330000 ± 4%)
//              across all 175 ONUs regardless of vendor/port/RX level — too
//              constant to be a live per-ONU physical reading (a real temperature
//              or distance would vary far more across 4 different PON ports and 4
//              ONU vendors). Likely a fixed device/link calibration constant, not
//              per-ONU telemetry. Left unmapped.
//
// A second per-ONU table exists at enterprises.17409.2.3.4.3.1.<column>.<index>
// (column 2 = INTEGER 1/2, column 3/5 = Hex-STRING bitmap-looking values starting
// 0xFF) that does NOT correlate with column 8's online/offline split (differs on
// ONUs column 8 marks online) — likely a VLAN/service-profile bitmap, not status.
// Left entirely unmapped; do not reuse column 2 here as a status field.

return [
    'oidOltName'    => '.1.3.6.1.2.1.1.5.0',
    'oidOltUptime'  => '.1.3.6.1.2.1.1.3.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltDesc'    => '.1.3.6.1.2.1.1.1.0',

    // ONU main info table (index: see encode/decode_cdata_epon17409_index() above)
    'oidOnuName'     => '.1.3.6.1.4.1.17409.2.3.4.1.1.2',
    'oidOnuMac'      => '.1.3.6.1.4.1.17409.2.3.4.1.1.7', // used as oidOnuSn — no separate SN field on this tree
    'oidOnuRunState' => '.1.3.6.1.4.1.17409.2.3.4.1.1.8', // 1=online, 2=offline — NOT CLI-cross-checked, see header
    'oidOnuVersion'  => '.1.3.6.1.4.1.17409.2.3.4.1.1.13', // firmware/software version string
    'oidOnuUptimeSeconds' => '.1.3.6.1.4.1.17409.2.3.4.1.1.15', // seconds since last MPCP re-registration, see header
    'oidOnuVendor'   => '.1.3.6.1.4.1.17409.2.3.4.1.1.25', // short vendor code, also used as 'model' — see header

    // ONU optical — RX + TX (index: "<onu_index>.1.<portInCard>", divisor 100 = dBm —
    // see header for why the trailing suffix isn't a literal fixed string)
    'oidOnuRxPower'        => '.1.3.6.1.4.1.17409.2.3.4.2.1.4',
    'oidOnuTxPowerOnu'     => '.1.3.6.1.4.1.17409.2.3.4.2.1.5',
    'optical_index_suffix' => '.1.1', // only used to compute suffix PART COUNT (2) — see header
    'rx_power_divisor'     => 100,

    // Not found / not confidently mapped on this tree — see header
    'oidOnuModel'      => null, // no true model-number string found — 'model' row value is oidOnuVendor instead
    'oidOnuDistance'   => null,

    // Index encoding constants (see encode_cdata_epon17409_index() in config/zteoid.php)
    'onu_index_card_step' => 0x1000000,
    'onu_index_base'      => 0x480000,
    'onu_index_port_step' => 0x1000, // 4096, bits reserved per port for onu id

    // Optional IF-MIB compatibility keys (port listing — same as config/cdata_oid.php)
    'oidIfDescr'      => '.1.3.6.1.2.1.2.2.1.2',
    'oidIfOperStatus' => '.1.3.6.1.2.1.2.2.1.8',
];
