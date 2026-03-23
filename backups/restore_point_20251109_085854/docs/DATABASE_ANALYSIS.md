# ANALISIS STRUKTUR DATABASE & REKOMENDASI PENYESUAIAN

## Status Saat Ini

### Tabel: akuns
**Kolom yang digunakan:**
- `category` - Kategori detail akun
- `group` - Pengelompokan besar (aktiva, kewajiban, ekuitas, pendapatan, beban)
- `type` - Jenis/tipe akun (jarang diisi)

### Mapping Kategori Saat Ini

#### 1. AKTIVA/ASET (group: 'aktiva')
```
✅ kas & bank                    -> 9 akun  (Aset Lancar)
✅ akun piutang                  -> 4 akun  (Aset Lancar)
✅ persediaan                    -> 1 akun  (Aset Lancar)
✅ aktiva lainnya                -> 4 akun  (Aset Lancar/Lainnya)
✅ aktiva tetap                  -> 5 akun  (Aset Tetap)
✅ depresiasi dan amortisasi     -> 4 akun  (Aset Tetap - kontra)
```

#### 2. KEWAJIBAN (group: 'kewajiban' atau 'hutang')
```
✅ akun hutang                   -> 20 akun (Kewajiban Lancar)
✅ kewajiban lancar lainnya      -> 8 akun  (Kewajiban Lancar)
⚠️  kewajiban jangka pendek      -> 1 akun  (group: 'hutang' - inconsistent)
❌ TIDAK ADA: Kewajiban Jangka Panjang
```

#### 3. EKUITAS (group: 'ekuitas')
```
✅ ekuitas                       -> 6 akun  (Modal, Laba Ditahan, dll)
```

#### 4. PENDAPATAN (group: 'pendapatan')
```
✅ pendapatan                    -> 7 akun  (Pendapatan Operasional)
✅ pendapatan lainnya            -> 2 akun  (Pendapatan Non-Operasional)
```

#### 5. BEBAN (group: 'beban')
```
✅ beban                         -> 21 akun (Beban Operasional)
✅ beban lainnya                 -> 3 akun  (Beban Non-Operasional)
✅ harga pokok penjualan         -> 4 akun  (HPP/COGS)
```

---

## MASALAH YANG DITEMUKAN

### 🔴 CRITICAL
1. **Inconsistency di Kewajiban**
   - Ada `group: 'hutang'` dan `group: 'kewajiban'`
   - Harus diseragamkan menjadi `kewajiban`

### 🟡 WARNING
2. **Tidak ada kategori "Kewajiban Jangka Panjang"**
   - Jika perusahaan punya hutang > 1 tahun, perlu ditambahkan
   
3. **Kolom `type` jarang diisi**
   - Hanya 3 nilai yang digunakan: 'aktiva lancar', 'pendapatan', 'utang jangka pendek'
   - Sebaiknya diisi konsisten atau tidak dipakai

---

## REKOMENDASI PENYESUAIAN

### Option 1: Penyesuaian Minimal (RECOMMENDED)
**Hanya fix inconsistency, tidak mengubah struktur besar**

```sql
-- Fix group inconsistency untuk kewajiban
UPDATE akuns 
SET `group` = 'kewajiban' 
WHERE `group` = 'hutang' AND deleted_at IS NULL;

-- Verifikasi hasil
SELECT category, `group`, COUNT(*) as jumlah 
FROM akuns 
WHERE deleted_at IS NULL 
GROUP BY category, `group` 
ORDER BY `group`, category;
```

**Mapping Controller akan menggunakan:**
```php
// NERACA
'Aset Lancar' => ['kas & bank', 'akun piutang', 'persediaan', 'aktiva lainnya']
'Aset Tetap' => ['aktiva tetap', 'depresiasi dan amortisasi']
'Kewajiban Lancar' => ['akun hutang', 'kewajiban lancar lainnya', 'kewajiban jangka pendek']
'Ekuitas' => ['ekuitas']

// LABA RUGI
'Pendapatan' => ['pendapatan', 'pendapatan lainnya']
'Beban' => ['beban', 'beban lainnya', 'harga pokok penjualan']
```

### Option 2: Standardisasi Penuh (OPTIONAL)
**Mengikuti standar akuntansi Indonesia**

