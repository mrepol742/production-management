<?php

namespace App\Console\Commands;

use App\Models\Deployment;
use App\Services\DeploymentService;
use Illuminate\Console\Command;

class SyncDeploymentSshKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deployments:sync-ssh-key
        {deployment : The deployment ID}
        {--from= : Path to the private key file}
        {--name= : Optional label for the stored key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import or re-sync a deployment SSH private key into the database';

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

        $sourcePath = $this->option('from') ?: $deployment->ssh_private_key_path;

        if (blank($sourcePath)) {
            $this->error('No source path provided. Use --from=/path/to/key or configure ssh_private_key_path first.');

            return self::FAILURE;
        }

        $result = $service->syncSshKeyFromPath(
            $deployment,
            $sourcePath,
            $this->option('name') ?: $deployment->ssh_key_name,
        );

        if (! $result['success']) {
            $this->error($result['output']);

            return self::FAILURE;
        }

        $this->info($result['output']);

        return self::SUCCESS;
    }
}
