<?php

namespace App\Http\Controllers;

use App\Helpers\WaGatewayHelper;
use App\Http\Controllers\Concerns\ManagesTenantSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan WA Gateway sisi admin tenant (/wa/settings):
 *  - Kecepatan blast notifikasi: NOTIF_DELAY_MIN/MAX, NOTIF_LONG_PAUSE_EVERY/EXTRA
 *    (dibaca SendsCustomerNotification::messageDelay)
 *  - Penanganan nomor gagal: WA_SUPPRESS_* (dibaca WaGatewayHelper) + daftar nomor tersuppress
 * Nilai disimpan di isp_master.tenants.env_variables — sama dengan Tenant ENV di super admin.
 */
class WaGatewaySettingController extends Controller
{
    use ManagesTenantSettings;

    /** Preset kecepatan blast. Default kode (tanpa ENV) = 'normal'. */
    public const SPEED_PRESETS = [
        'aman'   => ['label' => 'Aman (lambat)', 'NOTIF_DELAY_MIN' => 20, 'NOTIF_DELAY_MAX' => 45, 'NOTIF_LONG_PAUSE_EVERY' => 100,   'NOTIF_LONG_PAUSE_EXTRA' => 300],
        'normal' => ['label' => 'Normal',        'NOTIF_DELAY_MIN' => 10, 'NOTIF_DELAY_MAX' => 30, 'NOTIF_LONG_PAUSE_EVERY' => 1000,  'NOTIF_LONG_PAUSE_EXTRA' => 1000],
        'cepat'  => ['label' => 'Cepat',         'NOTIF_DELAY_MIN' => 5,  'NOTIF_DELAY_MAX' => 8,  'NOTIF_LONG_PAUSE_EVERY' => 10000, 'NOTIF_LONG_PAUSE_EXTRA' => 10000],
    ];

    /** Default WA_SUPPRESS_* sama dengan fallback di WaGatewayHelper::markSendFailure(). */
    public const SUPPRESS_DEFAULTS = [
        'WA_SUPPRESS_FAIL_THRESHOLD'  => 4,
        'WA_SUPPRESS_STEP2_THRESHOLD' => 6,
        'WA_SUPPRESS_STEP3_THRESHOLD' => 8,
        'WA_SUPPRESS_DAYS_STEP1'      => 7,
        'WA_SUPPRESS_DAYS_STEP2'      => 14,
        'WA_SUPPRESS_DAYS_STEP3'      => 30,
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorizeAdmin();
        $tenant = $this->currentTenantRow();

        $speed = [];
        foreach (array_keys(self::SPEED_PRESETS['normal']) as $key) {
            if ($key !== 'label') {
                $speed[$key] = (int) $this->readTenantEnv($tenant, $key, self::SPEED_PRESETS['normal'][$key]);
            }
        }

        $suppress = [];
        foreach (self::SUPPRESS_DEFAULTS as $key => $default) {
            $suppress[$key] = (int) $this->readTenantEnv($tenant, $key, $default);
        }
        $suppressEnabled = filter_var(
            $this->readTenantEnv($tenant, 'WA_SUPPRESS_ENABLED', true),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? true;

        $suppressed = collect();
        $gatewayErrorCount = 0;
        if (Schema::hasTable('wa_suppressions')) {
            $suppressed = DB::table('wa_suppressions')
                ->where('tenant', $this->suppressionTenantKey())
                ->whereNotNull('suppress_until')
                ->where('suppress_until', '>', now())
                ->orderByDesc('suppress_until')
                ->get();

            // Cocokkan nomor ke pelanggan untuk ditampilkan (format nomor di customers bisa 08.. / 62..).
            $numbers = $suppressed->pluck('number')->all();
            $local = array_map(fn ($n) => preg_replace('/^62/', '0', $n), $numbers);
            $customers = \App\Customer::whereIn('phone', array_merge($numbers, $local))
                ->get(['customer_id', 'name', 'phone'])
                ->keyBy(fn ($c) => preg_replace('/^0/', '62', preg_replace('/[\s\-+]/', '', $c->phone)));

            foreach ($suppressed as $row) {
                $row->customer = $customers->get($row->number);
                $row->gateway_error = WaGatewayHelper::isGatewayError($row->last_error);
                $gatewayErrorCount += $row->gateway_error ? 1 : 0;
            }
        }

        return view('wa.settings', [
            'presets'           => self::SPEED_PRESETS,
            'speed'             => $speed,
            'activePreset'      => $this->matchPreset($speed),
            'suppressEnabled'   => $suppressEnabled,
            'suppress'          => $suppress,
            'suppressed'        => $suppressed,
            'gatewayErrorCount' => $gatewayErrorCount,
        ]);
    }

    public function updateSpeed(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'preset'                 => 'required|in:' . implode(',', array_keys(self::SPEED_PRESETS)) . ',custom',
            'NOTIF_DELAY_MIN'        => 'required_if:preset,custom|nullable|integer|min:1|max:600',
            'NOTIF_DELAY_MAX'        => 'required_if:preset,custom|nullable|integer|min:1|max:600|gte:NOTIF_DELAY_MIN',
            'NOTIF_LONG_PAUSE_EVERY' => 'required_if:preset,custom|nullable|integer|min:10|max:100000',
            'NOTIF_LONG_PAUSE_EXTRA' => 'required_if:preset,custom|nullable|integer|min:1|max:100000',
        ]);

