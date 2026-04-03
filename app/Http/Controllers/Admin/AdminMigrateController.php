<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

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

        $results     = [];
        $php         = PHP_BINARY;
        $artisan     = base_path('artisan');

        foreach ($tenants as $tenant) {
            if (empty($tenant->db_database) || empty($tenant->db_username)) {
                $results[] = [
                    'tenant'   => $tenant->domain,
                    'database' => $tenant->db_database ?? '-',
                    'status'   => 'skipped',
                    'output'   => 'Konfigurasi DB tidak lengkap.',
                ];
                continue;
            }

            $cmd = [$php, $artisan, 'migrate', '--database=mysql', '--force', '--no-interaction'];

            if ($pretend) {
                $cmd[] = '--pretend';
            }

            $env = array_merge($_ENV, [
                'DB_HOST'     => $tenant->db_host     ?: '127.0.0.1',
                'DB_PORT'     => (string) ($tenant->db_port ?: '3306'),
                'DB_DATABASE' => $tenant->db_database,
                'DB_USERNAME' => $tenant->db_username,
                'DB_PASSWORD' => $tenant->db_password ?? '',
            ]);

            $process = new Process($cmd, base_path(), $env, null, 120);

            try {
                $process->run();
                $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());
                $output = trim($output);

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException($output ?: 'Exit code ' . $process->getExitCode());
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
