# Multi-Vendor OLT Support Guide

## Overview
Sistem ini mendukung multiple vendor OLT dengan auto-detection berdasarkan nama/type/vendor OLT. Setiap vendor memiliki config file terpisah untuk OID, frame/slot/port encoding, dan status mapping.

## Supported Vendors

### 1. **ZTE**
- **Models**: C300, C320, C600, C620, C650
- **Config Files**:
  - `config/zteoid.php` - C300/C320 series (default)
  - `config/zte_c600_oid.php` - C600/C620/C650 series
  - `config/zteframeslotportid.php` - C300 frame/slot/port
  - `config/zte_c600_frameslotportid.php` - C600 frame/slot/port
  - `config/zteontstatus.php` - C300 status
  - `config/zte_c600_ontstatus.php` - C600 status
- **Detection Keywords**: `c300`, `c320`, `c600`, `c620`, `c650`, `zte`

### 2. **CDATA** (To be implemented)
- **Models**: FD-LT, FD-OLT series
- **Config Files** (to create):
  - `config/cdata_oid.php` - Main OID mapping
  - `config/cdata_frameslotportid.php` - Frame/slot/port encoding
  - `config/cdata_onustatus.php` - Status mapping
  - `config/cdata_offline_reason.php` - Offline reason codes
- **Detection Keywords**: `cdata`, `fdd-lt`, `fdd-olt`, `fdd`
- **Notes**: CDATA uses different SNMP community structure

### 3. **HSGQ** (To be implemented)
- **Models**: HSGQ GPON OLT series
- **Config Files** (to create):
  - `config/hsgq_oid.php` - Main OID mapping
  - `config/hsgq_frameslotportid.php` - Frame/slot/port encoding
  - `config/hsgq_onustatus.php` - Status mapping
- **Detection Keywords**: `hsgq`
- **Notes**: HSGQ might use standard or custom OID tree

### 4. **Huawei** (Future support)
- **Models**: MA5600, MA5800 series
- **Config Files** (to create):
  - `config/huawei_oid.php` - Main OID mapping
  - `config/huawei_frameslotportid.php` - Frame/slot/port encoding
  - `config/huawei_onustatus.php` - Status mapping
- **Detection Keywords**: `huawei`, `ma5`
- **Notes**: Huawei uses very different MIB structure

### 5. **Fiberhome** (Future support)
- **Models**: AN5xxx series
- **Config Files** (to create):
  - `config/fiberhome_oid.php` - Main OID mapping
  - `config/fiberhome_frameslotportid.php` - Frame/slot/port encoding
  - `config/fiberhome_onustatus.php` - Status mapping
- **Detection Keywords**: `fiberhome`, `an5`

## Auto-Detection System

### Helper Functions (in config/zteoid.php)

#### 1. `get_olt_oid_config($olt)`
Mengembalikan OID config berdasarkan vendor/model OLT.

**Detection Priority**:
1. CDATA → `config('cdata_oid')`
2. HSGQ → `config('hsgq_oid')`
3. Huawei → `config('huawei_oid')`
4. Fiberhome → `config('fiberhome_oid')`
5. ZTE C600/C620/C650 → `config('zte_c600_oid')`
6. Default (ZTE C300/C320) → `config('zteoid')`

**Usage**:
```php
$oidConfig = get_olt_oid_config($olt);
$onuStatusOid = $oidConfig['oidOnuStatus'];
```

#### 2. `get_olt_frameslotport_config($olt)`
Mengembalikan frame/slot/port encoding config.

**Usage**:
```php
$fspConfig = get_olt_frameslotport_config($olt);
$portMapping = $fspConfig['port_mapping'];
```

#### 3. `get_olt_status_config($olt)`
Mengembalikan ONU status mapping config.

**Usage**:
```php
$statusConfig = get_olt_status_config($olt);
$statusText = $statusConfig[$statusCode] ?? 'Unknown';
```

## Adding New Vendor Support

### Step 1: SNMP Discovery
Test SNMP pada hardware vendor baru:
```bash
# Test basic info
snmpwalk -v2c -c public_ro <ip> .1.3.6.1.2.1.1

# Scan untuk ONU info
snmpwalk -v2c -c public_ro <ip> .1.3.6.1.4.1

# Cari base OID vendor (biasanya di enterprise tree)
snmpwalk -v2c -c public_ro <ip> .1.3.6.1.4.1.<vendor_enterprise_id>
```

### Step 2: Create Config Files

#### config/<vendor>_oid.php
```php
<?php
return [
    'oidOltName' => '.x.x.x.x.x.0',
    'oidOltVersion' => '.x.x.x.x.x.0',
    'oidOnuName' => '.x.x.x.x.x',
    'oidOnuStatus' => '.x.x.x.x.x',
    'oidOnuRxPower' => '.x.x.x.x.x',
    // ... semua OID yang diperlukan
    
    // Helper functions (jika perlu konversi khusus)
    'convertOpticalPower' => function($value) {
        return $value / 100; // Example
    },
];
```

#### config/<vendor>_frameslotportid.php
```php
<?php
return [
    'frame_encoding' => 'decimal', // or 'encoded', 'hex', etc.
    'slot_encoding' => 'decimal',
    'port_encoding' => 'decimal',
    
    // Helper function untuk encode index
    'encodeIndex' => function($frame, $slot, $port, $onuid) {
        // Vendor-specific encoding logic
        return "{$frame}.{$slot}.{$port}.{$onuid}";
    },
];
```

