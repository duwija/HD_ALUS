<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MikrotikSyncFailure;
use App\Customer;
use App\Distrouter;
use Carbon\Carbon;

class MikrotikSyncController extends Controller
{
    /**
     * Tampilkan daftar kegagalan sinkronisasi MikroTik.
     */
    public function index(Request $request)
    {
        $status   = $request->get('status', 'pending');  // pending | resolved | all
        $query    = MikrotikSyncFailure::with('customer')->orderBy('created_at', 'desc');

        if ($status === 'pending') {
            $query->whereIn('status', ['pending', 'retrying']);
        } elseif ($status === 'resolved') {
            $query->where('status', 'resolved');
        }

        $failures     = $query->paginate(50)->appends($request->query());
        $pendingCount = MikrotikSyncFailure::pending()->count();

        return view('mikrotik.sync-failures', compact('failures', 'pendingCount', 'status'));
    }

    /**
     * Retry manual — jalankan langsung sesuai status customer saat ini.
     * active (id_status=2) → enable secret
     * blocked/inactive/inprogress (selain 2) → disable secret
     */
    public function retry($id)
    {
        $failure = MikrotikSyncFailure::findOrFail($id);

        if ($failure->status === 'resolved') {
            return redirect()->back()->with('warning', 'Record ini sudah resolved, tidak perlu retry.');
        }

        if (!$failure->customer_id) {
            return redirect()->back()->with('error', 'Customer tidak ditemukan, tidak bisa retry otomatis.');
        }

        $customer = Customer::withTrashed()->find($failure->customer_id);
        if (!$customer) {
            return redirect()->back()->with('error', "Customer ID {$failure->customer_id} tidak ditemukan di database.");
        }

        $distrouter = Distrouter::withTrashed()->find($customer->id_distrouter);
        if (!$distrouter) {
            return redirect()->back()->with('error', "Distrouter untuk customer [{$customer->name}] tidak ditemukan.");
        }

        // Tentukan action berdasarkan status customer saat ini
        // id_status=2 → active → enable secret
        // id_status=4 (blocked), 3 (inactive), 1 (inprogress), dll → disable secret
        $action = ($customer->id_status == 2) ? 'enable' : 'disable';

        $adminName = auth()->user()->name ?? 'admin';

        // Tandai sebagai retrying + catat di error_message
        $failure->update([
            'status'        => 'retrying',
            'error_message' => $failure->error_message
                . "\n[Manual retry at " . now() . " by {$adminName} | status_customer={$customer->id_status} | action={$action}]",
        ]);

        try {
            if ($action === 'enable') {
                Distrouter::mikrotik_enable(
                    $distrouter->ip, $distrouter->user,
                    $distrouter->password, $distrouter->port,
                    $customer->pppoe
                );
            } else {
                Distrouter::mikrotik_disable(
                    $distrouter->ip, $distrouter->user,
                    $distrouter->password, $distrouter->port,
                    $customer->pppoe
                );
            }

            // Sukses → auto-resolve
            $failure->update([
                'status'      => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => $adminName,
                'notes'       => "Auto-resolved via manual retry | action={$action} | status_customer={$customer->id_status}",
            ]);

            \Log::info("[MANUAL RETRY SUCCESS] action={$action} | Customer: {$customer->name} ({$customer->customer_id}) | pppoe: {$customer->pppoe} | router: {$distrouter->ip} | by {$adminName}");

            return redirect()->back()->with('success',
                "Berhasil {$action} PPPoE [{$customer->pppoe}] untuk customer [{$customer->name}]. Record otomatis resolved."
            );

        } catch (\Exception $e) {
            // Gagal → kembali ke pending, catat error, tambah attempt
            $failure->update([
                'status'        => 'pending',
                'error_message' => $failure->error_message
                    . "\n[Retry FAILED at " . now() . ": " . $e->getMessage() . "]",
                'attempts'      => $failure->attempts + 1,
            ]);

            \Log::error("[MANUAL RETRY FAILED] action={$action} | Customer: {$customer->name} | pppoe: {$customer->pppoe} | router: {$distrouter->ip} | by {$adminName} | Error: " . $e->getMessage());

            return redirect()->back()->with('error',
                "Retry GAGAL untuk [{$customer->pppoe}]: " . $e->getMessage()
            );
        }
    }

    /**
     * Tandai sebagai resolved secara manual (sudah dikerjakan langsung di MikroTik).
     */
    public function resolve(Request $request, $id)
    {
        $failure = MikrotikSyncFailure::findOrFail($id);

        $failure->update([
            'status'       => 'resolved',
            'resolved_at'  => now(),
            'resolved_by'  => auth()->user()->name ?? 'manual',
            'notes'        => $request->input('notes', 'Diselesaikan manual oleh admin'),
        ]);

        return redirect()->back()->with('success', "Record [{$failure->customer_name}] ditandai sebagai resolved.");
    }

    /**
     * Resolve semua pending sekaligus.
     */
    public function resolveAll(Request $request)
    {
        $count = MikrotikSyncFailure::pending()->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => auth()->user()->name ?? 'manual',
            'notes'       => $request->input('notes', 'Bulk resolve oleh admin'),
        ]);

        return redirect()->back()->with('success', "{$count} record ditandai resolved.");
    }
}
