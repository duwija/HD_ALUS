<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TagController extends Controller
{
    /** Halaman daftar tag (aktif + terhapus) */
    public function index()
    {
        $tags        = \App\Tag::withCount(['tickets', 'customers'])->get();
        $trashedTags = \App\Tag::onlyTrashed()->withCount(['tickets', 'customers'])->get();
        return view('tag.index', compact('tags', 'trashedTags'));
    }

    /** Tambah tag baru (AJAX atau form POST) */
    public function store(Request $request)
    {
        try {
            $request->validate(['new_tag' => 'required|string|max:255']);

            $newTag = \App\Tag::create(['name' => $request->new_tag]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['id' => $newTag->id, 'name' => $newTag->name]);
            }
            return redirect()->route('tag.index')->with('success', 'Tag "' . $newTag->name . '" berhasil ditambahkan.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal menambahkan tag: ' . $e->getMessage());
        }
    }

    /** Update nama tag */
    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $tag = \App\Tag::findOrFail($id);
        $tag->update(['name' => $request->name]);
        return redirect()->route('tag.index')->with('success', 'Tag berhasil diperbarui.');
    }

    /** Soft delete tag */
    public function destroy($id)
    {
        $tag = \App\Tag::findOrFail($id);
        // Lepas relasi pivot dulu agar tidak mengganggu data aktif
        $tag->tickets()->detach();
        $tag->customers()->detach();
        $tag->delete();
        return redirect()->route('tag.index')->with('success', 'Tag "' . $tag->name . '" berhasil dihapus.');
    }

    /** Restore tag yang di-soft delete */
    public function restore($id)
    {
        $tag = \App\Tag::onlyTrashed()->findOrFail($id);
        $tag->restore();
        return redirect()->route('tag.index')->with('success', 'Tag "' . $tag->name . '" berhasil dipulihkan.');
    }

    /** Hard delete permanen */
    public function forceDestroy($id)
    {
        $tag = \App\Tag::onlyTrashed()->findOrFail($id);
        $tag->forceDelete();
        return redirect()->route('tag.index')->with('success', 'Tag berhasil dihapus permanen.');
    }
}
