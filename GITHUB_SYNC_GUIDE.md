# 🔄 GitHub Sync Admin Panel Guide

Panduan lengkap untuk menggunakan fitur GitHub Sync di Admin Panel ISP Multi-Tenant.

---

## 📑 Daftar Isi

1. [Overview](#overview)
2. [Akses Menu GitHub Sync](#akses-menu-github-sync)
3. [Status Display](#status-display)
4. [Pull dari GitHub](#pull-dari-github)
5. [Push ke GitHub](#push-ke-github)
6. [Refresh Status](#refresh-status)
7. [Melihat Perubahan File](#melihat-perubahan-file)
8. [Troubleshooting](#troubleshooting)
9. [Best Practices](#best-practices)

---

## 🎯 Overview

**GitHub Sync** adalah fitur admin panel untuk mengelola sinkronisasi kode aplikasi dengan repository GitHub secara langsung. Fitur ini memungkinkan admin untuk:

✅ Melihat status Git terkini (branch, commit terakhir, file yang berubah)  
✅ Pull kode terbaru dari GitHub  
✅ Push perubahan lokal ke GitHub (commit + push)  
✅ Melihat daftar file yang berubah  
✅ Refresh status kapan saja  

### Use Cases:
- **Development**: Push kode fitur baru ke GitHub
- **Deployment**: Pull update terbaru sebelum deploy ke production
- **Monitoring**: Cek status kode dan perubahan yang belum di-commit
- **Hot Fix**: Push emergency fix langsung dari admin panel

---

## 🌐 Akses Menu GitHub Sync

### Cara Akses:
1. Login ke **Admin Panel** (URL: `/admin`)
2. Di sidebar menu, cari **GitHub Sync** (icon: `<i class="fas fa-code-branch"></i>`)
3. Klik untuk membuka halaman GitHub Sync

### URL Langsung:
```
/admin/github-sync
```

---

## 📊 Status Display

### Informasi yang Ditampilkan:

#### 1. Current Branch
- Branch Git yang saat ini aktif
- Biasanya `main` atau `master`

#### 2. Changed Files
- Jumlah file yang memiliki perubahan lokal
- Jumlah ini akan berkurang setelah push ke GitHub

#### 3. Remote
- Informasi remote repository
- Format: `https://github.com/duwija/HD_ALUS`

#### 4. Last Commit
- Commit terakhir yang ada di repository
- Format: `hash commit_message`

#### 5. Local Changes Table
**Jika ada file yang berubah:**
- Status: Modified (M), Added (A), Deleted (D)
- Nama file
- Path lengkap

---

## ⬇️ Pull dari GitHub

### Keperluan:
Ambil kode terbaru dari GitHub ke server lokal.

### Cara Menggunakan:

1. Buka halaman GitHub Sync
2. Klik tombol **Pull from GitHub** (icon: download)
3. Sistem akan:
   - Menjalankan `git pull origin main`
   - Menampilkan result dialog

### Dialog Result:
```
✅ Success:
   Successfully pulled from GitHub
   [Output dari git pull]

❌ Failed:
   Error message akan ditampilkan
   [Troubleshoot menggunakan pesan error]
```

### Kapan Digunakan:
- Sebelum deploy ke production
- Setelah ada push dari developer lain
- Untuk mengambil update hotfix

### ⚠️ Perhatian:
- Jika ada local changes yang uncommitted, pull mungkin gagal
- Gunakan Push terlebih dahulu sebelum Pull jika ada perubahan

---

## ⬆️ Push ke GitHub

### Keperluan:
Kirim perubahan lokal ke GitHub.

### Cara Menggunakan:

1. Buka halaman GitHub Sync
2. Klik tombol **Push to GitHub** (icon: upload)
3. Modal akan muncul meminta **Commit Message**
4. Isi deskripsi perubahan (5-200 karakter)
5. Klik **Push** untuk melanjutkan

### Contoh Commit Message:
```
feat: add new payment gateway integration
fix: resolve invoice calculation bug
chore: update dependencies
docs: add deployment guide
refactor: simplify customer model
```

### Proses:
1. `git add .` → Semua file lokal ditambahkan
2. `git commit -m "your message"` → Commit dibuat
3. `git push origin main` → Push ke GitHub

### Dialog Result:
```
✅ Success:
   Commit: [commit info]
   Push: Successfully pushed to GitHub

❌ Failed:
   Error message dengan penjelasan
```

### Kapan Digunakan:
- Setelah membuat fitur baru
- Setelah bug fix
- Setelah update dokumentasi
- Setelah production fixes

### ⚠️ Perhatian:
- Cek perubahan file terlebih dahulu
- Pastikan commit message deskriptif
- Jangan push jika ada test yang belum jalan

---

## 🔄 Refresh Status

### Fungsi:
Memperbarui status Git terkini tanpa reload halaman.

### Cara Menggunakan:
1. Klik tombol **Refresh Status** (icon: refresh)
2. Halaman akan di-reload otomatis untuk menampilkan status terbaru

---

## 📋 Melihat Perubahan File

### Tabel Perubahan:
**Ditampilkan otomatis jika ada local changes:**

| Status | Arti | Badge |
|--------|------|-------|
| M | Modified (file diubah) | Warning (kuning) |
| A | Added (file baru) | Success (hijau) |
| D | Deleted (file dihapus) | Danger (merah) |

### Contoh:
```
Status   |  File
---------|------------------------------------------
Modified | app/Http/Controllers/MyController.php
Added    | resources/views/new-feature.blade.php
Deleted  | old_unused_file.php
```

---

## 🛠️ Troubleshooting

### Problem 1: "The remote end hung up unexpectedly"

**Cause:** Koneksi internet terputus saat push.

**Solution:**
```bash
# Retry push
git push origin main
```

---

### Problem 2: "Permission denied (publickey)"

**Cause:** SSH key atau git credentials tidak tersimpan.

**Solution:**
```bash
# Setup git credentials
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# Or generate SSH key
ssh-keygen -t rsa -b 4096 -C "your.email@example.com"
```

---

### Problem 3: "Your branch and origin/main have diverged"

**Cause:** Local dan remote branch memiliki commit yang berbeda.

**Solution:**
```bash
# Reset ke remote
git fetch origin
git reset --hard origin/main
```

---

### Problem 4: "fatal: Not a git repository"

**Cause:** Aplikasi bukan git repository.

**Solution:**
```bash
# Initialize git (jika belum ada)
git init
git remote add origin https://github.com/duwija/HD_ALUS
git fetch origin
git reset --hard origin/main
```

---

### Problem 5: Pull/Push tidak bekerja dari halaman

**Cause:** 
- `exec()` atau shell_exec mungkin di-disable di php.ini
- Permissions masalah

**Solution:**
```php
// Check di .env atau config
// Pastikan exec diperbolehkan

// Test dari terminal:
php artisan tinker
>>> shell_exec("git status")
```

---

## ✅ Best Practices

### 1. Commit Message Convention

```
// Format: type: description

feat: add new feature
fix: resolve bug
docs: update documentation
chore: routine maintenance
refactor: improve code structure
style: code formatting
test: add/update tests
perf: performance improvement
```

### 2. Pull Sebelum Push

```
1. Pull latest code dari GitHub
2. Resolve conflicts jika ada
3. Test aplikasi
4. Push perubahan Anda
```

### 3. Hindari Push Saat Production

```
❌ Jangan push ketika:
- Traffic production tinggi
- Belum test lengkap
- Database migration belum jalan

✅ Sebaiknya push ketika:
- Off-peak hours
- Sudah tested di staging
- Deployment plan ready
```

### 4. Review Perubahan Sebelum Push

```
Lihat tabel "Local Changes" untuk:
- Tidak ada file sensitif yang terupload
- Tidak ada credentials dalam file
- Tidak ada file temporary/cache
```

### 5. Backup Sebelum Pull

```
Khususnya production environment:
1. Backup database
2. Backup current code
3. Baru pull dari GitHub
4. Test aplikasi
5. Monitor error logs
```

---

## 📊 Monitoring Deployment

### Setelah Push:

1. **Check GitHub:**
   ```
   https://github.com/duwija/HD_ALUS
   ```

2. **Run CI/CD Pipeline (jika ada):**
   - Automated tests akan jalan
   - Deployment ke staging/production

3. **Monitor Error Logs:**
   ```
   Admin Panel > Application Logs
   ```

4. **Verify Functionality:**
   - Test critical features
   - Check customer portals
   - Monitor API endpoints

---

## 🚀 Integration dengan Deploy Scripts

GitHub Sync bisa dikombinasikan dengan deployment scripts:

```bash
# Di server production:

# 1. Pull kode terbaru
bash scripts/deploy.sh

# Atau gunakan deploy-all.sh untuk multi-server:
bash scripts/deploy-all.sh
```

---

## 📞 Support

Jika ada masalah:

1. Cek error message di response dialog
2. Lihat application logs di Admin Panel
3. Test command git di terminal langsung
4. Hubungi development team

---

## 📝 Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-03-24 | Initial release - Pull/Push functionality |

