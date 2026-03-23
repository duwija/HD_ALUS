<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\AppPromo;

/**
 * Controller untuk marketing mengelola promo/pengumuman di app
 * Route: /marketing/promos  (dilindungi auth admin/user)
 */
class AppPromoController extends Controller
{
    public function index()
    {
        $promos = AppPromo::latest()->paginate(20);
        return view('marketing.promos.index', compact('promos'));
    }

    public function create()
    {
        return view('marketing.promos.form', ['promo' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
            'badge'      => 'nullable|string|max:20',
            'image_url'  => 'nullable|url|max:500',
            'is_active'  => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = Auth::user()->name ?? Auth::user()->email ?? 'admin';

        AppPromo::create($data);

        return redirect()->route('marketing.promos.index')
            ->with('success', 'Promo berhasil disimpan.');
    }

    public function edit(AppPromo $promo)
    {
        return view('marketing.promos.form', compact('promo'));
    }

    public function update(Request $request, AppPromo $promo)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
            'badge'      => 'nullable|string|max:20',
            'image_url'  => 'nullable|url|max:500',
            'is_active'  => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $promo->update($data);

        return redirect()->route('marketing.promos.index')
            ->with('success', 'Promo berhasil diupdate.');
    }

    public function destroy(AppPromo $promo)
    {
        $promo->delete();
        return redirect()->route('marketing.promos.index')
            ->with('success', 'Promo dihapus.');
    }

    public function toggleActive(AppPromo $promo)
    {
        $promo->update(['is_active' => !$promo->is_active]);
        return response()->json(['is_active' => $promo->is_active]);
    }

    /**
     * Upload gambar/video untuk Summernote editor.
     * POST /marketing/promos/upload-media
     */
    public function uploadMedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm|max:20480',
        ]);

        $path = $request->file('file')->store('promo-media', 'public');

        return response()->json([
            'url'  => Storage::disk('public')->url($path),
            'type' => str_starts_with($request->file('file')->getMimeType(), 'video') ? 'video' : 'image',
        ]);
    }
}
