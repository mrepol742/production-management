<?php

namespace App\Services;

use App\Models\Deployment;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DeploymentService
{
    /**
     * Build a writable runtime home for external tools invoked by PHP.
     */
    protected function runtimeHome(): string
    {
        $path = storage_path('app/runtime-home');
        File::ensureDirectoryExists($path, 0700, true);

        return $path;
    }

    /**
     * Build a writable PM2 home directory for the current application.
     */
    protected function pm2Home(): string
    {
        $path = storage_path('app/runtime-pm2');
        File::ensureDirectoryExists($path, 0700, true);

        return $path;
    }

    /**
     * Run a command in the given directory and return the result.
     * 
     * @param array $command The command to run
     * @param string $cwd The directory to run the command in
     * @return array The result of the command
     */
    protected function run(array $command, string $cwd, array $env = []): array
    {
        $process = new Process($command, $cwd, $env + [
            'HOME' => $this->runtimeHome(),
        ]);
        $process->setTimeout(600);

        try {
            $process->run();
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'output' => $e->getMessage(),
            ];
        }

        return [
            'success' => $process->isSuccessful(),
            'output' => trim($process->getOutput()."\n".$process->getErrorOutput()),
        ];
    }

    /**
     * Run a Git command for the given deployment using its stored SSH key when present.
     */
    protected function runGit(Deployment $deployment, array $command, string $cwd): array
    {
        if (! $deployment->hasStoredSshKey() && ! $deployment->hasStoredSshConfig()) {
            return $this->run($command, $cwd);
        }

        $directory = storage_path('app/deployment-ssh');
        File::ensureDirectoryExists($directory, 0700, true);

        $keyPath = $directory.'/deployment-'.$deployment->id;
        $configPath = $directory.'/deployment-'.$deployment->id.'.config';
        $knownHostsPath = $directory.'/known_hosts';
        $sshCommandParts = ['ssh'];

        if ($deployment->hasStoredSshKey()) {
            File::put($keyPath, rtrim((string) $deployment->ssh_private_key)."\n");
            @chmod($keyPath, 0600);
            $sshCommandParts[] = '-i';
            $sshCommandParts[] = escapeshellarg($keyPath);
            $sshCommandParts[] = '-o';
            $sshCommandParts[] = 'IdentitiesOnly=yes';
        }

        if ($deployment->hasStoredSshConfig()) {
            File::put($configPath, rtrim((string) $deployment->ssh_config)."\n");
            @chmod($configPath, 0600);
            $sshCommandParts[] = '-F';
            $sshCommandParts[] = escapeshellarg($configPath);
        }

        $sshCommandParts[] = '-o';
        $sshCommandParts[] = 'StrictHostKeyChecking=accept-new';
        $sshCommandParts[] = '-o';
        $sshCommandParts[] = 'UserKnownHostsFile='.escapeshellarg($knownHostsPath);
        $sshCommand = implode(' ', $sshCommandParts);

        try {
            return $this->run($command, $cwd, [
                'GIT_SSH_COMMAND' => $sshCommand,
            ]);
        } finally {
            @unlink($keyPath);
            @unlink($configPath);
        }
    }

    /**
     * Store the SSH private key for a deployment.
     */
    public function updateSshKey(
        Deployment $deployment,
        ?string $privateKey,
        ?string $keyName = null,
        ?string $sourcePath = null,
    ): array {
        $privateKey = $privateKey !== null ? trim($privateKey) : null;

        if (blank($privateKey)) {
            $deployment->update([
                'ssh_key_name' => null,
                'ssh_private_key' => null,
                'ssh_private_key_path' => null,
            ]);

            return ['success' => true, 'output' => 'Stored SSH key removed.'];
        }

        if (! str_contains($privateKey, 'BEGIN OPENSSH PRIVATE KEY')
            && ! str_contains($privateKey, 'BEGIN RSA PRIVATE KEY')
            && ! str_contains($privateKey, 'BEGIN EC PRIVATE KEY')
            && ! str_contains($privateKey, 'BEGIN PRIVATE KEY')) {
            return ['success' => false, 'output' => 'The provided SSH private key does not look valid.'];
        }

        $deployment->update([
            'ssh_key_name' => $keyName ?: $deployment->ssh_key_name ?: 'deploy-key',
            'ssh_private_key' => $privateKey,
            'ssh_private_key_path' => $sourcePath,
        ]);

        return [
            'success' => true,
            'output' => 'Stored SSH key updated for Git operations.',
        ];
    }

    /**
     * Import the SSH private key from a file on disk.
     */
    public function syncSshKeyFromPath(
        Deployment $deployment,
        string $sourcePath,
        ?string $keyName = null,
    ): array {
        $sourcePath = trim($sourcePath);

        if ($sourcePath === '') {
            return ['success' => false, 'output' => 'SSH key source path is required.'];
        }

        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            return ['success' => false, 'output' => 'SSH key source path is not readable: '.$sourcePath];
        }

        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            return ['success' => false, 'output' => 'Failed to read SSH key source path: '.$sourcePath];
        }

        return $this->updateSshKey(
            $deployment,
            $contents,
            $keyName ?: basename($sourcePath),
            $sourcePath,
        );
    }

    /**
     * Store the SSH config for a deployment.
     */
    public function updateSshConfig(
        Deployment $deployment,
        ?string $sshConfig,
        ?string $sourcePath = null,
    ): array {
        $sshConfig = $sshConfig !== null ? trim($sshConfig) : null;

        if (blank($sshConfig)) {
            $deployment->update([
                'ssh_config' => null,
                'ssh_config_path' => null,
            ]);

            return ['success' => true, 'output' => 'Stored SSH config removed.'];
        }

        if (! str_contains($sshConfig, 'Host ')) {
            return ['success' => false, 'output' => 'The provided SSH config does not look valid.'];
        }

        $deployment->update([
            'ssh_config' => $sshConfig,
            'ssh_config_path' => $sourcePath,
        ]);

        return ['success' => true, 'output' => 'Stored SSH config updated for Git operations.'];
    }

    /**
     * Import the SSH config from a file on disk.
     */
    public function syncSshConfigFromPath(Deployment $deployment, string $sourcePath): array
    {
        $sourcePath = trim($sourcePath);

        if ($sourcePath === '') {
            return ['success' => false, 'output' => 'SSH config source path is required.'];
        }

        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            return ['success' => false, 'output' => 'SSH config source path is not readable: '.$sourcePath];
        }

        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            return ['success' => false, 'output' => 'Failed to read SSH config source path: '.$sourcePath];
        }

        return $this->updateSshConfig($deployment, $contents, $sourcePath);
    }

    /**
     * Update the rebase for the given deployment.
     *
     * @param Deployment $deployment The deployment to update
     * @return array The result of the update
     */
    public function updateRebase(Deployment $deployment): array
    {
        $path = $deployment->path;
        $branch = $deployment->branch ?: 'main';

        $steps = [];

        $fetch = $this->runGit($deployment, ['git', 'fetch', 'origin'], $path);
        $steps[] = "\$ git fetch origin\n".$fetch['output'];
        if (! $fetch['success']) {
            return ['success' => false, 'output' => implode("\n\n", $steps)];
        }

        $rebase = $this->runGit($deployment, ['git', 'rebase', "origin/{$branch}"], $path);
        $steps[] = "\$ git rebase origin/{$branch}\n".$rebase['output'];

        return [
            'success' => $rebase['success'],
            'output' => implode("\n\n", $steps),
        ];
    }

    /**
     * Update the environment file for the given deployment.
     *
     * @param Deployment $deployment The deployment to update
     * @param string $contents The contents of the environment file
     * @return array The result of the update
     */
    public function updateEnv(Deployment $deployment, string $contents): array
    {
        try {
            $path = $deployment->envPath();

            if (file_exists($path)) {
                $backupPath = $path.'.backup.'.now()->format('Ymd_His');
                copy($path, $backupPath);
                $this->pruneEnvBackups($path);
            }

            file_put_contents($path, $contents);

            return ['success' => true, 'output' => 'Updated '.$path];
        } catch (\Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    /**
     * List the environment backups for the given deployment.
     *
     * @param Deployment $deployment The deployment to list
     * @return array The list of environment backups
     */
    protected function pruneEnvBackups(string $envPath, int $keep = 10): void
    {
        $backups = glob($envPath.'.backup.*') ?: [];
        rsort($backups);

        foreach (array_slice($backups, $keep) as $old) {
            @unlink($old);
        }
    }

    /**
     * List the environment backups for the given deployment.
     *
     * @param Deployment $deployment The deployment to list
     * @return array The list of environment backups
     */
    public function listEnvBackups(Deployment $deployment): array
    {
        $backups = glob($deployment->envPath().'.backup.*') ?: [];
        rsort($backups);

        return array_map(fn ($path) => [
            'path' => $path,
            'label' => str_replace($deployment->envPath().'.backup.', '', $path),
        ], $backups);
    }

    /**
     * Read the contents of an environment backup.
     *
     * @param Deployment $deployment The deployment to read
     * @param string $label The label of the backup to read
     * @return ?string The contents of the backup, or null if it doesn't exist
     */
    public function readEnvBackup(Deployment $deployment, string $label): ?string
    {
        $path = $deployment->envPath().'.backup.'.$label;

        if (! str_starts_with(realpath($path) ?: '', dirname($deployment->envPath()))) {
            return null;
        }

        return file_exists($path) ? file_get_contents($path) : null;
    }

    /**
     * Read the contents of the environment file for the given deployment.
     *
     * @param Deployment $deployment The deployment to read
     * @return string The contents of the environment file
     */
    public function readEnv(Deployment $deployment): string
    {
        $path = $deployment->envPath();

        return file_exists($path) ? file_get_contents($path) : '';
    }

    /**
     * Optimize the Laravel application for the given deployment.
     *
     * @param Deployment $deployment The deployment to optimize
     * @return array The result of the optimization
     */
    public function optimizeLaravel(Deployment $deployment): array
    {
        return $this->run(['php', 'artisan', 'optimize'], $deployment->path);
    }

    /**
     * Restart the PM2 process for the given deployment.
     *
     * @param Deployment $deployment The deployment to restart
     * @return array The result of the restart
     */
    public function restartPm2(Deployment $deployment): array
    {
        $bin = env('PM2_BIN', 'pm2');
        $target = $deployment->pm2_instances ?: $deployment->pm2_name;

        return $this->run([$bin, 'restart', $target], $deployment->path, [
            'PM2_HOME' => $this->pm2Home(),
        ]);
    }

    /**
     * Pause the PM2 process for the given deployment.
     *
     * @param Deployment $deployment The deployment to pause
     * @return array The result of the pause
     */
    public function pausePm2(Deployment $deployment): array
    {
        $bin = env('PM2_BIN', 'pm2');
        $target = $deployment->pm2_instances ?: $deployment->pm2_name;

        return $this->run([$bin, 'stop', $target], $deployment->path, [
            'PM2_HOME' => $this->pm2Home(),
        ]);
    }

    /**
     * Pause the Laravel application for the given deployment.
     *
     * @param Deployment $deployment The deployment to pause
     * @return array The result of the pause
     */
    public function pauseLaravel(Deployment $deployment): array
    {
        return $this->run(['php', 'artisan', 'down'], $deployment->path);
    }

    /**
     * Resume the Laravel application for the given deployment.
     *
     * @param Deployment $deployment The deployment to resume
     * @return array The result of the resume
     */
    public function resumeLaravel(Deployment $deployment): array
    {
        return $this->run(['php', 'artisan', 'up'], $deployment->path);
    }

    /**
     * Redeploy the given deployment.
     *
     * @param Deployment $deployment The deployment to redeploy
     * @return array The result of the redeploy
     */
    public function redeploy(Deployment $deployment): array
    {
        $steps = [];

        $rebase = $this->updateRebase($deployment);
        $steps[] = $rebase['output'];
        if (! $rebase['success']) {
            return ['success' => false, 'output' => implode("\n\n", $steps)];
        }

        if ($deployment->isLaravel()) {
            $optimize = $this->optimizeLaravel($deployment);
            $steps[] = "\$ php artisan optimize\n".$optimize['output'];

            return ['success' => $optimize['success'], 'output' => implode("\n\n", $steps)];
        }

        $restart = $this->restartPm2($deployment);
        $steps[] = "\$ pm2 restart ".($deployment->pm2_instances ?: $deployment->pm2_name)."\n".$restart['output'];

        return ['success' => $restart['success'], 'output' => implode("\n\n", $steps)];
    }
}
