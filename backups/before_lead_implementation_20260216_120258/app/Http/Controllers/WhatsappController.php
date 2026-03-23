<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DataTables;
use App\Walog;

class WhatsappController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')
        ->except(['webhook', 'ack', 'history', 'chats', 'sessionStatus', 'qrStatus', 'delete', 'send']);

        $this->middleware('checkPrivilege:admin,noc,accounting,payment,user,vendor,merchant')
        ->except(['webhook', 'ack', 'history', 'chats', 'sessionStatus', 'qrStatus', 'delete', 'send']);
    }

    /**
     * Get gateway URL with /api suffix
     * Called after middleware runs, so tenant_config() has proper context
     */
    protected function getGatewayUrl()
    {
        return rtrim(tenant_config('wa_gateway_url', env('WA_GATEWAY_URL', 'http://127.0.0.1:3005')), '/') . '/api';
    }

    /* =======================================================
     * 🔘 CHAT INTERFACE
     * ======================================================= */
    public function chatSelector()
    {
        try {
            $response = Http::get($this->getGatewayUrl() . '/health');
            $data = $response->json();
            $sessions = $data['sessions'] ?? [];
        } catch (\Exception $e) {
            $sessions = [];
        }

        return view('wa.chat-selector', compact('sessions'));
    }

    public function chat($session)
    {
        return view('wa.chat', compact('session'));
    }

    /* =======================================================
     * 📱 QR & SESSION MANAGEMENT
     * ======================================================= */

    public function showQr(Request $request, $session)
    {
        try {
            $response = Http::timeout(8)->get("{$this->getGatewayUrl()}/{$session}/qr");
            $data = $response->json();
        } catch (\Exception $e) {
            return view('wa.qr', [
                'status' => 'error',
                'qrUrl' => null,
                'device' => [],
                'error' => 'Gagal terhubung ke Gateway: ' . $e->getMessage()
            ]);
        }

        $status = $data['status'] ?? 'error';
        $qrRaw  = $data['qr'] ?? null;
        $device = $data['device'] ?? [];
        $qrUrl  = $qrRaw ? 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrRaw) : null;

        return view('wa.qr', compact('status', 'qrUrl', 'device'));
    }

    public function logout($session)        { return $this->proxyAction("{$this->getGatewayUrl()}/{$session}/logout", 'logout'); }
    public function forceLogout($session)   { return $this->proxyAction("{$this->getGatewayUrl()}/{$session}/force-logout", 'force logout'); }
    public function cleanSession($session)  { return $this->proxyAction("{$this->getGatewayUrl()}/{$session}/clean", 'clean session'); }
    public function restart($session)       { return $this->proxyAction("{$this->getGatewayUrl()}/{$session}/restart", 'restart'); }
    
    public function delete($session)        
    { 
        try {
            $response = Http::timeout(10)->delete("{$this->getGatewayUrl()}/{$session}");
            if ($response->successful()) {
                return response()->json($response->json());
            }
            throw new \Exception('Gateway response not successful: ' . $response->status());
        } catch (\Exception $e) {
            Log::error("delete session: Gateway error", ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 503);
        }
    }

    public function sessionStatus($session)
    {
        try {
            $response = Http::timeout(5)->get("{$this->getGatewayUrl()}/{$session}/status");
            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('sessionStatus(): Gateway offline', ['error' => $e->getMessage(), 'session' => $session]);
            return response()->json([
                'session' => $session,
                'status' => 'gateway_offline',
                'message' => 'Gateway offline - please check Node.js service'
            ]);
        }
    }

    public function qrStatus($session)
    {
        try {
            $response = Http::timeout(5)->get("{$this->getGatewayUrl()}/{$session}/qr");
            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('qrStatus(): Gateway offline', ['error' => $e->getMessage(), 'session' => $session]);
            return response()->json([
                'session' => $session,
                'status' => 'gateway_offline',
                'message' => 'Gateway offline - cannot generate QR'
            ], 503);
        }
    }

    /* =======================================================
     * 💬 MESSAGE SEND, RECEIVE, ACK
     * ======================================================= */
    public function sendMedia(Request $request, $session)
    {
        $validated = $request->validate([
            'number' => 'required|string',
            'file' => 'required|file',
            'caption' => 'nullable|string'
        ]);

        $file = $request->file('file');
    $path = $file->store('wa_uploads', 'public'); // simpan ke storage/app/public/wa_uploads

    // misalnya kamu sudah punya fungsi kirim media ke gateway WhatsApp
    $result = Http::attach(
        'file', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
    )->post("{$this->getGatewayUrl()}/{$session}/send-media", [
        'number' => $validated['number'],
        'caption' => $validated['caption'] ?? '',
    ]);

    return response()->json($result->json());
}


public function send(Request $request, $session)
{
    $request->validate([
        'number' => 'required|string',
        'message' => 'required|string',
    ]);

    try {
        $payload = [
            'number' => $request->number,
            'message' => $request->message
        ];

        Log::info("send(): sending message", [
            'session' => $session,
            'number' => $payload['number'],
            'message' => $payload['message']
        ]);

        $response = Http::timeout(30)->post("{$this->getGatewayUrl()}/{$session}/send", $payload);

        if (!$response->successful()) {
            Log::warning("send(): gateway returned error", [
                'session' => $session,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'error' => 'Gateway responded with error',
                'status' => $response->status(),
                'body' => $response->json(),
                'session' => $session
            ], $response->status());
        }

        $data = $response->json();

            // Simpan ke log pesan keluar
        Walog::create([
            'session'     => $session,
            'number'      => $payload['number'],
            'message'     => $payload['message'],
            'status'      => 'pending',
            'message_id'  => $data['messageId'] ?? null,
            'direction'   => 'out',
            'created_at'  => now(),
        ]);

        Log::info("send(): message sent successfully", [
            'session' => $session,
            'number' => $payload['number']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim'
        ]);

    } catch (\Exception $e) {
        Log::error("send(): failed to send message", [
            'error' => $e->getMessage(),
            'session' => $session
        ]);

        return response()->json([
            'error' => 'Gagal mengirim pesan — gateway offline atau tidak responsif',
            'message' => $e->getMessage(),
            'session' => $session
        ], 503);
    }
}

