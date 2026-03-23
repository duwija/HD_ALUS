# Multi-Vendor OLT Setup Guide

## Overview
Sistem sudah di-setup untuk support multiple vendor OLT dengan auto-detection berdasarkan field `type` dan `name` di table `olts`.

## Database Setup

### Table OLTs
Pastikan table `olts` memiliki struktur:
```sql
CREATE TABLE olts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(15) NOT NULL,  -- Vendor detection field
    ip VARCHAR(50) NOT NULL,
    username VARCHAR(255),       -- SNMP community / SSH user
    password VARCHAR(255),       -- SNMP community / SSH pass
    -- fields lainnya...
);
```

### Naming Convention untuk Auto-Detection

Field **type** atau **name** harus mengandung keyword vendor:

#### ZTE C300/C320 (Default)
```sql
type: 'c300', 'c320', 'zte-c300', 'zte-c320'
name: 'OLT-ZTE-C300-JAKARTA', 'C320-DOWNTOWN'
```

#### ZTE C600/C620/C650
```sql
type: 'c600', 'c620', 'c650', 'zte-c600'
name: 'OLT-C620-MAIN', 'ZTE-C650-NORTH'
```

#### CDATA
```sql
type: 'cdata', 'fdd-lt', 'fdd-olt', 'fdd'
name: 'OLT-CDATA-FD1604', 'CDATA-GPON-01'
```

#### HSGQ
```sql
type: 'hsgq'
name: 'OLT-HSGQ-01', 'HSGQ-GPON-MAIN'
```

#### Huawei
```sql
type: 'huawei', 'ma5'
name: 'OLT-HUAWEI-MA5800', 'MA5600-JAKARTA'
```

#### Fiberhome
```sql
type: 'fiberhome', 'an5'
name: 'OLT-FIBERHOME-AN5516', 'AN5000-NORTH'
```

## Config Files Structure

### Existing (Ready to Use)
- ✅ `config/zteoid.php` - ZTE C300/C320 + Helper functions
- ✅ `config/zte_c600_oid.php` - ZTE C600/C620/C650
- ✅ `config/zte_c600_frameslotportid.php`
- ✅ `config/zte_c600_ontstatus.php`
- ✅ `config/zte_c600_offline_reason.php`
- ✅ `config/zte_c600_reg_mode.php`
- ✅ `config/zteframeslotportid.php`
- ✅ `config/zteontstatus.php`

### To Create (When Needed)
- ⏳ `config/cdata_oid.php`
- ⏳ `config/cdata_frameslotportid.php`
- ⏳ `config/cdata_onustatus.php`
- ⏳ `config/hsgq_oid.php`
- ⏳ `config/hsgq_frameslotportid.php`
- ⏳ `config/hsgq_onustatus.php`
- ⏳ `config/huawei_oid.php`
- ⏳ `config/huawei_frameslotportid.php`
- ⏳ `config/huawei_onustatus.php`

## Usage in Controller

### Before (Single Vendor)
```php
// Old way - hardcoded config
$oidConfig = config('zteoid');
$onuStatusOid = $oidConfig['oidOnuStatus'];
```

### After (Multi Vendor)
```php
// New way - auto-detection
$olt = Olt::find($oltId);
$oidConfig = get_olt_oid_config($olt);
$onuStatusOid = $oidConfig['oidOnuStatus'];

// Get status mapping
$statusConfig = get_olt_status_config($olt);
$statusText = $statusConfig[$statusValue] ?? 'Unknown';

// Get vendor name (for logging)
$vendor = get_olt_vendor($olt);
\Log::info("Processing OLT: {$olt->name} (Vendor: {$vendor})");
```

### Helper Functions Available

#### 1. get_olt_oid_config($olt)
Returns OID configuration array based on vendor.

```php
$olt = Olt::find(1);
$oidConfig = get_olt_oid_config($olt);
$rxPowerOid = $oidConfig['oidOltRxPower'];
```

#### 2. get_olt_frameslotport_config($olt)
Returns frame/slot/port encoding config.

```php
$fspConfig = get_olt_frameslotport_config($olt);
```

#### 3. get_olt_status_config($olt)
Returns status code mapping.

```php
$statusConfig = get_olt_status_config($olt);
$statusText = $statusConfig[1] ?? 'Unknown'; // 1 = online/working
```

#### 4. get_olt_vendor($olt)
Returns detected vendor name.

```php
$vendor = get_olt_vendor($olt);
// Returns: 'ZTE C300/C320', 'ZTE C600/C620/C650', 'CDATA', 'HSGQ', etc.
```

## Migration Steps

### Step 1: Update Database
Pastikan field `type` terisi dengan benar:

```sql
-- ZTE C300/C320
UPDATE olts SET type = 'c300' WHERE name LIKE '%C300%';
UPDATE olts SET type = 'c320' WHERE name LIKE '%C320%';

-- ZTE C600/C620/C650
UPDATE olts SET type = 'c620' WHERE name LIKE '%C620%';
UPDATE olts SET type = 'c650' WHERE name LIKE '%C650%';

-- CDATA
UPDATE olts SET type = 'cdata' WHERE name LIKE '%CDATA%';

-- HSGQ
UPDATE olts SET type = 'hsgq' WHERE name LIKE '%HSGQ%';
```

