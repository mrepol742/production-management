<?php

namespace App\Console\Commands;

use App\Models\DeploymentJob;
use App\Models\DeploymentLog;
use App\Services\DeploymentService;
use Illuminate\Console\Command;

class RunPendingDeploymentJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deployments:run-pending {--once : Run only one job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run queued deployment jobs. Intended for cron under the deploy user.';

    public function handle(DeploymentService $service): int
    {
        $jobs = DeploymentJob::with(['deployment', 'requestedBy'])
            ->where('status', DeploymentJob::STATUS_PENDING)
            ->orderBy('id')
            ->when($this->option('once'), fn ($query) => $query->limit(1))
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No pending deployment jobs.');

            return self::SUCCESS;
        }

        foreach ($jobs as $job) {
            $this->line('Running deployment job #'.$job->id.' for '.$job->deployment->name);

            $result = $service->runJob($job);

            DeploymentLog::create([
                'deployment_id' => $job->deployment_id,
                'user_id' => $job->requested_by,
                'action' => $job->action,
                'output' => $result['output'],
                'success' => $result['success'],
            ]);

            $this->line($result['success']
                ? 'Job #'.$job->id.' finished successfully.'
                : 'Job #'.$job->id.' failed.');
        }

        return self::SUCCESS;
    }
}