public function webhook(Request $request)
{
    $data = $request->validate([
        'session'   => 'required|string',
        'from'      => 'required|string',
        'body'      => 'nullable|string',
        'id'        => 'required|string',
        'timestamp' => 'required|integer',
    ]);

    Walog::create([
        'session'    => $data['session'],
        'number'     => $data['from'],
        'message'    => $data['body'],
        'status'     => 'received',
        'message_id' => $data['id'],
        'direction'  => 'in',
        'created_at' => now(),
    ]);

    return response()->noContent();
}

public function ack(Request $request, $session)
{
    $data = $request->validate([
        'id'  => 'required|string',
        'ack' => 'required|integer',
    ]);

    $log = Walog::where('session', $session)
    ->where('message_id', $data['id'])
    ->where('direction', 'out')
    ->first();

    if ($log) {
        $map = [0 => 'pending', 1 => 'sent', 2 => 'delivered', 3 => 'read'];
        $log->status = $map[$data['ack']] ?? 'unknown';
        $log->save();
    }

    return response()->noContent();
}

    /* =======================================================
     * 📋 CHATS & HISTORY
     * ======================================================= */

    public function chats(Request $request, $session)
    {
        try {
            $response = Http::timeout(60)->get("{$this->getGatewayUrl()}/{$session}/chats");

            if (!$response->successful()) {
                Log::warning("chats(): gateway returned non-200", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'session' => $session
                ]);

                return response()->json([
                    'error' => 'Gateway responded with error',
                    'status' => $response->status(),
                    'session' => $session
                ], $response->status());
            }

            $chats = $response->json();
            Log::info("chats(): got real data from gateway for {$session}", [
                'count' => is_array($chats) ? count($chats) : 0
            ]);

            return response()->json($chats);

        } catch (\Exception $e) {
            Log::error('chats(): Gateway offline', [
                'error' => $e->getMessage(),
                'session' => $session
            ]);

            return response()->json([
                'error' => 'Gateway offline or session not ready',
                'session' => $session
            ], 503);
        }
    }

    public function history(Request $request, $session)
    {
        $chatId = $request->query('chatId');

        if (!$chatId) {
            return response()->json(['error' => 'chatId parameter is required'], 400);
        }

        try {
            $url = "{$this->getGatewayUrl()}/{$session}/history?chatId=" . urlencode($chatId);

            Log::info("history(): fetching chat history", [
                'session' => $session,
                'chatId' => $chatId
            ]);

            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                Log::warning("history(): gateway returned non-200", [
                    'status' => $response->status(),
                    'session' => $session,
                    'chatId' => $chatId,
                    'body' => $response->body()
                ]);

                return response()->json([
                    'error' => 'Gateway responded with error',
                    'status' => $response->status(),
                    'session' => $session
                ], $response->status());
            }

            $data = $response->json();

            Log::info("history(): retrieved messages", [
                'session' => $session,
                'chatId' => $chatId,
                'message_count' => is_array($data) ? count($data) : 0
            ]);

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error("history(): Gateway offline or unresponsive", [
                'error' => $e->getMessage(),
                'session' => $session,
                'chatId' => $chatId
            ]);

            return response()->json([
                'error' => 'Gateway offline or session not ready',
                'message' => $e->getMessage(),
                'session' => $session
            ], 503);
        }
    }

    /* =======================================================
     * 📦 GROUPS & LOGS
     * ======================================================= */

    public function getGroups($session)
    {
        try {
            $response = Http::get("{$this->getGatewayUrl()}/{$session}/groups");
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal ambil data grup'], 500);
        }
    }

    public function logs()
    {
        $sessions = Walog::select('session')->distinct()->pluck('session')->toArray();
        return view('wa.logs', compact('sessions'));
    }

    public function logsTable(Request $request)
    {
        $query = Walog::query();

        if ($request->date_from && $request->date_end) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_end)->endOfDay()
            ]);
        }

        if ($request->number) $query->where('number', 'like', '%' . $request->number . '%');
        if ($request->session) $query->where('session', $request->session);
        if ($request->status) $query->where('status', $request->status);

        return DataTables::of($query)
        ->addIndexColumn()
        ->editColumn('message', fn($row) =>
            '<span title="' . e($row->message) . '">' . e(Str::limit($row->message, 65)) . '</span>'
        )
        ->rawColumns(['message'])
        ->make(true);
    }

    /* =======================================================
     * 🔧 HELPER
     * ======================================================= */

    protected function proxyAction($url, $actionName)
    {
        try {
            $response = Http::timeout(10)->post($url);
            if ($response->successful()) {
                return response()->json(['status' => 'success', 'action' => $actionName]);
            }
            throw new \Exception('Gateway response not successful: ' . $response->status());
        } catch (\Exception $e) {
            Log::error("{$actionName}(): Gateway error", ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 503);
        }
    }
}
