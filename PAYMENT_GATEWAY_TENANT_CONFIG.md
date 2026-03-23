# Payment Gateway Tenant Configuration

## ✅ Implementasi Selesai

Sistem payment gateway sekarang **fully integrated** dengan konfigurasi per-tenant.

---

## 📋 Fitur yang Telah Diimplementasikan

### 1. **Tenant-Specific Configuration**
Setiap tenant dapat memiliki konfigurasi payment gateway sendiri yang tersimpan di database `isp_master.tenants.env_variables`.

### 2. **Payment Method Toggle**
3 Payment gateway yang dapat di-enable/disable per tenant:
- **Bumdes/Payment Point** - Pembayaran via loket fisik
- **Winpay** - Multi-bank Virtual Account (Fixed fee Rp 2.500)
- **Tripay** - VA, E-Wallet, QRIS (Variable fee)

### 3. **Dynamic Invoice Page**
Halaman invoice (`/payment/{tempcode}/print`) sekarang menampilkan **hanya payment method yang enabled** untuk tenant tersebut.

---

## 🔧 Cara Konfigurasi

### **A. Via Admin Panel**

1. Login ke admin panel: `https://domain.com/admin/login`

2. Navigate: **Tenants → Edit Tenant → Custom Environment Variables**

3. Tambahkan variables berikut sesuai kebutuhan:

#### **Untuk Tripay:**
```
Key: TRIPAY_ENDPOINT
Value: https://tripay.co.id/api/transaction/create

Key: TRIPAY_APIKEY
Value: your_tripay_api_key_here

Key: TRIPAY_PRIVATEKEY
Value: your_tripay_private_key

Key: TRIPAY_MERCHANTCODE
Value: T12345
```

#### **Untuk Winpay:**
```
Key: WINPAY_ENDPOINT
Value: https://api.winpay.id

Key: WINPAY_KEY
Value: your_winpay_key_here

Key: WINPAY_SECRET
Value: your_winpay_secret_here
```

4. **Enable/Disable Gateway:**
   - Navigate: **Tenants → View Tenant → Payment Gateway Config**
   - Toggle switches untuk enable/disable masing-masing gateway
   - Save configuration

---

### **B. Via Database (Manual)**

```sql
-- Update env_variables untuk tenant tertentu
UPDATE isp_master.tenants 
SET env_variables = JSON_SET(
    COALESCE(env_variables, '{}'),
    '$.TRIPAY_APIKEY', 'your_key_here',
    '$.TRIPAY_PRIVATEKEY', 'your_private_key',
    '$.WINPAY_KEY', 'your_winpay_key',
    '$.WINPAY_SECRET', 'your_winpay_secret'
)
WHERE domain = 'tenant.domain.com';

-- Enable/Disable payment gateways
UPDATE isp_master.tenants 
SET 
    payment_bumdes_enabled = 1,  -- 1 = enabled, 0 = disabled
    payment_winpay_enabled = 1,
    payment_tripay_enabled = 1
WHERE domain = 'tenant.domain.com';
```

---

## 🎯 Priority System

Sistem menggunakan cascading priority untuk mendapatkan configuration:

```
1. Tenant env_variables (Database) - Highest priority
   ↓
2. Global .env file
   ↓
3. Default hardcoded value - Lowest priority
```

### Contoh:
```php
// Di code akan otomatis fallback
$key = tenant_config('TRIPAY_APIKEY', env('TRIPAY_APIKEY'));

// Jika TRIPAY_APIKEY ada di tenant env_variables → dipakai
// Jika tidak → fallback ke .env global
```

---

## 📁 File yang Telah Dimodifikasi

### 1. **`.env`** - Global fallback configuration
```env
TRIPAY_ENDPOINT="https://tripay.co.id/api/transaction/create"
TRIPAY_APIKEY="jmOaw7Wutq7PlzBAmLpr876qQQsKAgSV0PI9tnNK"
TRIPAY_PRIVATEKEY="kSpIJ-srPTZ-qX3Qv-4iS4M-N1EBS"
TRIPAY_MERCHANTCODE="T34487"

WINPAY_ENDPOINT="https://api.winpay.id"
WINPAY_KEY=""
WINPAY_SECRET=""
```

