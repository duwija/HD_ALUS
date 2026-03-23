# Single Page Multi-Vendor Implementation

## Architecture Decision: One Page for All Vendors ✅

**Question**: Apakah perlu membuat page berbeda untuk setiap vendor karena OID berbeda?

**Answer**: **TIDAK PERLU!** Satu page sudah cukup dengan auto-detection system.

## Why Single Page Works

### 1. **Auto-Detection System**
```php
// Controller automatically detects vendor
$olt = Olt::find($id);
$oidConfig = get_olt_oid_config($olt);  // Returns correct OID based on vendor
```

### 2. **Generic View Template**
View tidak perlu tahu tentang OID spesifik vendor:
```blade
<!-- resources/views/olt/show.blade.php -->
<p><strong>Name:</strong> {{ $olt->name }}</p>
<p><strong>Vendor:</strong> {{ $olt->vendor }}</p>
<p><strong>Type:</strong> {{ $olt->type }}</p>
```

### 3. **Controller Logic Handles Differences**
```php
public function getOltOnu($olt_id, $frame, $slot, $port) {
    $olt = Olt::find($olt_id);
    
    // Auto-detect OID config based on vendor
    $oidConfig = get_olt_oid_config($olt);
    $statusConfig = get_olt_status_config($olt);
    
    // Same code works for all vendors!
    $onuStatus = $oidConfig['oidOnuStatus'];
    $onuDistance = $oidConfig['oidOnuDistance'];
    
    // Query SNMP with vendor-specific OIDs
    $result = snmpwalk($olt->ip, $olt->community_ro, $onuStatus);
    
    return response()->json($result);
}
```

## Implementation Pattern

### ✅ **Correct Approach (Current)**

**Single Route for All Vendors**:
```php
// routes/web.php
Route::get('/olt/{id}', 'OltController@show');
Route::post('/olt/{olt_id}/onu/{frame}/{slot}/{port}', 'OltController@getOltOnu');
```

**Controller Auto-Detects**:
```php
public function show($id) {
    $olt = Olt::findOrFail($id);
    // View same for all vendors
    return view('olt.show', ['olt' => $olt]);
}

public function getOltOnu($olt_id, ...) {
    $olt = Olt::find($olt_id);
    
    // Auto-detect config
    $oidConfig = get_olt_oid_config($olt);
    
    // Use vendor-specific OIDs
    $snmp->walk($oidConfig['oidOnuStatus']);
}
```

### ❌ **Wrong Approach (Not Needed)**

**Multiple Routes per Vendor** (TIDAK PERLU):
```php
// DON'T DO THIS!
Route::get('/olt/zte/{id}', 'ZteOltController@show');
Route::get('/olt/cdata/{id}', 'CdataOltController@show');
Route::get('/olt/hsgq/{id}', 'HsgqOltController@show');
```

**Multiple Views per Vendor** (TIDAK PERLU):
```
resources/views/olt/
  ├── show_zte.blade.php    ❌ Not needed
  ├── show_cdata.blade.php  ❌ Not needed
  └── show_hsgq.blade.php   ❌ Not needed
```

## Benefits of Single Page

### ✅ **Maintainability**
- Update UI di 1 tempat untuk semua vendor
- Tidak ada duplikasi code
- Bug fix apply ke semua vendor

### ✅ **Scalability**
- Add vendor baru hanya perlu:
  1. Create config file (`config/newvendor_oid.php`)
  2. Update helper function detection
  3. Done! No view/route changes needed

### ✅ **Consistency**
- User experience sama untuk semua vendor
- Same navigation pattern
- Same feature availability

### ✅ **Code Reusability**
- Same JavaScript for AJAX calls
- Same DataTables configuration
- Same CSS styling

## How It Works

### Flow Diagram
```
User Request
    ↓
Route: /olt/{id}
    ↓
OltController@show($id)
    ↓
Load $olt from database
    ↓
Pass to view('olt.show')
    ↓
View displays vendor badge (auto-detected)
    ↓
AJAX calls controller methods
    ↓
Controller uses get_olt_oid_config($olt)
    ↓
Auto-detect vendor → Load correct OID config
    ↓
Query SNMP with vendor-specific OIDs
    ↓
Return data (same format for all vendors)
    ↓
JavaScript renders data (same for all vendors)
```

## Example: ONU List Query

### Single Method Handles All Vendors

