<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Tenant;
use Illuminate\Http\Request;

class AdminMigrateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Show the migration management page.
     */
    public function index()
    {
        $tenants = Tenant::orderBy('id')->get(['id', 'domain', 'rescode', 'db_database', 'is_active']);
        return view('admin.migrate.index', compact('tenants'));
    }

    /**
     * Run migrations for all (or a specific) tenant.
     * Called via AJAX POST from the UI.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function run(Request $request)
    {
        $tenantFilter    = $request->input('tenant');          // domain/rescode/id or null
        $includeInactive = $request->boolean('include_inactive', false);
        $pretend         = $request->boolean('pretend', false);

        // Build tenant query
        $query = Tenant::query()
            ->when(!$includeInactive, fn($q) => $q->where('is_active', true))
            ->when($tenantFilter, function ($q) use ($tenantFilter) {
                $q->where(function ($sub) use ($tenantFilter) {
                    $sub->where('domain', $tenantFilter)
                        ->orWhere('rescode', $tenantFilter);
                    if (is_numeric($tenantFilter)) {
                        $sub->orWhere('id', (int) $tenantFilter);
                    }
                });
            })
            ->orderBy('id');

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada tenant yang ditemukan.',
                'results' => [],
            ], 422);
        }

        $results = [];

        foreach ($tenants as $tenant) {
            try {
                // Build artisan command arguments
                $args = ['tenant:migrate-all', '--tenant' => (string) $tenant->id, '--no-interaction' => true];
                
                if ($pretend) {
                    $args['--pretend'] = true;
                }
                
                // Capture output buffer
                $output = '';
                $exitCode = \Artisan::call('tenant:migrate-all', [
                    '--tenant' => (string) $tenant->id,
                    '--no-interaction' => true,
                    '--pretend' => $pretend ? 'true' : 'false',
                ]);
                
                $output = \Artisan::output();

                if ($exitCode !== 0) {
                    throw new \RuntimeException($output ?: 'Exit code ' . $exitCode);
                }

                $results[] = [
                    'tenant'   => $tenant->domain,
                    'database' => $tenant->db_database,
                    'status'   => 'success',
                    'output'   => $output ?: 'Nothing to migrate.',
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'tenant'   => $tenant->domain,
                    'database' => $tenant->db_database,
                    'status'   => 'error',
                    'output'   => $e->getMessage(),
                ];
            }
        }

        $failed = collect($results)->where('status', 'error')->count();

        return response()->json([
            'success' => $failed === 0,
            'message' => $failed === 0
                ? 'Semua migrasi berhasil.'
                : "{$failed} tenant gagal dimigrasi.",
            'results' => $results,
        ]);
    }
}
