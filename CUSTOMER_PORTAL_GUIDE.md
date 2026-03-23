# Customer Portal - Panduan Implementasi

## Overview
Portal pelanggan untuk mengakses tagihan dengan sistem login menggunakan email dan password.

## URL Akses
- **Login**: `https://kencana.alus.co.id/tagihan/login`
- **Aktivasi**: `https://kencana.alus.co.id/tagihan/activate`
- **Pilih Customer**: `https://kencana.alus.co.id/tagihan/select-customer` (otomatis jika >1 customer)
- **Lihat Invoice**: `https://kencana.alus.co.id/invoice/cst/{encrypted_id}` (redirect otomatis)

## Fitur Utama

### 1. Login dengan Email
- Customer login menggunakan **email + password portal**
- Sistem mencari SEMUA customer dengan email yang sama
- Jika 1 customer → redirect langsung ke invoice terenkripsi
- Jika >1 customer → tampilkan halaman pilih customer

### 2. Multi-Customer per Email (Family Account)
Satu email bisa digunakan untuk beberapa customer (ayah, ibu, anak) dengan invoice terpisah.

### 3. Aktivasi Akun
Customer baru dapat aktivasi dengan:
- Email terdaftar
- Nomor telepon terdaftar  
- Buat password baru

## Database Schema

### Kolom Baru di `customers` table:
```sql
portal_password  VARCHAR(225) NULL  -- Password untuk login portal (terpisah dari password PPPoE)
remember_token   VARCHAR(100) NULL  -- Token untuk "Remember Me"
last_login_at    TIMESTAMP NULL     -- Tracking login terakhir
```

### Kolom Existing (tidak diubah):
```sql
password         VARCHAR(225) NULL  -- Password PPPoE (untuk MikroTik)
email            VARCHAR(255)       -- Email customer
phone            VARCHAR(191)       -- Nomor telepon
```

## Authentication Flow

```
1. Customer buka /tagihan/login
   ↓
2. Input email + password portal
   ↓
3. Sistem query: SELECT * FROM customers WHERE email = ?
   ↓
4. Validasi portal_password dengan Hash::check()
   ↓
5a. Jika 1 customer:
    → Auth::guard('customer')->login($customer)
    → Redirect ke /invoice/cst/{Crypt::encryptString($id)}
    
5b. Jika >1 customer:
    → Auth::guard('customer')->login($firstCustomer)
    → Redirect ke /tagihan/select-customer
    → Tampilkan list semua customer dengan email ini
    → User klik salah satu
    → Redirect ke /invoice/cst/{Crypt::encryptString($selectedId)}
```

## File Structure

### Controllers
- `app/Http/Controllers/CustomerAuthController.php` - Handle login, logout, aktivasi, selection

### Models  
- `app/Customer.php` - Extends Authenticatable dengan `getAuthPassword()` override

### Views
- `resources/views/tagihan/login.blade.php` - Form login
- `resources/views/tagihan/select-customer.blade.php` - Pilih customer (multi account)
- `resources/views/tagihan/activate.blade.php` - Form aktivasi pertama kali

### Routes (`routes/web.php`)
```php
Route::prefix('tagihan')->group(function() {
    // Public
    Route::get('/login', [CustomerAuthController::class, 'showLogin']);
    Route::post('/login', [CustomerAuthController::class, 'login']);
    Route::get('/activate', [CustomerAuthController::class, 'showActivate']);
    Route::post('/activate', [CustomerAuthController::class, 'activate']);
    
    // Protected (requires auth:customer)
    Route::middleware('auth:customer')->group(function() {
        Route::get('/select-customer', [CustomerAuthController::class, 'selectCustomer']);
        Route::get('/view-invoice/{customerId}', [CustomerAuthController::class, 'viewInvoice']);
        Route::get('/logout', [CustomerAuthController::class, 'logout']);
    });
});
```

### Config
- `config/auth.php` - Guard 'customer' dan provider 'customers'

### Migration
- `database/migrations/2026_01_27_000001_add_auth_fields_to_customers_table.php`

## Security Features

1. **Password Hashing**: Menggunakan `Hash::make()` dan `Hash::check()`
2. **Encryption**: Customer ID di URL menggunakan `Crypt::encryptString()`
3. **Guard Separation**: Customer guard terpisah dari admin guard
4. **Hidden Fields**: `portal_password` dan `remember_token` hidden dari API/JSON response
5. **Authentication Middleware**: Route protected dengan `auth:customer`

## Aktivasi Customer Baru

### Opsi 1: Self-Activation (via web)
1. Customer buka `/tagihan/activate`
2. Input: email + phone + password baru
3. Sistem validasi email+phone di database
4. Update `portal_password` untuk semua customer dengan email tersebut

### Opsi 2: Admin Set Password
Admin bisa langsung set password customer via query:
```sql
UPDATE customers 
SET portal_password = '$2y$10$...' -- hasil Hash::make('password123')
WHERE email = 'customer@email.com';
```

### Opsi 3: Bulk Generate (coming soon)
Script untuk generate password default untuk semua customer dan kirim via email/WA.

## Testing

### Test Login Flow
```bash
# 1. Buka browser ke:
https://kencana.alus.co.id/tagihan/login

# 2. Aktivasi dulu jika belum punya password:
https://kencana.alus.co.id/tagihan/activate
Email: customer@test.com
Phone: 081234567890
Password: test123456
Confirm: test123456

# 3. Login:
Email: customer@test.com  
Password: test123456

# 4. Jika 1 customer → auto redirect ke invoice
# 5. Jika >1 customer → pilih dari list → redirect ke invoice
```

### Manual Password Set via Tinker
```bash
php artisan tinker

$customer = App\Customer::where('email', 'test@example.com')->first();
$customer->portal_password = Hash::make('password123');
$customer->save();
```

## Troubleshooting

### Error: "Class 'Hash' not found"
Tambahkan di controller: `use Illuminate\Support\Facades\Hash;`

### Error: "Class 'Auth' not found"  
Tambahkan di controller: `use Illuminate\Support\Facades\Auth;`

### Error: "Class 'Crypt' not found"
Tambahkan di controller: `use Illuminate\Support\Facades\Crypt;`

### Customer tidak bisa login
- Cek apakah `portal_password` sudah terisi (bukan NULL)
- Cek apakah password di-hash dengan benar
- Cek guard di `config/auth.php` sudah benar

### Redirect loop
- Pastikan middleware `auth:customer` hanya di route yang protected
- Login dan activate harus public (tanpa middleware)

## Future Enhancements

1. **Password Reset**: Forgot password dengan email verification
2. **Profile Edit**: Customer bisa update data sendiri
3. **Invoice History**: List semua invoice dengan filter bulan/tahun
4. **Payment Gateway**: Bayar invoice langsung dari portal
5. **Notification**: Email/WA notifikasi saat invoice baru
6. **Download PDF**: Export invoice ke PDF

## Notes

- **Perbedaan Password**: 
  - `password` → untuk PPPoE MikroTik
  - `portal_password` → untuk login customer portal
  
- **Email Default**: Jika customer tidak punya email, default `return@alus.co.id` (perlu diubah untuk aktivasi)

- **Multi-tenant Safe**: Semua query customer aware dengan tenant isolation

- **Session Guard**: Menggunakan session-based auth, bukan token

## Support

Jika ada masalah:
1. Cek log Laravel: `storage/logs/laravel.log`
2. Cek error browser: Console → Network tab
3. Cek database: Pastikan kolom ada dan terisi
4. Test route: `php artisan route:list | grep tagihan`

---
Dibuat: 27 Januari 2026
Update terakhir: 27 Januari 2026
