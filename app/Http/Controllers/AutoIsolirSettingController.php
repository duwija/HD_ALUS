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
            'dailyReportEmails' => DailyReport::normalizeEmails($features['daily_report_emails'] ?? []),
        ]);
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
     * auto_invoice_day: 1..28 / "last" / kosong (mati); auto_invoice_period: current / next.
     */
    public function updateAutoInvoice(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'auto_invoice_day'    => ['nullable', 'regex:/^([1-9]|1[0-9]|2[0-8]|last)$/'],
            'auto_invoice_period' => 'required|in:current,next',
        ]);

        $day    = InvoiceAuto::normalizeDay($request->input('auto_invoice_day'));
        $period = $request->input('auto_invoice_period');

        $tenant = $this->currentTenantRow();
        $before = $this->decodeJsonColumn($tenant->features);
        $oldDay = InvoiceAuto::normalizeDay($before['auto_invoice_day'] ?? null);
        $oldPeriod = $before['auto_invoice_period'] ?? 'current';

        // $day sudah tervalidasi (int 1..28 / 'last' / null), $period dari daftar tetap.
        $dayJson = $day === null ? 'NULL' : ($day === 'last' ? "'last'" : (string) $day);
        DB::connection('isp_master')->table('tenants')
            ->where('id', $tenant->id)
            ->update([
                'features'   => DB::raw("JSON_SET(COALESCE(NULLIF(features, ''), '{}'), '$.auto_invoice_day', {$dayJson}, '$.auto_invoice_period', '{$period}')"),
                'updated_at' => now(),
            ]);

        $user = Auth::user();
        Log::channel('invoice')->info(sprintf(
            '[TenantSetting] tenant=%s auto_invoice %s/%s -> %s/%s oleh user #%s (%s) ip=%s',
            $tenant->domain,
            $oldDay ?? 'off', $oldPeriod,
            $day ?? 'off', $period,
            $user->id,
            $user->name,
            $request->ip()
        ));

        return redirect('settings/auto-isolir')->with('success', $day === null
            ? 'Invoice otomatis dimatikan.'
            : 'Invoice otomatis disimpan: setiap ' . self::autoInvoiceDayLabel($day) . ' jam 06:00, periode ' . ($period === 'next' ? 'bulan berikutnya' : 'bulan berjalan') . '.');
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
