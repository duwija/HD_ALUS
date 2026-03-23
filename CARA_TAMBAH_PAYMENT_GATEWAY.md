# Cara Menambah Payment Gateway Baru

Contoh kasus: menambah provider **Midtrans**.

---

## Langkah 1 — Daftarkan di `PaymentGateway` Model

File: `app/PaymentGateway.php`

Tambahkan entry baru di method `defaultProviders()`:

```php
['provider' => 'midtrans', 'label' => 'Midtrans Payment', 'icon' => 'fas fa-credit-card', 'enabled' => 0, 'sort_order' => 6],
```

> `enabled => 0` artinya default nonaktif, admin bisa aktifkan per tenant lewat admin panel.

---

## Langkah 2 — Jalankan Seeder di Setiap Tenant DB

```bash
php artisan db:seed --class="\PaymentGatewaySeeder"
```

Seeder bersifat **insert-only** untuk provider baru — tidak menimpa konfigurasi fee/enabled yang sudah ada.

Jika ada banyak tenant, jalankan untuk masing-masing DB:

```bash
# Set DB di .env atau pakai script loop
for DB in kencana perumnet adiyasa olima default maharani; do
    mysql -u root $DB -e "INSERT IGNORE INTO payment_gateways (provider,label,icon,enabled,fee_type,fee_amount,sort_order,created_at,updated_at) VALUES ('midtrans','Midtrans Payment','fas fa-credit-card',0,'none',0,6,NOW(),NOW());"
done
```

---

## Langkah 3 — Tambahkan Case di View Invoice

File: `resources/views/suminvoice/print.blade.php`

Cari blok `@foreach($gateways as $gw)` (ada **2 blok** — di tombol pilih gateway dan di section konfirmasi/notif).
Tambahkan `@case('midtrans')` di dalam `@switch($gw->provider)`:

```blade
@case('midtrans')
    <form method="POST" action="{{ url('/create-midtrans-payment') }}">
        @csrf
        <input type="hidden" name="invoice_id" value="{{ $suminvoice->id }}">
        <button type="submit" class="btn btn-warning btn-block">
            <i class="fas fa-credit-card"></i> Bayar via Midtrans
            @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                <small class="payment-fee">
                    +{{ $gw->fee_type === 'fixed' ? 'Rp '.number_format($gw->fee_amount,0,',','.') : $gw->fee_amount.'%' }}
                </small>
            @endif
        </button>
    </form>
@break
```

> Jika tidak ditambahkan case, blok `@default` akan otomatis membuat tombol generic ke `/create-midtrans-va`. Cukup untuk testing awal.

---

## Langkah 4 — Tambahkan Route

File: `routes/web.php`

```php
Route::post('/create-midtrans-payment', 'SuminvoiceController@createMidtransPayment');
```

---

## Langkah 5 — Tambahkan Method Controller

File: `app/Http/Controllers/SuminvoiceController.php`

```php
public function createMidtransPayment(Request $request)
{
    $invoice = Suminvoice::findOrFail($request->invoice_id);

    // TODO: Integrasi Midtrans Snap / Core API
    // $snapToken = \Midtrans\Snap::getSnapToken([...]);

    return redirect()->back()->with('info', 'Midtrans belum dikonfigurasi.');
}
```

---

## Langkah 6 — (Opsional) Tambahkan Fee Otomatis di Winpay Style

Jika provider ini perlu menambahkan biaya ke item invoice saat membuat VA, contohnya seperti Winpay:

File: `app/Http/Controllers/SuminvoiceController.php`, di method `createMidtransPayment()`:

```php
$gw  = \App\PaymentGateway::findForCurrentTenant('midtrans');
$fee = $gw ? $gw->calculateFee((float) $invoice->total_amount) : 0;

if ($fee > 0) {
    // Tambahkan fee ke daftar item sebelum kirim ke API
}
```

---

## Checklist Ringkas

| # | File | Yang Ditambah |
|---|------|---------------|
| 1 | `app/PaymentGateway.php` | Entry baru di `defaultProviders()` |
| 2 | Semua tenant DB | SQL `INSERT IGNORE` atau jalankan seeder |
| 3 | `resources/views/suminvoice/print.blade.php` | `@case('midtrans')` di 2 blok switch |
| 4 | `routes/web.php` | `Route::post(...)` |
| 5 | `app/Http/Controllers/SuminvoiceController.php` | Method `createMidtransPayment()` |

---

## Catatan Penting

- **Admin panel** (`Admin → Tenants → Payment Gateway`) otomatis menampilkan provider baru setelah seeder dijalankan — tidak perlu edit view admin.
- **Fee** dikonfigurasi per tenant oleh admin, tidak perlu hardcode di kode.
- **Aktif/nonaktif** juga dikontrol dari admin panel, bukan dari kode.
- Jika tabel `payment_gateways` belum ada di suatu tenant, sistem otomatis mengembalikan koleksi kosong (tidak crash).
