<?php

namespace App\Http\Controllers;

use App\Console\Commands\DailyReport;
use App\Console\Commands\InvoiceAuto;
use App\Console\Commands\IsolirReminder;
use App\Http\Controllers\Concerns\ManagesTenantSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pengaturan auto isolir dari sisi admin tenant.
 *
 * Saklar yang dipakai SAMA dengan yang diatur super admin di Tenant Management:
 * isp_master.tenants.features.auto_isolir — dibaca oleh command isolir:auto (jam 01:00).
 * Tenant hanya boleh mengubah key auto_isolir pada baris tenant-nya sendiri
 * (ditentukan dari domain yang sedang diakses, bukan dari input form).
 */
class AutoIsolirSettingController extends Controller
{
    use ManagesTenantSettings;

    /** Pilihan H-x yang bisa dicentang admin tenant. */
    private const REMINDER_OPTIONS = [1, 2, 3, 4, 5, 6, 7];

    /** Batas panjang catatan invoice (INV_NOTE). */
    private const INV_NOTE_MAX = 1000;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorizeAdmin();

        $tenant = $this->currentTenantRow();
        $features = $this->decodeJsonColumn($tenant->features);

        // Sama dengan IsolirAuto: key belum ada → dianggap aktif.
        $isSet   = array_key_exists('auto_isolir', $features);
        $enabled = !$isSet || (bool) $features['auto_isolir'];

        $today = (int) now()->format('j');
        $eligibleToday = \App\Customer::leftJoin('suminvoices', 'suminvoices.id_customer', '=', 'customers.id')
            ->where('suminvoices.payment_status', 0)
            ->where('customers.id_status', 2)
            ->where('customers.isolir_date', $today)
            ->distinct('customers.id')
            ->count('customers.id');

