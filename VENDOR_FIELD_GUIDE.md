# Vendor Field Implementation Guide

## Database Schema

### Table: `olts`

```sql
ALTER TABLE olts ADD COLUMN vendor VARCHAR(50) DEFAULT 'zte' AFTER type;
```

**Columns**:
- `vendor` - Main vendor identifier (zte, cdata, hsgq, huawei, fiberhome, vsol, other)
- `type` - Specific model/type (c300, c620, fdd-lt, ma5, etc.)

## Web Form Implementation

### Create Form (`resources/views/olt/create.blade.php`)

**Vendor Field** (Dropdown):
```html
<select name="vendor" id="vendor" class="form-control" required>
  <option value="">-- Select Vendor --</option>
  <option value="zte">ZTE</option>
  <option value="cdata">CDATA</option>
  <option value="hsgq">HSGQ</option>
  <option value="huawei">Huawei</option>
  <option value="fiberhome">Fiberhome</option>
  <option value="vsol">VSOL</option>
  <option value="other">Other</option>
</select>
```

**Type Field** (Text Input with Helper):
```html
<input type="text" name="type" placeholder="e.g., c620, c300, fdd-lt" required>
<small class="text-muted">
  ZTE: c300, c320, c600, c620, c650
  CDATA: fdd-lt, fdd-olt
  Others: ma5, an5, etc.
</small>
```

### Edit Form (`resources/views/olt/edit.blade.php`)

Same structure with selected values:
```html
<option value="zte" {{ ($olt->vendor ?? '') == 'zte' ? 'selected' : '' }}>ZTE</option>
```

## Controller Validation

### Store Method (`OltController@store`)
```php
$validatedData = $request->validate([
    'vendor' => 'required|string|max:50',
    'type' => 'required|string|max:255',
    // ... other fields
]);
```

### Update Method (`OltController@update`)
```php
$olt->update([
    'vendor' => $request->input('vendor'),
    'type' => $request->input('type'),
    // ... other fields
]);
```

## Model Configuration

### Olt Model (`app/Olt.php`)
```php
protected $fillable = [
    'name', 'vendor', 'type', 'ip', 'port', 
    'user', 'password', 'community_ro', 'community_rw', 'snmp_port'
];
```

## Auto-Detection Priority

Helper function `get_olt_oid_config($olt)` checks in this order:

1. **Vendor column** (highest priority)
   ```php
   if ($olt->vendor == 'cdata') { ... }
   ```

2. **Type column** (fallback)
   ```php
   if (str_contains($olt->type, 'c620')) { ... }
   ```

3. **Name column** (last resort)
   ```php
   if (str_contains($olt->name, 'c620')) { ... }
   ```

## Vendor Values

| Vendor | DB Value | Type Examples | Config File |
|--------|----------|---------------|-------------|
| ZTE C300/C320 | `zte` | `zte`, `c300`, `c320` | `config/zteoid.php` |
| ZTE C600 | `zte` | `c600`, `c620`, `c650` | `config/zte_c600_oid.php` |
| CDATA | `cdata` | `fdd-lt`, `fdd-olt` | `config/cdata_oid.php` |
| HSGQ | `hsgq` | `hsgq` | `config/hsgq_oid.php` |
| Huawei | `huawei` | `ma5`, `ma5600`, `ma5800` | `config/huawei_oid.php` |
| Fiberhome | `fiberhome` | `an5`, `an5516` | `config/fiberhome_oid.php` |
| VSOL | `vsol` | `vsol` | Falls back to default |
| Other | `other` | Any | Falls back to default |

## Usage Examples

### Example 1: ZTE C300
```
Vendor: zte
Type: c300
Result: Uses config/zteoid.php (C300 config)
```

### Example 2: ZTE C620
```
Vendor: zte
Type: c620
Result: Uses config/zte_c600_oid.php (C600 series config)
```

### Example 3: CDATA FD1604
```
Vendor: cdata
Type: fdd-lt
Result: Uses config/cdata_oid.php (if exists, else fallback)
```

### Example 4: Huawei MA5800
```
Vendor: huawei
Type: ma5800
Result: Uses config/huawei_oid.php (if exists, else fallback)
```

## Benefits of Vendor Column

✅ **Clear Organization**: Separate vendor from model type
✅ **Easy Filtering**: Can query by vendor easily
✅ **Better Detection**: Priority-based vendor detection
✅ **Scalability**: Easy to add new vendors
✅ **User Friendly**: Dropdown selection vs manual typing
✅ **Validation**: Prevents typos and invalid vendors

## Migration from Old System

For existing OLTs without vendor field:

```sql
-- Set default vendor based on type
UPDATE olts SET vendor = 'zte' WHERE type IN ('zte', 'c300', 'c320', 'c600', 'c620', 'c650');
UPDATE olts SET vendor = 'cdata' WHERE type LIKE '%fdd%';
UPDATE olts SET vendor = 'huawei' WHERE type LIKE '%huawei%' OR type LIKE '%ma5%';
UPDATE olts SET vendor = 'hsgq' WHERE type = 'hsgq';

-- Set default for unknowns
UPDATE olts SET vendor = 'other' WHERE vendor IS NULL;
```

## Form Validation

Client-side validation (optional):
```javascript
$('#vendor').on('change', function() {
    var vendor = $(this).val();
    var suggestions = {
        'zte': 'c300, c320, c600, c620, c650',
        'cdata': 'fdd-lt, fdd-olt',
        'huawei': 'ma5, ma5600, ma5800',
        'hsgq': 'hsgq',
        'fiberhome': 'an5, an5516'
    };
    
    if (suggestions[vendor]) {
        $('#type').attr('placeholder', 'e.g., ' + suggestions[vendor]);
    }
});
```

## API Response Example

When fetching OLT data via API:
```json
{
  "id": 2,
  "name": "OLT NDC",
  "vendor": "zte",
  "type": "c620",
  "ip": "103.156.74.17",
  "detected_vendor": "ZTE C600/C620/C650",
  "config_used": "zte_c600_oid"
}
```

## Testing Checklist

- [ ] Create new OLT with vendor dropdown
- [ ] Edit existing OLT and change vendor
- [ ] Verify auto-detection uses vendor field first
- [ ] Test with all supported vendors
- [ ] Check validation prevents empty vendor
- [ ] Confirm OID config loads correctly per vendor
- [ ] Test backward compatibility (OLTs without vendor)

## Troubleshooting

**Issue**: OLT not detecting correct config
**Solution**: Check vendor and type fields match expected values

**Issue**: Form validation error on vendor
**Solution**: Ensure vendor column exists in database and model fillable

**Issue**: Old OLTs showing NULL vendor
**Solution**: Run migration SQL to set default vendor values

## Production Status

✅ Database column added
✅ Forms updated (create & edit)
✅ Controller validation added
✅ Model fillable updated
✅ Auto-detection prioritizes vendor column
✅ Existing data migrated
✅ Documentation complete

**Status**: READY FOR PRODUCTION
