<?php

namespace App\Http\Controllers;
use \Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class UserController extends Controller
{
 public function __construct()
 {
    $this->middleware('auth');
    // $this->middleware('checkPrivilege:admin')->except('myprofile');
    $this->middleware('checkPrivilege:merchant')->only(['customermerchant','table_customermerchant']);
}


public function searchforjurnal(Request $request) {
    // Log::info('Data yang diterima:', $request->all());

    // Ambil data customer berdasarkan pencarian
    $users = \App\User::where('name', 'LIKE', "%{$request->q}%")

    ->limit(100)
    ->get();

    return response()->json($users);
}
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
     if ((Auth::user()->privilege)=="admin")
     {     
         // $user = \App\User::with('groups')->get();

        $user = \App\User::with(['groups', 'akuns'])->get();


        return view ('user/index',['user' =>$user]);
    }
    else
    {
      return redirect()->back()->with('error','Sorry, You Are Not Allowed to Access Destination page !!');
  }
}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function log()
    {
        if ((Auth::user()->privilege) != "admin") {
            return redirect()->back()->with('error', 'Sorry, You Are Not Allowed to Access Destination page !!');
        }

        // Gunakan tenant aktif dari session multi-tenant, fallback ke DB_DATABASE
        $tenantKey = app()->bound('tenant') ? (app('tenant')['db_database'] ?? env('DB_DATABASE', 'default')) : env('DB_DATABASE', 'default');
        $tenantDir = storage_path("logs/tenant_{$tenantKey}");
        $rootDir   = storage_path('logs');

        // Tenant-specific channel logs
        $tenantFiles = [];
        if (is_dir($tenantDir)) {
            foreach (glob($tenantDir . '/*.log') as $path) {
                $name = basename($path);
                if ($name === 'laravel.log') continue;
                $tenantFiles[] = [
                    'name'     => "tenant_{$tenantKey}/{$name}",
                    'label'    => $name,
                    'size'     => filesize($path),
                    'modified' => filemtime($path),
                ];
            }
        }
        usort($tenantFiles, fn($a, $b) => $b['modified'] - $a['modified']);

        // Non-tenant logs (Python OLT logs, etc.) from root storage/logs/
        $rootFiles = [];
        foreach (glob($rootDir . '/*.log') as $path) {
            $name = basename($path);
            if ($name === 'laravel.log') continue;
            $rootFiles[] = [
                'name'     => $name,
                'label'    => $name,
                'size'     => filesize($path),
                'modified' => filemtime($path),
            ];
        }
        usort($rootFiles, fn($a, $b) => $b['modified'] - $a['modified']);

        // Tenant laravel.log
        $tenantLogPath   = $tenantDir . '/laravel.log';
        $tenantLogExists = file_exists($tenantLogPath);
        $tenantLogInfo   = $tenantLogExists ? [
            'name'     => "tenant_{$tenantKey}/laravel.log",
            'size'     => filesize($tenantLogPath),
            'modified' => filemtime($tenantLogPath),
        ] : null;

        $appFiles = array_merge($tenantFiles, $rootFiles);

        return view('user/log', compact('appFiles', 'tenantLogInfo', 'tenantKey'));
    }

    public function logRead(\Illuminate\Http\Request $request)
    {
        if ((Auth::user()->privilege) != "admin") {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $file   = $request->get('file', '');
        $lines  = (int) $request->get('lines', 300);
        $logDir = storage_path('logs');

        // Sanitize path — only allow files within storage/logs
        $path = realpath($logDir . '/' . $file);
        if (!$path || !str_starts_with($path, realpath($logDir))) {
            return response()->json(['error' => 'File tidak ditemukan'], 404);
        }

        $content = $this->tailLogFile($path, $lines);
        return response()->json(['content' => $content, 'size' => filesize($path), 'modified' => date('d M Y H:i', filemtime($path))]);
    }

    private function tailLogFile(string $path, int $lineCount = 300): string
    {
        $fp = fopen($path, 'rb');
        if (!$fp) return '';
        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);
        $buf = '';
        while ($pos > 0 && substr_count($buf, "\n") < $lineCount) {
            $read  = min(8192, $pos);
            $pos  -= $read;
            fseek($fp, $pos);
            $buf = fread($fp, $read) . $buf;
        }
        fclose($fp);
        $all = explode("\n", $buf);
        return implode("\n", array_slice($all, -$lineCount));
    }

