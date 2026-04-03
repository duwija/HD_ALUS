<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Customer;

class PppSecretController extends Controller
{
    const CHAP_FILE = '/etc/ppp/chap-secrets';
    const CHAP_BACKUP = '/etc/ppp/chap-secrets.bak';

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Tampilkan halaman manajemen PPP secrets.
     */
    public function index()
    {
        // Ambil semua customer yang punya pppoe
        $customers = Customer::whereNotNull('pppoe')
            ->where('pppoe', '!=', '')
            ->whereNull('deleted_at')
            ->orderBy('pppoe')
            ->get(['id', 'customer_id', 'name', 'pppoe', 'password', 'id_status']);

        // Baca entri dari file chap-secrets (yang tidak terkait customer DB)
        $fileEntries = $this->parseChapFile();

        // Ambil daftar pppoe dari DB untuk filter
        $dbPppoes = $customers->pluck('pppoe')->map(fn($p) => strtolower(trim($p)))->toArray();

        // Entri di file tapi tidak ada di DB
        $fileOnly = array_filter($fileEntries, function ($entry) use ($dbPppoes) {
            return !in_array(strtolower(trim($entry['username'])), $dbPppoes);
        });

        $isFileWritable = is_writable(self::CHAP_FILE)
            || $this->canSudoWrite();

        return view('ppp.index', compact('customers', 'fileOnly', 'isFileWritable'));
    }

    /**
     * Sync semua customer (pppoe+password) dari DB ke /etc/ppp/chap-secrets.
     */
    public function sync()
    {
        $customers = Customer::whereNotNull('pppoe')
            ->where('pppoe', '!=', '')
            ->whereNull('deleted_at')
            ->orderBy('pppoe')
            ->get(['pppoe', 'password', 'name']);

        $lines   = [];
        $lines[] = '# CHAP Secrets - Di-generate otomatis oleh ' . config('app.name');
        $lines[] = '# Terakhir sync: ' . now()->format('Y-m-d H:i:s') . ' oleh ' . auth()->user()->name;
        $lines[] = '# client          server    secret            IP addresses';
        $lines[] = '';

        foreach ($customers as $c) {
            $username = $this->escapePppField($c->pppoe);
            $password = $this->escapePppField($c->password ?? '*');
            $comment  = '# ' . $c->name;
            $lines[]  = $comment;
            $lines[]  = sprintf('%-20s %-10s %-20s %s', $username, '*', $password, '*');
        }

        $content = implode("\n", $lines) . "\n";

        $result = $this->writeChapFile($content);

        if ($result['success']) {
            return redirect()->route('ppp.index')
                ->with('success', 'Sync berhasil! ' . $customers->count() . ' user ditulis ke ' . self::CHAP_FILE);
        }

        return redirect()->route('ppp.index')
            ->with('error', 'Sync gagal: ' . $result['message']);
    }