        $preset = $request->input('preset');
        $values = $preset === 'custom'
            ? $request->only(['NOTIF_DELAY_MIN', 'NOTIF_DELAY_MAX', 'NOTIF_LONG_PAUSE_EVERY', 'NOTIF_LONG_PAUSE_EXTRA'])
            : array_diff_key(self::SPEED_PRESETS[$preset], ['label' => true]);
        $values = array_map(fn ($v) => (string) (int) $v, $values);

        $tenant = $this->currentTenantRow();
        $this->writeTenantEnv($tenant, $values);
        $this->logChange($request, $tenant, "kecepatan blast -> {$preset} " . json_encode($values));

        return redirect('wa/settings')->with('success', 'Kecepatan blast disimpan: ' . ($preset === 'custom' ? 'Custom' : self::SPEED_PRESETS[$preset]['label']) . '.');
    }

    public function updateSuppress(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'WA_SUPPRESS_ENABLED'         => 'required|boolean',
            'WA_SUPPRESS_FAIL_THRESHOLD'  => 'required|integer|min:2|max:50',
            'WA_SUPPRESS_STEP2_THRESHOLD' => 'required|integer|gt:WA_SUPPRESS_FAIL_THRESHOLD|max:100',
            'WA_SUPPRESS_STEP3_THRESHOLD' => 'required|integer|gt:WA_SUPPRESS_STEP2_THRESHOLD|max:200',
            'WA_SUPPRESS_DAYS_STEP1'      => 'required|integer|min:1|max:365',
            'WA_SUPPRESS_DAYS_STEP2'      => 'required|integer|gte:WA_SUPPRESS_DAYS_STEP1|max:365',
            'WA_SUPPRESS_DAYS_STEP3'      => 'required|integer|gte:WA_SUPPRESS_DAYS_STEP2|max:365',
        ]);

        $values = ['WA_SUPPRESS_ENABLED' => $request->boolean('WA_SUPPRESS_ENABLED') ? '1' : '0'];
        foreach (array_keys(self::SUPPRESS_DEFAULTS) as $key) {
            $values[$key] = (string) (int) $request->input($key);
        }

        $tenant = $this->currentTenantRow();
        $this->writeTenantEnv($tenant, $values);
        $this->logChange($request, $tenant, 'suppress -> ' . json_encode($values));

        return redirect('wa/settings')->with('success', 'Pengaturan nomor gagal disimpan.');
    }

    /** Lepas suppress satu nomor, atau semua nomor yang tersuppress karena gangguan gateway (scope=gateway). */
    public function release(Request $request)
    {
        $this->authorizeAdmin();
        abort_unless(Schema::hasTable('wa_suppressions'), 404);

        $request->validate([
            'scope'  => 'required|in:number,gateway',
            'number' => 'required_if:scope,number|nullable|regex:/^\d{8,15}$/',
        ]);

        $query = DB::table('wa_suppressions')
            ->where('tenant', $this->suppressionTenantKey())
            ->whereNotNull('suppress_until')
            ->where('suppress_until', '>', now());

        if ($request->input('scope') === 'number') {
            $ids = (clone $query)->where('number', $request->input('number'))->pluck('id');
        } else {
            $ids = (clone $query)->get(['id', 'last_error'])
                ->filter(fn ($r) => WaGatewayHelper::isGatewayError($r->last_error))
                ->pluck('id');
        }

        $count = $ids->isEmpty() ? 0 : DB::table('wa_suppressions')->whereIn('id', $ids)->update([
            'suppress_until'       => null,
            'consecutive_failures' => 0,
            'reason'               => null,
            'updated_at'           => now(),
        ]);

        $tenant = $this->currentTenantRow();
        $this->logChange($request, $tenant, 'lepas suppress ' . ($request->input('scope') === 'number' ? $request->input('number') : 'gangguan gateway') . " ({$count} nomor)");

        return redirect('wa/settings')->with('success', "{$count} nomor dilepas dari suppress.");
    }

    /** Sama dengan WaGatewayHelper::tenantKey() — kolom wa_suppressions.tenant berisi domain_name. */
    private function suppressionTenantKey(): string
    {
        $tenant = (string) tenant_config('domain_name', env('DOMAIN_NAME', 'default'));

        return $tenant !== '' ? $tenant : 'default';
    }

    private function matchPreset(array $speed): string
    {
        foreach (self::SPEED_PRESETS as $name => $preset) {
            if (array_diff_key($preset, ['label' => true]) == $speed) {
                return $name;
            }
        }

        return 'custom';
    }

    private function logChange(Request $request, $tenant, string $what): void
    {
        $user = Auth::user();
        Log::channel('notif')->info(sprintf(
            '[WaGatewaySetting] tenant=%s %s oleh user #%s (%s) ip=%s',
            $tenant->domain,
            $what,
            $user->id,
            $user->name,
            $request->ip()
        ));
    }
}
