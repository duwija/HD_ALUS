<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Ticket;
use App\Ticketdetail;
use App\Ticketcategorie;
use App\User;
use Illuminate\Http\Request;

class TicketApiController extends Controller
{
    // ── Daftar tiket (semua / my ticket) ───────────────────────────────────
    public function index(Request $request)
    {
        $user  = $request->user();
        $mine  = $request->boolean('mine', false);
        $status= $request->get('status');   // Open, Inprogress, Solve, Close, (kosong = semua)
        $search= $request->get('search');

        $query = Ticket::with(['categorie','assignToUser','ticketdetail'])
            ->orderByDesc('created_at');

        if ($mine) {
            $query->where('assign_to', $user->id);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tittle', 'like', "%$search%")
                  ->orWhere('id', $search);
            });
        }

        $tickets = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $tickets->map(fn($t) => $this->ticketData($t)),
            'meta'    => [
                'current_page' => $tickets->currentPage(),
                'last_page'    => $tickets->lastPage(),
                'total'        => $tickets->total(),
            ],
        ]);
    }

    // ── Detail tiket + riwayat update ──────────────────────────────────────
    public function show(Request $request, $id)
    {
        $ticket = Ticket::with([
            'categorie', 'assignToUser', 'customer',
            'ticketdetail', 'steps', 'currentStep',
        ])->findOrFail($id);

        $details = $ticket->ticketdetail()
            ->with('ticket')
            ->orderBy('created_at')
            ->get()
            ->map(fn($d) => [
                'id'          => $d->id,
                'description' => $d->description,
                'updated_by'  => $d->updated_by,
                'created_at'  => $d->created_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'success' => true,
            'data'    => array_merge($this->ticketData($ticket), [
                'customer'    => $ticket->customer ? [
                    'id'   => $ticket->customer->id,
                    'name' => $ticket->customer->name,
                    'phone'=> $ticket->customer->phone,
                ] : null,
                'description' => $ticket->description,
                'member'      => $ticket->member,
                'updates'     => $details,
                'steps'       => $ticket->steps->map(fn($s) => [
                    'id'       => $s->id,
                    'name'     => $s->name,
                    'position' => $s->position,
                    'is_current' => $ticket->current_step_id == $s->id,
                ]),
            ]),
        ]);
    }

    // ── Tambah update / komentar ────────────────────────────────────────────
    public function addUpdate(Request $request, $id)
    {
        $request->validate(['description' => 'required|string|max:2000']);

        $ticket = Ticket::findOrFail($id);
        $user   = $request->user();

        $detail = Ticketdetail::create([
            'id_ticket'   => $ticket->id,
            'description' => $request->description,
            'updated_by'  => $user->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Update berhasil ditambahkan.',
            'data'    => [
                'id'          => $detail->id,
                'description' => $detail->description,
                'updated_by'  => $detail->updated_by,
                'created_at'  => $detail->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // ── Update status tiket ─────────────────────────────────────────────────
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Open,Inprogress,Solve,Close',
        ]);

        $ticket = Ticket::findOrFail($id);
        $user   = $request->user();

        // Hanya technician yang ditugaskan atau semua karyawan bisa update
        $ticket->update(['status' => $request->status]);

        // Tambah log update
        Ticketdetail::create([
            'id_ticket'   => $ticket->id,
            'description' => "Status diubah ke [{$request->status}] oleh {$user->name}",
            'updated_by'  => $user->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Status tiket diperbarui ke {$request->status}.",
            'status'  => $ticket->status,
        ]);
    }

    // ── Ringkasan (untuk badge di app) ──────────────────────────────────────
    public function summary(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data'    => [
                'my_open'      => Ticket::where('assign_to', $user->id)->where('status', 'Open')->count(),
                'my_inprogress'=> Ticket::where('assign_to', $user->id)->where('status', 'Inprogress')->count(),
                'my_total'     => Ticket::where('assign_to', $user->id)->where('status','!=','Close')->count(),
                'all_open'     => Ticket::where('status', 'Open')->count(),
                'all_inprogress'=> Ticket::where('status', 'Inprogress')->count(),
            ],
        ]);
    }

    // ── Private helper ──────────────────────────────────────────────────────
    private function ticketData(Ticket $t): array
    {
        return [
            'id'          => $t->id,
            'tittle'      => $t->tittle,
            'status'      => $t->status,
            'called_by'   => $t->called_by,
            'phone'       => $t->phone,
            'date'        => $t->date,
            'time'        => $t->time,
            'category'    => optional($t->categorie)->name ?? optional($t->category)->name,
            'assign_to'   => optional($t->assignToUser)->name,
            'assign_to_id'=> $t->assign_to,
            'updates_count'=> $t->ticketdetail_count ?? $t->ticketdetail?->count() ?? 0,
            'created_at'  => $t->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
