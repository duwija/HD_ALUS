# Konfigurasi Payment Gateway

## Arsitektur (Sistem Baru)

Konfigurasi payment gateway disimpan di tabel **`payment_gateways`** pada **database masing-masing tenant** (bukan di `.env` atau tabel `tenants`).

| Kolom        | Tipe    | Keterangan                                                       |
|-------------|---------|------------------------------------------------------------------|
| `provider`  | string  | Slug unik: `xendit`, `winpay`, `tripay`, `bumdes`, `duitku`      |
| `label`     | string  | Nama tampil di UI                                                |
| `icon`      | string  | FontAwesome class, misal `fas fa-wallet`                         |
| `enabled`   | tinyint | `1` = aktif (tampil di invoice), `0` = nonaktif                  |
| `fee_type`  | string  | `none` / `fixed` / `percent`                                     |
| `fee_amount`| decimal | Nominal (fixed) atau persentase (percent)                        |
| `fee_label` | string  | Teks label biaya, misal `Biaya Admin`                            |
| `sort_order`| int     | Urutan tampil di halaman invoice                                 |
| `settings`  | JSON    | Konfigurasi khusus provider (API key, dll)                       |

---

## Admin Panel

URL: `/admin/tenants/{id}/payment-gateway`

Di halaman ini admin dapat:
- Enable / disable tiap provider
- Mengatur tipe dan nominal biaya
- Mengisi **Merchant Code**, **API Key**, **metode VA**, dan **mode Sandbox**
  (untuk provider yang memiliki field tersebut, seperti Duitku)

---

## Provider yang Tersedia

| Provider | Label                  | Metode                    | Status default |
|----------|------------------------|---------------------------|----------------|
| `xendit` | Xendit                 | VA, E-Wallet, QRIS        | Aktif          |
| `winpay` | Winpay                 | Bank VA & Retail Outlet   | Aktif          |
| `tripay` | Tripay                 | E-Wallet & QRIS           | Aktif          |
| `bumdes` | Bumdes / Payment Point | Bayar di Loket Terdekat   | Aktif          |
| `duitku` | Duitku                 | VA, QRIS & E-Wallet       | Aktif          |

---

## Konfigurasi Duitku

### Settings JSON (`settings` column)

```json
{
  "subtitle":       "VA, QRIS & E-Wallet",
  "merchant_code":  "DXXXX",
  "api_key":        "your-api-key-here",
  "payment_method": "I1",
  "sandbox":        false
}
```

| Field            | Keterangan                                                              |
|-----------------|-------------------------------------------------------------------------|
| `merchant_code` | Kode proyek dari Duitku Merchant Portal. Format: `DXXXX`               |
| `api_key`       | API Key dari merchant portal Duitku                                     |
| `payment_method`| Kode metode pembayaran (lihat tabel di bawah)                           |
| `sandbox`       | `true` = gunakan endpoint sandbox, `false` = production                 |

### Kode Metode Pembayaran Duitku

| Kode | Bank / Metode           |
|------|-------------------------|
| `I1` | BNI Virtual Account     |
| `BT` | Permata Virtual Account |
| `M2` | Mandiri Virtual Account |
| `BC` | BCA Virtual Account     |
| `BR` | BRIVA (BRI)             |
| `B1` | CIMB Niaga VA           |
| `BV` | BSI Virtual Account     |
| `SP` | QRIS (Shopee Pay)       |
| `NQ` | QRIS (Nobu)             |
| `DA` | DANA E-Wallet           |
| `OV` | OVO E-Wallet            |

### Endpoint API

| Mode        | URL                                                              |
|-------------|------------------------------------------------------------------|
| Sandbox     | `https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry`      |
| Production  | `https://passport.duitku.com/webapi/api/merchant/v2/inquiry`     |

### Callback URL

Daftarkan URL berikut di dashboard Duitku merchant:

```
https://{domain-tenant}/duitku/callback
```

Contoh untuk kencana:
```
https://kencana.alus.co.id/duitku/callback
```

### Signature Formula

```
MD5(merchantCode + merchantOrderId + paymentAmount + apiKey)
```

---

## ENV Variables (Fallback)

Konfigurasi pertama-tama dibaca dari kolom `settings` di tabel `payment_gateways`.
Jika field kosong, akan fallback ke ENV / `tenant_config`.

```env
# Duitku
DUITKU_MERCHANT_CODE=DXXXX
DUITKU_API_KEY=your-api-key
DUITKU_SANDBOX=false
```

> ⚠️ **Rekomendasi:** Simpan di tabel `payment_gateways` via admin panel, bukan `.env`.
> Menggunakan tabel memungkinkan konfigurasi berbeda per tenant tanpa restart server.

---

## Update via SQL (Manual)

```sql
-- Aktifkan Duitku
UPDATE payment_gateways SET enabled = 1 WHERE provider = 'duitku';

-- Set API key dan merchant code
UPDATE payment_gateways
SET settings = JSON_SET(
    IFNULL(settings, '{}'),
    '$.merchant_code',  'DXXXX',
    '$.api_key',        'your-api-key-here',
    '$.payment_method', 'I1',
    '$.sandbox',        false
)
WHERE provider = 'duitku';

-- Nonaktifkan provider tertentu
UPDATE payment_gateways SET enabled = 0 WHERE provider = 'xendit';
```

---

## Alur Pembayaran Duitku

```
Customer klik "Bayar via Duitku"
    → POST /create-duitku-va
    → SuminvoiceController@createDuitkuVA
    → API Duitku: POST /v2/inquiry
    → Simpan reference ke suminvoices.payment_id
    → Redirect ke paymentUrl (halaman Duitku)

Customer bayar di halaman Duitku
    → Duitku kirim POST ke /duitku/callback
    → XenditCallbackController@update_duitku
    → Verifikasi signature MD5
    → Update invoice payment_status = 1
    → Entri jurnal otomatis
    → Notifikasi ke customer (WhatsApp / Email / FCM)
    → Aktifkan kembali PPPoE jika diblokir
```

---

## Troubleshooting

### Tombol Duitku tidak muncul di invoice
1. Pastikan `enabled = 1` di tabel `payment_gateways`
2. Cek log: `storage/logs/laravel.log`

### Error "Konfigurasi Duitku belum diisi"
Field `api_key` atau `merchant_code` kosong. Isi via admin panel atau SQL di atas.

### Error callback signature tidak valid
Pastikan `api_key` di settings sudah benar dan sama dengan di dashboard Duitku merchant portal.

### Callback tidak diterima
- Pastikan URL callback sudah didaftarkan di Duitku merchant portal
- IP Duitku yang perlu di-whitelist (production):
  `182.23.85.8`, `182.23.85.9`, `182.23.85.10`, `103.177.101.184`, `103.177.101.185`
- IP Duitku sandbox: `182.23.85.11`, `182.23.85.12`

### Mode Sandbox
Set `sandbox: true` di settings untuk testing.
Demo pembayaran VA sandbox: `https://sandbox.duitku.com/payment/demo/demosuccesstransaction.aspx`

---

## Catatan Multi-Tenant

Setiap tenant punya baris sendiri di tabel `payment_gateways` di masing-masing DB-nya.
- Tenant A bisa pakai Duitku + Tripay
- Tenant B bisa pakai Xendit + Bumdes saja
- Konfigurasi sepenuhnya independen per tenant

Konfigurasi via admin panel: `/admin/tenants/{id}/payment-gateway`