### Step 2: Update OltController
Replace semua `config('zteoid')` dengan `get_olt_oid_config($olt)`:

```php
// Find patterns like:
config('zteoid')
config('zteframeslotportid')
config('zteontstatus')

// Replace with:
get_olt_oid_config($olt)
get_olt_frameslotport_config($olt)
get_olt_status_config($olt)
```

### Step 3: Test
1. Test dengan existing ZTE C300/C320 (backward compatibility)
2. Test dengan ZTE C620 (new config)
3. Add vendor baru sesuai kebutuhan

## Adding New Vendor

### Example: CDATA Support

#### 1. Create config/cdata_oid.php
```php
<?php
return [
    'oidOltName' => '.1.3.6.1.2.1.1.5.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOnuStatus' => '.x.x.x.x.x', // SNMP walk to find
    'oidOnuRxPower' => '.x.x.x.x.x',
    // ... all OIDs
];
```

#### 2. Create config/cdata_frameslotportid.php
```php
<?php
return [
    'frame_format' => 'decimal',
    'slot_format' => 'decimal',
    'port_format' => 'decimal',
    'encodeIndex' => function($frame, $slot, $port, $onuid) {
        return "0.{$slot}.{$port}.{$onuid}"; // CDATA usually 0/slot/port
    },
];
```

#### 3. Create config/cdata_onustatus.php
```php
<?php
return [
    1 => 'online',
    2 => 'offline',
    3 => 'los',
    // ... status codes
];
```

#### 4. Add OLT to Database
```sql
INSERT INTO olts (name, type, ip, username, password) 
VALUES ('OLT-CDATA-FD1604', 'cdata', '192.168.1.10', 'public', 'public');
```

#### 5. Test
```php
$olt = Olt::where('type', 'cdata')->first();
$oidConfig = get_olt_oid_config($olt);
dd($oidConfig); // Should return config('cdata_oid')
```

## Vendor Detection Logic

Priority order:
1. Check `type` field for vendor keywords
2. Check `name` field for vendor keywords
3. Default to ZTE C300/C320

Keywords checked (case-insensitive):
- **CDATA**: cdata, fdd-lt, fdd-olt, fdd
- **HSGQ**: hsgq
- **Huawei**: huawei, ma5
- **Fiberhome**: fiberhome, an5
- **ZTE C600**: c600, c620, c650, zte-c600
- **ZTE C300**: Default fallback

## Error Handling

Jika config file vendor tidak ada:
```php
// System akan log warning dan fallback ke ZTE C300
\Log::warning("CDATA OLT detected but config/cdata_oid.php not found. Using ZTE default.");
```

## Testing Checklist

- [ ] ZTE C300/C320 masih berfungsi (backward compatibility)
- [ ] ZTE C620 menggunakan config yang benar
- [ ] Auto-detection bekerja dari database field
- [ ] Vendor baru bisa ditambahkan tanpa modify core files
- [ ] Error handling proper jika config tidak ada

## Example SQL Queries

### Check Current OLT Types
```sql
SELECT id, name, type FROM olts;
```

### Update OLT Types
```sql
-- Bulk update by name pattern
UPDATE olts SET type = 'c620' WHERE name LIKE '%620%';
UPDATE olts SET type = 'cdata' WHERE name LIKE '%CDATA%';
UPDATE olts SET type = 'hsgq' WHERE name LIKE '%HSGQ%';
```

### Test Vendor Detection
```sql
SELECT 
    id,
    name,
    type,
    CASE 
        WHEN LOWER(type) LIKE '%cdata%' OR LOWER(name) LIKE '%cdata%' THEN 'CDATA'
        WHEN LOWER(type) LIKE '%hsgq%' OR LOWER(name) LIKE '%hsgq%' THEN 'HSGQ'
        WHEN LOWER(type) LIKE '%c620%' OR LOWER(name) LIKE '%c620%' THEN 'ZTE C620'
        WHEN LOWER(type) LIKE '%c600%' OR LOWER(name) LIKE '%c600%' THEN 'ZTE C600'
        ELSE 'ZTE C300/C320'
    END as detected_vendor
FROM olts;
```

## Benefits

✅ **Scalable**: Add vendor baru tanpa modify existing code
✅ **Flexible**: Detection dari database field (type/name)
✅ **Backward Compatible**: Existing ZTE C300/C320 tetap jalan
✅ **Maintainable**: Vendor config terpisah per file
✅ **Auto-Detection**: Tidak perlu manual config per OLT
✅ **Fallback Safe**: Default ke ZTE jika config tidak ada

## Future Enhancements

1. **Web UI**: Interface untuk manage vendor config
2. **SNMP Auto-Discovery**: Detect vendor dari sysDescr OID
3. **Vendor-Specific Features**: Custom logic per vendor
4. **Hybrid SNMP+CLI**: Fallback mechanism
5. **Config Validation**: Test OID sebelum save
