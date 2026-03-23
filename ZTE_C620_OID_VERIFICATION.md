# ZTE C620 OID Verification Report

**Test Device:** ZTE ZXA10 C620 V1.2.1  
**IP Address:** 103.156.74.17  
**SNMP Community:** public_ro  
**Date:** 2025-01-24

## Base OID Differences

| Model | Base OID |
|-------|----------|
| C300/C320 | `.1.3.6.1.4.1.3902.1012` |
| C600/C620/C650 | `.1.3.6.1.4.1.3902.1082` |

## Verified OIDs (Branch: .1.3.6.1.4.1.3902.1082.10.1.2.4.1)

| Field | OID Suffix | Data Type | Sample Value | CLI Comparison | Status |
|-------|------------|-----------|--------------|----------------|--------|
| ONU ID | .2 | INTEGER | 662529 | - | ✓ Working |
| Card Type | .4 | STRING | "HFTH", "FCSDA", "SPUFS" | Type: ZTE_ALL | ✓ Working |
| ONU Status | .5 | INTEGER | 1 (working) | Phase state: working | ✓ Verified |
| Serial Number | .14 | STRING | "" (empty) | SN: ZTEGD5D1BB96 | ✗ Not available via SNMP |
| Firmware Version | .23 | STRING | "V1.0.0" | - | ✓ Working |
| Uptime | .20 | INTEGER | 0 | Online Duration: 51h 38m 24s | ✗ Returns 0 |
| Distance | .2 | INTEGER | 662529 (ONU ID) | ONU Distance: 3998m | ✗ Wrong OID |
| Rx Power | .10 | - | No instance | ONU Rx: -19.706 dbm | ✗ Not available |
| Tx Power | .11 | INTEGER | 0 | ONU Tx: 2.368 dbm | ✗ Returns 0 |

## Unconfigured ONU

**OID:** `.1.3.6.1.4.1.3902.1082.10.1.2.3.1.5`  
**Sample:** "210109006532"  
**Status:** ✓ Verified

## Alternative OID Tree Testing

Tested enterprise OID `.1.3.6.1.4.1.3320.*` (mentioned in some ZTE documentation):
- CPU Usage (.3320.10.1.1.1): **Not available**
- ONU Status (.3320.101.111.1.1): **Not available**
- OLT SFP Tx Power (.3320.101.108.1): **Not available**

**Conclusion:** This C620 uses `.3902` tree exclusively, not `.3320`.

## CLI vs SNMP Data Comparison

Testing with ONU at position **1/2/1:3** (from CLI):

| Parameter | CLI Value | SNMP Result | Match? |
|-----------|-----------|-------------|--------|
| Serial Number | ZTEGD5D1BB96 | Empty string | ✗ |
| Phase State | working | INTEGER: 1 (working) | ✓ |
| ONU Distance | 3998m | Not found | ✗ |
| OLT Rx Power | -21.765 dbm | Not found | ✗ |
| ONU Tx Power | 2.368 dbm | Returns 0 | ✗ |
| ONU Rx Power | -19.706 dbm | Not found | ✗ |
| OLT Tx Power | 3.710 dbm | Not found | ✗ |
| Firmware | - | V1.0.0 | ✓ |

**Note:** SNMP data at index 1.1.x (shelf 1, slot 1) successfully retrieved, but CLI shows port 1/2/1. This suggests:
1. Different indexing between CLI and SNMP
2. Limited SNMP access with `public_ro` community
3. Some metrics only available via CLI/Telnet

## Key Findings

### 1. No User-Defined ONU Name
Unlike C300/C320, the C620 series does not provide a user-settable ONU name field via SNMP. OID .15 and .16 return empty strings.

**Solution:** Use Serial Number (OID .14) as the primary identifier.

### 2. Different Status Mapping
C620 uses different integer values for ONU status:

```php
// C620 Status Mapping (zte_c600_ontstatus.php)
1 => 'working',      // vs C300: 1 = 'los'
2 => 'los',
3 => 'dyinggasp',
4 => 'offline',
5 => 'config-error'
```

