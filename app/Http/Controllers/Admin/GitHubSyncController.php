<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitHubSyncController extends Controller
{
    /**
     * Show GitHub sync page
     */
    public function index()
    {
        $status = $this->getGitStatus();
        return view('admin.github-sync', compact('status'));
    }

    /**
     * Get current git status
     */
    private function getGitStatus()
    {
        $basePath = base_path();
        
        try {
            // Get current branch
            $branch = trim(shell_exec("cd $basePath && git rev-parse --abbrev-ref HEAD 2>&1"));
            
            // Get remote URL
            $remote = trim(shell_exec("cd $basePath && git config --get remote.origin.url 2>&1"));
            
            // Get last commit
            $lastCommit = trim(shell_exec("cd $basePath && git log -1 --oneline 2>&1"));
            
            // Get status
            $gitStatus = shell_exec("cd $basePath && git status --porcelain 2>&1");
            $hasChanges = !empty(trim($gitStatus));
            
            // Count changed files
            $changedFiles = array_filter(array_map('trim', explode("\n", $gitStatus)));
            
            return [
                'success' => true,
                'branch' => $branch,
                'remote' => $remote,
                'lastCommit' => $lastCommit,
                'hasChanges' => $hasChanges,
                'changedFiles' => $changedFiles,
                'changedCount' => count($changedFiles),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Pull from GitHub
     */
    public function pull(Request $request)
    {
        try {
            $basePath = base_path();
            
            // Run git pull
            $output = shell_exec("cd $basePath && git pull origin main 2>&1");
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully pulled from GitHub',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pull from GitHub',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Push to GitHub
     */
    public function push(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:5|max:200',
        ]);

        try {
            $basePath = base_path();
            $message = $request->message;
            
            // Add all changes
            shell_exec("cd $basePath && git add . 2>&1");
            
            // Commit
            $commitOutput = shell_exec("cd $basePath && git commit -m \"$message\" 2>&1");
            
            // Push
            $pushOutput = shell_exec("cd $basePath && git push origin main 2>&1");
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully pushed to GitHub',
                'commit_output' => $commitOutput,
                'push_output' => $pushOutput,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to push to GitHub',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh status
     */
    public function refresh()
    {
        $status = $this->getGitStatus();
        return response()->json($status);
    }

    /**
     * Get detailed file changes
     */
    public function getChanges()
    {
        try {
            $basePath = base_path();
            $output = shell_exec("cd $basePath && git diff --name-status 2>&1");
            
            $changes = [];
            foreach (explode("\n", $output) as $line) {
                if (!empty($line)) {
                    $parts = explode("\t", $line);
                    if (count($parts) >= 2) {
                        $changes[] = [
                            'status' => $parts[0], // M=Modified, A=Added, D=Deleted
                            'file' => $parts[1],
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'changes' => $changes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
