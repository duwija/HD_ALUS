<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GitHubSyncController extends Controller
{
    private $basePath;
    private $git;

    /** Path to credentials file (stored in storage/app — writable by apache) */
    private $credFile;

    public function __construct()
    {
        $this->basePath = base_path();
        $this->git      = "git -c safe.directory={$this->basePath} -c user.name='Admin' -c user.email='admin@kencana.alus.co.id'";
        $this->credFile = storage_path('app/github_credentials.json');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Credential helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** Read stored credentials. Returns array or null. */
    private function getCredentials(): ?array
    {
        if (!file_exists($this->credFile)) {
            return null;
        }
        $data = json_decode(file_get_contents($this->credFile), true);
        return (is_array($data) && !empty($data['token'])) ? $data : null;
    }

    /** Build authenticated git remote URL from stored credentials. */
    private function getAuthUrl(): ?string
    {
        $creds = $this->getCredentials();
        if (!$creds) {
            return null;
        }
        return "https://{$creds['username']}:{$creds['token']}@github.com/{$creds['username']}/{$creds['repo']}";
    }

    private function hasToken(): bool
    {
        return $this->getCredentials() !== null;
    }

    private function getMaskedToken(): array
    {
        $creds = $this->getCredentials();
        if ($creds) {
            return [
                'username' => $creds['username'],
                'repo'     => $creds['repo'],
                'token'    => substr($creds['token'], 0, 6) . '***',
            ];
        }

        // Fall back to plain remote URL info
        $bp  = $this->basePath;
        $git = $this->git;
        $url = trim(shell_exec("cd $bp && $git config --get remote.origin.url 2>&1") ?? '');
        if (preg_match('#github\.com/([^/]+)/(.+?)(?:\.git)?$#', $url, $m)) {
            return ['username' => $m[1], 'repo' => $m[2], 'token' => null];
        }

        return ['username' => '', 'repo' => '', 'token' => null];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Pages & actions
    // ──────────────────────────────────────────────────────────────────────────

    public function index()
    {
        $status      = $this->getGitStatus();
        $hasToken    = $this->hasToken();
        $tokenMasked = $this->getMaskedToken();
        return view('admin.github-sync', compact('status', 'hasToken', 'tokenMasked'));
    }

    /**
     * Save GitHub credentials — stored in storage/app (writable by apache).
     * Does NOT write to .git/config (owned by root, not writable by apache).
     */
    public function saveToken(Request $request)
    {
        $request->validate([
            'github_token'    => 'required|string|min:10',
            'github_username' => 'required|string',
            'github_repo'     => 'required|string',
        ]);

        $token    = trim($request->github_token);
        $username = trim($request->github_username);
        $repo     = trim(trim($request->github_repo), '/');

        $data = json_encode([
            'username'   => $username,
            'repo'       => $repo,
            'token'      => $token,
            'updated_at' => date('Y-m-d H:i:s'),
        ], JSON_PRETTY_PRINT);

        if (file_put_contents($this->credFile, $data) === false) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: storage/app tidak bisa ditulis.',
            ], 500);
        }

        // Make sure the file is not world-readable
        chmod($this->credFile, 0640);

        return response()->json([
            'success' => true,
            'message' => 'Token tersimpan. Push & Pull sekarang aktif.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Git operations
    // ──────────────────────────────────────────────────────────────────────────

    private function getGitStatus(): array
    {
        $bp  = $this->basePath;
        $git = $this->git;

        try {
            $branch     = trim(shell_exec("cd $bp && $git rev-parse --abbrev-ref HEAD 2>&1") ?? '');
            $lastCommit = trim(shell_exec("cd $bp && $git log -1 --oneline 2>&1") ?? '');
            $gitStatus  = shell_exec("cd $bp && $git status --porcelain 2>&1") ?? '';

            if (str_contains($branch, 'fatal:') || str_contains($branch, 'error:')) {
                return ['success' => false, 'error' => $branch];
            }

            // Display remote: prefer stored creds (masked), fall back to .git/config
            $creds = $this->getCredentials();
            if ($creds) {
                $remote = "https://github.com/{$creds['username']}/{$creds['repo']}";
            } else {
                $rawRemote = trim(shell_exec("cd $bp && $git config --get remote.origin.url 2>&1") ?? '');
                $remote    = preg_replace('#(https://)([^:]+):([^@]+)@#', '$1$2:***@', $rawRemote);
            }

            $changedFiles = array_values(array_filter(array_map('trim', explode("\n", $gitStatus))));

            return [
                'success'      => true,
                'branch'       => $branch,
                'remote'       => $remote,
                'lastCommit'   => $lastCommit,
                'hasChanges'   => !empty($changedFiles),
                'changedFiles' => $changedFiles,
                'changedCount' => count($changedFiles),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function pull(Request $request)
    {
        $bp      = $this->basePath;
        $git     = $this->git;
        $authUrl = $this->getAuthUrl();

        if (!$authUrl) {
            return response()->json(['success' => false, 'message' => 'Token belum dikonfigurasi.']);
        }

        $output  = shell_exec("cd $bp && $git pull " . escapeshellarg($authUrl) . " main 2>&1");
        $success = !str_contains($output, 'fatal:') && !str_contains($output, 'error:');

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Berhasil pull dari GitHub' : 'Pull gagal',
            'output'  => $output,
        ]);
    }

    public function push(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:5|max:200',
        ]);

        $bp      = $this->basePath;
        $git     = $this->git;
        $authUrl = $this->getAuthUrl();
        $message = escapeshellarg($request->message);

        if (!$authUrl) {
            return response()->json(['success' => false, 'message' => 'Token belum dikonfigurasi.']);
        }

        shell_exec("cd $bp && $git add . 2>&1");
        $commitOutput = shell_exec("cd $bp && $git commit -m $message 2>&1");
        $pushOutput   = shell_exec("cd $bp && $git push " . escapeshellarg($authUrl) . " main 2>&1");

        $success = !str_contains($pushOutput, 'fatal:') && !str_contains($pushOutput, 'error:');

        return response()->json([
            'success'       => $success,
            'message'       => $success ? 'Berhasil push ke GitHub' : 'Push gagal',
            'commit_output' => $commitOutput,
            'push_output'   => $pushOutput,
        ]);
    }

    public function refresh()
    {
        return response()->json($this->getGitStatus());
    }

    public function getChanges()
    {
        $bp  = $this->basePath;
        $git = $this->git;

        $output  = shell_exec("cd $bp && $git diff --name-status HEAD 2>&1") ?? '';
        $changes = [];

        foreach (explode("\n", $output) as $line) {
            if (!empty(trim($line))) {
                $parts = explode("\t", $line);
                if (count($parts) >= 2) {
                    $changes[] = ['status' => $parts[0], 'file' => $parts[1]];
                }
            }
        }

        return response()->json(['success' => true, 'changes' => $changes]);
    }
}
