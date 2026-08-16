<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Customer;
use App\Olt;
use App\Http\Controllers\OltController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reboot ONT/ONU pelanggan, dipicu setelah EnableMikrotikJob sukses.
 * Fire-and-forget: hasil reboot (sukses/gagal) tidak divalidasi dan tidak
 * boleh mempengaruhi status enable PPPoE customer yang sudah berhasil.
 */
class RebootOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $customerId;
    protected $tenantDomain;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
        $tenant = app('tenant');
        $this->tenantDomain = $tenant['domain'] ?? null;
    }

    public function handle()
    {
        $this->restoreTenantContext();

        try {
            $customer = Customer::withTrashed()->find($this->customerId);
            if (!$customer || empty($customer->id_olt) || empty($customer->id_onu) || !str_contains($customer->id_onu, ':')) {
                return;
            }

            [$key, $onuValue] = explode(':', $customer->id_onu, 2);
            if ($key === '' || $onuValue === '') {
                return;
            }

            $olt = Olt::find($customer->id_olt);
            if (!$olt) {
                return;
            }

            // Sama persis dengan logika tombol "Reboot" manual di resources/views/customer/show.blade.php
            $vendorCheck = strtolower(($olt->vendor ?? '') . ' ' . ($olt->type ?? '') . ' ' . ($olt->name ?? ''));
            $isHsgq  = str_contains($vendorCheck, 'hsgq');
            $isCdata = str_contains($vendorCheck, 'cdata') || str_contains($vendorCheck, 'fdd');
            $isVsol  = str_contains($vendorCheck, 'vsol');
            $isC600  = str_contains($vendorCheck, 'c600') || str_contains($vendorCheck, 'c620') || str_contains($vendorCheck, 'c650');

            if ($isHsgq || $isCdata || $isVsol) {
                $portId = $key;
            } elseif ($isC600) {
                $portId = str_replace('/', '_', $key);
            } else {
                $portId = config('zteframeslotportid')[$key] ?? null;
            }

            if ($portId === null) {
                Log::warning("[RebootOnuJob] portId tidak ditemukan untuk Customer ID {$customer->id} (id_onu={$customer->id_onu})");
                return;
            }

            Log::info("[RebootOnuJob] Reboot ONU Customer ID {$customer->id} ({$customer->pppoe}) — OLT {$customer->id_olt}, port {$portId}, ONU {$onuValue}");

            app(OltController::class)->onureboot($customer->id_olt, $portId, $onuValue);

        } catch (\Throwable $e) {
            // Tidak perlu retry / tidak boleh gagalkan job lain — cukup dicatat.
            Log::warning('[RebootOnuJob] Gagal reboot ONU: ' . $e->getMessage());
        }
    }

    private function restoreTenantContext(): void
    {
        if (empty($this->tenantDomain)) return;

        try {
            $tenant = \App\Services\TenantDatabaseSwitcher::fetchTenantArray($this->tenantDomain);
            if (!$tenant) return;

            app()->instance('tenant', $tenant);

            if (!\App\Services\TenantDatabaseSwitcher::switchTo($tenant)) {
                Log::error("[TENANT] RebootOnuJob gagal restore context untuk {$this->tenantDomain} (switch DB gagal setelah retry).");
                return;
            }
        } catch (\Exception $e) {
            Log::error('[TENANT] RebootOnuJob gagal restore context: ' . $e->getMessage());
        }
    }
}
