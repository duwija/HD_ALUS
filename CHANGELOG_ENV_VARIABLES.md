# CHANGELOG - Environment Variables Database System

## [1.2.1] - 2026-03-29

### 📧 Marketing Email Tenant Env

#### Added
- `MARKETING_EMAIL` sebagai env tenant untuk tujuan email order add-on portal pelanggan.
- Tombol cepat **Set MARKETING_EMAIL** di halaman admin tenant create/edit.

#### Changed
- `TenantManagementController::processEnvVariables()` sekarang otomatis mengisi default `MARKETING_EMAIL=duwija@trikamedia.com` jika key belum diinput.

#### Notes
- Nilai ini bisa dioverride per tenant dari menu **Custom Environment Variables** di admin tenant.

## [1.2.0] - 2026-02-27

### 🔔 Notification Delay Variables

#### Added
- `NOTIF_DELAY_MIN` — Delay minimum antar pesan WhatsApp massal (detik, default: 180)
- `NOTIF_DELAY_MAX` — Delay maximum antar pesan WhatsApp massal (detik, default: 360)
- `NOTIF_LONG_PAUSE_EVERY` — Long pause setiap N pesan (default: random 18–27)
- `NOTIF_LONG_PAUSE_EXTRA` — Extra delay saat long pause (detik, default: 600)

#### Changed
- `SuminvoiceController::messageDelay()` — base delay, long pause extra, dan minimum safety kini membaca dari `tenant_config()` dengan fallback ke nilai default semula
- `SuminvoiceController` — `$longPauseEvery` di fungsi `blockReminder()` dan `sendNotifInvoice()` kini membaca dari `NOTIF_LONG_PAUSE_EVERY`

#### Use Case
Tenant dengan gateway WhatsApp cepat bisa set delay lebih singkat; tenant yang menggunakan gateway dengan anti-flood ketat bisa set lebih lama, tanpa perlu deploy ulang.

---

## [1.1.0] - 2026-02-27

### ⚙️ Queue Worker Configuration Variables

#### Added
- `QUEUE_SLEEP` — Jeda worker saat queue kosong (detik, default: 3)
- `QUEUE_TRIES` — Maks retry job sebelum masuk failed_jobs (default: 3)
- `QUEUE_TIMEOUT` — Timeout per job (detik, default: 120)
- `QUEUE_MAX_JOBS` — Worker restart otomatis setelah N job (default: 500)

#### Changed
- Admin panel Queue Worker Monitor (`/admin/tenants/{id}`) mendapat panel **Settings** untuk mengubah parameter ini secara visual
- `TenantManagementController::queueConfig()` — method baru yang menyimpan nilai ke `env_variables` dan merewrite `/etc/supervisord.d/{slug}.conf` lalu restart worker via supervisorctl
- `TenantManagementController::queueStatus()` — kini mengembalikan `queue_settings` dalam response JSON

---

## [1.0.0] - 2025-11-22

### 🎉 NEW FEATURE: Custom Environment Variables Per Tenant

#### Added
- **Database JSON Storage**: Kolom `env_variables` (JSON) di tabel `tenants`
- **Helper Function**: `tenant_env($key, $default)` dengan 3-tier priority:
  1. Database JSON (tenant-specific)
  2. Global .env file (shared)
  3. Default value (fallback)
- **Admin UI Integration**:
  - Section "Custom Environment Variables" di Create Tenant form
  - Section "Custom Environment Variables" di Edit Tenant form
  - Dynamic add/remove rows dengan JavaScript
  - Auto-uppercase KEY validation
  - Info box dengan contoh variables
- **Controller Logic**:
  - Method `processEnvVariables()` untuk handle form submission
  - Automatic empty key filtering
  - Support untuk Create & Update tenant
- **Documentation**:
  - `ENV_VARIABLES_DATABASE_GUIDE.md` - Comprehensive guide
  - `QUICK_START_ENV_VARIABLES.md` - Quick reference
  - `ENV_VARIABLES_SUMMARY.md` - Feature summary
  - Updated `README.md` dengan fitur baru
- **Testing**:
  - `test_env_variables.sh` - Automated test script
  - Test priority chain (Database → .env → Default)
  - Test helper function
  - Test UI integration

#### Changed
- **TenantHelpers.php**: Enhanced `tenant_env()` function untuk check database JSON first
- **TenantManagementController.php**: Added ENV variables processing di Create & Update
- **create.blade.php**: Added ENV variables section dengan dynamic form
- **edit.blade.php**: Added ENV variables section dengan dynamic form

#### Technical Details
```php
// Priority order implementation
function tenant_env($key, $default = null)
{
    // 1. Check tenant JSON database
    $tenant = app('tenant');
    if ($tenant && isset($tenant['env_variables'][$key])) {
        return $tenant['env_variables'][$key];
    }
    
    // 2. Check tenant config (from TenantMiddleware)
    $value = config('tenant.' . strtolower($key));
    if ($value !== null) {
        return $value;
    }
    
    // 3. Fallback to global .env
    return env(strtoupper($key), $default);
}
```

#### Use Cases Supported
1. **WhatsApp Integration Per Tenant**
   ```php
   env_variables: {
       "WHATSAPP_TOKEN": "EAAJf...",
       "WHATSAPP_NUMBER": "628xxx"
   }
   ```

2. **Payment Gateway Per Tenant**
   ```php
   env_variables: {
       "XENDIT_SECRET": "xnd_production_xxx",
       "XENDIT_PUBLIC": "xnd_public_xxx"
   }
   ```

