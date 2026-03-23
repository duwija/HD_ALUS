# Admin Authentication System - Tenant Management

## Overview
Sistem autentikasi terpisah untuk tenant management menggunakan database khusus (`isp_admin`) dengan guard Laravel terpisah dari tenant users.

## Arsitektur

### Database Separation
- **isp_admin**: Database khusus untuk admin tenant management
- **isp_master**: Database untuk konfigurasi tenant
- **[tenant_db]**: Database per tenant untuk user tenant

### Guards & Providers
```php
'guards' => [
    'web' => [                      // Guard untuk tenant users
        'provider' => 'users',
    ],
    'admin' => [                    // Guard untuk admin tenant management
        'provider' => 'admin_users',
    ],
],

'providers' => [
    'users' => [
        'model' => App\User::class,
    ],
    'admin_users' => [
        'model' => App\AdminUser::class,
    ],
],
```

## File Structure

### Models
- `app/AdminUser.php` - Model untuk admin users (connection: admin)

### Controllers
- `app/Http/Controllers/Admin/AdminAuthController.php` - Login/logout admin
- `app/Http/Controllers/Admin/TenantManagementController.php` - CRUD tenant

### Migrations
- `database/migrations/admin/2025_11_16_154039_create_admin_users_table.php`

### Views
- `resources/views/admin/auth/login.blade.php` - Halaman login admin
- `resources/views/tenants/index.blade.php` - List tenant
- `resources/views/tenants/create.blade.php` - Form tambah tenant
- `resources/views/tenants/edit.blade.php` - Form edit tenant
- `resources/views/tenants/show.blade.php` - Detail tenant

### Routes
```php
// Public admin routes
GET  /admin/login       - Show login form
POST /admin/login       - Process login
POST /admin/logout      - Logout

// Protected admin routes (auth:admin middleware)
GET    /admin/tenants            - List tenants
GET    /admin/tenants/create     - Show create form
POST   /admin/tenants            - Store new tenant
GET    /admin/tenants/{id}       - Show tenant details
GET    /admin/tenants/{id}/edit  - Show edit form
PUT    /admin/tenants/{id}       - Update tenant
DELETE /admin/tenants/{id}       - Delete tenant
POST   /admin/tenants/{id}/toggle - Toggle active status
```

## Configuration

### Environment Variables (.env)
```env
# Admin Database
ADMIN_DB_HOST=127.0.0.1
ADMIN_DB_PORT=3306
ADMIN_DB_DATABASE=isp_admin
ADMIN_DB_USERNAME=root
ADMIN_DB_PASSWORD=your_password
```

### Database Connection (config/database.php)
```php
'admin' => [
    'driver' => 'mysql',
    'host' => env('ADMIN_DB_HOST', '127.0.0.1'),
    'port' => env('ADMIN_DB_PORT', '3306'),
    'database' => env('ADMIN_DB_DATABASE', 'isp_admin'),
    'username' => env('ADMIN_DB_USERNAME', 'root'),
    'password' => env('ADMIN_DB_PASSWORD', ''),
    // ... other config
],
```

## Admin Users Table Schema

```sql
CREATE TABLE admin_users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    remember_token VARCHAR(100),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

## Usage

### 1. Create Admin Database (One-time setup)
```bash
mysql -u root -p
CREATE DATABASE isp_admin;
exit
```

### 2. Run Admin Migration
```bash
php artisan migrate --database=admin --path=database/migrations/admin
```

### 3. Create Admin User
```bash
php artisan admin:create
```

Interactive prompt:
```
Name: Super Admin
Email: admin@example.com
Password: ********
Confirm Password: ********
```

### 4. Login to Admin Panel
```
URL: https://yourdomain.com/admin/login
Email: admin@example.com
Password: ********
```

## Artisan Commands

### admin:create
Membuat admin user baru untuk tenant management.

```bash
php artisan admin:create
```

**Interactive prompts:**
- Name
- Email (unique)
- Password (min 8 characters)
- Confirm Password

**Validation:**
- Name: required, max 255
- Email: required, valid email, unique in admin_users table
- Password: required, min 8 characters, must match confirmation

**Output:**
```
Admin user created successfully!

+----+-------------+----------------------+--------+
| ID | Name        | Email                | Status |
+----+-------------+----------------------+--------+
| 1  | Super Admin | admin@kencana.alus.co.id | Active |
+----+-------------+----------------------+--------+

You can now login at: https://kencana.alus.co.id/admin/login
```

## Security Features

### 1. Separate Database
Admin users disimpan di database terpisah (`isp_admin`), tidak bisa diakses oleh tenant.

### 2. Guard Isolation
```php
// Admin guard
Auth::guard('admin')->attempt($credentials)
Auth::guard('admin')->check()
Auth::guard('admin')->logout()

