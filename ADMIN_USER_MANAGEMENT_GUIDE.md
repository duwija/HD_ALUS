# Admin User Management - Quick Guide

## 🎯 Overview

Halaman manajemen untuk mengelola user admin yang memiliki akses ke Admin Panel (Tenant Management).

## 🔐 Access

```
URL: https://kencana.alus.co.id/admin/users
Login: admin@kencana.alus.co.id
Password: Admin123!@#
```

## ✨ Features

### 1. View All Admin Users
- List semua admin users dengan DataTables
- Filter dan search
- Status (Active/Inactive)
- Last login information
- Sortable columns

### 2. Create Admin User
**Path:** Admin Panel → Admin Users → Tambah Admin User

**Required Fields:**
- Name (required)
- Email (required, unique)
- Password (required, min 8 characters)
- Password Confirmation (required)
- Status (active/inactive checkbox)

**Features:**
- Email uniqueness validation
- Password confirmation matching
- Password strength requirement (min 8 char)
- Default active status

### 3. Edit Admin User
**Path:** Admin Panel → Admin Users → Edit (icon pensil)

**Can Update:**
- Name
- Email (with uniqueness check)
- Password (optional - leave blank to keep current)
- Status (active/inactive)

**Restrictions:**
- Cannot disable your own account
- Cannot delete your own account

**Information Shown:**
- Created date
- Last login date
- Current status

### 4. Delete Admin User
**Path:** Admin Panel → Admin Users → Delete (icon trash)

**Features:**
- Confirmation dialog
- Cannot delete your own account
- Permanent deletion (no soft delete)

### 5. Toggle Status
**Path:** Admin Panel → Admin Users → Toggle icon (ban/check)

**Features:**
- Quick activate/deactivate
- Cannot deactivate your own account
- Visual feedback (badge changes)

## 🎨 UI Features

### DataTables Integration
- Search all fields
- Sort by any column
- Pagination (25 per page)
- Responsive design
- Indonesian language

### Visual Indicators
- **Active Badge** (Green): User can login
- **Inactive Badge** (Red): User cannot login
- **"You" Badge** (Blue): Current logged-in user
- Icons for all actions

### Action Buttons
- 🖊️ **Edit** (Yellow): Modify user details
- 🚫/✓ **Toggle** (Gray/Green): Change status
- 🗑️ **Delete** (Red): Remove user

## 🔒 Security Features

### Self-Protection
```php
// Cannot delete yourself
if ($admin->id === auth('admin')->id()) {
    return 'Tidak dapat menghapus akun sendiri!';
}

// Cannot disable yourself
if ($admin->id === auth('admin')->id()) {
    return 'Tidak dapat menonaktifkan akun sendiri!';
}
```

### Password Security
- Bcrypt hashing automatically
- Minimum 8 characters required
- Password confirmation matching
- Never shown in plain text

### Email Validation
- Must be valid email format
- Must be unique across all admin users
- Cannot duplicate existing emails

## 📋 Routes

```php
// View all admin users
GET  /admin/users                    → admin.users.index

// Create form
GET  /admin/users/create             → admin.users.create

// Store new admin
POST /admin/users                    → admin.users.store

// Edit form
GET  /admin/users/{id}/edit          → admin.users.edit

// Update admin
PUT  /admin/users/{id}               → admin.users.update

// Delete admin
DELETE /admin/users/{id}             → admin.users.destroy

// Toggle status
POST /admin/users/{id}/toggle        → admin.users.toggle-status
```

## 🎯 Use Cases

### 1. Add New Admin User
```
1. Login ke Admin Panel
2. Klik "Admin Users" di sidebar
3. Klik "Tambah Admin User"
4. Isi form:
   - Name: John Doe
   - Email: john@company.com
   - Password: SecurePass123!
   - Confirm Password: SecurePass123!
   - Status: Active (checked)
5. Klik "Simpan Admin User"
```

### 2. Change Admin Password
```
1. Go to Admin Users
2. Click Edit on the user
3. Scroll to "Change Password"
4. Enter new password
5. Confirm new password
6. Click "Update Admin User"
```

### 3. Disable Admin Access
```
1. Go to Admin Users
2. Click Toggle icon (ban)
3. Confirm action
4. User status changes to "Inactive"
5. User cannot login anymore
```

### 4. Restore Admin Access
```
1. Go to Admin Users
2. Click Toggle icon (check) on inactive user
3. Confirm action
4. User status changes to "Active"
5. User can login again
```

## 💡 Tips

### Best Practices
1. **Strong Passwords**: Use combination of upper, lower, numbers, symbols
2. **Unique Emails**: One email per admin user
3. **Regular Cleanup**: Remove inactive/unused admin accounts
4. **Monitor Last Login**: Check for suspicious activity

### Security Recommendations
1. Change default admin password immediately
2. Create individual accounts (don't share)
3. Disable accounts instead of sharing credentials
4. Use company email addresses
5. Regular password rotation

## 🔍 Troubleshooting

### Cannot Create Admin
**Problem:** Email already exists
**Solution:** Use different email or update existing user

**Problem:** Password too short
**Solution:** Use minimum 8 characters

**Problem:** Password confirmation doesn't match
**Solution:** Retype both password fields correctly

### Cannot Delete Admin
**Problem:** "Tidak dapat menghapus akun sendiri"
**Solution:** Login with different admin account to delete this one

### Cannot Login After Creation
**Problem:** Account inactive
**Solution:** Edit admin and check "Active" status

## 📊 Database Schema

```sql
CREATE TABLE `admin_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 🎨 Navigation

```
Admin Panel (Top Bar)
├── Super Admin (Dropdown)
│   ├── My Profile → Edit your own account
│   └── Logout → Sign out
│
└── Sidebar
    ├── Tenant Management → Manage tenants
    └── Admin Users → Manage admin users ⬅️ YOU ARE HERE
```

## ✅ Current Admin

**Default Account:**
```
Name: Super Admin
Email: admin@kencana.alus.co.id
Password: Admin123!@#
Status: Active
Created: 16 Nov 2025
```

## 🚀 Quick Actions

**Add New Admin:**
```bash
# Via Tinker
php artisan tinker
```
```php
use App\AdminUser;
use Illuminate\Support\Facades\Hash;

AdminUser::create([
    'name' => 'New Admin',
    'email' => 'newadmin@company.com',
    'password' => Hash::make('password123'),
    'is_active' => true
]);
```

**Or use the command:**
```bash
php artisan admin:create
```

## 📝 Related Documentation

- **[ADMIN_AUTH_GUIDE.md](ADMIN_AUTH_GUIDE.md)** - Admin authentication system
- **[ADMIN_QUICK_REF.md](ADMIN_QUICK_REF.md)** - Admin commands reference
- **[TENANT_MANAGEMENT_UI_GUIDE.md](TENANT_MANAGEMENT_UI_GUIDE.md)** - Tenant management

---

**Created:** November 22, 2025
**Version:** 1.0.0
**Status:** ✅ Production Ready