3. **Custom SMTP Per Tenant**
   ```php
   env_variables: {
       "SMTP_HOST": "smtp.gmail.com",
       "SMTP_USERNAME": "noreply@tenant.com"
   }
   ```

#### Testing Results
```
✅ Test 1: Database storage - PASSED
✅ Test 2: tenant_env() function - PASSED
✅ Test 3: Helper function exists - PASSED
✅ Test 4: View integration - PASSED
✅ Test 5: Controller method - PASSED
✅ Test 6: Priority chain - PASSED
   - Database JSON: ✅ Working
   - Global .env: ✅ Working
   - Default value: ✅ Working
```

#### Sample Data
```
KC Tenant env_variables:
{
    "WHATSAPP_TOKEN": "EAAJf1234567890_test_token",
    "WHATSAPP_NUMBER": "628123456789",
    "XENDIT_SECRET": "xnd_development_secret_key",
    "XENDIT_PUBLIC": "xnd_public_test_key",
    "SMTP_USERNAME": "noreply@kencana.co.id"
}
```

#### Files Modified
1. `app/Helpers/TenantHelpers.php` - Enhanced tenant_env()
2. `app/Http/Controllers/Admin/TenantManagementController.php` - Added processEnvVariables()
3. `resources/views/tenants/create.blade.php` - Added ENV section + JS
4. `resources/views/tenants/edit.blade.php` - Added ENV section + JS
5. `README.md` - Updated with new features

#### Files Created
1. `ENV_VARIABLES_DATABASE_GUIDE.md` - Full documentation
2. `QUICK_START_ENV_VARIABLES.md` - Quick start guide
3. `ENV_VARIABLES_SUMMARY.md` - Feature summary
4. `test_env_variables.sh` - Test script
5. `CHANGELOG_ENV_VARIABLES.md` - This file

#### Security Considerations
- ✅ Plain text storage in JSON (acceptable for tenant-specific keys)
- ⚠️ Not suitable for master/root credentials (keep in .env)
- ✅ Per-tenant isolation maintained
- ✅ No additional security risks vs .env files
- 💡 Consider encryption for production if handling super sensitive data

#### Backward Compatibility
- ✅ Fully backward compatible
- ✅ Existing `env()` calls still work
- ✅ New `tenant_env()` provides enhanced functionality
- ✅ No breaking changes to existing code
- ✅ Optional feature - can be ignored if not needed

#### Performance
- ✅ Tenant data cached for 1 hour (existing mechanism)
- ✅ No additional database queries per request
- ✅ JSON parsing done by MySQL natively
- ✅ Minimal performance impact

#### Admin UI Experience
```
Login → Tenant Management → Edit/Create Tenant
└─ Scroll to "Custom Environment Variables"
   └─ Click "+ Tambah Variable"
      └─ Input KEY and VALUE
         └─ Click "Update Tenant"
            └─ Done! ✅
```

#### Developer Experience
```php
// Before (hardcoded or global .env)
$token = env('WHATSAPP_TOKEN'); // Same for all tenants

// After (per-tenant from database)
$token = tenant_env('WHATSAPP_TOKEN'); // Different per tenant
```

---

## Migration Notes

### For Existing Tenants
1. Identify tenant-specific ENV variables in `.env` file
2. Login to Admin UI
3. Edit tenant and add variables
4. Test functionality
5. Remove from `.env` if no longer needed as global

### Example Migration
```bash
# Before: .env file
KC_WHATSAPP_TOKEN=EAAJf...
KC_XENDIT_SECRET=xnd_...

# After: In database via Admin UI
KC tenant → env_variables: {
    "WHATSAPP_TOKEN": "EAAJf...",
    "XENDIT_SECRET": "xnd_..."
}

# Remove KC_* from .env
```

---

## Future Enhancements (Optional)

### Possible Improvements
- [ ] Encryption for sensitive values in JSON
- [ ] ENV variables export/import (JSON/CSV)
- [ ] Bulk edit multiple tenants
- [ ] Variable templates (preset configs)
- [ ] ENV variables history/audit log
- [ ] Validation rules per variable type
- [ ] Visual diff between tenant configs

### Not Planned (Out of Scope)
- ❌ UI for editing .env global file (use SSH/text editor)
- ❌ Auto-sync with external config services
- ❌ Real-time config reload (requires app restart/cache clear)

---

## Support & Documentation

### Quick Links
- Admin UI: https://your-domain.com/admin/login
- Full Guide: [ENV_VARIABLES_DATABASE_GUIDE.md](ENV_VARIABLES_DATABASE_GUIDE.md)
- Quick Start: [QUICK_START_ENV_VARIABLES.md](QUICK_START_ENV_VARIABLES.md)
- Summary: [ENV_VARIABLES_SUMMARY.md](ENV_VARIABLES_SUMMARY.md)

### Testing
```bash
# Run automated tests
./test_env_variables.sh

# Manual testing
php artisan tinker
$tenant = App\Tenant::where('rescode', 'KC')->first();
print_r($tenant->env_variables);
```

### Troubleshooting
1. Variable tidak terbaca → Clear cache: `php artisan config:clear`
2. UI tidak muncul → Hard refresh browser: Ctrl+F5
3. Save gagal → Check laravel.log di storage/tenants/[RESCODE]/logs/

---

## Credits

**Developed by**: ISP Management Team
**Date**: November 22, 2025
**Version**: 1.0.0
**Status**: ✅ Production Ready

---

## Summary

✨ **Custom ENV variables per tenant via database JSON**
✅ **Priority: Database → .env → Default**
🎨 **User-friendly Admin UI**
📚 **Comprehensive documentation**
🧪 **Fully tested and working**

**Ready for production use!** 🚀
