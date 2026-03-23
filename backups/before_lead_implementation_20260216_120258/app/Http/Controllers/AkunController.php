<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AkunController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response

     */
    
    public function __construct()
    {
        $this->middleware('auth');
    }

//     public function index()
//     {
//         //
//         $parents = \App\Akun::whereNull('parent')
//         ->whereNotIn('akun_code', function ($query) {
//             $query->select('id_akun')->from('jurnals');
//         })
//         ->get();



//         $akun = \App\Akun::orderBy('akun_code', 'ASC')->get();
//     $groups = \App\Akun::distinct()->pluck('group'); // Mengambil daftar grup unik
//     foreach ($akun as $item) {
//         $item->isUsedInJournals = \DB::table('jurnals')->where('id_akun', $item->akun_code)->exists();
//         $item->hasParent = \App\Akun::where('parent', $item->akun_code)->exists();
//     }

//     return view('akun.index', [
//         'akun' => $akun,
//         'groups' => $groups,
//         'parents' => $parents,
//     ]);
// }


    public function index()
    {
        //Ambil akun tanpa parent (level 0)
        $parents = \App\Akun::whereNotIn('akun_code', function ($query) {
            $query->select('id_akun')->from('jurnals');
        })
        ->get();



        $akun = \App\Akun::orderBy('akun_code', 'ASC')->get();
    $groups = \App\Akun::distinct()->pluck('group'); // Mengambil daftar grup unik
    foreach ($akun as $item) {
        $item->isUsedInJournals = \DB::table('jurnals')->where('id_akun', $item->akun_code)->exists();
        $item->hasParent = \App\Akun::where('parent', $item->akun_code)->exists();
        $rootAkuns = \App\Akun::whereNull('parent')->get();
    }

    return view('akun.index',[
        'rootAkuns'=>$rootAkuns,
        'akun' => $akun,
        'groups' => $groups,
        'parents' => $parents,
    ]);
}


public function filterParents($category)
{
    $filteredParents = \App\Akun::where('category', $category)
    ->whereNotIn('akun_code', function ($query) {
        $query->select('id_akun')->from('jurnals');
    })
    ->get();

    return response()->json($filteredParents);
}

public function getTypes($group)
{
    $types = \App\Akun::where('group', $group)->pluck('type')->unique();
    return response()->json($types);
}

public function getCategories($type)
{
    $categories = \App\Akun::where('type', $type)->pluck('category')->unique();
    return response()->json($categories);
}

public function jurnal()
{
        //
 $jurnal = \App\Akuntransaction::orderBy('date','ASC')->get();

 return view ('akun/jurnal',['jurnal' =>$jurnal]);
}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //



        $groupedAkuns = \App\Akun::all()->groupBy('group');
        return view('akun.create',['groupedAkuns'=>$groupedAkuns]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {


        $request->validate([
            'name' => 'required|string|unique:akuns',
            'akun_code' => 'required|unique:akuns',
            'category' => 'required',
            'group' => 'required',
            'description' => 'nullable|string',
        // 'tax' => 'required', 
        // 'tax_value' => 'required', 
        ]);


        try {
    // Validasi input
//          
//            


    // Menyimpan data ke database
            \App\Akun::create($request->all());

    // Redirect ke halaman sebelumnya dengan pesan sukses
            return redirect()->back()->with('success', 'Item created successfully!');
        } catch (\Exception $e) {
    // Tangani error jika terjadi kegagalan
            return redirect()->back()->with('error', 'Failed to create item: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getChildren($parentCode)
    {
        $children = \App\Akun::where('parent', $parentCode)->get();
        return response()->json($children);
    }

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
        $akun = \App\Akun::findOrFail($id);


        // Kirim data ke view edit
        return view('akun.edit',['akun' =>$akun]);
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
        {
            $request->validate([
                'name' => 'required|string',
                'akun_code' => 'required|string|unique:akuns,akun_code,' . $id,
                'category' => 'required|integer',
                'description' => 'nullable|string',
                'tax' => 'nullable|boolean',
                'tax_value' => 'nullable|numeric|min:0',
            ]);

            $akun = Akun::findOrFail($id);
            $akun->update($request->all());

            return redirect()->route('akun.index')->with('success', 'Akun updated successfully!');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($akun_code)
    {


    // Cek apakah akun_code digunakan di tabel jurnals
        $isUsedInJournals = \DB::table('jurnals')->where('id_akun', $akun_code)->exists();

    // Jika digunakan, tampilkan pesan error dan jangan hapus
        if ($isUsedInJournals) {
            return redirect()->back()->with('error', 'The account cannot be deleted because it is already used in the journals table.
                ');
        }

    // Hapus data dari tabel akun
        $akun = \App\Akun::where('akun_code', $akun_code)->first();

        if ($akun) {
            $akun->delete();
            return redirect()->back()->with('success', 'The account has been successfully deleted.');
        }

        return redirect()->back()->with('error', 'The account was not found.');
    }

    public function getStates($id) 
    {        
        $states = \App\Akuntransaction::Where('name',$id)->first();
        
        return json_encode($states);
    }
}
