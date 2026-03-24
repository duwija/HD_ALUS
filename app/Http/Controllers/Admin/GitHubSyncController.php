<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitHubSyncController extends Controller
{
    private $basePath;
    private $git;

    public function __construct()
    {
        $this->basePath = base_path();
        // Use -c safe.directory=* to bypass ownership check (web user ≠ repo owner)
        $this->git = "git -c safe.directory={$this->basePath}";
    }

    /**
     * Show GitHub sync page
     */
    public function index()
    {
        $status      = $this->getGitStatus();
        $hasToken    = $this->hasToken();
        $tokenMasked = $this->getMaskedToken();
        return view('admin.github-sync', compact('status', 'hasToken', 'tokenMasked'));
    }

    /**
     * Save GitHub token — update remote URL to embed the token
     */
    public function saveToken(Request $request)
    {
        $request->validate([
            'github_token'    => 'required|string|min:10',
            'github_username' => 'required|string',
            'github_repo'     => 'required|string',
        ]);

        $token    = $request->github_token;
        $username = $request->github_username;
        $repo     = $request->github_repo;
        $bp       = $this->basePath;
        $git      = $this->git;

        // Build URL with embedded token
        $newUrl = "https://{$username}:{$token}@github.com/{$username}/{$repo}";

        $output = shell_exec("cd $bp && $git remote set-url origin " . escapeshellarg($newUrl) . " 2>&1");

        // Store token info (without token) in .git/github_config for display
        file_put_contents(
            $bp . '/.git/github_config',
            json_encode(['username' => $username, 'repo' => $repo, 'token_set' => true, 'updated_at' => now()->toDateTimeString()])
        );

        return response()->json([
            'success' => true,
            'message' => 'GitHub token saved successfully. Push & Pull should now work.',
        ]);
    }

    /**
     * Check if token is already embedded in remote URL
     */
    private function hasToken(): bool
    {
        $bp  = $this->basePath;
        $git = $this->git;
        $url = trim(shell_exec("cd $bp && $git config --get remote.origin.url 2>&1") ?? '');
        return str_contains($url, '@github.com');
    }

    /**
     * Return masked remote info for display (hide token)
     */
    private function getMaskedToken(): array
    {
        $bp  = $this->basePath;
        $git = $this->git;
        $url = trim(shell_exec("cd $bp && $git config --get remote.origin.url 2>&1") ?? '');

        if (preg_match('#https://([^:]+):([^@]+)@github\.com/([^/]+)/(.+)#', $url, $m)) {
            return [
                'username' => $m[1],
                'repo'     => $m[4],
                'token'    => substr($m[2], 0, 6) . '***',
            ];
        }

        // Try to read config file
        $configFile = $bp . '/.git/github_config';
        if (file_exists($configFile)) {
            $cfg = json_decode(file_get_contents($configFile), true);
            return [
                'username' => $cfg['username'] ?? '',
                'repo'     => $cfg['repo'] ?? '',
                'token'    => '(saved)',
            ];
        }

        // Extract from plain URL
        if (preg_match('#github\.com/([^/]+)/(.+)#', $url, $m)) {
            return ['username' => $m[1], 'repo'  => rtrim($m[2], '.git'), 'token' => null];
        }

        return ['username' => '', 'repo' => '', 'token' => null];
    }

    /**
     * Get current git status
     */
    private function getGitStatus()
    {
        $bp  = $this->basePath;
        $git = $this->git;

        try {
            $branch     = trim(shell_exec("cd $bp && $git rev-parse --abbrev-ref HEAD 2>&1") ?? '');
            // Mask token from remote URL before displaying
            $rawRemote  = trim(shell_exec("cd $bp && $git config --get remote.origin.url 2>&1") ?? '');
            $remote     = preg_replace('#(https://)([^:]+):([^@]+)@#', '$1$2:***@', $rawRemote);
            $lastCommit = trim(shell_exec("cd $bp && $git log -1 --oneline 2>&1") ?? '');
            $gitStatus  = shell_exec("cd $bp && $git status --porcelain 2>&1") ?? '';

            // Detect if git commands still failed
            if (str_contains($branch, 'fatal:') || str_contains($branch, 'error:')) {
                return ['success' => false, 'error' => $branch];
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

    /**
     * Pull from GitHub
     */
    public function pull(Request $request)
    {
        $bp  = $this->basePath;
        $git = $this->git;

        $output = shell_exec("cd $bp && $git pull origin main 2>&1");

        $success = !str_contains($output, 'fatal:') && !str_contains($output, 'error:');

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Successfully pulled from GitHub' : 'Pull failed',
            'output'  => $output,
        ]);
    }

    /**
     * Push to GitHub
     */
    public function push(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:5|max:200',
        ]);

        $bp      = $this->basePath;
        $git     = $this->git;
        $message = escapeshellarg($request->message);

        shell_exec("cd $bp && $git add . 2>&1");
        $commitOutput = shell_exec("cd $bp && $git commit -m $message 2>&1");
        $pushOutput   = shell_exec("cd $bp && $git push origin main 2>&1");

        $success = !str_contains($pushOutput, 'fatal:') && !str_contains($pushOutput, 'error:');

        return response()->json([
            'success'       => $success,
            'message'       => $success ? 'Successfully pushed to GitHub' : 'Push failed',
            'commit_output' => $commitOutput,
            'push_output'   => $pushOutput,
        ]);
    }

    /**
     * Refresh status (returns JSON for AJAX)
     */
    public function refresh()
    {
        return response()->json($this->getGitStatus());
    }

    /**
     * Get detailed file changes
     */
    public function getChanges()
    {
        $bp  = $this->basePath;
        $git = $this->git;

        $output  = shell_exec("cd $bp && $git diff --name-status 2>&1") ?? '';
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
