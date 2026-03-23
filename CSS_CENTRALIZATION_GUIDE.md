# Centralized Stylesheet Implementation
## Financial Reports & Accounting System

### 📋 Summary
Semua styling untuk laporan keuangan dan halaman akuntansi telah dipindahkan ke **satu file stylesheet utama** untuk memudahkan maintenance dan konsistensi.

---

### 📁 File Stylesheet Utama

**Location:** `public/css/financial-reports.css`

**Loaded in:** `resources/views/layout/main.blade.php` (line ~42)

```html
<link rel="stylesheet" href="{{url('css/financial-reports.css')}}">
```

---

### ✅ Files Updated (Inline Styles Removed)

1. ✅ `resources/views/jurnal/general.blade.php` - Transaksi General
2. ✅ `resources/views/jurnal/kasbank.blade.php` - Kas & Bank Dashboard
3. ✅ `resources/views/jurnal/jumum.blade.php` - Jurnal Umum
4. ✅ `resources/views/jurnal/bukubesar.blade.php` - Buku Besar
5. ✅ `resources/views/jurnal/neraca_saldo.blade.php` - Neraca Saldo
6. ✅ `resources/views/jurnal/neraca.blade.php` - Neraca (Balance Sheet)
7. ✅ `resources/views/jurnal/laba_rugi.blade.php` - Laba Rugi (Income Statement)
8. ✅ `resources/views/jurnal/arus_kas.blade.php` - Arus Kas (Cash Flow)
9. ✅ `resources/views/jurnal/perubahan_modal.blade.php` - Perubahan Modal (Changes in Equity)

---

### 🎨 CSS Classes Available

#### **Headers**
- `.card-header-custom` - Soft blue gradient header untuk semua cards
- `.form-section-title` - Title dengan underline untuk sections
- `.section-title` - Section divider dengan border

#### **Summary Cards**
- `.summary-card` - Base class untuk summary cards
- `.debit-card` - Green border untuk total debit
- `.kredit-card` - Red border untuk total kredit
- `.saldo-card` / `.balance-card` - Blue border untuk saldo
- `.asset-card` - Cyan untuk assets
- `.liability-card` - Yellow untuk liabilities
- `.equity-card` - Purple untuk equity

#### **Tables**
- `.financial-table` - Base class untuk semua financial tables
- `.amount-column` - Right-aligned monospace untuk amounts
- `.total-row` - Dark background untuk total rows
- `.subtotal-row` - Green gradient untuk subtotals
- `.grandtotal-row` - Red gradient untuk grand totals
- `.level-0`, `.level-1`, `.level-2`, `.level-3` - Hierarchy levels

#### **Forms**
- `.filter-section` - Gray background untuk filter forms
- `.form-section` - Section containers dengan padding
- `.form-section-title` - Blue underlined titles

#### **Result Boxes**
- `.result-box.profit` - Green gradient untuk profit
- `.result-box.loss` - Red gradient untuk loss
- `.result-box.neutral` - Gray gradient untuk neutral

#### **Buttons**
- `.btn-add-row` - Green button untuk add row
- `.btn-export` - White button dengan border untuk export

#### **Charts**
- `.chart-container` - Container dengan shadow untuk charts

#### **Utility Classes**
- `.text-debit` - Green text untuk debit amounts
- `.text-kredit` - Red text untuk kredit amounts
- `.text-balance` - Blue text untuk balance
- `.period-info` - Blue alert box untuk period information
- `.monospace` - Monospace font untuk numbers

---

### 🔧 How to Make Style Changes

**Before (❌ Old Way):**
- Edit inline `<style>` in setiap file
- Copy-paste ke file lain untuk consistency
- Sulit maintain kalau ada 10+ files

**After (✅ New Way):**
1. Edit **hanya satu file**: `public/css/financial-reports.css`
2. Run: `php artisan view:clear`
3. Refresh browser
4. **Semua halaman langsung update!**

---

### 📝 Example Usage

#### Before (Inline Style):
```blade
<style>
  .card-header-custom {
    background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
    color: white;
    padding: 1rem 1.25rem;
  }
</style>

<div class="card-header-custom">
  <h3>My Report</h3>
</div>
```

#### After (External CSS):
```blade
{{-- No <style> block needed! --}}

<div class="card-header-custom">
  <h3>My Report</h3>
</div>
```

---

### 🎯 Benefits

1. **Single Source of Truth**: Satu file untuk semua styling
2. **Easy Maintenance**: Edit di satu tempat, apply ke semua pages
3. **Consistency**: Tidak ada style yang berbeda antar pages
4. **Performance**: Browser cache CSS file (faster loading)
5. **Clean Code**: Blade files lebih bersih tanpa inline styles
6. **Scalability**: Mudah add new reports dengan style yang sama

---

### 🛠️ Utility Script

**File:** `remove_inline_styles.py`

Script Python untuk remove inline styles dari multiple files sekaligus.

```bash
python3 remove_inline_styles.py
```

---

### 🚀 Quick Reference

**Change header color:**
```css
/* Edit: public/css/financial-reports.css */
.card-header-custom {
  background: linear-gradient(135deg, #YOUR_COLOR_1, #YOUR_COLOR_2);
}
```

**Change summary card colors:**
```css
.summary-card.debit-card {
  border-left: 4px solid #YOUR_COLOR;
}
```

**Change table header:**
```css
.financial-table thead th {
  background-color: #YOUR_COLOR;
}
```

---

### 📞 Support

Jika ada masalah dengan styling:
1. Check file: `public/css/financial-reports.css`
2. Clear cache: `php artisan view:clear`
3. Hard refresh browser: `Ctrl+Shift+R` atau `Cmd+Shift+R`

---

**Version:** 1.0  
**Last Updated:** November 9, 2025  
**Status:** ✅ Production Ready