Tambahkan kategori baru jika diperlukan:
```sql
-- Tambah kategori standar jika ada akun yang perlu
-- Kategori yang direkomendasikan:
-- 'aset lancar', 'aset tetap', 'aset lainnya'
-- 'kewajiban lancar', 'kewajiban jangka panjang'
-- 'ekuitas'
-- 'pendapatan operasional', 'pendapatan lainnya'
-- 'beban operasional', 'beban lainnya', 'harga pokok penjualan'
```

---

## KEPUTUSAN IMPLEMENTASI

### ✅ Yang Akan Dilakukan di Controller
1. **Gunakan mapping fleksibel** berdasarkan `group` dan `category` yang ada
2. **Handle inconsistency** dengan kondisi OR di query
3. **Grouping dinamis** untuk laporan

```php
// Contoh: Ambil semua Aset Lancar
$asetLancar = Akun::whereIn('category', [
    'kas & bank', 
    'akun piutang', 
    'persediaan', 
    'aktiva lainnya'
])->where('group', 'aktiva')->get();

// Contoh: Ambil semua Kewajiban (handle inconsistency)
$kewajiban = Akun::whereIn('category', [
    'akun hutang',
    'kewajiban lancar lainnya',
    'kewajiban jangka pendek'
])->whereIn('group', ['kewajiban', 'hutang'])->get();
```

### ⚠️ SQL Fix yang Disarankan
```sql
-- Jalankan sekali untuk fix inconsistency
UPDATE akuns 
SET `group` = 'kewajiban' 
WHERE `group` = 'hutang' 
AND deleted_at IS NULL;
```

---

## PERHITUNGAN SALDO

### Normal Balance per Kategori

**DEBET (+)**
- Aset (kas & bank, piutang, persediaan, aktiva tetap, dll)
- Beban (beban operasional, beban lainnya, HPP)

**KREDIT (+)**
- Kewajiban (hutang, kewajiban lancar)
- Ekuitas (modal, laba ditahan)
- Pendapatan (pendapatan, pendapatan lainnya)

### Formula di Controller
```php
// Untuk Aset & Beban
$saldo = $saldoAwal + SUM(debet) - SUM(kredit);

// Untuk Kewajiban, Ekuitas & Pendapatan  
$saldo = $saldoAwal + SUM(kredit) - SUM(debet);
```

---

## REKOMENDASI AKHIR

### 🎯 Untuk Implementasi Sekarang:
1. ✅ **TIDAK perlu ubah database** - gunakan mapping fleksibel di controller
2. ✅ **Handle inconsistency** dengan whereIn untuk group
3. ✅ **Dokumentasikan** kategori yang digunakan

### 📋 Untuk Masa Depan (Optional):
1. Jalankan SQL fix untuk group inconsistency
2. Standardisasi pengisian kolom `type`
3. Tambah kategori "Kewajiban Jangka Panjang" jika diperlukan

---

## Mapping Final untuk Controller

```php
const CATEGORY_MAPPING = [
    'aset_lancar' => ['kas & bank', 'akun piutang', 'persediaan', 'aktiva lainnya'],
    'aset_tetap' => ['aktiva tetap'],
    'aset_lainnya' => ['depresiasi dan amortisasi'], // kontra aset
    
    'kewajiban_lancar' => ['akun hutang', 'kewajiban lancar lainnya', 'kewajiban jangka pendek'],
    'kewajiban_jangka_panjang' => [], // kosong untuk sekarang
    
    'ekuitas' => ['ekuitas'],
    
    'pendapatan' => ['pendapatan'],
    'pendapatan_lainnya' => ['pendapatan lainnya'],
    
    'beban' => ['beban'],
    'beban_lainnya' => ['beban lainnya'],
    'hpp' => ['harga pokok penjualan'],
];

const GROUP_MAPPING = [
    'aktiva' => 'debit_normal',
    'kewajiban' => 'credit_normal',
    'hutang' => 'credit_normal', // backward compatibility
    'ekuitas' => 'credit_normal',
    'pendapatan' => 'credit_normal',
    'beban' => 'debit_normal',
];
```

---

**Kesimpulan:** Struktur database sudah cukup baik, hanya perlu mapping yang tepat di controller. Tidak perlu perubahan database untuk implementasi sekarang.