### 2. **`app/Http/Controllers/SuminvoiceController.php`**
- `createWinpayVA()` - Gunakan `tenant_config()` untuk WINPAY credentials
- `deleteWinpayVA()` - Gunakan `tenant_config()` untuk WINPAY credentials
- `findWinpayVA()` - Gunakan `tenant_config()` untuk WINPAY credentials

### 3. **`app/Http/Controllers/PaymentController.php`**
- Sudah menggunakan `tenant_config()` untuk TRIPAY credentials (baris 281-284)

### 4. **`resources/views/payment/print.blade.php`**
- Menampilkan payment buttons berdasarkan tenant configuration
- Check `payment_bumdes_enabled`, `payment_winpay_enabled`, `payment_tripay_enabled`
- Modal Tripay dan Bumdes hanya muncul jika enabled

---

## 🧪 Testing

### **Test 1: Cek Konfigurasi Tenant**
```sql
SELECT 
    domain,
    JSON_PRETTY(env_variables) as env_vars,
    payment_bumdes_enabled,
    payment_winpay_enabled,
    payment_tripay_enabled
FROM isp_master.tenants
WHERE domain = 'your.domain.com';
```

### **Test 2: Test Payment Button Display**
1. Buka halaman invoice: `https://domain.com/payment/{tempcode}`
2. Pastikan hanya payment method yang enabled yang muncul
3. Klik payment button dan pastikan credentials yang benar terkirim

### **Test 3: Test Winpay Integration**
1. Set credentials Winpay di tenant env_variables
2. Klik button "Winpay" di invoice page
3. Check log: `tail -f storage/logs/laravel.log`
4. Pastikan tidak ada error "WINPAY_KEY not found"

### **Test 4: Test Tripay Integration**
1. Set credentials Tripay di tenant env_variables
2. Klik button "Tripay" di invoice page
3. Pilih payment method (BCA VA, QRIS, etc)
4. Verify transaction created dengan credentials yang benar

---

## 🔒 Security Notes

1. **Credentials di Database:**
   - Tersimpan di `isp_master` database (terpisah dari tenant database)
   - Hanya admin panel yang bisa akses
   - Tidak perlu enkripsi karena sudah protected by database access

2. **API Keys Validation:**
   - Setiap payment gateway akan reject invalid credentials
   - Error logging tersedia di `storage/logs/laravel.log`

3. **Production Recommendations:**
   - Gunakan tenant-specific credentials untuk setiap tenant
   - Jangan share credentials antar tenant
   - Monitor transaction logs regular

---

## 📞 Troubleshooting

### **Payment button tidak muncul**
**Solusi:** 
- Check `payment_*_enabled` di database
- Pastikan status invoice = UNPAID (`payment_status = 0`)
- Pastikan `current_inv_status = 0`

### **Error "WINPAY_KEY not found"**
**Solusi:**
- Set WINPAY_KEY di `.env` global atau
- Set WINPAY_KEY di tenant env_variables

### **Tripay transaction gagal**
**Solusi:**
- Verify TRIPAY_APIKEY, TRIPAY_PRIVATEKEY correct
- Check Tripay dashboard untuk status merchant
- Check `storage/logs/laravel.log` untuk error details

### **Modal tidak muncul**
**Solusi:**
- Pastikan Bootstrap JS loaded
- Check browser console untuk JavaScript errors
- Pastify jQuery version compatible

---

## 🎉 Summary

✅ **Setiap tenant bisa punya payment gateway credentials sendiri**  
✅ **Payment method bisa di-enable/disable per tenant via admin panel**  
✅ **Invoice page dinamis menampilkan hanya method yang enabled**  
✅ **Fallback ke global .env jika tenant tidak set credentials**  
✅ **Fully backward compatible dengan setup existing**

---

**Last Updated:** January 23, 2026  
**Backup Location:** `/root/backup_kencana_20260123_014155.tar.gz`
