<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TagController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'new_tag' => 'required|string|max:255',
            ]);

        // Membuat tag baru
            $newTag = \App\Tag::create([
                'name' => $request->new_tag,
            ]);

        // Mengembalikan response JSON dengan data tag baru
            return response()->json([
                'id' => $newTag->id,
                'name' => $newTag->name,
            ]);
            return redirect()->back()->with('success', '');
        } catch (\Exception $e) {
        // Jika terjadi error, kembalikan response JSON error
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


}