    /**
     * Tambah entri PPP manual (tidak terkait customer).
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100|regex:/^[a-zA-Z0-9._@-]+$/',
            'password' => 'required|string|max:100',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        // Baca file lama, tambah entri baru
        $existing = file_exists(self::CHAP_FILE) ? file_get_contents(self::CHAP_FILE) : '';

        // Cek apakah username sudah ada di file
        if ($this->usernameExistsInFile($username)) {
            return redirect()->route('ppp.index')
                ->with('error', "Username '{$username}' sudah ada di file chap-secrets.");
        }

        $newLine  = sprintf('%-20s %-10s %-20s %s', $username, '*', $this->escapePppField($password), '*');
        $content  = rtrim($existing) . "\n# Manual entry\n" . $newLine . "\n";

        $result = $this->writeChapFile($content);

        if ($result['success']) {
            return redirect()->route('ppp.index')
                ->with('success', "User '{$username}' berhasil ditambahkan ke chap-secrets.");
        }

        return redirect()->route('ppp.index')
            ->with('error', 'Gagal menulis ke file: ' . $result['message']);
    }

    /**
     * Hapus entri dari /etc/ppp/chap-secrets berdasarkan username.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100',
        ]);

        $username = $request->input('username');

        if (!file_exists(self::CHAP_FILE)) {
            return redirect()->route('ppp.index')->with('error', 'File chap-secrets tidak ditemukan.');
        }

        $lines      = file(self::CHAP_FILE, FILE_IGNORE_NEW_LINES);
        $filtered   = [];
        $skipNext   = false;

        foreach ($lines as $line) {
            $trimmed = ltrim($line);

            // Jika sebelumnya tanda bahwa baris ini adalah comment sebelum entry yang dihapus
            if ($skipNext && str_starts_with($trimmed, '#')) {
                $skipNext = false;
                continue;
            }
            $skipNext = false;

            // Deteksi baris entry (bukan komentar)
            if (!str_starts_with($trimmed, '#') && trim($line) !== '') {
                $parts = preg_split('/\s+/', trim($line));
                if (isset($parts[0]) && strtolower($parts[0]) === strtolower($username)) {
                    // Hapus baris sebelumnya jika komentar (sudah ada di $filtered)
                    if (!empty($filtered) && str_starts_with(ltrim(end($filtered)), '#')) {
                        array_pop($filtered);
                    }
                    continue; // skip entri ini
                }
            }

            $filtered[] = $line;
        }

        $content = implode("\n", $filtered) . "\n";
        $result  = $this->writeChapFile($content);

        if ($result['success']) {
            return redirect()->route('ppp.index')
                ->with('success', "User '{$username}' berhasil dihapus dari chap-secrets.");
        }

        return redirect()->route('ppp.index')
            ->with('error', 'Gagal menulis ke file: ' . $result['message']);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Parse /etc/ppp/chap-secrets dan kembalikan array entri.
     */
    private function parseChapFile(): array
    {
        if (!file_exists(self::CHAP_FILE)) {
            return [];
        }

        $entries = [];
        $lines   = file(self::CHAP_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '#')) {
                continue;
            }

            $parts = preg_split('/\s+/', $trimmed);
            if (count($parts) >= 3) {
                $entries[] = [
                    'username' => $parts[0],
                    'server'   => $parts[1] ?? '*',
                    'password' => $parts[2] ?? '',
                    'ip'       => $parts[3] ?? '*',
                ];
            }
        }

        return $entries;
    }

    /**
     * Cek apakah username sudah ada di file.
     */
    private function usernameExistsInFile(string $username): bool
    {
        $entries = $this->parseChapFile();
        foreach ($entries as $entry) {
            if (strtolower($entry['username']) === strtolower($username)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tulis konten ke /etc/ppp/chap-secrets.
     * Coba direct write dulu, fallback ke sudo tee.
     */
    private function writeChapFile(string $content): array
    {
        // Backup dulu
        if (file_exists(self::CHAP_FILE)) {
            @copy(self::CHAP_FILE, self::CHAP_BACKUP);
        }

        // Coba tulis langsung
        if (is_writable(self::CHAP_FILE) || is_writable(dirname(self::CHAP_FILE))) {
            $bytes = file_put_contents(self::CHAP_FILE, $content, LOCK_EX);
            if ($bytes !== false) {
                chmod(self::CHAP_FILE, 0640);
                return ['success' => true, 'message' => ''];
            }
        }

        // Fallback: tulis ke file temp lalu sudoers copy
        $tmpFile = tempnam(sys_get_temp_dir(), 'chap_');
        file_put_contents($tmpFile, $content);

        // Gunakan sudo cp (memerlukan sudoers rule: www-data ALL=(root) NOPASSWD: /bin/cp /tmp/chap_* /etc/ppp/chap-secrets)
        $escaped = escapeshellarg($tmpFile);
        $cmd = "sudo cp {$escaped} " . escapeshellarg(self::CHAP_FILE) . " 2>&1 && sudo chmod 640 " . escapeshellarg(self::CHAP_FILE) . " 2>&1";
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        @unlink($tmpFile);

        if ($returnCode === 0) {
            return ['success' => true, 'message' => ''];
        }

        return [
            'success' => false,
            'message' => implode(' ', $output) ?: 'Permission denied. Pastikan sudoers sudah dikonfigurasi.',
        ];
    }

    /**
     * Cek apakah sudo tersedia untuk write ke chap-secrets.
     */
    private function canSudoWrite(): bool
    {
        exec('sudo -n cp /dev/null /dev/null 2>&1', $out, $code);
        return $code === 0;
    }

    /**
     * Escape field untuk format chap-secrets (tambahkan quote jika ada spasi).
     */
    private function escapePppField(string $value): string
    {
        if (strpos($value, ' ') !== false) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }
        return $value;
    }
}
