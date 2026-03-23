<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class FileController extends Controller
{
   public function __construct()
   {
    $this->middleware('auth');
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
    public function delete($filename)
    {
        try {
            // Get tenant-specific backup path (check multiple config keys)
            $rescode = config('app.rescode') ?? config('tenant.rescode', 'default');
            $file = public_path("tenants/{$rescode}/backup/" . $filename);
            
            if (File::exists($file)) {
                File::delete($file);
                return redirect()->back()->with('success', 'File deleted successfully!');
            } else {
                return redirect()->back()->with('error', 'File not found!');
            }
        } catch (\Exception $e) {
            \Log::error('Delete file error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete file: ' . $e->getMessage());
        }
    }
    
    public function download($filename)
    {
        try {
            // Get tenant-specific backup path (check multiple config keys)
            $rescode = config('app.rescode') ?? config('tenant.rescode', 'default');
            $file = public_path("tenants/{$rescode}/backup/" . $filename);
            
            if (File::exists($file)) {
                return response()->download($file, $filename);
            } else {
                abort(404, 'File not found');
            }
        } catch (\Exception $e) {
            \Log::error('Download file error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to download file: ' . $e->getMessage());
        }
    }
    public function backup()
    {
        try {
            // Get tenant-specific backup path (check multiple config keys)
            $rescode = config('app.rescode') ?? config('tenant.rescode', 'default');
            $backupPath = public_path("tenants/{$rescode}/backup");
            
            // Check if directory exists, if not create it recursively
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true, true);
                
                // Fix ownership to apache:apache (PHP-FPM user) if running as root
                if (function_exists('posix_getuid') && posix_getuid() === 0) {
                    @chown($backupPath, 'apache');
                    @chgrp($backupPath, 'apache');
                }
            }
            
            // Just get files and show them - remove is_readable check that's causing issues
            $files = File::files($backupPath);
            
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            return view('file.index', compact('files'));
        } catch (\Exception $e) {
            \Log::error('Backup directory error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to access backup directory: ' . $e->getMessage());
        }
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
                'file' => 'required'
            ]); 

            if($request->file('file')) {
                $file = $request->file('file');
                $name = str_replace('-', ' ', $file->getClientOriginalName());
                $filename = time().'_'.$name;

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

                \App\File::create([
                    'id_customer' => $id_customer,
                    'name' => $file->getClientOriginalName(),
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
