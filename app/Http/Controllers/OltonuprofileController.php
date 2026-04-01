<?php

namespace App\Http\Controllers;

use App\Oltonuprofile;
use Illuminate\Http\Request;

class OltonuprofileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
          //
        $oltonuprofile = \App\Oltonuprofile::where('id_olt', $id)
        ->orderBy('vlan','ASC')->get();
        $olt = \App\Olt::findOrFail($id);

        return view ('oltonuprofile/index',['oltonuprofile' =>$oltonuprofile, 'olt' =>$olt]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($olt)
    {
        //

      return view ('oltonuprofile/create',['olt' => $olt] );
  }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $request ->validate([
            'id_olt' => 'required',
            'name' => 'required',
            'vlan' => 'required',
            'created_at' => 'required',
            // 'description' => 'required'
        ]);


        \App\Oltonuprofile::create($request->all());

        return redirect ('/oltonuprofile/olt/'.$request->id_olt)->with('success','Item created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Oltonuprofile  $oltonuprofile
     * @return \Illuminate\Http\Response
     */
    public function show(Oltonuprofile $oltonuprofile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Oltonuprofile  $oltonuprofile
     * @return \Illuminate\Http\Response
     */
    public function edit(Oltonuprofile $oltonuprofile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Oltonuprofile  $oltonuprofile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Oltonuprofile $oltonuprofile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Oltonuprofile  $oltonuprofile
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $olt)
    {
        //
     \App\Oltonuprofile::destroy($id, $olt);
     return redirect ('oltonuprofile/olt/'.$olt)->with('success','Item deleted successfully!');
 }
}
