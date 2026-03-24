<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TenantManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display tenant list
     */
    public function index()
    {
        $tenants = Tenant::orderBy('created_at', 'desc')->get();
        return view('tenants.index', compact('tenants'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('tenants.create');
    }

    /**
     * Store new tenant
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'domain' => 'required|unique:isp_master.tenants,domain',
            'app_name' => 'required',
            'rescode' => 'required|min:2|max:10|unique:isp_master.tenants,rescode',
            'db_database' => 'required',
            'db_username' => 'required',
            'db_password' => 'required',
        ], [
            'domain.required' => 'Domain wajib diisi.',
            'domain.unique' => 'Domain sudah digunakan oleh tenant lain.',
            'app_name.required' => 'App Name wajib diisi.',
            'rescode.required' => 'Rescode wajib diisi.',
            'rescode.min' => 'Rescode minimal 2 karakter.',
            'rescode.max' => 'Rescode maksimal 10 karakter.',
            'rescode.unique' => 'Rescode sudah digunakan oleh tenant lain.',
            'db_database.required' => 'DB Name wajib diisi.',
            'db_username.required' => 'DB Username wajib diisi.',
            'db_password.required' => 'DB Password wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Create database if requested
            if ($request->create_database) {
                DB::statement("CREATE DATABASE IF NOT EXISTS {$request->db_database} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                // ── Clone structure from `kencana` (model database) ──────────
                // Selalu gunakan `kencana` sebagai template, bukan Tenant::first()
                $sourceDb   = 'kencana';
                $rootUser   = env('DB_USERNAME', 'root');
                $rootPass   = env('DB_PASSWORD', '');
                $dbHost     = env('DB_HOST', '127.0.0.1');
                $dumpFile   = storage_path('app/temp_structure_' . $request->db_database . '.sql');

                // Export structure only (no data) dari database kencana
                $dumpCmd = sprintf(
                    'mysqldump -h%s -u%s -p%s --no-data --routines --triggers %s > %s 2>&1',
                    escapeshellarg($dbHost),
                    escapeshellarg($rootUser),
                    escapeshellarg($rootPass),
                    escapeshellarg($sourceDb),
                    escapeshellarg($dumpFile)
                );
                exec($dumpCmd, $dumpOutput, $dumpReturn);

                if ($dumpReturn !== 0 || !file_exists($dumpFile) || filesize($dumpFile) < 100) {
                    throw new \Exception('Gagal export struktur dari database kencana: ' . implode(' ', $dumpOutput));
                }

                // Import ke database baru
                $importCmd = sprintf(
                    'mysql -h%s -u%s -p%s %s < %s 2>&1',
                    escapeshellarg($dbHost),
                    escapeshellarg($rootUser),
                    escapeshellarg($rootPass),
                    escapeshellarg($request->db_database),
                    escapeshellarg($dumpFile)
                );
                exec($importCmd, $importOutput, $importReturn);

                @unlink($dumpFile);

                if ($importReturn !== 0) {
                    throw new \Exception('Gagal import struktur ke database baru: ' . implode(' ', $importOutput));
                }

                \Log::info("Tenant DB created: {$request->db_database} cloned from {$sourceDb}");
            }

            // Create tenant
            $tenant = Tenant::create([
                'domain' => $request->domain,
                'app_name' => $request->app_name,
                'signature' => $request->signature ?? $request->app_name,
                'rescode' => strtoupper($request->rescode),
                'db_host' => $request->db_host ?? '127.0.0.1',
                'db_port' => $request->db_port ?? '3306',
                'db_database' => $request->db_database,
                'db_username' => $request->db_username,
                'db_password' => $request->db_password,
                'mail_from' => $request->mail_from ?? 'admin@' . $request->domain,
                'features' => [
                    'accounting' => $request->has('feature_accounting'),
                    'ticketing' => $request->has('feature_ticketing'),
                    'whatsapp' => $request->has('feature_whatsapp'),
                    'payment_gateway' => $request->has('feature_payment'),
                ],
                'env_variables' => $this->processEnvVariables($request),
                'is_active' => true,
                'notes' => $request->notes,
            ]);

            // Create storage directories
            $rescode = strtoupper($request->rescode);
            $basePath = base_path();
            
            // Create directories with proper permissions
            $directories = [
                "storage/tenants/{$rescode}/logs",
                "storage/tenants/{$rescode}/app/public",
                "public/tenants/{$rescode}/img",
                "public/tenants/{$rescode}/storage",
                "public/tenants/{$rescode}/upload",
                "public/tenants/{$rescode}/backup",
                "public/tenants/{$rescode}/users",
            ];

            $directoriesCreated = [];
            $directoriesFailed = [];

            foreach ($directories as $dir) {
                $fullPath = "{$basePath}/{$dir}";
                if (!is_dir($fullPath)) {
                    // Try using PHP native functions first
                    if (@mkdir($fullPath, 0777, true)) {
                        $directoriesCreated[] = $dir;
                        // Try to set permissions
                        @chmod($fullPath, 0777);
                    } else {
                        // Fallback to shell command
                        $output = [];
                        $returnVar = 0;
                        exec("mkdir -p " . escapeshellarg($fullPath) . " 2>&1", $output, $returnVar);
                        
                        if ($returnVar === 0 || is_dir($fullPath)) {
                            exec("chmod 777 " . escapeshellarg($fullPath) . " 2>&1");
                            $directoriesCreated[] = $dir;
                        } else {
                            $directoriesFailed[] = $dir;
                            \Log::error("Failed to create directory: {$dir}", ['output' => $output]);
                        }
                    }
                }
            }

            // Create log file
            $logFile = "{$basePath}/storage/tenants/{$rescode}/logs/laravel.log";
            if (!file_exists($logFile)) {
                if (@touch($logFile)) {
                    @chmod($logFile, 0666);
                } else {
                    exec("touch " . escapeshellarg($logFile) . " 2>&1");
                    exec("chmod 666 " . escapeshellarg($logFile) . " 2>&1");
                }
            }

            // Copy default favicon to tenant img directory
            $defaultFavicon = "{$basePath}/public/favicon.png";
            $tenantFavicon = "{$basePath}/public/tenants/{$rescode}/img/favicon.png";
            if (file_exists($defaultFavicon) && !file_exists($tenantFavicon)) {
                if (@copy($defaultFavicon, $tenantFavicon)) {
                    @chmod($tenantFavicon, 0666);
                } else {
                    exec("cp " . escapeshellarg($defaultFavicon) . " " . escapeshellarg($tenantFavicon) . " 2>&1");
                    exec("chmod 666 " . escapeshellarg($tenantFavicon) . " 2>&1");
                }
            }

            // Log summary
            if (!empty($directoriesCreated)) {
                \Log::info("Tenant {$rescode} directories created: " . implode(', ', $directoriesCreated));
            }
            if (!empty($directoriesFailed)) {
                \Log::warning("Tenant {$rescode} directories failed: " . implode(', ', $directoriesFailed));
            }

            return redirect()->route('admin.tenants.index')
                ->with('success', "Tenant {$tenant->app_name} berhasil dibuat! Jangan lupa setup nginx config dan SSL certificate.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $tenant = Tenant::findOrFail($id);
        return view('tenants.edit', compact('tenant'));
    }

    /**
     * Update tenant
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'domain' => 'required|unique:isp_master.tenants,domain,' . $id,
            'app_name' => 'required',
            'signature' => 'nullable',
            'mail_from' => 'nullable|email',
            'db_host' => 'nullable',
            'db_port' => 'nullable|numeric',
            'db_database' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',
            'notes' => 'nullable',
            'feature_accounting' => 'nullable|boolean',
            'feature_ticketing' => 'nullable|boolean',
            'feature_whatsapp' => 'nullable|boolean',
            'feature_payment' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ], [
            'domain.required' => 'Domain wajib diisi.',
            'domain.unique' => 'Domain sudah digunakan oleh tenant lain.',
            'app_name.required' => 'App Name wajib diisi.',
            'mail_from.email' => 'Format email tidak valid.',
            'db_port.numeric' => 'DB Port harus berupa angka.',
            'db_database.required' => 'DB Name wajib diisi.',
            'db_username.required' => 'DB Username wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $tenant->update([
                'domain' => $request->domain,
                'app_name' => $request->app_name,
                'signature' => $request->signature ?? $request->app_name,
                // Rescode tidak bisa diubah, jadi tidak di-update
                'db_host' => $request->db_host ?? '127.0.0.1',
                'db_port' => $request->db_port ?? '3306',
                'db_database' => $request->db_database,
                'db_username' => $request->db_username,
                'mail_from' => $request->mail_from,
                'features' => [
                    'accounting' => $request->has('feature_accounting'),
                    'ticketing' => $request->has('feature_ticketing'),
                    'whatsapp' => $request->has('feature_whatsapp'),
                    'payment_gateway' => $request->has('feature_payment'),
                ],
                'env_variables' => $this->processEnvVariables($request),
                'is_active' => $request->has('is_active'),
                'notes' => $request->notes,
            ]);

            // Update password if provided
            if ($request->filled('db_password')) {
                $tenant->db_password = $request->db_password;
                $tenant->save();
            }

            return redirect()->route('admin.tenants.index')
                ->with('success', "Tenant {$tenant->app_name} berhasil diupdate!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete tenant
     */
    public function destroy($id)
    {
        try {
            $tenant = Tenant::findOrFail($id);
            $appName = $tenant->app_name;
            
            $tenant->delete();

            return redirect()->route('admin.tenants.index')
                ->with('success', "Tenant {$appName} berhasil dihapus!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Toggle tenant status
     */
    public function toggleStatus($id)
    {
        try {
            $tenant = Tenant::findOrFail($id);
            $tenant->is_active = !$tenant->is_active;
            $tenant->save();

            $status = $tenant->is_active ? 'diaktifkan' : 'dinonaktifkan';
            
            return redirect()->back()
                ->with('success', "Tenant {$tenant->app_name} berhasil {$status}!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Show tenant details
     */
    public function show($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        // Configure tenant database connection
        \Config::set('database.connections.tenant_temp', [
            'driver' => 'mysql',
            'host' => $tenant->db_host ?? '127.0.0.1',
            'port' => $tenant->db_port ?? '3306',
            'database' => $tenant->db_database,
            'username' => $tenant->db_username,
            'password' => $tenant->db_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);
        
        // Get customer statistics by status from tenant database
        $customerStats = [
            'total' => \DB::connection('tenant_temp')->table('customers')->count(),
            'active' => \DB::connection('tenant_temp')->table('customers')->where('id_status', 1)->count(),
            'potential' => \DB::connection('tenant_temp')->table('customers')->where('id_status', 2)->count(),
            'block' => \DB::connection('tenant_temp')->table('customers')->where('id_status', 3)->count(),
            'inactive' => \DB::connection('tenant_temp')->table('customers')->where('id_status', 4)->count(),
            'company_properti' => \DB::connection('tenant_temp')->table('customers')->where('id_status', 5)->count(),
            'deleted' => \DB::connection('tenant_temp')->table('customers')->whereNotNull('deleted_at')->count(),
        ];
        
        // Purge temporary connection
        \DB::purge('tenant_temp');
        
        // Check storage paths
        $rescode = $tenant->rescode;
        $basePath = base_path();
        
        $storagePaths = [
            'private' => [
                'base' => "storage/tenants/{$rescode}",
                'logs' => "storage/tenants/{$rescode}/logs",
                'app' => "storage/tenants/{$rescode}/app/public",
            ],
            'public' => [
                'base' => "public/tenants/{$rescode}",
                'storage' => "public/tenants/{$rescode}/storage",
                'upload' => "public/tenants/{$rescode}/upload",
                'customerfiles' => "public/tenants/{$rescode}/upload/customerfiles",
                'backup' => "public/tenants/{$rescode}/backup",
                'users' => "public/tenants/{$rescode}/users",
                'img' => "public/tenants/{$rescode}/img",
            ]
        ];
        
        // Check if directories exist and get additional info
        $storageStatus = [];
        foreach (['private', 'public'] as $type) {
            foreach ($storagePaths[$type] as $key => $path) {
                $fullPath = "{$basePath}/{$path}";
                $exists = is_dir($fullPath);
                $writable = $exists && is_writable($fullPath);
                $fileCount = 0;
                $totalSize = 0;
                
                // Count files and calculate size if directory exists
                if ($exists) {
                    try {
                        $files = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                            \RecursiveIteratorIterator::SELF_FIRST
                        );
                        
                        foreach ($files as $file) {
                            if ($file->isFile()) {
                                $fileCount++;
                                $totalSize += $file->getSize();
                            }
                        }
                    } catch (\Exception $e) {
                        // Silent fail if can't read directory
                    }
                }
                
                $storageStatus[$type][$key] = [
                    'path' => $path,
                    'exists' => $exists,
                    'writable' => $writable,
                    'file_count' => $fileCount,
                    'total_size' => $totalSize,
                    'size_formatted' => $this->formatBytes($totalSize),
                ];
            }
        }
        
        return view('tenants.show', compact('tenant', 'storagePaths', 'storageStatus', 'customerStats'));
    }
    
    /**
     * Switch to tenant database
     */
    private function switchToTenantDatabase($tenant)
    {
        \Config::set('database.connections.mysql.host', $tenant->db_host ?? '127.0.0.1');
        \Config::set('database.connections.mysql.port', $tenant->db_port ?? '3306');
        \Config::set('database.connections.mysql.database', $tenant->db_database);
        \Config::set('database.connections.mysql.username', $tenant->db_username);
        \Config::set('database.connections.mysql.password', $tenant->db_password);
        
        \DB::purge('mysql');
        \DB::reconnect('mysql');
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Upload tenant assets (favicon, logos)
     */
    public function uploadAssets(Request $request, $id)
    {
        // Debug logging
        \Log::info('Upload Assets Called', [
            'tenant_id' => $id,
            'has_files' => $request->hasFile('favicon') || $request->hasFile('login_logo') || $request->hasFile('invoice_logo'),
            'all_input' => $request->all(),
            'files' => [
                'favicon' => $request->hasFile('favicon'),
                'login_logo' => $request->hasFile('login_logo'),
                'invoice_logo' => $request->hasFile('invoice_logo'),
            ]
        ]);
        
        $tenant = Tenant::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'favicon' => 'nullable|file|mimes:png,ico,x-icon|max:2048',
            'login_logo' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
            'invoice_logo' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
        ], [
            'favicon.file' => 'Favicon harus berupa file.',
            'favicon.mimes' => 'Favicon harus berformat PNG atau ICO.',
            'favicon.max' => 'Ukuran favicon maksimal 2MB.',
            'login_logo.file' => 'Login logo harus berupa file.',
            'login_logo.mimes' => 'Login logo harus berformat PNG, JPG, atau JPEG.',
            'login_logo.max' => 'Ukuran login logo maksimal 2MB.',
            'invoice_logo.file' => 'Invoice logo harus berupa file.',
            'invoice_logo.mimes' => 'Invoice logo harus berformat PNG, JPG, atau JPEG.',
            'invoice_logo.max' => 'Ukuran invoice logo maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            \Log::info('Upload validation failed', [
                'errors' => $validator->errors()->toArray(),
                'files_info' => [
                    'favicon' => $request->hasFile('favicon') ? [
                        'original_name' => $request->file('favicon')->getClientOriginalName(),
                        'mime' => $request->file('favicon')->getMimeType(),
                        'size' => $request->file('favicon')->getSize(),
                        'extension' => $request->file('favicon')->getClientOriginalExtension(),
                    ] : null,
                ]
            ]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $uploadPath = public_path("tenants/{$tenant->rescode}/img");
            
            \Log::info('Upload attempt details', [
                'upload_path' => $uploadPath,
                'path_exists' => is_dir($uploadPath),
                'path_writable' => is_writable($uploadPath),
                'has_favicon' => $request->hasFile('favicon'),
                'has_login_logo' => $request->hasFile('login_logo'),
                'has_invoice_logo' => $request->hasFile('invoice_logo'),
                'favicon_valid' => $request->hasFile('favicon') ? $request->file('favicon')->isValid() : false,
                'login_valid' => $request->hasFile('login_logo') ? $request->file('login_logo')->isValid() : false,
                'invoice_valid' => $request->hasFile('invoice_logo') ? $request->file('invoice_logo')->isValid() : false,
            ]);
            
            // Create directory if not exists
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0777, true);
            }

            // Ensure directory is writable
            @chmod($uploadPath, 0777);

            $uploadedFiles = [];

            // Upload Favicon
            if ($request->hasFile('favicon') && $request->file('favicon')->isValid()) {
                $file = $request->file('favicon');
                $tempPath = $file->getRealPath();
                $targetPath = "{$uploadPath}/favicon.png";
                
                // Remove old file if exists
                if (file_exists($targetPath)) {
                    @unlink($targetPath);
                }
                
                // Copy file using system command for better permission handling
                $result = @copy($tempPath, $targetPath);
                if ($result) {
                    @chmod($targetPath, 0666);
                    $uploadedFiles[] = 'Favicon';
                }
            }

            // Upload Login Logo
            if ($request->hasFile('login_logo') && $request->file('login_logo')->isValid()) {
                $file = $request->file('login_logo');
                $tempPath = $file->getRealPath();
                $targetPath = "{$uploadPath}/trikamedia.png";
                
                if (file_exists($targetPath)) {
                    @unlink($targetPath);
                }
                
                $result = @copy($tempPath, $targetPath);
                if ($result) {
                    @chmod($targetPath, 0666);
                    $uploadedFiles[] = 'Login Logo';
                }
            }

            // Upload Invoice Logo
            if ($request->hasFile('invoice_logo') && $request->file('invoice_logo')->isValid()) {
                $file = $request->file('invoice_logo');
                $tempPath = $file->getRealPath();
                $targetPath = "{$uploadPath}/logoinv.png";
                
                if (file_exists($targetPath)) {
                    @unlink($targetPath);
                }
                
                $result = @copy($tempPath, $targetPath);
                if ($result) {
                    @chmod($targetPath, 0666);
                    $uploadedFiles[] = 'Invoice Logo';
                }
            }

            if (count($uploadedFiles) > 0) {
                $message = 'Berhasil upload: ' . implode(', ', $uploadedFiles);
                return redirect()->route('admin.tenants.show', $tenant->id)
                    ->with('success', $message);
            } else {
                return redirect()->route('admin.tenants.show', $tenant->id)
                    ->with('info', 'Tidak ada file yang diupload.');
            }

        } catch (\Exception $e) {
            \Log::error("Upload assets error for tenant {$tenant->rescode}: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Gagal upload assets: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Process environment variables from request
     */
    private function processEnvVariables($request)
    {
        $envVariables = [];
        
        if ($request->has('env_variables_keys') && $request->has('env_variables_values')) {
            $keys = $request->input('env_variables_keys', []);
            $values = $request->input('env_variables_values', []);
            
            foreach ($keys as $index => $key) {
                // Skip empty keys
                if (empty(trim($key))) {
                    continue;
                }
                
                // Clean the key but preserve case
                $cleanKey = trim($key);
                $value = $values[$index] ?? '';
                
                $envVariables[$cleanKey] = $value;
            }
        }
        
        return $envVariables;
    }

    /**
     * Show payment gateway configuration form (new: reads from payment_gateways table)
     */
    public function paymentGatewayConfig($id)
    {
        $tenant = Tenant::findOrFail($id);

        $this->connectTenantTemp($tenant);

        // Ambil semua gateway di tenant DB
        $gateways = \DB::connection('tenant_temp')
            ->table('payment_gateways')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('provider');

        \DB::purge('tenant_temp');

        return view('tenants.payment-gateway-config', compact('tenant', 'gateways'));
    }

    /**
     * Update payment gateway configuration (new: saves to payment_gateways table)
     */
    public function updatePaymentGatewayConfig(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $this->connectTenantTemp($tenant);

        $providers = $request->input('providers', []);

        foreach ($providers as $provider => $data) {
            $row = \DB::connection('tenant_temp')
                ->table('payment_gateways')
                ->where('provider', $provider)
                ->first();

            if ($row) {
                $updateData = [
                    'enabled'    => (int)($data['enabled'] ?? 0) === 1 ? 1 : 0,
                    'fee_type'   => $data['fee_type']   ?? 'none',
                    'fee_amount' => $data['fee_amount'] ?? 0,
                    'fee_label'  => $data['fee_label']  ?? 'Biaya Transaksi',
                    'sort_order' => $data['sort_order'] ?? 99,
                    'updated_at' => now(),
                ];

                // Gabung settings baru ke settings lama (agar field yg tidak dikirim tidak hilang)
                if (!empty($data['settings']) && is_array($data['settings'])) {
                    $oldSettings  = json_decode($row->settings ?? '{}', true) ?? [];
                    $newSettings  = array_merge($oldSettings, $data['settings']);
                    // Normalkan tipe boolean sandbox
                    if (isset($newSettings['sandbox'])) {
                        $newSettings['sandbox'] = (bool)(int)$newSettings['sandbox'];
                    }
                    $updateData['settings'] = json_encode($newSettings);
                }

                \DB::connection('tenant_temp')
                    ->table('payment_gateways')
                    ->where('provider', $provider)
                    ->update($updateData);
            }
        }

        \DB::purge('tenant_temp');

        return redirect()->back()->with('success', 'Konfigurasi payment gateway berhasil disimpan.');
    }

    // ── PAYMENT POINTS (Bumdes / Lokasi Bayar) ────────────────────────────────

    /**
     * Tampilkan daftar payment point (merchants) tenant
     */
    public function paymentPoints($id)
    {
        $tenant = Tenant::findOrFail($id);
        $this->connectTenantTemp($tenant);
        $points = \DB::connection('tenant_temp')
            ->table('merchants')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
        \DB::purge('tenant_temp');
        return view('tenants.payment-points', compact('tenant', 'points'));
    }

    /**
     * Simpan payment point baru
     */
    public function storePaymentPoint(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        $request->validate([
            'name'    => 'required|string|max:30',
            'address' => 'required|string',
            'phone'   => 'nullable|string|max:20',
        ]);
        $this->connectTenantTemp($tenant);
        \DB::connection('tenant_temp')->table('merchants')->insert([
            'name'          => $request->name,
            'contact_name'  => $request->contact_name ?? '-',
            'id_user'       => 0,
            'payment_point' => 1,
            'phone'         => $request->phone ?? '',
            'address'       => $request->address,
            'coordinate'    => $request->coordinate ?? '',
            'description'   => $request->description ?? '',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        \DB::purge('tenant_temp');
        return redirect()->route('admin.tenants.payment-points', $id)->with('success', 'Lokasi pembayaran berhasil ditambahkan.');
    }

    /**
     * Update payment point
     */
    public function updatePaymentPoint(Request $request, $id, $pointId)
    {
        $tenant = Tenant::findOrFail($id);
        $request->validate([
            'name'    => 'required|string|max:30',
            'address' => 'required|string',
        ]);
        $this->connectTenantTemp($tenant);
        \DB::connection('tenant_temp')->table('merchants')
            ->where('id', $pointId)
            ->whereNull('deleted_at')
            ->update([
                'name'         => $request->name,
                'contact_name' => $request->contact_name ?? '-',
                'phone'        => $request->phone ?? '',
                'address'      => $request->address,
                'coordinate'   => $request->coordinate ?? '',
                'description'  => $request->description ?? '',
                'payment_point'=> 1,
                'updated_at'   => now(),
            ]);
        \DB::purge('tenant_temp');
        return redirect()->route('admin.tenants.payment-points', $id)->with('success', 'Lokasi pembayaran berhasil diupdate.');
    }

    /**
     * Hapus (soft delete) payment point
     */
    public function destroyPaymentPoint($id, $pointId)
    {
        $tenant = Tenant::findOrFail($id);
        $this->connectTenantTemp($tenant);
        \DB::connection('tenant_temp')->table('merchants')
            ->where('id', $pointId)
            ->update(['deleted_at' => now()]);
        \DB::purge('tenant_temp');
        return redirect()->route('admin.tenants.payment-points', $id)->with('success', 'Lokasi pembayaran dihapus.');
    }

    /**
     * Helper: configure temporary tenant DB connection
     */
    private function connectTenantTemp(Tenant $tenant): void
    {
        \Config::set('database.connections.tenant_temp', [
            'driver'    => 'mysql',
            'host'      => $tenant->db_host     ?? '127.0.0.1',
            'port'      => $tenant->db_port     ?? '3306',
            'database'  => $tenant->db_database,
            'username'  => $tenant->db_username,
            'password'  => $tenant->db_password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);
        \DB::purge('tenant_temp');
    }
    
    /**
     * Backup tenant database manually
     */
    public function backupDatabase($id)
    {
        try {
            $tenant = Tenant::findOrFail($id);
            
            $rescode = $tenant->rescode;
            $dbName = $tenant->db_database;
            $dbUser = $tenant->db_username;
            $dbPass = $tenant->db_password;
            $dbHost = $tenant->db_host ?? '127.0.0.1';
            $dbPort = $tenant->db_port ?? '3306';
            
            // Create backup directory if not exists
            $backupDir = public_path("tenants/{$rescode}/backup");
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
                @chown($backupDir, 'apache');
                @chgrp($backupDir, 'apache');
            }
            
            // Generate filename with timestamp
            $timestamp = date('Y-m-d_His');
            $filename = "{$dbName}_{$timestamp}.sql";
            $filepath = "{$backupDir}/{$filename}";
            
            // Build mysqldump command
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s 2>&1',
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($filepath)
            );
            
            // Execute backup
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                \Log::error("Database backup failed for {$rescode}", [
                    'output' => $output,
                    'return_code' => $returnVar
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Backup failed'
                ], 500);
            }
            
            // Fix file permissions
            if (file_exists($filepath)) {
                chmod($filepath, 0644);
                @chown($filepath, 'apache');
                @chgrp($filepath, 'apache');
                
                $filesize = filesize($filepath);
                $filesizeMB = round($filesize / 1024 / 1024, 2);
                
                \Log::info("Database backup success for {$rescode}", [
                    'filename' => $filename,
                    'size' => $filesizeMB . ' MB'
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => "Database berhasil di-backup ({$filesizeMB} MB)",
                    'filename' => $filename,
                    'size' => $filesizeMB
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Backup file not created'
            ], 500);
            
        } catch (\Exception $e) {
            \Log::error('Backup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show list of backup files for tenant
     */
    public function backups($id)
    {
        $tenant = Tenant::findOrFail($id);
        $rescode = $tenant->rescode;
        $backupDir = public_path("tenants/{$rescode}/backup");
        
        $files = [];
        
        if (is_dir($backupDir)) {
            $fileList = \File::files($backupDir);
            
            foreach ($fileList as $file) {
                $files[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'size_formatted' => $this->formatBytes($file->getSize()),
                    'modified' => $file->getMTime(),
                    'modified_formatted' => date('Y-m-d H:i:s', $file->getMTime()),
                    'path' => $file->getPathname(),
                ];
            }
            
            // Sort by modified time (newest first)
            usort($files, function($a, $b) {
                return $b['modified'] - $a['modified'];
            });
        }
        
        return view('tenants.backups', compact('tenant', 'files'));
    }
    
    /**
     * Download backup file
     */
    public function downloadBackup($id, $filename)
    {
        $tenant = Tenant::findOrFail($id);
        $rescode = $tenant->rescode;
        $filepath = public_path("tenants/{$rescode}/backup/{$filename}");
        
        if (!file_exists($filepath)) {
            abort(404, 'Backup file not found');
        }
        
        return response()->download($filepath, $filename);
    }
    
    /**
     * Delete backup file
     */
    public function deleteBackup($id, $filename)
    {
        try {
            $tenant = Tenant::findOrFail($id);
            $rescode = $tenant->rescode;
            $filepath = public_path("tenants/{$rescode}/backup/{$filename}");
            
            if (!file_exists($filepath)) {
                return redirect()->back()->with('error', 'Backup file not found');
            }
            
            unlink($filepath);
            
            \Log::info("Backup file deleted", [
                'tenant' => $rescode,
                'filename' => $filename
            ]);
            
            return redirect()->back()->with('success', 'Backup file deleted successfully');
            
        } catch (\Exception $e) {
            \Log::error('Delete backup error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Show customers list for tenant
     */
    public function customers($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        // Configure tenant database connection
        \Config::set('database.connections.tenant_temp', [
            'driver' => 'mysql',
            'host' => $tenant->db_host ?? '127.0.0.1',
            'port' => $tenant->db_port ?? '3306',
            'database' => $tenant->db_database,
            'username' => $tenant->db_username,
            'password' => $tenant->db_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);
        
        // Get filter data from tenant database
        $statuses = \DB::connection('tenant_temp')->table('statuscustomers')->pluck('name', 'id');
        $plans = \DB::connection('tenant_temp')->table('plans')->pluck('name', 'id');
        $merchants = \DB::connection('tenant_temp')->table('merchants')->pluck('name', 'id');
        
        // Purge temporary connection
        \DB::purge('tenant_temp');
        
        return view('tenants.customers', compact('tenant', 'statuses', 'plans', 'merchants'));
    }
    
    /**
     * Get customer data for DataTables
     */
    public function customersData(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        
        // Configure tenant database connection
        \Config::set('database.connections.tenant_temp', [
            'driver' => 'mysql',
            'host' => $tenant->db_host ?? '127.0.0.1',
            'port' => $tenant->db_port ?? '3306',
            'database' => $tenant->db_database,
            'username' => $tenant->db_username,
            'password' => $tenant->db_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);
        
        // Build customer query
        $customerQuery = \DB::connection('tenant_temp')
            ->table('customers')
            ->select(
                'customers.id',
                'customers.customer_id',
                'customers.name',
                'customers.address',
                'customers.id_merchant',
                'customers.billing_start',
                'customers.isolir_date',
                'customers.id_plan',
                'customers.id_status',
                'customers.notification'
            )
            ->whereNull('customers.deleted_at');
        
        // Calculate customer counts by status
        $customerCounts = \DB::connection('tenant_temp')
            ->table('customers')
            ->select('id_status', \DB::raw('count(*) as total'))
            ->whereNull('deleted_at')
            ->when(!empty($request->filter) && !empty($request->parameter), function ($query) use ($request) {
                if ($request->filter === 'isolir_date') {
                    $query->where($request->filter, $request->parameter);
                } else {
                    $query->where($request->filter, 'LIKE', "%{$request->parameter}%");
                }
            })
            ->when(!empty($request->id_status), function ($query) use ($request) {
                $query->where('id_status', $request->id_status);
            })
            ->when(!empty($request->id_plan), function ($query) use ($request) {
                $query->where('id_plan', $request->id_plan);
            })
            ->when(!empty($request->id_merchant), function ($query) use ($request) {
                $query->where('id_merchant', $request->id_merchant);
            })
            ->groupBy('id_status')
            ->pluck('total', 'id_status');
        
        $stats = [
            'total' => array_sum($customerCounts->toArray()),
            'potential' => $customerCounts[1] ?? 0,
            'active' => $customerCounts[2] ?? 0,
            'inactive' => $customerCounts[3] ?? 0,
            'block' => $customerCounts[4] ?? 0,
            'company_properti' => $customerCounts[5] ?? 0,
        ];
        
        // Apply filters
        if (!empty($request->filter) && !empty($request->parameter)) {
            if ($request->filter === 'isolir_date') {
                $customerQuery->where("customers." . $request->filter, $request->parameter);
            } else {
                $customerQuery->where("customers." . $request->filter, 'LIKE', "%" . $request->parameter . "%");
            }
        }
        
        if (!empty($request->id_status)) {
            $customerQuery->where('id_status', $request->id_status);
        }
        
        if (!empty($request->id_plan)) {
            $customerQuery->where('id_plan', $request->id_plan);
        }
        
        if (!empty($request->id_merchant)) {
            $customerQuery->where('id_merchant', $request->id_merchant);
        }
        
        // Order by ID DESC
        $customerQuery->orderBy('id', 'DESC');
        
        return \DataTables::of($customerQuery)
            ->addIndexColumn()
            ->editColumn('customer_id', function ($customer) {
                return '<span class="badge badge-primary">' . $customer->customer_id . '</span>';
            })
            ->addColumn('merchant', function ($customer) {
                $merchant = \DB::connection('tenant_temp')
                    ->table('merchants')
                    ->where('id', $customer->id_merchant)
                    ->first();
                
                return $merchant ? '<span>' . $merchant->name . '</span>' : '<span class="text-muted">No Merchant</span>';
            })
            ->addColumn('plan', function ($customer) {
                $plan = \DB::connection('tenant_temp')
                    ->table('plans')
                    ->where('id', $customer->id_plan)
                    ->first();
                
                return $plan ? '<span>' . $plan->name . ' (' . number_format($plan->price, 0, ',', '.') . ')</span>' : '-';
            })
            ->addColumn('status_cust', function ($customer) {
                $status = \DB::connection('tenant_temp')
                    ->table('statuscustomers')
                    ->where('id', $customer->id_status)
                    ->first();
                
                if (!$status) {
                    return '<span class="badge badge-secondary">Unknown</span>';
                }
                
                $badgeClass = match ($status->name) {
                    'Active' => 'badge-success',
                    'Inactive' => 'badge-secondary',
                    'Block' => 'badge-danger',
                    'Company_Properti' => 'badge-warning',
                    default => 'badge-info',
                };
                
                return '<span class="badge ' . $badgeClass . '">' . $status->name . '</span>';
            })
            ->addColumn('invoice', function ($customer) {
                $count = \DB::connection('tenant_temp')
                    ->table('suminvoices')
                    ->where('id_customer', $customer->id)
                    ->where('payment_status', 0)
                    ->count();
                
                return $count >= 1 ? '<span class="badge badge-warning">' . $count . '</span>' : '';
            })
            ->editColumn('notification', function ($customer) {
                $icon = match ($customer->notification) {
                    1 => '<i class="fab fa-whatsapp text-success"></i>',
                    2 => '<i class="fas fa-envelope text-primary"></i>',
                    0, null => '<i class="fas fa-ban text-muted"></i>',
                    default => ''
                };
                
                return '<span class="text-center">' . $icon . '</span>';
            })
            ->rawColumns(['customer_id', 'merchant', 'plan', 'status_cust', 'invoice', 'notification'])
            ->with('stats', $stats)
            ->make(true);
    }

    /**
     * Display tenant transactions
     */
    public function transactions($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        // Configure tenant database connection
        \Config::set('database.connections.tenant_temp', [
            'driver' => 'mysql',
            'host' => $tenant->db_host ?? '127.0.0.1',
            'port' => $tenant->db_port ?? '3306',
            'database' => $tenant->db_database,
            'username' => $tenant->db_username,
            'password' => $tenant->db_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);
        
        $today = \Carbon\Carbon::today();
        $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
        $endOfWeek = \Carbon\Carbon::now()->endOfWeek();
        $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
        $endOfMonth = \Carbon\Carbon::now()->endOfMonth();
        
        // Get grouped transactions by user
        $groupedTransactionsUser = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->whereBetween('payment_date', [$startOfMonth, $today->copy()->addDay()])
            ->groupBy('updated_by')
            ->get();
        
        // Daily transactions report (only paid)
        $dailyTransactions = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->where('payment_status', 1)
            ->selectRaw('DATE(payment_date) as date, COUNT(*) as volume, SUM(recieve_payment) as total_paid')
            ->groupBy(\DB::raw('DATE(payment_date)'))
            ->orderBy('date')
            ->get();
        
        // Total payment today
        $totalPaymentToday = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->whereDate('payment_date', \Carbon\Carbon::today())
            ->sum('recieve_payment');
        
        // Total transaction this week
        $totalTransactionThisWeek = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->whereBetween('payment_date', [$startOfWeek, $endOfWeek])
            ->sum('recieve_payment');
        
        // Total transaction this month
        $totalTransactionThisMonth = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('recieve_payment');
        
        // Total receivable
        $totalReceivable = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->where('payment_status', 0)
            ->sum('total_amount');
        
        // Grouped transactions by user with total
        $groupedTransactions = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->select('updated_by', \DB::raw('SUM(recieve_payment) as total_amount'))
            ->groupBy('updated_by')
            ->get();
        
        // Get suminvoices for the last week
        $suminvoice = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->orderBy('updated_at', 'DESC')
            ->whereNotNull('updated_by')
            ->whereBetween('payment_date', [date('Y-m-d', strtotime("-1 week")), date('Y-m-d')])
            ->get();
        
        // Get merchants
        $merchant = \DB::connection('tenant_temp')
            ->table('merchants')
            ->pluck('name', 'id');
        
        // Get kas bank accounts
        $parentAkuns = \DB::connection('tenant_temp')
            ->table('akuns')
            ->whereNotNull('parent')
            ->pluck('parent');
        
        $kasbank = \DB::connection('tenant_temp')
            ->table('akuns')
            ->where('category', 'kas & bank')
            ->whereNotIn('akun_code', $parentAkuns)
            ->get();
        
        // Purge temporary connection
        \DB::purge('tenant_temp');
        
        return view('tenants.transactions', [
            'tenant' => $tenant,
            'dailyTransactions' => $dailyTransactions,
            'suminvoice' => $suminvoice,
            'user' => $groupedTransactionsUser,
            'totalPaymentToday' => $totalPaymentToday,
            'totalTransactionThisWeek' => $totalTransactionThisWeek,
            'totalTransactionThisMonth' => $totalTransactionThisMonth,
            'totalReceivable' => $totalReceivable,
            'groupedTransactions' => $groupedTransactions,
            'merchant' => $merchant,
            'kasbank' => $kasbank
        ]);
    }
    
    /**
     * Get transaction data for DataTables (AJAX)
     */
    public function transactionsData(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        
        // Configure tenant database connection
        \Config::set('database.connections.tenant_temp', [
            'driver' => 'mysql',
            'host' => $tenant->db_host ?? '127.0.0.1',
            'port' => $tenant->db_port ?? '3306',
            'database' => $tenant->db_database,
            'username' => $tenant->db_username,
            'password' => $tenant->db_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);
        
        try {
            $dateStart = \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('dateStart'))->setTime(0, 0);
            $dateEnd = \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('dateEnd'))->endOfDay();
        } catch (\Exception $e) {
            $dateStart = \Carbon\Carbon::today()->startOfMonth();
            $dateEnd = \Carbon\Carbon::today()->endOfDay();
        }
        
        $parameter = $request->input('parameter');
        $updatedBy = $request->input('updatedBy');
        $id_merchant = $request->input('id_merchant');
        $kasbank = $request->input('kasbank');
        
        // Grouped by user
        $groupedTransactionsUser = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->join('customers', 'customers.id', '=', 'suminvoices.id_customer')
            ->whereBetween('suminvoices.payment_date', [$dateStart, $dateEnd])
            ->where('suminvoices.payment_status', 1)
            ->select(
                'suminvoices.updated_by',
                \DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
                \DB::raw('SUM(suminvoices.total_amount) as total_amount'),
                \DB::raw('SUM(suminvoices.merchant_fee) as total_fee')
            );
        
        if (!empty($updatedBy)) {
            $groupedTransactionsUser->where('suminvoices.updated_by', $updatedBy);
        }
        
        if (!empty($id_merchant)) {
            $groupedTransactionsUser->where('customers.id_merchant', $id_merchant);
        }
        
        if (!empty($kasbank)) {
            $groupedTransactionsUser->where('suminvoices.payment_point', $kasbank);
        }
        
        $groupedTransactionsUser = $groupedTransactionsUser
            ->groupBy('suminvoices.updated_by')
            ->get();
        
        // Grouped by merchant
        $groupedTransactionsMerchant = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->join('customers', 'customers.id', '=', 'suminvoices.id_customer')
            ->whereBetween('suminvoices.payment_date', [$dateStart, $dateEnd])
            ->where('suminvoices.payment_status', 1)
            ->select(
                'customers.id_merchant',
                \DB::raw('SUM(suminvoices.recieve_payment) as total_payment')
            );
        
        if (!empty($updatedBy)) {
            $groupedTransactionsMerchant->where('suminvoices.updated_by', $updatedBy);
        }
        
        if (!empty($id_merchant)) {
            $groupedTransactionsMerchant->where('customers.id_merchant', $id_merchant);
        }
        
        if (!empty($kasbank)) {
            $groupedTransactionsMerchant->where('suminvoices.payment_point', $kasbank);
        }
        
        $groupedTransactionsMerchant = $groupedTransactionsMerchant
            ->groupBy('customers.id_merchant')
            ->get();
        
        // Grouped by kasbank
        $groupedTransactionsKasbank = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->join('customers', 'customers.id', '=', 'suminvoices.id_customer')
            ->whereBetween('suminvoices.payment_date', [$dateStart, $dateEnd])
            ->where('suminvoices.payment_status', 1)
            ->select(
                'suminvoices.payment_point',
                \DB::raw('SUM(suminvoices.recieve_payment) as total_payment')
            );
        
        if (!empty($updatedBy)) {
            $groupedTransactionsKasbank->where('suminvoices.updated_by', $updatedBy);
        }
        
        if (!empty($id_merchant)) {
            $groupedTransactionsKasbank->where('customers.id_merchant', $id_merchant);
        }
        
        if (!empty($kasbank)) {
            $groupedTransactionsKasbank->where('suminvoices.payment_point', $kasbank);
        }
        
        $groupedTransactionsKasbank = $groupedTransactionsKasbank
            ->groupBy('suminvoices.payment_point')
            ->get();
        
        // Get merchants
        $merchants = \DB::connection('tenant_temp')
            ->table('merchants')
            ->get();
        
        // Get kasbank accounts
        $parentAkuns = \DB::connection('tenant_temp')
            ->table('akuns')
            ->whereNotNull('parent')
            ->pluck('parent');
        
        $kasbanks = \DB::connection('tenant_temp')
            ->table('akuns')
            ->where('category', 'kas & bank')
            ->whereNotIn('akun_code', $parentAkuns)
            ->get();
        
        // Get users
        $users = \DB::connection('tenant_temp')
            ->table('users')
            ->get();
        
        // Build main query
        $query = \DB::connection('tenant_temp')
            ->table('suminvoices')
            ->join('customers', 'customers.id', '=', 'suminvoices.id_customer')
            ->leftJoin('merchants', 'merchants.id', '=', 'customers.id_merchant')
            ->leftJoin('akuns', 'akuns.akun_code', '=', 'suminvoices.payment_point')
            ->whereBetween('suminvoices.payment_date', [$dateStart, $dateEnd])
            ->select([
                'suminvoices.*',
                'customers.customer_id',
                'customers.name as customer_name',
                'customers.address',
                'merchants.name as merchant_name',
                'akuns.name as kasbank_name'
            ]);
        
        // Apply filters
        if (!empty($parameter)) {
            $query->where(function ($q) use ($parameter) {
                $q->where('suminvoices.invoice_no', 'LIKE', "%{$parameter}%")
                  ->orWhere('customers.customer_id', 'LIKE', "%{$parameter}%")
                  ->orWhere('customers.name', 'LIKE', "%{$parameter}%");
            });
        }
        
        if (!empty($updatedBy)) {
            $query->where('suminvoices.updated_by', $updatedBy);
        }
        
        if (!empty($id_merchant)) {
            $query->where('customers.id_merchant', $id_merchant);
        }
        
        if (!empty($kasbank)) {
            $query->where('suminvoices.payment_point', $kasbank);
        }
        
        // Get total counts and sums
        $totalRecords = $query->count();
        $totalAmount = $query->sum('suminvoices.total_amount');
        $totalFee = $query->sum('suminvoices.merchant_fee');
        $totalPayment = $query->sum('suminvoices.recieve_payment');
        
        // Get paginated data
        $start = $request->input('start', 0);
        $length = $request->input('length', 50);
        
        $data = $query
            ->orderBy('suminvoices.updated_at', 'DESC')
            ->skip($start)
            ->take($length)
            ->get();
        
        // Purge temporary connection
        \DB::purge('tenant_temp');
        
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
            'groupedTransactionsUser' => $groupedTransactionsUser,
            'groupedTransactionsMerchant' => $groupedTransactionsMerchant,
            'groupedTransactionsKasbank' => $groupedTransactionsKasbank,
            'merchants' => $merchants,
            'kasbanks' => $kasbanks,
            'users' => $users,
            'totalAmount' => $totalAmount,
            'totalFee' => $totalFee,
            'totalPayment' => $totalPayment
        ]);
    }

    /**
     * List application log files — tenant-specific channel logs + Python OLT/jobs
     */
    public function logIndex(Request $request)
    {
        $tenantKey = env('DB_DATABASE', 'default');
        $tenantDir = storage_path("logs/tenant_{$tenantKey}");
        $rootDir   = storage_path('logs');

        $files = [];

        // Scan tenant-specific dir (invoice, notif, payment, auth, laravel, etc.)
        if (is_dir($tenantDir)) {
            foreach (glob($tenantDir . '/*.log') as $path) {
                $name = basename($path);
                if ($name === 'laravel.log') continue;

                if (str_starts_with($name, 'invoice'))     $type = 'invoice';
                elseif (str_starts_with($name, 'notif'))   $type = 'notif';
                elseif (str_starts_with($name, 'payment')) $type = 'payment';
                elseif (str_starts_with($name, 'isolir'))  $type = 'isolir';
                elseif (str_starts_with($name, 'auth'))    $type = 'auth';
                elseif (str_starts_with($name, 'jobs'))    $type = 'jobs';
                else                                       $type = 'other';

                $files[] = [
                    'name'     => "tenant_{$tenantKey}/{$name}",
                    'label'    => $name,
                    'type'     => $type,
                    'tenant'   => true,
                    'size'     => filesize($path),
                    'modified' => filemtime($path),
                ];
            }
        }

        // Scan root dir for Python OLT/jobs logs (not tenant PHP channels)
        foreach (glob($rootDir . '/*.log') as $path) {
            $name = basename($path);
            if ($name === 'laravel.log') continue;
            // Skip legacy PHP channel files that have been migrated to tenant dir
            $phpChannels = ['invoice', 'notif', 'payment', 'isolir', 'auth', 'jobsprocess'];
            $isPhpChannel = false;
            foreach ($phpChannels as $p) { if (str_starts_with($name, $p)) { $isPhpChannel = true; break; } }

            if (str_starts_with($name, 'olt_log')) {
                $type = 'olt';
            } elseif ($isPhpChannel) {
                $type = 'legacy'; // old shared files before migration
            } else {
                $type = 'other';
            }

            $files[] = [
                'name'     => $name,
                'label'    => $name,
                'type'     => $type,
                'tenant'   => false,
                'size'     => filesize($path),
                'modified' => filemtime($path),
            ];
        }

        usort($files, fn($a, $b) => $b['modified'] - $a['modified']);

        $grouped = [];
        foreach ($files as $f) {
            $grouped[$f['type']][] = $f;
        }

        return view('admin.logs.index', compact('grouped', 'files', 'tenantKey'));
    }

    /**
     * View content of a specific log file
     */
    public function logView(Request $request)
    {
        $file    = $request->get('file');
        $lines   = (int) $request->get('lines', 200);
        $logDir  = storage_path('logs');
        // Allow one level of subdir (e.g. tenant_kencana/invoice-2025.log)
        $path    = realpath($logDir . '/' . $file);

        if (!$file || !$path || !file_exists($path) || !str_starts_with($path, realpath($logDir))) {
            abort(404, 'Log file tidak ditemukan');
        }

        // Read last N lines
        $content = $this->tailFile($path, $lines);

        return view('admin.logs.view', [
            'filename' => basename($file),
            'content'  => $content,
            'lines'    => $lines,
            'size'     => filesize($path),
            'modified' => filemtime($path),
        ]);
    }

    /**
     * View tenant-specific laravel.log
     */
    public function tenantLog(Request $request, $id)
    {
        $tenant = \DB::connection('master')->table('tenants')->where('id', $id)->first();
        if (!$tenant) abort(404);

        $rescode = $tenant->rescode ?? 'default';
        $lines   = (int) $request->get('lines', 300);
        $path    = storage_path("logs/tenant_{$rescode}/laravel.log");

        $content  = null;
        $fileSize = null;
        $modified = null;

        if (file_exists($path)) {
            $content  = $this->tailFile($path, $lines);
            $fileSize = filesize($path);
            $modified = filemtime($path);
        }

        return view('admin.logs.tenant', compact('tenant', 'rescode', 'content', 'fileSize', 'modified', 'lines', 'path'));
    }

    /**
     * Read last N lines from a file efficiently
     */
    private function tailFile(string $path, int $lineCount = 200): string
    {
        $fp   = fopen($path, 'rb');
        if (!$fp) return '';

        fseek($fp, 0, SEEK_END);
        $pos   = ftell($fp);
        $chunk = 8192;
        $buf   = '';
        $count = 0;

        while ($pos > 0 && $count < $lineCount) {
            $read  = min($chunk, $pos);
            $pos  -= $read;
            fseek($fp, $pos);
            $buf    = fread($fp, $read) . $buf;
            $count  = substr_count($buf, "\n");
        }
        fclose($fp);

        $allLines = explode("\n", $buf);
        $result   = array_slice($allLines, -$lineCount);
        return implode("\n", $result);
    }

    /**
     * Get queue worker status for a tenant (AJAX)
     */
    public function queueStatus($id)
    {
        $tenant = Tenant::findOrFail($id);
        $slug = explode('.', $tenant->domain)[0];
        $programGroup = "{$slug}_queue_worker";

        // Get supervisor worker status
        $supervisorOutput = shell_exec("sudo supervisorctl status '{$programGroup}:*' 2>&1");
        $workers = [];
        if ($supervisorOutput) {
            foreach (explode("\n", trim($supervisorOutput)) as $line) {
                if (empty(trim($line))) continue;
                // Parse: name   STATUS   pid X, uptime H:MM:SS
                preg_match('/^(\S+)\s+(\S+)\s*(.*)$/', trim($line), $m);
                $workers[] = [
                    'name'   => $m[1] ?? $line,
                    'status' => $m[2] ?? 'UNKNOWN',
                    'info'   => $m[3] ?? '',
                ];
            }
        }

        // Get jobs stats from tenant DB
        $pendingJobs = 0;
        $failedJobs  = 0;
        try {
            \Config::set('database.connections.tenant_temp', [
                'driver'    => 'mysql',
                'host'      => $tenant->db_host ?? '127.0.0.1',
                'port'      => $tenant->db_port ?? '3306',
                'database'  => $tenant->db_database,
                'username'  => $tenant->db_username,
                'password'  => $tenant->db_password,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => false,
            ]);
            $pendingJobs = \DB::connection('tenant_temp')->table('jobs')->count();
            $failedJobs  = \DB::connection('tenant_temp')->table('failed_jobs')->count();
            \DB::purge('tenant_temp');
        } catch (\Exception $e) {
            // DB unreachable
        }

        $envVars = $tenant->env_variables ?? [];
        $queueSettings = [
            'sleep'    => (int) ($envVars['QUEUE_SLEEP']    ?? 3),
            'tries'    => (int) ($envVars['QUEUE_TRIES']    ?? 3),
            'timeout'  => (int) ($envVars['QUEUE_TIMEOUT']  ?? 120),
            'max_jobs' => (int) ($envVars['QUEUE_MAX_JOBS'] ?? 500),
        ];

        return response()->json([
            'workers'        => $workers,
            'program'        => $programGroup,
            'pending_jobs'   => $pendingJobs,
            'failed_jobs'    => $failedJobs,
            'conf_exists'    => file_exists("/etc/supervisord.d/{$slug}.conf"),
            'queue_settings' => $queueSettings,
            'timestamp'      => now()->format('H:i:s'),
        ]);
    }

    /**
     * Restart queue worker for a tenant (AJAX)
     */
    public function queueRestart($id)
    {
        $tenant = Tenant::findOrFail($id);
        $slug   = explode('.', $tenant->domain)[0];
        $programGroup = "{$slug}_queue_worker";

        $output = shell_exec("sudo supervisorctl restart '{$programGroup}:*' 2>&1");

        return response()->json([
            'success' => true,
            'message' => "Worker {$programGroup} restarted.",
            'output'  => trim($output),
        ]);
    }

    /**
     * Save queue worker config (sleep/tries/timeout/max-jobs) for a tenant
     */
    public function queueConfig(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        $slug   = explode('.', $tenant->domain)[0];
        $programGroup = "{$slug}_queue_worker";

        $sleep   = max(1,   (int) $request->input('queue_sleep',    3));
        $tries   = max(1,   (int) $request->input('queue_tries',    3));
        $timeout = max(30,  (int) $request->input('queue_timeout',  120));
        $maxJobs = max(10,  (int) $request->input('queue_max_jobs', 500));

        // Save to tenant env_variables
        $envVars = $tenant->env_variables ?? [];
        $envVars['QUEUE_SLEEP']    = $sleep;
        $envVars['QUEUE_TRIES']    = $tries;
        $envVars['QUEUE_TIMEOUT']  = $timeout;
        $envVars['QUEUE_MAX_JOBS'] = $maxJobs;
        $tenant->env_variables = $envVars;
        $tenant->save();

        // Rewrite supervisor conf if it exists
        $confPath = "/etc/supervisord.d/{$slug}.conf";
        if (file_exists($confPath)) {
            $conf = file_get_contents($confPath);
            $conf = preg_replace('/--sleep=\d+/',    "--sleep={$sleep}",       $conf);
            $conf = preg_replace('/--tries=\d+/',    "--tries={$tries}",       $conf);
            $conf = preg_replace('/--timeout=\d+/',  "--timeout={$timeout}",   $conf);
            $conf = preg_replace('/--max-jobs=\d+/', "--max-jobs={$maxJobs}",  $conf);
            // Write via sudo tee
            $tmpFile = tempnam(sys_get_temp_dir(), 'sup_');
            file_put_contents($tmpFile, $conf);
            shell_exec("sudo tee {$confPath} < {$tmpFile} > /dev/null 2>&1");
            unlink($tmpFile);
        }

        // Reload supervisor and restart worker
        $output  = shell_exec("sudo supervisorctl reread 2>&1");
        $output .= shell_exec("sudo supervisorctl update 2>&1");
        $output .= shell_exec("sudo supervisorctl restart '{$programGroup}:*' 2>&1");

        return response()->json([
            'success'  => true,
            'message'  => "Queue config saved and worker restarted.",
            'output'   => trim($output),
            'settings' => compact('sleep', 'tries', 'timeout', 'maxJobs'),
        ]);
    }
}
