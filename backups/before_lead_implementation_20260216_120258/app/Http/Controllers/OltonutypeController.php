<?php

namespace App\Http\Controllers;

use App\Oltonutype;
use Illuminate\Http\Request;

class OltonutypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
     $oltonutype = \App\Oltonutype::where('id_olt', $id)
     ->orderBy('name','ASC')->get();
     $olt = \App\Olt::findOrFail($id);

     return view ('oltonutype/index',['oltonutype' =>$oltonutype, 'olt' =>$olt]);
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
            'created_at' => 'required',
            // 'description' => 'required'
        ]);

        \App\Oltonutype::create($request->all());

        return redirect ('/oltonutype/olt/'.$request->id_olt)->with('success','Item created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Oltonutype  $oltonutype
     * @return \Illuminate\Http\Response
     */
    public function show(Oltonutype $oltonutype)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Oltonutype  $oltonutype
     * @return \Illuminate\Http\Response
     */
    public function edit(Oltonutype $oltonutype)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Oltonutype  $oltonutype
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Oltonutype $oltonutype)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Oltonutype  $oltonutype
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $olt)
    {
        //
       \App\Oltonutype::destroy($id, $olt);
       return redirect ('oltonutype/olt/'.$olt)->with('success','Item deleted successfully!');
   }
}
