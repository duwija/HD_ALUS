# ZTE C620 Index Format Fix

## Problem
Error when accessing `/olt/2` route:
```
Error in packet at 'SNMPv2-SMI::enterprises.3902.1082.10.1.2.4.1.5.1.1': No Such Instance
```

## Root Cause
Config file `zte_c600_frameslotportid.php` had incorrect mapping:
- **Wrong format**: "1/2" → "1.2" (missing frame prefix, only slot/port)
- **Correct format**: "1/2/1" → "1.2" (frame/slot/port → shelf.slot)

C620 OID branch `.1082.10.1.2.4.1` uses **shelf.slot.onuid** format where:
- Database stores: "frame/slot/port" (e.g., "1/2/1")
- SNMP index uses: "shelf.slot.onuid" (e.g., ".1.2.3")
- **Port number is NOT encoded** in this branch's index
- All 16 ports on same slot share same shelf.slot prefix

## Solution
1. **Rebuilt `config/zte_c600_frameslotportid.php`**:
   - Changed mapping from "slot/port" to "frame/slot/port"
   - All ports on same slot map to same index
   - Example: "1/2/1", "1/2/2", ..., "1/2/16" all → "1.2"

2. **Added `encode_zte_c600_index()` helper function**:
   - For OID branch `.1082.500.*` which uses encoded index
   - Formula: `(17 << 24) + (frame << 16) + ((slot+1) << 8) + port`
   - Example: Port 1/1/1 → 285278721 (0x11010201)
   - Verified against real C620 hardware @ 103.156.74.17

## Index Format Differences in C620

### Branch `.10.1.2.4.1.*` (Basic ONU Info)
- Format: `.{oidBase}.{shelf}.{slot}.{onuid}`
- Example: `.1082.10.1.2.4.1.5.1.1.3` (status, shelf 1, slot 1, ONU 3)
- Config: Uses `zte_c600_frameslotportid.php` mapping
- Port NOT included in index

### Branch `.500.*` (Advanced ONU Info)
- Format: `.{oidBase}.{encodedIndex}.{onuid}`
- Example: `.1082.500.10.2.3.3.1.6.285278721.1` (SN, port 1/1/1, ONU 1)
- Config: Uses `encode_zte_c600_index()` function
- Encoded index includes frame, slot, port

## Verification
```bash
# Test basic ONU status (branch .10.1.2.4.1)
snmpget -v2c -c public_ro 103.156.74.17 .1.3.6.1.4.1.3902.1082.10.1.2.4.1.5.1.1.3
# Result: INTEGER: 1 (working) ✓

# Test serial number (branch .500)
snmpget -v2c -c public_ro 103.156.74.17 .1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.6.285278721.1
# Result: Hex-STRING: 5A 54 45 47 C8 51 C6 2F ✓
```

## Files Modified
- `config/zte_c600_frameslotportid.php` - Rebuilt with correct mapping
- `config/zteoid.php` - Added `encode_zte_c600_index()` function

## Status
✅ Error resolved - Route `/olt/2` now works correctly
✅ SNMP queries successful for all tested ONUs
✅ Helper functions ready for both index formats
