<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class FileController extends Controller
{
   public function __construct()
   {
    $this->middleware('auth');
    // Backup konfigurasi router (berisi password): admin saja.
    $this->middleware('checkPrivilege:admin')->only(['backup']);
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }
    public function backup()
    {
        // Hanya backup konfigurasi MikroTik. Dump database tenant tidak ditampilkan/diunduh dari sini
        // (dikelola superadmin, lihat App\Services\TenantBackupStorage).
        // Backup konfigurasi MikroTik tenant ini (App\Services\MikrotikConfigBackup), file terenkripsi
        // diunduh lewat /distrouter/{id}/config-backup/{backupId}.
        $routerBackups = \App\MikrotikBackup::where('tenant_id', (int) tenant_id())
            ->where('status', 'ok')->whereNotNull('file_path')
            ->latest('id')->get();

        return view('file.index', [
            'routerBackups' => $routerBackups,
            'retentionDays' => \App\Services\MikrotikConfigBackup::RETENTION_DAYS,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validation
            $request->validate([
                'file' => 'required|file|max:10240',
                'file_name' => 'nullable|string|max:191',
            ]); 

            if($request->file('file')) {
                $file = $request->file('file');
                if ($this->isBlockedUpload($file)) {
                    return redirect()->back()->with('error', 'File type is not allowed for security reasons.');
                }

                $originalName = $file->getClientOriginalName();
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                $extension = strtolower($file->getClientOriginalExtension());
                $safeBase = preg_replace('/[^A-Za-z0-9_-]/', '_', $baseName);
                $filename = time().'_'.$safeBase.($extension ? '.'.$extension : '');

                // Get tenant-specific upload path
                $rescode = config('app.rescode') ?? config('tenant.rescode', 'default');
                $location = public_path("tenants/{$rescode}/upload/customerfiles");
                $relativePath = "tenants/{$rescode}/upload/customerfiles";

                // Create directory if not exists
                if (!File::exists($location)) {
                    File::makeDirectory($location, 0755, true, true);
                    
                    // Fix ownership to apache:apache (PHP-FPM user)
                    if (function_exists('posix_getuid') && posix_getuid() === 0) {
                        @chown($location, 'apache');
                        @chgrp($location, 'apache');
                    }
                }

                // Upload file
                $file->move($location, $filename);
                
                // Fix uploaded file ownership
                $uploadedFile = $location . '/' . $filename;
                if (function_exists('posix_getuid') && posix_getuid() === 0) {
                    @chown($uploadedFile, 'apache');
                    @chgrp($uploadedFile, 'apache');
                }

                $id_customer = ($request['id_customer']);

                // Gunakan nama custom jika diisi, fallback ke nama asli file
                $customName = trim($request->input('file_name', ''));
                $displayName = $customName !== '' ? $customName : $originalName;

                \App\File::create([
                    'id_customer' => $id_customer,
                    'name' => $displayName,
                    'path' => $relativePath.'/'.$filename, 
                ]);

                return redirect('/customer/'.$id_customer)->with('success','File uploaded successfully!');
            } else {
                return redirect('/customer/'.$id_customer)->with('error','File not uploaded!');
            }
        } catch (\Exception $e) {
            \Log::error('File upload error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to upload file: ' . $e->getMessage());
        }
    }

    protected function isBlockedUpload($uploadedFile)
    {
        $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps'];

        $originalName = strtolower((string) $uploadedFile->getClientOriginalName());
        $nameParts = array_filter(explode('.', $originalName));
        foreach ($nameParts as $part) {
            if (in_array($part, $blockedExtensions, true)) {
                return true;
            }
        }

        $clientExtension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        if (in_array($clientExtension, $blockedExtensions, true)) {
            return true;
        }

        $guessedExtension = strtolower((string) $uploadedFile->guessExtension());
        if ($guessedExtension !== '' && in_array($guessedExtension, $blockedExtensions, true)) {
            return true;
        }

        $mime = strtolower((string) $uploadedFile->getClientMimeType());
        return strpos($mime, 'php') !== false;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Get file record
            $fileRecord = \App\File::findOrFail($id);
            
            // Delete physical file if exists
            $filePath = public_path($fileRecord->path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
            
            // Delete database record
            $fileRecord->delete();
            
            return redirect('/customer')->with('success','File deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('File delete error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete file: ' . $e->getMessage());
        }
    }
}
