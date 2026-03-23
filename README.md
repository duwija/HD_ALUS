<p align="center"><img src="https://res.cloudinary.com/dtfbvvkyp/image/upload/v1566331377/laravel-logolockup-cmyk-red.svg" width="400"></p>

# ISP Management System - Multi-Tenant

Laravel-based ISP (Internet Service Provider) management system with **database-driven multi-tenancy**.

## 🚀 Features

### Multi-Tenant Architecture
- ✅ **Database Master System**: Central tenant management via `isp_master` database
- ✅ **Dynamic Config Override**: Runtime configuration per tenant domain
- ✅ **Separate Admin Auth**: Isolated authentication for tenant management
- ✅ **Per-Tenant Database**: Each tenant has independent database
- ✅ **Per-Tenant Storage**: Isolated file storage structure

### Environment Variables Management ⭐ NEW
- ✅ **Database JSON Storage**: Custom ENV variables per tenant
- ✅ **Priority System**: Database → .env → Default fallback
- ✅ **Admin UI**: User-friendly interface for managing variables
- ✅ **Helper Function**: `tenant_env()` for easy access
- ✅ **Use Cases**: WhatsApp, Payment Gateway, SMTP per tenant

### Core ISP Features
- Customer management with topology
- Invoice & payment tracking
- Ticketing system
- Device & OLT management
- WhatsApp integration
- Payment gateway integration (Xendit)
- Accounting & financial reports

## 📚 Documentation

### Quick Start Guides
- **[QUICK_START_TENANT_UI.md](QUICK_START_TENANT_UI.md)** - Tenant management via Admin UI
- **[QUICK_START_ENV_VARIABLES.md](QUICK_START_ENV_VARIABLES.md)** - ENV variables quick reference

### Comprehensive Guides
- **[DATABASE_TENANT_GUIDE.md](DATABASE_TENANT_GUIDE.md)** - Multi-tenant architecture
- **[ENV_VARIABLES_DATABASE_GUIDE.md](ENV_VARIABLES_DATABASE_GUIDE.md)** - ENV variables system
- **[ADMIN_AUTH_GUIDE.md](ADMIN_AUTH_GUIDE.md)** - Admin authentication
- **[TENANT_MANAGEMENT_UI_GUIDE.md](TENANT_MANAGEMENT_UI_GUIDE.md)** - Full UI guide

### Reference
- **[ADMIN_QUICK_REF.md](ADMIN_QUICK_REF.md)** - Admin commands reference
- **[ENV_VARIABLES_SUMMARY.md](ENV_VARIABLES_SUMMARY.md)** - ENV system summary

## 🔧 System Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Nginx / Apache
- Composer
- Node.js & NPM (for assets)

## 🎯 Admin Access

```
URL: https://your-domain.com/admin/login
Default Admin: admin@kencana.alus.co.id
Password: Admin123!@#
```

## 📖 Environment Variables Usage

### Basic Usage
```php
// Anywhere in code
$whatsappToken = tenant_env('WHATSAPP_TOKEN');
$xenditSecret = tenant_env('XENDIT_SECRET', 'default');
```

### Blade Templates
```blade
<p>WhatsApp: {{ tenant_env('WHATSAPP_NUMBER', '628xxx') }}</p>
```

### Priority Order
```
1. Database JSON (tenant-specific) ← FIRST
2. Global .env file (shared)       ← FALLBACK  
3. Default value                   ← LAST
```

## 🛠️ Artisan Commands

```bash
# Tenant Management
php artisan tenant:list              # List all tenants
php artisan tenant:create           # Create new tenant (interactive)
php artisan tenant:fix-permissions  # Fix storage permissions

# Admin Management
php artisan admin:create            # Create admin user (interactive)
```

## 🗄️ Database Structure

### Master Database (`isp_master`)
- `tenants` - Tenant configurations with JSON env_variables

### Admin Database (`isp_admin`)
- `admin_users` - Admin authentication

### Tenant Databases
- `adiyasa_2.2`, `kencana`, etc. - Per-tenant data

## 📁 Directory Structure

```
storage/tenants/[RESCODE]/
  ├── logs/
  │   └── laravel.log
  └── app/public/

public/tenants/[RESCODE]/
  ├── storage/
  ├── upload/
  ├── backup/
  └── users/
```

## 🔐 Security

- ✅ Separate admin authentication guard
- ✅ Per-tenant database isolation
- ✅ Encrypted passwords & tokens
- ✅ Database credentials in .env
- ⚠️ ENV variables in JSON (plain text - OK for tenant keys)

## 🧪 Testing

```bash
# Test ENV variables system
./test_env_variables.sh

# Manual testing via Tinker
php artisan tinker
```

## 📊 Tech Stack

- **Framework**: Laravel 8.83.27
- **PHP**: 8.2.29
- **Web Server**: Nginx 1.20.1
- **Database**: MySQL
- **Frontend**: Blade, Bootstrap, jQuery
- **Caching**: Laravel Cache (1 hour TTL)

## 🎨 Key Features Highlight

### 1. Dynamic Tenant Detection
Automatic tenant identification from domain with runtime config override.

### 2. Database-Driven Configuration
No more `.env` files per tenant - all managed via database.

### 3. Admin Panel
Beautiful, user-friendly interface for tenant management with auto-create features.

### 4. Custom ENV Variables
Per-tenant environment variables for APIs, integrations, and custom configs.

## 📝 License

This ISP Management System is proprietary software.

## 🤝 Support

For documentation and support, refer to the guides in the `/docs` directory or the markdown files in the root directory.
- [Runtime Converter](http://runtimeconverter.com/)
- [WebL'Agence](https://weblagence.com/)
- [Invoice Ninja](https://www.invoiceninja.com)
- [iMi digital](https://www.imi-digital.de/)
- [Earthlink](https://www.earthlink.ro/)
- [Steadfast Collective](https://steadfastcollective.com/)
- [We Are The Robots Inc.](https://watr.mx/)
- [Understand.io](https://www.understand.io/)
- [Abdel Elrafa](https://abdelelrafa.com)
- [Hyper Host](https://hyper.host)
- [Appoly](https://www.appoly.co.uk)
- [OP.GG](https://op.gg)
- [云软科技](http://www.yunruan.ltd/)

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