        return view('admin.auto-isolir', [
            'enabled'       => $enabled,
            'isSet'         => $isSet,
            'today'         => $today,
            'eligibleToday' => $eligibleToday,
            'reminderDays'  => IsolirReminder::normalizeDays($features['isolir_reminder_days'] ?? []),
            'reminderOptions' => self::REMINDER_OPTIONS,
            'invNote'       => $this->readInvNote($tenant),
            'invNoteMax'    => self::INV_NOTE_MAX,
            'autoInvoiceDay'    => InvoiceAuto::normalizeDay($features['auto_invoice_day'] ?? null),
            'autoInvoicePeriod' => ($features['auto_invoice_period'] ?? 'current') === 'next' ? 'next' : 'current',
            'autoInvoiceLast'   => $features['auto_invoice_last'] ?? null,
            'autoInvoiceMode'   => InvoiceAuto::normalizeMode($features['auto_invoice_mode'] ?? null),
            'autoInvoiceBeforeIsolir' => InvoiceAuto::normalizeBeforeIsolir($features['auto_invoice_before_isolir'] ?? null),
            'autoInvoiceSummary' => self::autoInvoiceSummary($features),
            'dailyReportEmails' => DailyReport::normalizeEmails($features['daily_report_emails'] ?? []),
            'isolirMethod'  => \App\Distrouter::isolirSetting()[0],
            'isolirProfile' => \App\Distrouter::isolirSetting()[1],
            // Key belum ada → aktif (sama dengan tenant_notify_enabled)
            'notifInvoiceCreate' => !array_key_exists('notif_invoice_create', $features) || (bool) $features['notif_invoice_create'],
            'notifPayment'       => !array_key_exists('notif_payment', $features) || (bool) $features['notif_payment'],
            'customPay'          => $this->customPaymentRow(),
        ]);
    }

    /**
     * Metode isolir MikroTik: disable secret PPPoE, atau ganti profile secret ke profile isolir.
     * Dibaca lewat Distrouter::isolirSetting() di mikrotik_isolir() / mikrotik_enable().
     */
    public function updateIsolirMethod(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'isolir_method'  => 'required|in:disable,profile',
            'isolir_profile' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_.\-]+$/'],
        ], [
            'isolir_profile.regex' => 'Nama profile hanya boleh huruf, angka, titik, strip, dan underscore.',
        ]);

        $method  = $request->input('isolir_method');
        $profile = trim((string) $request->input('isolir_profile')) ?: 'ISOLIR';

        $tenant = $this->currentTenantRow();
        [$oldMethod, $oldProfile] = \App\Distrouter::isolirSetting();

        DB::connection('isp_master')->update(
            "UPDATE tenants SET features = JSON_SET(COALESCE(NULLIF(features, ''), '{}'), '$.isolir_method', ?, '$.isolir_profile', ?), updated_at = ? WHERE id = ?",
            [$method, $profile, now(), $tenant->id]
        );

        $user = Auth::user();
        Log::channel('isolir')->info(sprintf(
            '[TenantSetting] tenant=%s isolir_method %s/%s -> %s/%s oleh user #%s (%s) ip=%s',
            $tenant->domain, $oldMethod, $oldProfile, $method, $profile, $user->id, $user->name, $request->ip()
        ));

        return redirect('settings/auto-isolir')->with('success', $method === 'profile'
            ? "Metode isolir: ganti profile ke {$profile}."
            : 'Metode isolir: disable secret PPPoE.');
    }

    /**
     * Generator script firewall + web proxy MikroTik untuk captive portal /isolir.
     * Hanya menampilkan script (copy-paste ke terminal router), tidak mengubah router.
     */
    public function mikrotikScript(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'ros'        => 'nullable|in:6,7',
            'proxy_port' => 'nullable|integer|between:1025,65535',
            'extra'      => 'nullable|string|max:2000',
        ]);

        $domain = strtolower((string) tenant_config('domain_name', env('DOMAIN_NAME')));
        [$method, $profile] = \App\Distrouter::isolirSetting();
        [$extraHosts, $invalidHosts] = \App\Services\IsolirScriptBuilder::parseHosts($request->input('extra'));

        $opt = [
            'domain'      => $domain,
            'profile'     => $profile,
            'ros'         => (int) $request->input('ros', 7),
            'proxy_port'  => (int) $request->input('proxy_port', 8082),
            'extra_hosts' => $extraHosts,
        ];

        return view('admin.isolir-script', [
            'opt'          => $opt,
            'method'       => $method,
            'extra'        => $request->input('extra', ''),
            'invalidHosts' => $invalidHosts,
            'domainValid'  => \App\Services\IsolirScriptBuilder::isValidHost($domain),
            'gatewayHosts' => \App\Services\IsolirScriptBuilder::gatewayHosts(),
            'script'       => \App\Services\IsolirScriptBuilder::build($opt),
            'rollback'     => \App\Services\IsolirScriptBuilder::rollback(),
        ]);
    }

    /**
     * Saklar notifikasi pelanggan saat invoice dibuat / saat pelunasan.
     * Dibaca lewat tenant_notify_enabled() di CreateInvJob, SuminvoiceController, XenditCallbackController.
     */
    public function updateNotification(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'notif_invoice_create' => 'required|boolean',
            'notif_payment'        => 'required|boolean',
        ]);
        $onCreate  = $request->boolean('notif_invoice_create');
        $onPayment = $request->boolean('notif_payment');

        $tenant = $this->currentTenantRow();
        DB::connection('isp_master')->table('tenants')
            ->where('id', $tenant->id)
            ->update([
                'features'   => DB::raw("JSON_SET(COALESCE(NULLIF(features, ''), '{}'), '$.notif_invoice_create', " . ($onCreate ? 'true' : 'false') . ", '$.notif_payment', " . ($onPayment ? 'true' : 'false') . ')'),
                'updated_at' => now(),
            ]);

        $user = Auth::user();
        Log::channel('notif')->info(sprintf(
            '[TenantSetting] tenant=%s notif_invoice_create=%s notif_payment=%s oleh user #%s (%s) ip=%s',
            $tenant->domain,
            $onCreate ? 'ON' : 'OFF',
            $onPayment ? 'ON' : 'OFF',
            $user->id,
            $user->name,
            $request->ip()
        ));

        return redirect('settings/auto-isolir')->with('success', sprintf(
            'Notifikasi pelanggan disimpan: saat invoice dibuat %s, saat pelunasan %s.',
            $onCreate ? 'AKTIF' : 'NONAKTIF',
            $onPayment ? 'AKTIF' : 'NONAKTIF'
        ));
    }

    /**
     * Simpan penerima laporan harian (command report:daily, jam 07:00, data kemarin).
     * Kosong = laporan tidak dikirim.
     */
    public function updateDailyReport(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate(['daily_report_emails' => 'nullable|string|max:2000']);

        $raw    = preg_split('/[\s,;]+/', (string) $request->input('daily_report_emails', ''), -1, PREG_SPLIT_NO_EMPTY);
        $emails = DailyReport::normalizeEmails($raw);
        $invalid = array_values(array_filter($raw, fn ($e) => !filter_var(trim($e), FILTER_VALIDATE_EMAIL)));
        if ($invalid) {
            return back()->withInput()->withErrors(['daily_report_emails' => 'Email tidak valid: ' . implode(', ', $invalid)]);
        }

        $tenant = $this->currentTenantRow();
        DB::connection('isp_master')->update(
            "UPDATE tenants SET features = JSON_SET(COALESCE(NULLIF(features, ''), '{}'), '$.daily_report_emails', JSON_EXTRACT(?, '$')), updated_at = ? WHERE id = ?",
            [json_encode($emails), now(), $tenant->id]
        );

        $user = Auth::user();
        Log::channel('notif')->info(sprintf(
            '[TenantSetting] tenant=%s daily_report_emails -> [%s] oleh user #%s (%s) ip=%s',
            $tenant->domain, implode(', ', $emails), $user->id, $user->name, $request->ip()
        ));

        return redirect('settings/auto-isolir')->with('success', empty($emails)
            ? 'Laporan harian dimatikan.'
            : 'Laporan harian akan dikirim setiap jam 07:00 ke: ' . implode(', ', $emails) . '.');
    }

    /** Kirim laporan kemarin sekarang juga ke penerima yang tersimpan (untuk cek). */
    public function sendDailyReportNow(Request $request)
    {
        $this->authorizeAdmin();

        $tenant = $this->currentTenantRow();
        $emails = DailyReport::normalizeEmails($this->decodeJsonColumn($tenant->features)['daily_report_emails'] ?? []);
        if (empty($emails)) {
            return redirect('settings/auto-isolir')->with('error', 'Simpan dulu email penerima laporan harian.');
        }

        try {
            $report = (new \App\Services\DailyReportService)->build(now()->subDay());
            \Illuminate\Support\Facades\Mail::to($emails)->send(new \App\Mail\DailyReportMail($report, (string) config('app.name')));
        } catch (\Throwable $e) {
            Log::channel('notif')->error('[DailyReport] Kirim manual gagal: ' . $e->getMessage());
            return redirect('settings/auto-isolir')->with('error', 'Gagal mengirim laporan: ' . $e->getMessage());
        }

        $user = Auth::user();
        Log::channel('notif')->info("[DailyReport] Kirim manual oleh user #{$user->id} ({$user->name}) ke " . implode(', ', $emails));

        return redirect('settings/auto-isolir')->with('success', 'Laporan kemarin terkirim ke: ' . implode(', ', $emails) . '.');
    }

    /**
     * Simpan jadwal invoice otomatis (command invoice:auto, jam 06:00).
     * Mode "date": auto_invoice_day 1..28 / "last" + auto_invoice_period current / next.
     * Mode "isolir": auto_invoice_before_isolir 1..28 (H-x sebelum tanggal isolir tiap pelanggan).
     */
    public function updateAutoInvoice(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'auto_invoice_mode'          => 'required|in:off,date,isolir',
            'auto_invoice_day'           => ['nullable', 'regex:/^([1-9]|1[0-9]|2[0-8]|last)$/', 'required_if:auto_invoice_mode,date'],
            'auto_invoice_period'        => 'nullable|in:current,next|required_if:auto_invoice_mode,date',
            'auto_invoice_before_isolir' => 'nullable|integer|between:1,28|required_if:auto_invoice_mode,isolir',
        ], [
            'auto_invoice_day.required_if'           => 'Pilih tanggal eksekusi.',
            'auto_invoice_before_isolir.required_if' => 'Pilih H-berapa sebelum tanggal isolir.',
            'auto_invoice_before_isolir.between'     => 'H-x harus antara 1 dan 28.',
        ]);

        $choice = $request->input('auto_invoice_mode');
        $mode   = $choice === 'isolir' ? 'isolir' : 'date';
        // Mode "date" dengan day null = mati (perilaku lama). Mode isolir: day dikosongkan supaya jelas tidak jalan.
        $day    = $choice === 'date' ? InvoiceAuto::normalizeDay($request->input('auto_invoice_day')) : null;
        $before = $choice === 'isolir' ? InvoiceAuto::normalizeBeforeIsolir($request->input('auto_invoice_before_isolir')) : null;
        $tenant   = $this->currentTenantRow();
        $features = $this->decodeJsonColumn($tenant->features);
        $old      = self::autoInvoiceSummary($features);
        // Periode hanya dikirim di mode "date"; mode lain mempertahankan nilai lama.
        $period = $request->input('auto_invoice_period')
            ?? ((($features['auto_invoice_period'] ?? 'current') === 'next') ? 'next' : 'current');

        // Semua nilai sudah tervalidasi/ternormalisasi (int / 'last' / null / daftar tetap).
        $dayJson    = $day === null ? 'NULL' : ($day === 'last' ? "'last'" : (string) $day);
        $beforeJson = $before === null ? 'NULL' : (string) $before;
        DB::connection('isp_master')->table('tenants')
            ->where('id', $tenant->id)
            ->update([
                'features'   => DB::raw("JSON_SET(COALESCE(NULLIF(features, ''), '{}'), '$.auto_invoice_day', {$dayJson}, '$.auto_invoice_period', '{$period}', '$.auto_invoice_mode', '{$mode}', '$.auto_invoice_before_isolir', {$beforeJson})"),
                'updated_at' => now(),
            ]);

        $new = self::autoInvoiceSummary([
            'auto_invoice_mode' => $mode, 'auto_invoice_day' => $day,
            'auto_invoice_period' => $period, 'auto_invoice_before_isolir' => $before,
        ]);

        $user = Auth::user();
        Log::channel('invoice')->info(sprintf(
            '[TenantSetting] tenant=%s auto_invoice "%s" -> "%s" oleh user #%s (%s) ip=%s',
            $tenant->domain, $old, $new, $user->id, $user->name, $request->ip()
        ));

        return redirect('settings/auto-isolir')->with('success', $new === 'mati'
            ? 'Invoice otomatis dimatikan.'
            : 'Invoice otomatis disimpan: ' . $new . '.');
    }

    /** Ringkasan pengaturan invoice otomatis untuk badge, pesan & log. */
    public static function autoInvoiceSummary(array $features): string
    {
        if (InvoiceAuto::normalizeMode($features['auto_invoice_mode'] ?? null) === 'isolir') {
            $before = InvoiceAuto::normalizeBeforeIsolir($features['auto_invoice_before_isolir'] ?? null);
            return $before === null ? 'mati' : "H-{$before} sebelum tanggal isolir tiap pelanggan, jam 06:00";
        }

        $day = InvoiceAuto::normalizeDay($features['auto_invoice_day'] ?? null);
        if ($day === null) {
            return 'mati';
        }

        return 'setiap ' . self::autoInvoiceDayLabel($day) . ' jam 06:00, periode '
            . (($features['auto_invoice_period'] ?? 'current') === 'next' ? 'bulan berikutnya' : 'bulan berjalan');
    }

    public static function autoInvoiceDayLabel($day): string
    {
        return $day === 'last' ? 'akhir bulan' : 'tanggal ' . $day;
    }

    /**
     * Simpan catatan invoice (INV_NOTE) ke isp_master.tenants.env_variables —
     * nilai yang sama dengan yang diatur super admin di Tenant ENV.
     */
    public function updateInvNote(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate(['inv_note' => 'nullable|string|max:' . self::INV_NOTE_MAX]);
        $note = str_replace("\r\n", "\n", (string) $request->input('inv_note', ''));

        $tenant = $this->currentTenantRow();
        $old    = $this->readInvNote($tenant);

        // writeTenantEnv menimpa key yang sudah dipakai tenant (INV_NOTE / inv_note); key baru → INV_NOTE.
        $this->writeTenantEnv($tenant, ['INV_NOTE' => $note]);
        $keys = ['INV_NOTE'];

        $user = Auth::user();
        Log::channel('isolir')->info(sprintf(
            '[TenantSetting] tenant=%s %s diubah (%d -> %d karakter) oleh user #%s (%s) ip=%s',
            $tenant->domain,
            implode('/', $keys),
            mb_strlen($old),
            mb_strlen($note),
            $user->id,
            $user->name,
            $request->ip()
        ));

        return redirect('settings/auto-isolir')->with('success', 'Catatan invoice disimpan.');
    }

    /**
     * Metode bayar "Custom" (konten HTML di halaman tagihan pelanggan).
     * Baris yang sama (payment_gateways.provider = custom di DB tenant) dengan yang diedit
     * super admin di Tenant Management → Payment Gateway, jadi kedua sisi selalu sinkron.
     */
    public function updateCustomPayment(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'custom_enabled' => 'nullable|in:0,1',
            'custom_title'   => 'nullable|string|max:150',
            'custom_html'    => 'nullable|string|max:2000000', // gambar dari editor tersimpan base64
        ]);

        $row = $this->customPaymentRow(true);
        if (!$row) {
            return redirect('settings/auto-isolir')->with('error', 'Tabel payment gateway belum tersedia di tenant ini.');
        }

        $settings = json_decode($row->settings ?? '{}', true) ?: [];
        $oldLen   = mb_strlen((string) ($settings['custom_html'] ?? ''));
        $settings['custom_title'] = trim((string) $request->input('custom_title', '')) ?: 'Instruksi Pembayaran';
        $settings['custom_html']  = self::sanitizeCustomHtml((string) $request->input('custom_html', ''));
        $enabled = (int) $request->input('custom_enabled', 0) === 1 ? 1 : 0;

        DB::table('payment_gateways')->where('provider', 'custom')->update([
            'enabled'    => $enabled,
            'settings'   => json_encode($settings),
            'updated_at' => now(),
        ]);

        $user = Auth::user();
        Log::channel('isolir')->info(sprintf(
            '[TenantSetting] tenant=%s payment custom diubah (enabled=%d, html %d -> %d karakter) oleh user #%s (%s) ip=%s',
            tenant('domain'), $enabled, $oldLen, mb_strlen($settings['custom_html']), $user->id, $user->name, $request->ip()
        ));

        return redirect('settings/auto-isolir')->with('success', 'Metode bayar Custom disimpan' . ($enabled ? ' dan aktif.' : ' (nonaktif).'));
    }

    /** Baris provider custom; $create = buat dari default bila belum ada. */
    private function customPaymentRow(bool $create = false)
    {
        try {
            if (!\Schema::hasTable('payment_gateways')) {
                return null;
            }
            $row = DB::table('payment_gateways')->where('provider', 'custom')->first();
            if (!$row && $create) {
                $def = collect(\App\PaymentGateway::defaultProviders())->firstWhere('provider', 'custom');
                DB::table('payment_gateways')->insert([
                    'domain' => null, 'provider' => 'custom', 'label' => $def['label'], 'icon' => $def['icon'],
                    'enabled' => 0, 'fee_type' => 'none', 'fee_amount' => 0, 'fee_label' => 'Biaya Transaksi',
                    'sort_order' => $def['sort_order'], 'settings' => json_encode($def['settings']),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $row = DB::table('payment_gateways')->where('provider', 'custom')->first();
            }
            if ($row) {
                $row->settings_array = json_decode($row->settings ?? '{}', true) ?: [];
            }
            return $row;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Konten tampil mentah di halaman tagihan pelanggan: buang script/iframe/object,
     * atribut event (onclick=...) dan URL javascript: — format, gambar, tabel, link tetap.
     */
    private static function sanitizeCustomHtml(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|style|form|meta|base)\b[^>]*>.*?</\1\s*>#is', '', $html);
        $html = preg_replace('#<(script|iframe|object|embed|style|form|meta|base|link)\b[^>]*/?>#is', '', $html);
        $html = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\']?)\s*(javascript|vbscript|data:text/html)[^"\'>\s]*\2#i', '$1="#"', $html);
        return trim($html);
    }

    /**
     * Simpan pilihan H-x pengingat sebelum isolir (boleh lebih dari satu, boleh kosong = mati).
     * Dipakai command isolir:reminder (jam 09:00), tetap jalan walau auto isolir dimatikan.
     */
    public function updateReminder(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'reminder_days'   => 'nullable|array',
            'reminder_days.*' => 'integer|in:' . implode(',', self::REMINDER_OPTIONS),
        ]);
        $days = IsolirReminder::normalizeDays($request->input('reminder_days', []));

        $tenant = $this->currentTenantRow();
        $before = $this->decodeJsonColumn($tenant->features);
        $old    = IsolirReminder::normalizeDays($before['isolir_reminder_days'] ?? []);

        // $days sudah tervalidasi integer dari daftar tetap → aman disusun ke JSON_ARRAY.
        DB::connection('isp_master')->table('tenants')
            ->where('id', $tenant->id)
            ->update([
                'features'   => DB::raw("JSON_SET(COALESCE(NULLIF(features, ''), '{}'), '$.isolir_reminder_days', JSON_ARRAY(" . implode(',', $days) . '))'),
                'updated_at' => now(),
            ]);

        $user = Auth::user();
        Log::channel('isolir')->info(sprintf(
            '[AutoIsolirSetting] tenant=%s isolir_reminder_days [%s] -> [%s] oleh user #%s (%s) ip=%s',
            $tenant->domain,
            implode(',', $old),
            implode(',', $days),
            $user->id,
            $user->name,
            $request->ip()
        ));

        return redirect('settings/auto-isolir')->with('success', empty($days)
            ? 'Pengingat sebelum isolir dimatikan.'
            : 'Pengingat disimpan: ' . implode(', ', array_map(fn ($d) => "H-{$d}", $days)) . '.');
    }

    public function update(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate(['auto_isolir' => 'required|boolean']);
        $enabled = $request->boolean('auto_isolir');

        $tenant = $this->currentTenantRow();
        $before = $this->decodeJsonColumn($tenant->features);
        $old    = array_key_exists('auto_isolir', $before) ? ($before['auto_isolir'] ? 'ON' : 'OFF') : 'NULL(ON)';

        // JSON_SET: hanya key auto_isolir yang diubah, key features lain tidak tersentuh.
        DB::connection('isp_master')->table('tenants')
            ->where('id', $tenant->id)
            ->update([
                'features'   => DB::raw("JSON_SET(COALESCE(NULLIF(features, ''), '{}'), '$.auto_isolir', " . ($enabled ? 'true' : 'false') . ')'),
                'updated_at' => now(),
            ]);

        $user = Auth::user();
        Log::channel('isolir')->info(sprintf(
            '[AutoIsolirSetting] tenant=%s auto_isolir %s -> %s oleh user #%s (%s) ip=%s',
            $tenant->domain,
            $old,
            $enabled ? 'ON' : 'OFF',
            $user->id,
            $user->name,
            $request->ip()
        ));

        return redirect('settings/auto-isolir')
            ->with('success', 'Auto isolir berhasil di' . ($enabled ? 'aktifkan.' : 'nonaktifkan.'));
    }

    private function readInvNote($tenant): string
    {
        return (string) $this->readTenantEnv($tenant, 'INV_NOTE', '');
    }
}
