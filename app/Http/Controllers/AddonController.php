<?php

namespace App\Http\Controllers;

use App\Addon;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    public function index()
    {
        $addons = Addon::withTrashed()->orderBy('id', 'DESC')->get();
        return view('addon.index', compact('addons'));
    }

    public function create()
    {
        return view('addon.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:191|unique:addons,name',
            'price' => 'required|integer|min:0',
            'description' => 'nullable|string|max:191',
        ]);

        Addon::create($request->only('name', 'price', 'description'));

        return redirect()->route('addon.index')->with('success', 'Add-on berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $addon = Addon::withTrashed()->findOrFail($id);
        return view('addon.edit', compact('addon'));
    }

    public function update(Request $request, $id)
    {
        $addon = Addon::withTrashed()->findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:191|unique:addons,name,' . $id,
            'price' => 'required|integer|min:0',
            'description' => 'nullable|string|max:191',
        ]);

        $addon->update($request->only('name', 'price', 'description'));

        return redirect()->route('addon.index')->with('success', 'Add-on berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $addon = Addon::findOrFail($id);
        $addon->delete();
        return redirect()->route('addon.index')->with('success', 'Add-on berhasil dihapus.');
    }

    public function restore($id)
    {
        Addon::withTrashed()->findOrFail($id)->restore();
        return redirect()->route('addon.index')->with('success', 'Add-on berhasil dipulihkan.');
    }
}