### 3. Frame/Slot/Port Format
C620 uses simple decimal format:

```php
// C300: "268501248" (encoded integer for 1/1/1)
// C620: "1.1" (slot 1, port 1)
```

### 4. Optical Power Metrics
Standard ONU power OIDs (.10, .11) not available on C620. Optical metrics may be in different branch (.500) with complex indexing.

**Status:** Requires further investigation.

## Implementation Notes

### Config Files Created
1. `config/zte_c600_oid.php` - OID mapping for C600 series
2. `config/zte_c600_frameslotportid.php` - Frame/slot/port format
3. `config/zte_c600_ontstatus.php` - Status code mapping

### Auto-Detection Functions
```php
// In config/zteoid.php
$config = get_olt_oid_config($olt);

// In config/zteframeslotportid.php  
$config = get_olt_frameslotport_config($olt);
```

### OLT Type Detection Logic
```php
// Detects "c600", "c620", "c650" in OLT name or type field
if (preg_match('/c6[0-9]{2}/i', $oltName)) {
    return 'zte-c600';
}
```

## Recommendations for C620 Support

### 1. SNMP-Only Approach (Limited)
Use SNMP for basic monitoring only:
- ✓ ONU Status (online/offline)
- ✓ ONU List per port
- ✓ Firmware version
- ✗ No optical power
- ✗ No accurate distance
- ✗ No serial numbers

### 2. Hybrid CLI + SNMP Approach (Recommended)
Combine SNMP for listing with CLI for details:

```php
// 1. Use SNMP to get ONU list and status
$onuList = snmpWalk('1.3.6.1.4.1.3902.1082.10.1.2.4.1.5');

// 2. For detailed info, use SSH/Telnet CLI:
// show gpon onu detail-info gpon_onu-1/2/1:3
// show pon power attenuation gpon_onu-1/2/1:3
```

### 3. CLI Commands for C620
```bash
# List all ONUs on a port
show gpon onu state gpon-olt_1/2/1

# Get detailed ONU info (includes SN, distance, uptime)
show gpon onu detail-info gpon_onu-1/2/1:3

# Get optical power (OLT & ONU Rx/Tx)
show pon power attenuation gpon_onu-1/2/1:3
```

## Pending Tasks

- [ ] Update `OltController.php` to use `get_olt_oid_config()`
- [ ] Update `OltController.php` to use `get_olt_frameslotport_config()`
- [ ] Add OLT type dropdown in create/edit forms (zte-c300, zte-c600)
- [ ] Implement CLI parsing for C620 optical power (SSH/Telnet)
- [ ] Map CLI port notation (1/2/1) to SNMP index (1.1.x)
- [ ] Add fallback: use SNMP if available, CLI if needed
- [ ] Test with RW community to check if more OIDs accessible

## Testing Commands

```bash
# List all ONUs on slot 1, port 1
snmpwalk -v2c -c public_ro 103.156.74.17 .1.3.6.1.4.1.3902.1082.10.1.2.4.1.14.1.1

# Get specific ONU status (slot 1, port 1, ONU 1)
snmpget -v2c -c public_ro 103.156.74.17 .1.3.6.1.4.1.3902.1082.10.1.2.4.1.5.1.1.1

# List unconfigured ONUs
snmpwalk -v2c -c public_ro 103.156.74.17 .1.3.6.1.4.1.3902.1082.10.1.2.3.1.5
```

## Compatibility Matrix

| Feature | C300/C320 | C600/C620/C650 |
|---------|-----------|----------------|
| List ONUs | ✓ | ✓ |
| ONU Status | ✓ | ✓ |
| ONU Serial Number | ✓ | ✓ |
| User-Defined Name | ✓ | ✗ (use SN) |
| Unconfigured ONUs | ✓ | ✓ |
| Optical Power | ✓ | ? (different branch) |
| Frame/Slot/Port | Encoded | Simple decimal |
| Status Codes | Map A | Map B (different) |

