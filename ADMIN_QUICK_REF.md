# Quick Reference: Admin Authentication

## Login Credentials

### Default Admin
```
URL: https://kencana.alus.co.id/admin/login
Email: admin@kencana.alus.co.id
Password: Admin123!@#
```

## Common Commands

### Create New Admin
```bash
php artisan admin:create
```

### List Admins
```bash
mysql -u root -p isp_admin -e "SELECT id, name, email, is_active FROM admin_users;"
```

### Reset Password (Tinker)
```bash
php artisan tinker
$admin = App\AdminUser::find(1);
$admin->password = bcrypt('new_password');
$admin->save();
```

### Deactivate Admin
```sql
USE isp_admin;
UPDATE admin_users SET is_active = 0 WHERE email = 'admin@example.com';
```

## URLs

| Page | URL | Access |
|------|-----|--------|
| Admin Login | /admin/login | Public |
| Tenant List | /admin/tenants | Admin only |
| Create Tenant | /admin/tenants/create | Admin only |
| Edit Tenant | /admin/tenants/{id}/edit | Admin only |
| Tenant Detail | /admin/tenants/{id} | Admin only |

## Database Info

| Item | Value |
|------|-------|
| Database | isp_admin |
| Table | admin_users |
| Guard | admin |
| Model | App\AdminUser |

## Key Points

✅ Admin login TERPISAH dari tenant users  
✅ Database khusus: `isp_admin`  
✅ Guard khusus: `admin`  
✅ Tidak bisa akses aplikasi tenant  
✅ Hanya untuk manage tenant  

❌ Tenant users TIDAK bisa akses /admin/tenants  
❌ Admin users TIDAK bisa login ke tenant app  
❌ Database TIDAK shared  

## Troubleshooting

### Cannot login
```bash
# Check admin exists
mysql -u root -p isp_admin -e "SELECT * FROM admin_users WHERE email='your@email.com';"

# Check password (reset if needed)
php artisan tinker
$admin = App\AdminUser::where('email', 'your@email.com')->first();
$admin->password = bcrypt('new_password');
$admin->save();
```

### 302 Redirect loop
```bash
php artisan config:clear
php artisan cache:clear
php artisan session:clear
```

### 404 on /admin/tenants
```bash
php artisan route:clear
php artisan route:cache
```

---
**Quick Access:** `ADMIN_AUTH_GUIDE.md` untuk dokumentasi lengkap