public function create()
{
        //
   if ((Auth::user()->privilege)=="admin")
   {     
    $groups = \App\Group::all();
    $akuns = \App\Akun::where('category', 'kas & bank')->get();
    $supervisors = \App\User::where('is_active', 1)->orderBy('name')->get(['id','name','job_title']);

    return view ('user/create', ['groups' => $groups, 'akuns'=>$akuns, 'supervisors'=>$supervisors]);
}
else
{
  return redirect()->back()->with('error','Sorry, You Are Not Allowed to Access Destination page !!');
}

}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:191',
    //         'full_name' => 'required|string',
    //         'date_of_birth' => 'required|date',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:6',
    //         'job_title' => 'required|string',
    //         'employee_type' => 'required|string',
    //         'join_date' => 'required|date',
    //         'address' => 'required|string',
    //         'phone' => 'required|string',
    //         'photo' => 'nullable|mimes:jpg,png,jpeg,gif|max:2048',
    //         'groups' => 'required|array',
    //         'groups.*' => 'exists:groups,id',
    //         'akuns' => 'nullable|array',
    //         'akuns.*' => 'exists:akuns,id',
    //     ]);

    // // Upload photo if provided
    //     $imageName = $request->file('photo') 
    //     ? time() . '.' . $request->photo->getClientOriginalExtension() 
    //     : 'user.png';

    //     if ($request->hasFile('photo')) {
    //         $request->photo->move(public_path('storage/users'), $imageName);
    //     }

    // // Create user
    //     $user = \App\User::create([
    //         'name' => $request->name,
    //         'full_name' => $request->full_name,
    //         'date_of_birth' => $request->date_of_birth,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //         'job_title' => $request->job_title,
    //         'employee_type' => $request->employee_type,
    //         'join_date' => $request->join_date,
    //         'address' => $request->address,
    //         'phone' => $request->phone,
    //         'description' => $request->description,
    //         'photo' => $imageName,
    //     ]);

    // // Assign groups to the user
    //     $user->groups()->sync($request->groups);
    //     if (!empty($request->akuns)) {
    //         $user->akuns()->sync($request->akuns);
    //     }

    //     return redirect('/user')->with('success', 'User created successfully!');
    // }


    public function store(Request $request)
    {
    // Validasi Input
        $request->validate([
            'name' => 'required|string|max:191',
            'full_name' => 'required|string',
            'date_of_birth' => 'required|date',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'job_title' => 'required|string',
            'employee_type' => 'required|string',
            'join_date' => 'required|date',
            'address' => 'required|string',
            'phone' => 'required|string',
            'photo' => 'nullable|mimes:jpg,png,jpeg,gif|max:2048',
            'groups' => 'required|array',
            'groups.*' => 'exists:groups,id',
            'akuns' => 'nullable|array',
            'akuns.*' => 'exists:akuns,id',
        ]);

    // Default foto
        $imageName = 'user.png';

        try {
        // Mulai Database Transaction
            DB::beginTransaction();

        // Upload foto - Prioritaskan cropped photo
            if ($request->filled('cropped_photo')) {
                // Handle cropped photo from base64
                $base64Image = $request->cropped_photo;
                
                // Extract base64 string
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                    $data = substr($base64Image, strpos($base64Image, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif
                    
                    // Decode base64
                    $data = str_replace(' ', '+', $data);
                    $decodedImage = base64_decode($data);
                    
                    if ($decodedImage === false) {
                        throw new \Exception('Base64 decode failed');
                    }
                    
                    // Generate unique filename
                    $fileName = 'user_' . time() . '_' . uniqid() . '.' . $type;
                    
                    // Save directly to public/storage/users/
                    $filePath = public_path('storage/users/' . $fileName);
                    
                    // Ensure directory exists
                    if (!file_exists(public_path('storage/users'))) {
                        mkdir(public_path('storage/users'), 0777, true);
                    }
                    
                    $saved = file_put_contents($filePath, $decodedImage);
                    
                    if (!$saved) {
                        throw new \Exception('Failed to save cropped image');
                    }
                    
                    $imageName = $fileName;
                }
            } elseif ($request->hasFile('photo')) {
                // Fallback to original file if no crop
                $imageName = time() . '.' . $request->photo->getClientOriginalExtension();
                $request->photo->move(public_path('storage/users'), $imageName);
            }

        // Membuat User
            $user = \App\User::create([
                'name' => $request->name,
                'full_name' => $request->full_name,
                'date_of_birth' => $request->date_of_birth,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'job_title' => $request->job_title,
                'employee_type' => $request->employee_type,
                'join_date' => $request->join_date,
                'address' => $request->address,
                'phone' => $request->phone,
                'description' => $request->description ?? null,
                'photo' => $imageName,
                'supervisor_id' => $request->supervisor_id ?: null,
            ]);

        // Menyinkronkan groups
            $user->groups()->sync($request->groups);

        // Menyinkronkan akuns jika ada
            if (!empty($request->akuns)) {
                $user->akuns()->sync($request->akuns);
            }

        // Commit Transaksi jika berhasil
            DB::commit();

            return redirect('/user')->with('success', 'User created successfully!');
        } catch (\Exception $e) {
        // Rollback jika terjadi kesalahan
            DB::rollBack();

        // Hapus foto yang diupload jika ada
            if ($imageName !== 'user.png' && Storage::exists("public/$imageName")) {
                Storage::delete("public/$imageName");
            }

        // Kembalikan dengan error
            return redirect()->back()->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()]);
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
       if ((Auth::user()->privilege)=="admin")
         {    $user = \App\User::findOrFail($id);  
            $akuns = \App\Akun::where('category', 'kas & bank')->get();
            $userAkunIds = $user->akuns->pluck('id')->toArray();
            $merchants = \App\Merchant::all();
            $groups = \App\Group::all();
            $userGroupIds = $user->groups->pluck('id')->toArray();
            $supervisors = \App\User::where('is_active', 1)->where('id','!=',$id)->orderBy('name')->get(['id','name','job_title']);
            return view ('user.edit',['user' => $user, 'groups' => $groups, 'userGroupIds'=>$userGroupIds, 'akuns' =>$akuns, 'userAkunIds'=>$userAkunIds, 'merchants' =>$merchants, 'supervisors'=>$supervisors]);
        }
        else
        {
          return redirect()->back()->with('error','Sorry, You Are Not Allowed to Access Destination page !!');
      }
  }
  public function myprofile($id)
  {

   if ($id == Auth::user()->id)
   {
    return view ('user/myprofile',['user' => \App\User::findOrFail($id)]);
}
else
{
    abort(404, 'You dont have permision to view this page');
}
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


        $request->validate([
            'name' => 'required',
            'full_name' => 'required',
            'date_of_birth' => 'required|date',
            'password' => 'required',
            'job_title' => 'required',
            'employee_type' => 'required',
            'join_date' => 'required|date',
            'address' => 'required',
            'phone' => 'required',
            'privilege' => 'required',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'groups' => 'array|nullable',
            'groups.*' => 'exists:groups,id',
            'akuns' => 'nullable|array',
            'akuns.*' => 'exists:akuns,akun_code',
            'id_merchant' =>'nullable',
        ]);

    // Start DB Transaction
        DB::beginTransaction();

        try {
        // Find the user
            $user = \App\User::findOrFail($id);

        // Hash the password if it's not already hashed
            $password = strlen($request->password) >= 50 ? $request->password : Hash::make($request->password);

        // Handle photo upload - Prioritaskan cropped photo
            if ($request->filled('cropped_photo')) {
                // Handle cropped photo from base64
                $base64Image = $request->cropped_photo;
                
                // Extract base64 string
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                    $data = substr($base64Image, strpos($base64Image, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif
                    
                    // Decode base64
                    $data = str_replace(' ', '+', $data);
                    $decodedImage = base64_decode($data);
                    
                    if ($decodedImage === false) {
                        throw new \Exception('Base64 decode failed');
                    }
                    
                    // Delete old photo if exists and not default
                    if ($user->photo && $user->photo != 'user.png' && file_exists(public_path("storage/users/{$user->photo}"))) {
                        @unlink(public_path("storage/users/{$user->photo}"));
                    }
                    
                    // Generate unique filename
                    $fileName = 'user_' . time() . '_' . uniqid() . '.' . $type;
                    
                    // Save directly to public/storage/users/
                    $filePath = public_path('storage/users/' . $fileName);
                    
                    // Ensure directory exists
                    if (!file_exists(public_path('storage/users'))) {
                        mkdir(public_path('storage/users'), 0777, true);
                    }
                    
                    $saved = file_put_contents($filePath, $decodedImage);
                    
                    if (!$saved) {
                        throw new \Exception('Failed to save cropped image');
                    }
                    
                    $user->photo = $fileName;
                }
            } elseif ($request->hasFile('photo')) {
                // Fallback to original file if no crop
            // Delete the old photo if exists
                if ($user->photo && $user->photo != 'user.png' && file_exists(public_path("storage/users/{$user->photo}"))) {
                    @unlink(public_path("storage/users/{$user->photo}"));
                }

            // Store the new photo
                $imageName = time() . '.' . $request->photo->getClientOriginalExtension();
                $request->photo->move(public_path('storage/users'), $imageName);
                $user->photo = $imageName;
            }

        // Update user details
            $user->update([
                'name' => $request->name,
                'full_name' => $request->full_name,
                'date_of_birth' => $request->date_of_birth,
                'password' => $password,
                'job_title' => $request->job_title,
                'employee_type' => $request->employee_type,
                'join_date' => $request->join_date,
                'address' => $request->address,
                'phone' => $request->phone,
                'privilege' => $request->privilege,
                'description' => $request->description,
                'id_merchant' => $request->id_merchant,
                'supervisor_id' => $request->supervisor_id ?: null,
                'dashboard_preference' => in_array($request->privilege, ['merchant','vendor']) ? null : ($request->dashboard_preference ?: null),
            ]);

        // Sync groups
            // dd($request->akun);
            if (!empty($request->groups)) {
               $user->groups()->sync($request->groups);
           }
           if (!empty($request->akuns)) {
            $user->akuns()->sync($request->akuns);
        }

        // Commit Transaction
        DB::commit();

        return redirect('/user')->with('success', 'User updated successfully!');
    } catch (\Exception $e) {
        // Rollback Transaction
        DB::rollBack();

        return redirect('/user')->with('error', 'Failed to update user: ' . $e->getMessage());
    }
}



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleActive($id)
    {
        $user = \App\User::findOrFail($id);

        // Jangan izinkan menonaktifkan diri sendiri
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "User {$user->name} berhasil {$status}.");
    }

    public function destroy($id)
    {
        //
      $result=  \App\User::destroy($id);
      if($result)
      {
        return redirect ('/user')->with('success','Item Deleted successfully!');
    }
    else
    {
        return redirect ('/user')->with('error','Field!');
    }
}
}