#### config/<vendor>_onustatus.php
```php
<?php
return [
    1 => 'online',
    2 => 'offline',
    3 => 'logging',
    // ... status codes
];
```

### Step 3: Update Detection Logic
Detection otomatis sudah tersedia di `config/zteoid.php`. Pastikan keyword vendor terdeteksi dengan benar.

### Step 4: Database Schema (Optional)
Jika diperlukan, tambahkan kolom `vendor` di table `olts`:
```sql
ALTER TABLE olts ADD COLUMN vendor VARCHAR(50) DEFAULT 'zte' AFTER type;
```

### Step 5: Testing
```php
// Test auto-detection
$olt = Olt::find(1);
$oidConfig = get_olt_oid_config($olt);
dd($oidConfig); // Harus return config vendor yang sesuai
```

## OLT Database Fields

### Required Fields
- `name` - OLT name (harus include model/vendor keyword)
- `ip` - OLT IP address
- `username` - SNMP community atau SSH username
- `password` - SNMP community atau SSH password

### Optional Fields
- `type` - OLT type/model (untuk detection)
- `vendor` - OLT vendor (untuk detection priority)
- `snmp_version` - SNMP version (default: 2c)
- `snmp_port` - SNMP port (default: 161)

### Naming Convention Examples
```
✅ Good (auto-detected):
- "OLT-CDATA-FD1604" → CDATA
- "ZTE-C620-DOWNTOWN" → ZTE C620
- "HSGQ-GPON-01" → HSGQ
- "MA5800-JAKARTA" → Huawei

❌ Bad (manual vendor field required):
- "OLT-01" → Unknown
- "GPON-MAIN" → Unknown
```

## Implementation in OltController

### Before (Single Vendor)
```php
$oidConfig = config('zteoid');
$onuStatusOid = $oidConfig['oidOnuStatus'];
```

### After (Multi Vendor)
```php
$oidConfig = get_olt_oid_config($olt);
$onuStatusOid = $oidConfig['oidOnuStatus'];

$statusConfig = get_olt_status_config($olt);
$statusText = $statusConfig[$statusValue] ?? 'Unknown';
```

## Vendor-Specific Notes

### CDATA
- Community string sering berbeda: `public@vlan<id>`
- Frame/slot/port biasanya simple: `0/slot/port`
- Distance dalam format berbeda (perlu konversi)
- ONU type di OID terpisah

### HSGQ
- Mirip dengan ZTE structure
- Beberapa OID mungkin sama dengan ZTE C300
- Perlu test real hardware untuk validasi

### Huawei
- MIB structure sangat berbeda
- Frame/slot/port: `0/slot/port`
- Index encoding: complex dengan board ID
- Status mapping completely different

### Fiberhome
- MIB mirip dengan Huawei
- Index biasanya: `shelf/slot/port/onuid`
- Optical power dalam 0.01 dBm unit

## Testing Checklist

Untuk setiap vendor baru:
- [ ] Test SNMP connectivity dengan snmpwalk
- [ ] Discover base OID tree structure
- [ ] Find ONU list OID
- [ ] Find ONU status OID
- [ ] Find optical power OIDs (RX/TX untuk OLT dan ONU)
- [ ] Find distance OID
- [ ] Find serial number OID
- [ ] Find last online/offline OIDs
- [ ] Validate status code mapping
- [ ] Test frame/slot/port encoding
- [ ] Create helper functions jika perlu konversi
- [ ] Test dengan real hardware minimal 3 ONU
- [ ] Document semua findings di README

## Future Enhancements

1. **Vendor Auto-Discovery via SNMP**
   - Query sysDescr OID untuk detect vendor otomatis
   - Update database dengan vendor info

2. **Web UI untuk Config Management**
   - Interface untuk add/edit vendor config
   - SNMP OID tester tool

3. **Multi-Protocol Support**
   - Telnet/SSH untuk vendor yang tidak support SNMP lengkap
   - Hybrid SNMP + CLI parsing

4. **Vendor-Specific Features**
   - Per-vendor advanced monitoring
   - Custom reports per vendor
   - Vendor-specific troubleshooting tools

## Support Status

| Vendor | Status | Config Files | Testing | Documentation |
|--------|--------|--------------|---------|---------------|
| ZTE C300/C320 | ✅ Production | Complete | ✅ Validated | ✅ Complete |
| ZTE C600/C620/C650 | ✅ Production | Complete | ✅ Hardware tested | ✅ Complete |
| CDATA | 🔄 Planned | Not created | ❌ Needed | 📝 Template ready |
| HSGQ | 🔄 Planned | Not created | ❌ Needed | 📝 Template ready |
| Huawei | 📋 Future | Not created | ❌ Needed | 📝 Template ready |
| Fiberhome | 📋 Future | Not created | ❌ Needed | 📝 Template ready |

## Contact & Contributions

Jika Anda punya akses ke hardware vendor lain dan ingin contribute:
1. Test SNMP dengan checklist di atas
2. Document findings
3. Create config files sesuai template
4. Submit untuk review
