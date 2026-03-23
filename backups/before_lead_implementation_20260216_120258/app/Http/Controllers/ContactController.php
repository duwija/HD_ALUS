<?php

namespace App\Http\Controllers;
use DataTables;
use Illuminate\Http\Request;
use Carbon\Carbon;


class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return view('contact/index');
    }

    public function searchforjurnal(Request $request) {
    // Log::info('Data yang diterima:', $request->all());

    // Ambil data customer berdasarkan pencarian
        $contacts = \App\Contact::where('name', 'LIKE', "%{$request->q}%")
        ->orWhere('category', 'LIKE', "%{$request->q}%")
        ->orWhere('contact_id', 'LIKE', "%{$request->q}%")
        ->limit(100)
        ->get();

        return response()->json($contacts);
    }

    public function table_contact_list(Request $request)
    {

        if ($request->ajax()) {
            $contacts = \App\Contact::latest()->get();
            return DataTables::of($contacts)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '
                <a href="/contact/' . $row->contact_id . '/jurnal" class="btn btn-sm btn-primary">Jurnal</a>
                <a href="/contact/' . $row->id . '/edit" class="btn btn-sm btn-warning">Edit</a>
                <form action="/contact/' . $row->id . '" method="POST" class="d-inline delete-form" onsubmit="return confirmDelete(event)">
                ' . csrf_field() . '
                ' . method_field('DELETE') . '
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view ('contact/create');
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
            'contact_id'   => 'required|string|max:15',
            'name'    => 'required|string|max:100|unique:contacts,name',
            'phone'   => 'required|string|max:15',
            'email'   => 'required|email|max:100',
            'address' => 'required|string|max:255',
            'category'    => 'required|string',
            'note'    => 'nullable|string',
        ]);

        \App\Contact::create([
            'contact_id' =>$request->contact_id,
            'name'    => $request->name,
            'phone'   => $request->phone,
            'email'   => $request->email,
            'address' => $request->address,
            'category' => $request->category,
            'note'    => $request->note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('contact')->with('success', 'contact added successfully.');
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
        $contact = \App\Contact::findOrFail($id);
        return view('contact/edit', compact('contact'));
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
            'name'    => 'required|string|max:100|unique:contacts,name,' . $id,
            'category'    => 'required|string|max:255',
            'phone'   => 'required|string|max:15',
            'email'   => 'required|email|max:100',
            'address' => 'required|string|max:255',
            'note'    => 'nullable|string',
        ]);

        $contact = \App\Contact::findOrFail($id);
        $contact->update($request->all());

        return redirect('/contact')->with('success', 'contact updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        \App\Contact::findOrFail($id)->delete();
        return redirect('contact')->with('success', 'contact deleted successfully.');
    }
}
