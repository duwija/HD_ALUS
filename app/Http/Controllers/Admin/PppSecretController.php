<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PppSecretController extends Controller
{
    const CHAP_FILE   = '/etc/ppp/chap-secrets';
    const CHAP_BACKUP = '/etc/ppp/chap-secrets.bak';

    /**
     * Tampilkan semua entri dari file /etc/ppp/chap-secrets.
     */
    public function index()
    {
        $entries        = $this->parseChapFile();
        $isFileWritable = is_writable(self::CHAP_FILE) || $this->canSudoWrite();
        $fileExists     = file_exists(self::CHAP_FILE);

        return view('admin.ppp-secrets', compact('entries', 'isFileWritable', 'fileExists'));
    }

    /**
     * Tambah entri PPP ke file.
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100|regex:/^[a-zA-Z0-9._@-]+$/',
            'password' => 'required|string|max:100',
            'ip'       => 'nullable|string|max:50',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');
        $ip       = $request->input('ip', '*') ?: '*';

        if ($this->usernameExistsInFile($username)) {
            return redirect()->route('admin.ppp-secrets.index')
                ->with('error', "Username '{$username}' sudah ada di file chap-secrets.");
        }

        $existing = file_exists(self::CHAP_FILE) ? file_get_contents(self::CHAP_FILE) : '';
        $newLine  = sprintf('%-20s %-10s %-20s %s', $username, '*', $this->escapePppField($password), $ip);
        $content  = rtrim($existing) . "\n# Manual entry\n" . $newLine . "\n";

        $result = $this->writeChapFile($content);

        if ($result['success']) {
            return redirect()->route('admin.ppp-secrets.index')
                ->with('success', "User '{$username}' berhasil ditambahkan ke chap-secrets.");
        }

        return redirect()->route('admin.ppp-secrets.index')
            ->with('error', 'Gagal menulis ke file: ' . $result['message']);
    }

    /**
     * Hapus entri dari /etc/ppp/chap-secrets.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100',
        ]);

        $username = $request->input('username');

        if (!file_exists(self::CHAP_FILE)) {
            return redirect()->route('admin.ppp-secrets.index')
                ->with('error', 'File chap-secrets tidak ditemukan.');
        }

        $lines    = file(self::CHAP_FILE, FILE_IGNORE_NEW_LINES);
        $filtered = [];

        foreach ($lines as $line) {
            $trimmed = ltrim($line);

            if (!str_starts_with($trimmed, '#') && trim($line) !== '') {
                $parts = preg_split('/\s+/', trim($line));
                if (isset($parts[0]) && strtolower($parts[0]) === strtolower($username)) {
                    // Hapus komentar sebelumnya jika ada
                    if (!empty($filtered) && str_starts_with(ltrim(end($filtered)), '#')) {
                        array_pop($filtered);
                    }
                    continue;
                }
            }

            $filtered[] = $line;
        }

        $content = implode("\n", $filtered) . "\n";
        $result  = $this->writeChapFile($content);

        if ($result['success']) {
            return redirect()->route('admin.ppp-secrets.index')
                ->with('success', "User '{$username}' berhasil dihapus dari chap-secrets.");
        }

        return redirect()->route('admin.ppp-secrets.index')
            ->with('error', 'Gagal menulis ke file: ' . $result['message']);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

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

    private function usernameExistsInFile(string $username): bool
    {
        foreach ($this->parseChapFile() as $entry) {
            if (strtolower($entry['username']) === strtolower($username)) {
                return true;
            }
        }
        return false;
    }

    private function writeChapFile(string $content): array
    {
        if (file_exists(self::CHAP_FILE)) {
            @copy(self::CHAP_FILE, self::CHAP_BACKUP);
        }

        if (is_writable(self::CHAP_FILE) || is_writable(dirname(self::CHAP_FILE))) {
            $bytes = file_put_contents(self::CHAP_FILE, $content, LOCK_EX);
            if ($bytes !== false) {
                chmod(self::CHAP_FILE, 0640);
                return ['success' => true, 'message' => ''];
            }
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'chap_');
        file_put_contents($tmpFile, $content);

        $escaped    = escapeshellarg($tmpFile);
        $target     = escapeshellarg(self::CHAP_FILE);
        $cmd        = "sudo cp {$escaped} {$target} 2>&1 && sudo chmod 640 {$target} 2>&1";
        $output     = [];
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

    private function canSudoWrite(): bool
    {
        exec('sudo -n cp /dev/null /dev/null 2>&1', $out, $code);
        return $code === 0;
    }

    private function escapePppField(string $value): string
    {
        if (strpos($value, ' ') !== false) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }
        return $value;
    }
}
