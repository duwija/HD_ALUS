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
            'domain' => 'required|unique:master.tenants,domain',
            'app_name' => 'required',
            'rescode' => 'required|min:2|max:10|unique:master.tenants,rescode',
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
                
                // Import structure from existing database
                $sourceTenant = Tenant::first();
                if ($sourceTenant) {
                    $sourceDb = $sourceTenant->db_database;
                    $dumpFile = storage_path('app/temp_structure.sql');
                    
                    // Export structure
                    exec("mysqldump -u {$request->db_username} -p'{$request->db_password}' --no-data {$sourceDb} > {$dumpFile}");
                    
                    // Import to new database
                    exec("mysql -u {$request->db_username} -p'{$request->db_password}' {$request->db_database} < {$dumpFile}");
                    
                    // Clean up
                    @unlink($dumpFile);
                }
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
            'domain' => 'required|unique:master.tenants,domain,' . $id,
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
     * Show payment gateway configuration form
     */
    public function paymentGatewayConfig($id)
    {
        $tenant = Tenant::findOrFail($id);
        return view('tenants.payment-gateway-config', compact('tenant'));
    }

    /**
     * Update payment gateway configuration
     */
    public function updatePaymentGatewayConfig(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_bumdes_enabled' => 'required|in:0,1',
            'payment_winpay_enabled' => 'required|in:0,1',
            'payment_tripay_enabled' => 'required|in:0,1',
        ]);
        
        $tenant = Tenant::findOrFail($id);
        
        $tenant->update([
            'payment_bumdes_enabled' => $request->payment_bumdes_enabled,
            'payment_winpay_enabled' => $request->payment_winpay_enabled,
            'payment_tripay_enabled' => $request->payment_tripay_enabled,
        ]);
        
        // Clear config and cache
        \Artisan::call('config:clear');
        \Artisan::call('cache:clear');
        
        return redirect()->back()->with('success', 'Payment gateway configuration updated successfully!');
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
}
