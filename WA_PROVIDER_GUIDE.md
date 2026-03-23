# WhatsApp Provider Guide

Panduan konfigurasi multi-provider WhatsApp per tenant. Sistem mendukung **4 provider** yang dapat diganti tanpa ubah kode — cukup set ENV variable di halaman Tenant Edit.

---

## Daftar Provider yang Didukung

| `WA_PROVIDER` | Provider | Tipe | Keterangan |
|---|---|---|---|
| `gateway` | Self-hosted WA Gateway | Free text | **Default** — pakai server WA sendiri (Baileys/WA-Web) |
| `fonnte` | [Fonnte](https://fonnte.com) | Free text | SaaS cloud WA, tidak butuh HP sendiri |
| `wablas` | [Wablas](https://wablas.com) | Free text | SaaS cloud WA, bisa pilih region server |
| `qontak` | Qontak (Official WA Business API) | Template | Resmi Meta/WA Business, butuh template terverifikasi |

---

## Cara Kerja

Ketika blast notifikasi dikirim (pengingat tagihan / blokir), sistem:

1. Mengecek `customer->notification`:
   - `0` = skip (tidak dikirim)
   - `1` = WhatsApp → masuk ke WaService
   - `2` = Email
   - `3` = FCM Push Notification (Mobile App)

2. `WaService::sendReminder()` membaca `WA_PROVIDER` dari ENV tenant secara otomatis

3. Request dikirim ke provider yang sesuai

Tiap tenant bisa punya provider berbeda. Tenant A pakai gateway sendiri, Tenant B pakai Fonnte, Tenant C pakai Qontak.

---

## Konfigurasi Per Provider

### 1. Gateway (Default — Self-hosted)

Tidak perlu setup tambahan jika server WA gateway sudah berjalan.

```
wa_gateway_url = http://127.0.0.1:3005
```

> `WA_PROVIDER` tidak perlu diisi, default sudah `gateway`.

---

### 2. Fonnte

**Langkah setup:**
1. Daftar di [fonnte.com](https://fonnte.com)
2. Tambahkan device (scan QR WA)
3. Salin **API Token** dari menu Devices

**ENV yang diset di Tenant:**
```
WA_PROVIDER     = fonnte
WA_FONNTE_TOKEN = xxxxxxxxxxxxxxxxxxxxxxxx
```

**Keterangan request yang dikirim:**
- URL: `https://api.fonnte.com/send`
- Header: `Authorization: <token>`
- Body: `{ target, message, delay }`

---

### 3. Wablas

**Langkah setup:**
1. Daftar di [wablas.com](https://wablas.com)
2. Sambungkan device WA
3. Salin **Token** dan **Server URL** dari dashboard → API

**ENV yang diset di Tenant:**
```
WA_PROVIDER      = wablas
WA_WABLAS_TOKEN  = xxxxxxxxxxxxxxxxxxxxxxxx
WA_WABLAS_URL    = https://my.wablas.com
```

> Beberapa akun Wablas punya URL server berbeda, misalnya `https://solo.wablas.com` atau `https://jogja.wablas.com`. Sesuaikan dengan info di dashboard.

**Keterangan request yang dikirim:**
- URL: `<WA_WABLAS_URL>/api/send-message`
- Header: `Authorization: <token>`
- Body: `{ phone, message }`

---

### 4. Qontak (Official WhatsApp Business API)

**Langkah setup:**
1. Daftar di [qontak.com](https://qontak.com) — perlu verifikasi bisnis
2. Buat template pesan di dashboard Qontak (kategori: Marketing / Utility)
3. Template **wajib** punya format:
   - Parameter body `{{1}}` = nama pelanggan
   - Parameter body `{{2}}` = customer ID
   - Button URL `{{1}}` = link tagihan
4. Tunggu template disetujui oleh Meta
5. Salin **Access Token**, **Template ID**, dan **Channel Integration ID**

**ENV yang diset di Tenant:**
```
WA_PROVIDER              = qontak
WA_QONTAK_TOKEN          = Bearer_token_dari_qontak
WA_QONTAK_TEMPLATE_ID    = xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
WA_QONTAK_CHANNEL_ID     = xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
WA_QONTAK_API_URL        = https://service-chat.qontak.com/api/open/v1/broadcasts/whatsapp/direct
```

> `WA_QONTAK_API_URL` opsional, hanya diisi jika endpoint Qontak berubah.

**Keterangan request yang dikirim:**
- URL: `WA_QONTAK_API_URL`
- Header: `Authorization: Bearer <token>`
- Body: JSON dengan `to_number`, `to_name`, `message_template_id`, `parameters`

> **Catatan:** Qontak hanya mendukung pengiriman via template terverifikasi. Tidak bisa kirim teks bebas. Hanya cocok untuk pesan standar seperti pengingat tagihan.

---

## Log Monitoring

Semua aktivitas tercatat di log `notif` (file: `storage/logs/notif-YYYY-MM-DD.log`):

```
[INFO]  [WA:fonnte] Kirim reminder CID C001 | Budi → 628123456789
[INFO]  [WA:fonnte] Response: {"status":true,...}
[INFO]  Sent Remainder message to CID C001 | Budi | sent

[WARN]  [WA:qontak] Konfigurasi tidak lengkap (token/template/channel kosong).
[WARN]  [WA:wablas] WA_WABLAS_TOKEN belum dikonfigurasi.
[ERROR] [WA:gateway] Error CID C002: cURL error 7: Failed to connect...
```

Untuk melihat log: **Admin → Logs → Notif Logs**

---

## Troubleshooting

| Gejala | Kemungkinan Penyebab | Solusi |
|---|---|---|
| Pesan tidak terkirim, log: `token not set` | ENV token belum diisi | Cek `WA_FONNTE_TOKEN` / `WA_WABLAS_TOKEN` di Tenant ENV |
| Log: `config incomplete` (Qontak) | `WA_QONTAK_TOKEN`, `TEMPLATE_ID`, atau `CHANNEL_ID` kosong | Isi semua 3 key Qontak di Tenant ENV |
| Log: `cURL error 7` | Gateway self-hosted tidak berjalan | Cek service WA gateway di server |
| Pesan terkirim tapi isi kosong / error dari Qontak | Template belum disetujui atau parameter tidak cocok | Cek status template di dashboard Qontak |
| Semua customer di-skip, tidak ada log | `customer->notification = 0` | Ubah nilai notification di data customer |

---

## File Terkait

| File | Keterangan |
|---|---|
| `app/Services/WaService.php` | Core service, semua logic provider ada di sini |
| `app/Jobs/NotifInvJob.php` | Queue job yang memanggil `WaService::sendReminder()` |
| `app/Traits/SendsCustomerNotification.php` | Dispatch + routing WA/Email/FCM berdasarkan `customer->notification` |
| `app/Http/Middleware/TenantMiddleware.php` | Whitelist ENV keys `WA_PROVIDER`, `WA_FONNTE_TOKEN`, dst. |

---

## Menambah Provider Baru

Edit `app/Services/WaService.php`, tambah 1 case dan 1 method:

```php
// Di sendReminder():
'zenziva' => static::sendViaZenziva($phone, static::buildReminderMessage($name, $cid, $encryptedurl)),

// Method baru:
protected static function sendViaZenziva(string $phone, string $message): string
{
    $userkey = tenant_config('WA_ZENZIVA_USERKEY', '');
    $passkey = tenant_config('WA_ZENZIVA_PASSKEY', '');
    // ... HTTP call ke Zenziva API
}
```

Tidak perlu ubah controller, job, atau trait sama sekali.