```php
public function getOltOnu($olt_id, $frame, $slot, $port) {
    $olt = Olt::find($olt_id);
    
    // Auto-detect vendor and load config
    $oidConfig = get_olt_oid_config($olt);
    $statusConfig = get_olt_status_config($olt);
    $vendor = get_olt_vendor($olt);
    
    // Log for debugging
    \Log::info("Querying ONU list", [
        'olt_id' => $olt_id,
        'vendor' => $vendor,
        'config' => get_class($oidConfig) // Will show which config is used
    ]);
    
    // Use vendor-specific OIDs (different for each vendor)
    $onuStatusOid = $oidConfig['oidOnuStatus'];
    $onuDistanceOid = $oidConfig['oidOnuDistance'];
    $onuSnOid = $oidConfig['oidOnuSn'];
    
    // Query SNMP (same code for all vendors)
    $snmp = new \SNMP(\SNMP::VERSION_2c, $olt->ip, $olt->community_ro);
    $statusResults = $snmp->walk($onuStatusOid);
    $distanceResults = $snmp->walk($onuDistanceOid);
    $snResults = $snmp->walk($onuSnOid);
    
    // Process results (format may differ per vendor)
    if (isset($oidConfig['convertOpticalPower'])) {
        // ZTE C600 has helper functions
        foreach ($powerResults as $index => $value) {
            $powerResults[$index] = $oidConfig['convertOpticalPower']($value, 'olt_rx');
        }
    }
    
    // Return same JSON structure for all vendors
    return response()->json([
        'vendor' => $vendor,
        'status' => $statusResults,
        'distance' => $distanceResults,
        'serial_number' => $snResults,
    ]);
}
```

## View Updates for Multi-Vendor

### Display Vendor Information

```blade
<!-- resources/views/olt/show.blade.php -->

<div class="card-body">
  <p><strong>Name:</strong> {{ $olt->name }}</p>
  
  <p><strong>Vendor:</strong> 
    @php
      $vendorBadges = [
        'zte' => 'badge-info',
        'cdata' => 'badge-success',
        'hsgq' => 'badge-warning',
        'huawei' => 'badge-danger',
        'fiberhome' => 'badge-primary',
      ];
      $badge = $vendorBadges[$olt->vendor ?? 'other'] ?? 'badge-secondary';
      $detectedVendor = get_olt_vendor($olt);
    @endphp
    <span class="badge {{ $badge }}">{{ strtoupper($olt->vendor) }}</span>
    <small class="text-muted">({{ $detectedVendor }})</small>
  </p>
  
  <p><strong>Type:</strong> {{ $olt->type }}</p>
  <p><strong>IP Address:</strong> {{ $olt->ip }}</p>
</div>
```

### Conditional Features (if needed)

```blade
<!-- Show vendor-specific features only if supported -->
@php
  $oidConfig = get_olt_oid_config($olt);
  $hasOnuTxPower = isset($oidConfig['oidOnuTxPower']);
@endphp

@if($hasOnuTxPower)
  <th>ONU TX Power</th>
@endif
```

## JavaScript/AJAX (Same for All Vendors)

```javascript
// Single AJAX call works for all vendors
function loadOnuList(oltId, frame, slot, port) {
    $.ajax({
        url: `/olt/${oltId}/onu/${frame}/${slot}/${port}`,
        method: 'GET',
        success: function(response) {
            // Response format same for all vendors
            console.log('Vendor:', response.vendor);
            renderOnuTable(response);
        }
    });
}

// Single render function
function renderOnuTable(data) {
    // Same rendering logic for all vendors
    data.status.forEach(function(onu) {
        // Add row to table
    });
}
```

## When Would You Need Multiple Pages?

### ❌ **NOT Needed For**:
- Different OID structures (handled by config)
- Different SNMP communities (stored in DB)
- Different data formats (handled by helper functions)
- Different status codes (handled by status config)

### ✅ **ONLY Needed If**:
- Completely different UI/UX requirements per vendor
- Different features available (e.g., vendor A has graphs, vendor B doesn't)
- Different user workflows

**Solution**: Use conditional rendering in single view instead of separate pages!

## Testing Single Page Approach

### Test with Multiple Vendors

```php
// Test ZTE C300
$olt1 = Olt::find(1); // ZTE C300
$config1 = get_olt_oid_config($olt1);
// Uses config/zteoid.php

// Test ZTE C620
$olt2 = Olt::find(2); // ZTE C620
$config2 = get_olt_oid_config($olt2);
// Uses config/zte_c600_oid.php

// Same view, different configs
return view('olt.show', ['olt' => $olt1]); // Works
return view('olt.show', ['olt' => $olt2]); // Works
```

## Migration Checklist

✅ **Current Status**:
- [x] Single route `/olt/{id}` for all vendors
- [x] Single view `olt/show.blade.php` for all vendors
- [x] Auto-detection in config helper functions
- [x] Vendor badge display in view
- [x] Controller uses `get_olt_oid_config($olt)`

✅ **Future Enhancement** (optional):
- [ ] Add vendor-specific feature toggles
- [ ] Conditional column display based on available OIDs
- [ ] Vendor-specific help text/tooltips

## Conclusion

**Single Page Approach = ✅ RECOMMENDED**

**Reasons**:
1. Auto-detection system handles OID differences
2. Easier to maintain and scale
3. Consistent user experience
4. No code duplication
5. Adding new vendor only needs config file

**Implementation Status**: ✅ **READY - Already using single page approach**

The system is already designed correctly with single page for all vendors!
