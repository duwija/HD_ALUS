<?php

namespace App\Http\Controllers;

use App\Distpointgroup;
use Illuminate\Http\Request;
use Exception;   
use DB;
use DataTables;
class DistpointgroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

      $distpointgroups = \App\Distpointgroup::all();


      return view ('distpointgroup/index',['distpointgroups' =>$distpointgroups]);
  }




  public function table_distpointgroup_list(Request $request){


     $distpointgroup = \App\Distpointgroup::all();

     return DataTables::of($distpointgroup)
     ->addIndexColumn()
     ->editColumn('name',function($distpointgroup)
     {

        return ' <a href="/distpointgroup/'.$distpointgroup->id.'" title="distpointgroup" class="badge badge-primary text-center  "> '.$distpointgroup->name. '</a>';
    })


     ->rawColumns(['DT_RowIndex','name','capacity','description'])

     ->make(true);
 }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //

       return view ('distpointgroup/create');
   }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:distpointgroups,name',
            'capacity' => 'required|numeric',
            'description' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            Distpointgroup::create($validated);
            DB::commit();

            return redirect('/distpointgroup')->with('success', 'Item created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
            ->withInput()
            ->withErrors(['error' => 'Failed to create item: ' . $e->getMessage()]);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Distpointgroup  $distpointgroup
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $distpointgroup = \App\Distpointgroup::findOrFail($id);

    // Ambil semua ID distpoint dalam grup ini
        $distpoints_in_group = \App\Distpoint::where('distpointgroup_id', $id)->get();
        $distpoint_ids = $distpoints_in_group->pluck('id');

    // Hitung total customer dalam grup ini
        $customer_group_count = \App\Customer::whereIn('id_distpoint', $distpoint_ids)->count();

    // Hitung jumlah distpoint dalam grup ini
        $group_distpoint_count = $distpoints_in_group->count();

    // Hitung total port kapasitas dari semua distpoint di grup ini
        $group_total_capacity = $distpoints_in_group->sum('ip');

    // Ambil data chart hirarki untuk OrgChart
        $distpoint_chart = $distpoints_in_group;

    // ===============================
    // Maps Markers: Buat array locations
    // ===============================
        $locations = [];

    // Tambahkan semua distpoint di grup ke lokasi
        foreach ($distpoints_in_group as $dist) {
            if ($this->isValidCoordinate($dist->coordinate)) {
                $locations[] = [
                    'coordinate' => $dist->coordinate,
                    'name' => $dist->name,
                    'type' => 'distpoint',
                    'capacity' => $dist->ip,
                    'id' => $dist->id,
                    'description' =>$dist->description,
                    'parent_coordinate' => $this->isValidCoordinate(optional($dist->parentDistPoint)->coordinate) 
                    ? optional($dist->parentDistPoint)->coordinate 
                    : null,
                ];
            }
        }

    // Tambahkan semua customer yang terkait distpoint di grup
        $customers = \App\Customer::whereIn('id_distpoint', $distpoint_ids)->get();
        foreach ($customers as $customer) {
            if ($this->isValidCoordinate($customer->coordinate)) {
                $locations[] = [
                    'coordinate' => $customer->coordinate,
                    'cid' => $customer->customer_id,
                    'name' => $customer->name,
                    'status' => $customer->status_name->name,
                    'id' => $customer->id,
                    'type' => 'customer',
                    'parent_coordinate' => $this->isValidCoordinate(optional($customer->distpoint)->coordinate)
                    ? optional($customer->distpoint)->coordinate
                    : null,
                ];
            }
        }

    // Tentukan pusat peta berdasarkan grup (ambil salah satu distpoint)
        $center = [
            'coordinate' => $distpoints_in_group->first()?->coordinate ?? tenant_config('COORDINATE_CENTER', env('COORDINATE_CENTER', '-6.200000,106.816666')),
            'zoom' => 15
        ];

        return view('distpointgroup.show', compact(
            'distpointgroup',
            'customer_group_count',
            'group_distpoint_count',
            'group_total_capacity',
            'distpoint_chart',
            'center',
            'locations'
        ));
    }

// Fungsi validasi koordinat
    private function isValidCoordinate($coordinate)
    {
        if (!$coordinate || !is_string($coordinate)) return false;

        $parts = explode(',', $coordinate);
        if (count($parts) !== 2) return false;

        $lat = trim($parts[0]);
        $lng = trim($parts[1]);

        return is_numeric($lat) && is_numeric($lng);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Distpointgroup  $distpointgroup
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //


        return view ('distpointgroup.edit',['distpointgroup' => \App\Distpointgroup::findOrFail($id)]);


    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Distpointgroup  $distpointgroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'capacity' => 'required|numeric',
            'description' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $group = \App\Distpointgroup::findOrFail($id);

            $dataToUpdate = [
                'capacity' => $validated['capacity'],
                'description' => $validated['description'] ?? null,
            ];

        // Cek apakah name berubah
            if ($validated['name'] !== $group->name) {
                $dataToUpdate['name'] = $validated['name'];
            }

            $group->update($dataToUpdate);

            DB::commit();

            return redirect('/distpointgroup')->with('success', 'Item updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
            ->withInput()
            ->withErrors(['error' => 'Failed to update item: ' . $e->getMessage()]);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Distpointgroup  $distpointgroup
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $deleted = Distpointgroup::destroy($id);

            if (!$deleted) {
                throw new \Exception("Data with ID $id not found or already deleted.");
            }

            DB::commit();

            return redirect('/site')->with('success', 'Item deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete Distpointgroup: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to delete item: ' . $e->getMessage());
        }
    }
}
