<?php

namespace App\Http\Controllers;

use App\Ticketcategorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketcategorieController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkPrivilege:admin,noc');
    }

    /**
     * Display a listing of ticket categories.
     */
    public function index()
    {
        $categories = Ticketcategorie::orderBy('name', 'asc')->get();
        return view('ticketcategories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new ticket category.
     */
    public function create()
    {
        return view('ticketcategories.create');
    }

    /**
     * Store a newly created ticket category in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:ticketcategories,name',
            'workflow' => 'nullable|string',
        ]);

        $category = new Ticketcategorie();
        $category->name = $request->name;
        
        // Parse JSON workflow from hidden field
        if ($request->workflow) {
            $category->workflow = json_decode($request->workflow, true);
        } else {
            $category->workflow = null;
        }
        
        $category->save();

        Log::channel('ticket')->info('Ticket Category Created: ' . $category->name . ' by ' . auth()->user()->name);

        return redirect()->route('ticketcategories.index')
            ->with('success', 'Kategori tiket berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified ticket category.
     */
    public function edit($id)
    {
        $category = Ticketcategorie::findOrFail($id);
        return view('ticketcategories.edit', compact('category'));
    }

    /**
     * Update the specified ticket category in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:ticketcategories,name,' . $id,
            'workflow' => 'nullable|string',
        ]);

        $category = Ticketcategorie::findOrFail($id);
        $category->name = $request->name;
        
        // Parse JSON workflow from hidden field
        if ($request->workflow) {
            $category->workflow = json_decode($request->workflow, true);
        } else {
            $category->workflow = null;
        }
        
        $category->save();

        Log::channel('ticket')->info('Ticket Category Updated: ' . $category->name . ' by ' . auth()->user()->name);

        return redirect()->route('ticketcategories.index')
            ->with('success', 'Kategori tiket berhasil diperbarui.');
    }

    /**
     * Remove the specified ticket category from storage (soft delete).
     */
    public function destroy($id)
    {
        $category = Ticketcategorie::findOrFail($id);
        $categoryName = $category->name;
        
        // Cek apakah kategori ini digunakan oleh ticket
        $ticketCount = \DB::table('tickets')
            ->where('id_categori', $id)
            ->count();
        
        if ($ticketCount > 0) {
            // Jika ada ticket yang menggunakan, lakukan soft delete
            $category->delete();
            Log::channel('ticket')->info('Ticket Category Soft Deleted (used by ' . $ticketCount . ' tickets): ' . $categoryName . ' by ' . auth()->user()->name);
            
            return redirect()->route('ticketcategories.index')
                ->with('success', 'Kategori tiket berhasil diarsipkan (masih digunakan oleh ' . $ticketCount . ' tiket).');
        } else {
            // Jika tidak ada ticket yang menggunakan, tetap soft delete untuk keamanan
            $category->delete();
            Log::channel('ticket')->info('Ticket Category Soft Deleted: ' . $categoryName . ' by ' . auth()->user()->name);
            
            return redirect()->route('ticketcategories.index')
                ->with('success', 'Kategori tiket berhasil dihapus.');
        }
    }

    /**
     * Display trashed categories (optional untuk restore nanti)
     */
    public function trashed()
    {
        $categories = Ticketcategorie::onlyTrashed()->orderBy('deleted_at', 'desc')->get();
        return view('ticketcategories.trashed', compact('categories'));
    }

    /**
     * Restore a soft deleted category
     */
    public function restore($id)
    {
        $category = Ticketcategorie::onlyTrashed()->findOrFail($id);
        $category->restore();
        
        Log::channel('ticket')->info('Ticket Category Restored: ' . $category->name . ' by ' . auth()->user()->name);
        
        return redirect()->route('ticketcategories.index')
            ->with('success', 'Kategori tiket berhasil dipulihkan.');
    }
}