// Tenant guard
Auth::guard('web')->attempt($credentials)
Auth::guard('web')->check()
```

### 3. Middleware Protection
```php
// Hanya admin yang bisa akses
Route::middleware(['auth:admin'])->group(function() {
    // Tenant management routes
});
```

### 4. Password Hashing
Password di-hash dengan bcrypt otomatis via Laravel Authenticatable.

### 5. CSRF Protection
Semua form dilindungi CSRF token.

### 6. Remember Me Token
Opsi "Remember Me" untuk persistent login.

## Access Control

### Admin Panel Access
✅ **BISA AKSES:**
- Admin users yang terdaftar di `isp_admin.admin_users`
- Login via `/admin/login`

❌ **TIDAK BISA AKSES:**
- Tenant users (user di database tenant)
- User tanpa autentikasi
- Inactive admin users (is_active = 0)

### Tenant Application Access
✅ **BISA AKSES:**
- Tenant users di database masing-masing tenant
- Login via route tenant normal

❌ **TIDAK BISA AKSES:**
- Admin users (ada di database terpisah)

## Login Flow

### Admin Login
```
1. User akses /admin/login
2. Input email & password
3. Sistem check di admin_users table (database: isp_admin)
4. Jika valid: redirect ke /admin/tenants
5. Jika invalid: kembali ke login dengan error
```

### Tenant Login
```
1. User akses domain tenant (contoh: kencana.alus.co.id)
2. TenantMiddleware switch database ke tenant database
3. Input email & password
4. Sistem check di users table (database: isp_[tenant])
5. Jika valid: redirect ke dashboard tenant
6. Jika invalid: kembali ke login dengan error
```

## Checking Authentication

### In Blade Views
```blade
@auth('admin')
    <!-- Hanya tampil untuk admin yang login -->
    <p>Welcome, {{ Auth::guard('admin')->user()->name }}</p>
@endauth

@guest('admin')
    <!-- Tampil untuk yang belum login sebagai admin -->
    <a href="{{ route('admin.login') }}">Login</a>
@endguest
```

### In Controllers
```php
use Illuminate\Support\Facades\Auth;

// Check if admin logged in
if (Auth::guard('admin')->check()) {
    $admin = Auth::guard('admin')->user();
    $name = $admin->name;
    $email = $admin->email;
}

// Get current admin
$admin = Auth::guard('admin')->user();
```

### In Middleware
```php
// Protect routes
Route::middleware(['auth:admin'])->group(function() {
    // Only accessible by logged-in admin
});
```

## Managing Admin Users

### Create New Admin
```bash
php artisan admin:create
```

### Deactivate Admin (SQL)
```sql
USE isp_admin;
UPDATE admin_users SET is_active = 0 WHERE email = 'admin@example.com';
```

### Activate Admin (SQL)
```sql
USE isp_admin;
UPDATE admin_users SET is_active = 1 WHERE email = 'admin@example.com';
```

### List All Admins (SQL)
```sql
USE isp_admin;
SELECT id, name, email, is_active, created_at FROM admin_users;
```

### Reset Admin Password (Tinker)
```bash
php artisan tinker

$admin = App\AdminUser::where('email', 'admin@example.com')->first();
$admin->password = bcrypt('new_password');
$admin->save();
```

## Troubleshooting

### Cannot access /admin/tenants
**Symptom:** Redirect ke /admin/login

**Solution:**
1. Login terlebih dahulu via `/admin/login`
2. Pastikan admin user ada dan active
3. Check session configuration

### Wrong credentials
**Symptom:** "Email atau password salah"

**Solution:**
1. Pastikan email benar (case sensitive)
2. Pastikan password benar
3. Check admin user ada di database:
```sql
SELECT * FROM isp_admin.admin_users WHERE email = 'your@email.com';
```

### Admin user not found
**Symptom:** User tidak bisa login meskipun data ada

**Solution:**
1. Check is_active = 1
2. Clear cache: `php artisan cache:clear`
3. Check guard configuration di config/auth.php

### Session issues
**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan session:clear
```

## Differences: Admin vs Tenant Users

| Aspect | Admin Users | Tenant Users |
|--------|-------------|--------------|
| Database | isp_admin | [tenant_db] per tenant |
| Table | admin_users | users |
| Guard | admin | web |
| Login URL | /admin/login | /login (per tenant) |
| Model | App\AdminUser | App\User |
| Access | Tenant Management | Tenant Application |
| Middleware | auth:admin | auth:web |

## Best Practices

### 1. Limited Admin Access
Hanya buat admin user untuk orang yang benar-benar perlu akses tenant management.

### 2. Strong Passwords
Enforce minimal 8 karakter dengan kombinasi huruf, angka, simbol.

### 3. Audit Log
Pertimbangkan menambah log untuk track aktivitas admin (create/edit/delete tenant).

### 4. Backup Admin Database
```bash
mysqldump -u root -p isp_admin > admin_backup_$(date +%Y%m%d).sql
```

### 5. Regular Password Change
Ganti password admin secara berkala (3-6 bulan).

## Related Documentation
- `DATABASE_TENANT_GUIDE.md` - Panduan database multi-tenant
- `TENANT_MANAGEMENT_UI_GUIDE.md` - Panduan UI tenant management
- `QUICK_START_TENANT_UI.md` - Quick start guide

---

**Created:** 2025-11-16  
**Version:** 1.0  
**Author:** System Administrator
