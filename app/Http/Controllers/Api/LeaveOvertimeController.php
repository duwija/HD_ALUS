<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\LeaveRequest;
use App\OvertimeRequest;
use App\User;
use Carbon\Carbon;

/**
 * API Controller untuk Pengajuan Izin/Cuti/Lembur
 * Base URL: /api/employee/
 */
class LeaveOvertimeController extends Controller
{
    // ── LEAVE REQUESTS ────────────────────────────────────────────────────

    /** GET /api/employee/leaves  — daftar pengajuan izin/cuti milik saya */
    public function leaveIndex(Request $request)
    {
        $user   = $request->user();
        $leaves = LeaveRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($l) => $this->formatLeave($l));

        return response()->json(['success' => true, 'data' => $leaves]);
    }

    /** POST /api/employee/leaves  — ajukan izin/cuti baru */
    public function leaveStore(Request $request)
    {
        $request->validate([
            'type'       => 'required|in:cuti,sakit,izin_lainnya',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);
        $days  = $start->diffInDays($end) + 1;

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('leave_attachments', 'public');
        }

        $leave = LeaveRequest::create([
            'user_id'    => $user->id,
            'type'       => $request->type,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'days'       => $days,
            'reason'     => $request->reason,
            'attachment' => $attachmentPath,
            'status'     => 'pending',
        ]);

        // Notifikasi ke supervisor
        $this->notifySupervisor($user, 'Pengajuan ' . $leave->type_text,
            "{$user->name} mengajukan {$leave->type_text} pada {$request->start_date}" . ($days > 1 ? " s/d {$request->end_date}" : '') . ".");

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dikirim.',
            'data'    => $this->formatLeave($leave),
        ], 201);
    }

    /** GET /api/employee/leaves/{id} — detail */
    public function leaveShow(Request $request, $id)
    {
        $leave = LeaveRequest::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->formatLeave($leave)]);
    }

    // ── OVERTIME REQUESTS ─────────────────────────────────────────────────

    /** GET /api/employee/overtimes  — daftar pengajuan lembur */
    public function overtimeIndex(Request $request)
    {
        $user      = $request->user();
        $overtimes = OvertimeRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => $this->formatOvertime($o));

        return response()->json(['success' => true, 'data' => $overtimes]);
    }

    /** POST /api/employee/overtimes  — ajukan lembur baru */
    public function overtimeStore(Request $request)
    {
        $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'reason'     => 'required|string|max:1000',
        ]);

        $user = $request->user();

        $start    = Carbon::parse($request->date . ' ' . $request->start_time);
        $end      = Carbon::parse($request->date . ' ' . $request->end_time);
        $duration = round($start->floatDiffInHours($end), 2);

        $overtime = OvertimeRequest::create([
            'user_id'        => $user->id,
            'date'           => $request->date,
            'start_time'     => $request->start_time,
            'end_time'       => $request->end_time,
            'duration_hours' => $duration,
            'reason'         => $request->reason,
            'status'         => 'pending',
        ]);

        // Notifikasi ke supervisor
        $this->notifySupervisor($user, 'Pengajuan Lembur',
            "{$user->name} mengajukan lembur pada {$request->date} ({$request->start_time}–{$request->end_time}).");

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dikirim.',
            'data'    => $this->formatOvertime($overtime),
        ], 201);
    }

    /** GET /api/employee/overtimes/{id} — detail */
    public function overtimeShow(Request $request, $id)
    {
        $overtime = OvertimeRequest::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->formatOvertime($overtime)]);
    }

    // ── APPROVAL (Supervisor) ─────────────────────────────────────────────

    /** POST /api/employee/leaves/{id}/approve  */
    public function leaveApprove(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected', 'notes' => 'nullable|string']);

        $leave = LeaveRequest::findOrFail($id);

        // Pastikan user adalah supervisor karyawan tsb
        $emp = User::find($leave->user_id);
        if (!$emp || $emp->supervisor_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki akses.'], 403);
        }

        $leave->update([
            'status'         => $request->status,
            'approved_by'    => $request->user()->id,
            'approved_at'    => now(),
            'approval_notes' => $request->notes,
        ]);

        // Notifikasi ke karyawan
        $this->notifyUser($emp, 'Status Pengajuan ' . $leave->type_text,
            "Pengajuan {$leave->type_text} Anda telah " . ($request->status === 'approved' ? 'disetujui' : 'ditolak') . ".");

        return response()->json(['success' => true, 'message' => 'Status diperbarui.', 'data' => $this->formatLeave($leave)]);
    }

    /** POST /api/employee/overtimes/{id}/approve  */
    public function overtimeApprove(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected', 'notes' => 'nullable|string']);

        $overtime = OvertimeRequest::findOrFail($id);

        $emp = User::find($overtime->user_id);
        if (!$emp || $emp->supervisor_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki akses.'], 403);
        }

        $overtime->update([
            'status'         => $request->status,
            'approved_by'    => $request->user()->id,
            'approved_at'    => now(),
            'approval_notes' => $request->notes,
        ]);

        $this->notifyUser($emp, 'Status Pengajuan Lembur',
            "Pengajuan lembur Anda tanggal {$overtime->date->format('d/m/Y')} telah " . ($request->status === 'approved' ? 'disetujui' : 'ditolak') . ".");

        return response()->json(['success' => true, 'message' => 'Status diperbarui.', 'data' => $this->formatOvertime($overtime)]);
    }

    // ── Supervisor: daftar pending approval ──────────────────────────────

    /** GET /api/employee/supervisor/leaves  — izin yg perlu diapprove */
    public function supervisorLeaves(Request $request)
    {
        $me = $request->user();
        $subordinateIds = User::where('supervisor_id', $me->id)->pluck('id');

        $leaves = LeaveRequest::whereIn('user_id', $subordinateIds)
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($l) => $this->formatLeave($l, true));

        return response()->json(['success' => true, 'data' => $leaves]);
    }

    /** GET /api/employee/supervisor/overtimes — lembur yg perlu diapprove */
    public function supervisorOvertimes(Request $request)
    {
        $me = $request->user();
        $subordinateIds = User::where('supervisor_id', $me->id)->pluck('id');

        $overtimes = OvertimeRequest::whereIn('user_id', $subordinateIds)
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => $this->formatOvertime($o, true));

        return response()->json(['success' => true, 'data' => $overtimes]);
    }

    // ── FCM Token ─────────────────────────────────────────────────────────

    /** POST /api/employee/fcm-token — simpan/update FCM token */
    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['success' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function formatLeave(LeaveRequest $l, bool $withUser = false): array
    {
        $data = [
            'id'             => $l->id,
            'type'           => $l->type,
            'type_text'      => $l->type_text,
            'start_date'     => $l->start_date?->format('Y-m-d'),
            'end_date'       => $l->end_date?->format('Y-m-d'),
            'days'           => $l->days,
            'reason'         => $l->reason,
            'attachment_url' => $l->attachment ? asset('storage/KC/' . $l->attachment) : null,
            'status'         => $l->status,
            'status_text'    => $l->status_badge,
            'approved_by'    => optional($l->approver)->name,
            'approved_at'    => $l->approved_at?->format('Y-m-d H:i'),
            'approval_notes' => $l->approval_notes,
            'created_at'     => $l->created_at?->format('Y-m-d H:i'),
        ];
        if ($withUser) {
            $data['employee_name'] = optional($l->user)->name;
        }
        return $data;
    }

    private function formatOvertime(OvertimeRequest $o, bool $withUser = false): array
    {
        $data = [
            'id'             => $o->id,
            'date'           => $o->date?->format('Y-m-d'),
            'start_time'     => $o->start_time,
            'end_time'       => $o->end_time,
            'duration_hours' => $o->duration_hours,
            'reason'         => $o->reason,
            'status'         => $o->status,
            'status_text'    => $o->status_badge,
            'approved_by'    => optional($o->approver)->name,
            'approved_at'    => $o->approved_at?->format('Y-m-d H:i'),
            'approval_notes' => $o->approval_notes,
            'created_at'     => $o->created_at?->format('Y-m-d H:i'),
        ];
        if ($withUser) {
            $data['employee_name'] = optional($o->user)->name;
        }
        return $data;
    }

    /** Kirim FCM push notification ke supervisor */
    private function notifySupervisor(User $employee, string $title, string $body): void
    {
        if (!$employee->supervisor_id) return;
        $supervisor = User::find($employee->supervisor_id);
        if ($supervisor && $supervisor->fcm_token) {
            $this->sendFcm($supervisor->fcm_token, $title, $body);
        }
    }

    /** Kirim FCM push notification ke karyawan */
    private function notifyUser(User $user, string $title, string $body): void
    {
        if ($user->fcm_token) {
            $this->sendFcm($user->fcm_token, $title, $body);
        }
    }

    /** Kirim FCM via HTTP V1 API (Service Account) */
    private function sendFcm(string $token, string $title, string $body): void
    {
        $projectId   = config('services.firebase.project_id');
        $accessToken = $this->getFcmAccessToken();
        if (!$accessToken || !$projectId) {
            \Log::warning('FCM: credentials tidak tersedia atau access token gagal');
            return;
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 5]);
            $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'message' => [
                        'token'        => $token,
                        'notification' => ['title' => $title, 'body'  => $body],
                        'android'      => ['notification' => ['sound' => 'default']],
                        'data'         => ['title' => $title, 'body'  => $body],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::warning('FCM send error: ' . $e->getMessage());
        }
    }

    /**
     * Dapatkan OAuth2 access token dari Service Account JSON
     * (tanpa library tambahan — pakai openssl PHP bawaan)
     */
    private function getFcmAccessToken(): ?string
    {
        $credentialsPath = config('services.firebase.credentials');
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            \Log::warning('FCM: file credentials tidak ditemukan: ' . $credentialsPath);
            return null;
        }

        try {
            $creds = json_decode(file_get_contents($credentialsPath), true);

            $now = time();
            $header  = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = $this->base64url(json_encode([
                'iss'   => $creds['client_email'],
                'sub'   => $creds['client_email'],
                'aud'   => 'https://oauth2.googleapis.com/token',
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $sigInput   = "$header.$payload";
            $privateKey = openssl_pkey_get_private($creds['private_key']);
            openssl_sign($sigInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $jwt = "$sigInput." . $this->base64url($signature);

            $response = (new \GuzzleHttp\Client(['timeout' => 10]))
                ->post('https://oauth2.googleapis.com/token', [
                    'form_params' => [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion'  => $jwt,
                    ],
                ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'] ?? null;

        } catch (\Throwable $e) {
            \Log::warning('FCM getAccessToken error: ' . $e->getMessage());
            return null;
        }
    }

    /** Base64url encode (RFC 4648) */
    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
