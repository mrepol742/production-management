<?php

namespace App\Console\Commands;

use App\Models\Deployment;
use App\Services\DeploymentService;
use Illuminate\Console\Command;

class SyncDeploymentSsh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deployments:sync-ssh
        {deployment : The deployment ID}
        {--key= : Path to the private key file}
        {--config= : Path to the SSH config file}
        {--name= : Optional label for the stored key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import or re-sync deployment SSH key and SSH config into the database';

    /**
     * Execute the console command.
     */
    public function handle(DeploymentService $service): int
    {
        $deployment = Deployment::find($this->argument('deployment'));

        if (! $deployment) {
            $this->error('Deployment not found.');

            return self::FAILURE;
        }

        $keyPath = $this->option('key') ?: $deployment->ssh_private_key_path;
        $configPath = $this->option('config') ?: $deployment->ssh_config_path;

        if (blank($keyPath) && blank($configPath)) {
            $this->error('Nothing to sync. Use --key=/path/to/key, --config=/path/to/config, or configure saved source paths first.');

            return self::FAILURE;
        }

        $messages = [];

        if (filled($keyPath)) {
            $result = $service->syncSshKeyFromPath(
                $deployment,
                $keyPath,
                $this->option('name') ?: $deployment->ssh_key_name,
            );

            if (! $result['success']) {
                $this->error($result['output']);

                return self::FAILURE;
            }

            $messages[] = $result['output'];
            $deployment->refresh();
        }

        if (filled($configPath)) {
            $result = $service->syncSshConfigFromPath($deployment, $configPath);

            if (! $result['success']) {
                $this->error($result['output']);

                return self::FAILURE;
            }

            $messages[] = $result['output'];
        }

        $this->info(implode(' ', $messages));

        return self::SUCCESS;
    }
}
