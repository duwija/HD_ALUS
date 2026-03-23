<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\LeaveRequest;
use App\OvertimeRequest;
use App\User;
use Carbon\Carbon;

class LeaveOvertimeAdminController extends Controller
{
    /** Hanya admin / hrd / management */
    private function isFullAdmin(): bool
    {
        $priv = Auth::user()->privilege ?? '';
        return in_array($priv, ['admin', 'hrd', 'management']);
    }

    /** User yang punya bawahan (supervisor) */
    private function isSupervisor(): bool
    {
        return User::where('supervisor_id', Auth::id())->exists();
    }

    private function checkAccess(): bool
    {
        return $this->isFullAdmin() || $this->isSupervisor();
    }

    /** Kembalikan array user_id bawahan langsung (kosong jika full-admin = lihat semua) */
    private function subordinateIds(): ?array
    {
        if ($this->isFullAdmin()) return null; // null = tidak di-filter
        return User::where('supervisor_id', Auth::id())->pluck('id')->all();
    }

    // ── IZIN / CUTI ──────────────────────────────────────────────────────────

    /** GET /leave */
    public function leaveIndex(Request $request)
    {
        if (!$this->checkAccess()) abort(403);

        $subIds = $this->subordinateIds();

        $q = LeaveRequest::with(['user', 'approver'])
            ->orderByDesc('created_at');

        // Supervisor hanya melihat pengajuan bawahannya
        if ($subIds !== null) $q->whereIn('user_id', $subIds);

        if ($request->filled('status'))   $q->where('status', $request->status);
        if ($request->filled('type'))     $q->where('type', $request->type);
        if ($request->filled('user_id'))  $q->where('user_id', $request->user_id);
        if ($request->filled('month'))    $q->whereRaw("DATE_FORMAT(start_date,'%Y-%m') = ?", [$request->month]);

        $leaves    = $q->paginate(20)->appends($request->all());
        $employees = $subIds !== null
            ? User::whereIn('id', $subIds)->orderBy('name')->get(['id', 'name'])
            : User::where('is_active', 1)->orderBy('name')->get(['id', 'name']);
        $pending   = $subIds !== null
            ? LeaveRequest::whereIn('user_id', $subIds)->where('status', 'pending')->count()
            : LeaveRequest::where('status', 'pending')->count();

        return view('leave.index', compact('leaves', 'employees', 'pending'));
    }

    /** POST /leave/{id}/approve */
    public function leaveApprove(Request $request, $id)
    {
        if (!$this->checkAccess()) abort(403);

        $request->validate([
            'action' => 'required|in:approved,rejected',
            'notes'  => 'nullable|string|max:500',
        ]);

        $leave = LeaveRequest::findOrFail($id);
        if ($leave->status !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }

        // Supervisor hanya boleh approve bawahan langsung
        if (!$this->isFullAdmin()) {
            $emp = User::find($leave->user_id);
            if (!$emp || $emp->supervisor_id !== Auth::id()) abort(403);
        }

        $leave->update([
            'status'         => $request->action,
            'approved_by'    => Auth::id(),
            'approved_at'    => now(),
            'approval_notes' => $request->notes,
        ]);

        // Push notif ke karyawan (opsional)
        $this->notifyEmployee($leave->user, $request->action, $leave->type_text);

        $label = $request->action === 'approved' ? 'disetujui' : 'ditolak';
        return back()->with('success', "Pengajuan {$leave->type_text} atas nama {$leave->user->name} berhasil {$label}.");
    }

    // ── LEMBUR ────────────────────────────────────────────────────────────────

    /** GET /overtime */
    public function overtimeIndex(Request $request)
    {
        if (!$this->checkAccess()) abort(403);

        $subIds = $this->subordinateIds();

        $q = OvertimeRequest::with(['user', 'approver'])
            ->orderByDesc('created_at');

        // Supervisor hanya melihat lembur bawahannya
        if ($subIds !== null) $q->whereIn('user_id', $subIds);

        if ($request->filled('status'))   $q->where('status', $request->status);
        if ($request->filled('user_id'))  $q->where('user_id', $request->user_id);
        if ($request->filled('month'))    $q->whereRaw("DATE_FORMAT(date,'%Y-%m') = ?", [$request->month]);

        $overtimes = $q->paginate(20)->appends($request->all());
        $employees = $subIds !== null
            ? User::whereIn('id', $subIds)->orderBy('name')->get(['id', 'name'])
            : User::where('is_active', 1)->orderBy('name')->get(['id', 'name']);
        $pending   = $subIds !== null
            ? OvertimeRequest::whereIn('user_id', $subIds)->where('status', 'pending')->count()
            : OvertimeRequest::where('status', 'pending')->count();

        return view('leave.overtime', compact('overtimes', 'employees', 'pending'));
    }

    /** POST /overtime/{id}/approve */
    public function overtimeApprove(Request $request, $id)
    {
        if (!$this->checkAccess()) abort(403);

        $request->validate([
            'action' => 'required|in:approved,rejected',
            'notes'  => 'nullable|string|max:500',
        ]);

        $overtime = OvertimeRequest::findOrFail($id);
        if ($overtime->status !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }

        // Supervisor hanya boleh approve bawahan langsung
        if (!$this->isFullAdmin()) {
            $emp = User::find($overtime->user_id);
            if (!$emp || $emp->supervisor_id !== Auth::id()) abort(403);
        }

        $overtime->update([
            'status'         => $request->action,
            'approved_by'    => Auth::id(),
            'approved_at'    => now(),
            'approval_notes' => $request->notes,
        ]);

        $this->notifyEmployee($overtime->user, $request->action, 'Lembur');

        $label = $request->action === 'approved' ? 'disetujui' : 'ditolak';
        return back()->with('success', "Pengajuan lembur atas nama {$overtime->user->name} berhasil {$label}.");
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function notifyEmployee(?User $user, string $action, string $type): void
    {
        if (!$user || !$user->fcm_token) return;
        $title = $action === 'approved' ? "✅ {$type} Disetujui" : "❌ {$type} Ditolak";
        $body  = $action === 'approved'
            ? "Pengajuan {$type} Anda telah disetujui oleh atasan Anda."
            : "Pengajuan {$type} Anda ditolak oleh atasan Anda. Cek catatan di aplikasi.";

        try {
            $projectId   = config('services.firebase.project_id');
            $creds       = config('services.firebase.credentials');
            if (!$projectId || !$creds || !file_exists($creds)) return;

            $sa = json_decode(file_get_contents($creds), true);
            $now = time();
            $b64 = fn(string $d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
            $header  = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = $b64(json_encode([
                'iss' => $sa['client_email'], 'sub' => $sa['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'iat' => $now, 'exp' => $now + 3600,
            ]));
            $sig = ''; openssl_sign("$header.$payload", $sig, openssl_pkey_get_private($sa['private_key']), OPENSSL_ALGO_SHA256);
            $jwt = "$header.$payload." . $b64($sig);

            $resp = (new \GuzzleHttp\Client(['timeout' => 10]))
                ->post('https://oauth2.googleapis.com/token', ['form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]]);
            $token = json_decode($resp->getBody(), true)['access_token'] ?? null;
            if (!$token) return;

            (new \GuzzleHttp\Client(['timeout' => 5]))
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
                    'json'    => ['message' => [
                        'token'        => $user->fcm_token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data'         => ['title' => $title, 'body' => $body],
                    ]],
                ]);
        } catch (\Throwable $e) {
            \Log::warning('FCM admin notify error: ' . $e->getMessage());
        }
    }
}
