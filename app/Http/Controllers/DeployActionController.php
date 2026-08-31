<?php

namespace App\Http\Controllers;

use App\Models\Deployment;
use App\Models\DeploymentJob;
use App\Models\DeploymentLog;
use App\Services\DeploymentService;
use Illuminate\Http\Request;

class DeployActionController extends Controller
{
    /**
     * Authorize the user to perform an action on the given deployment.
     *
     * @param Deployment $deployment The deployment to authorize access for.
     * @param Request $request The request object.
     * @return void
     */
    protected function authorizeAccess(Deployment $deployment, Request $request): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if (!$deployment->isAssignedTo($user)) {
            abort(403, 'This deployment is not assigned to you.');
        }
    }

    /**
     * Log the action performed on the deployment.
     *
     * @param Deployment $deployment The deployment to log.
     * @param Request $request The request object.
     * @param string $action The action performed.
     * @param array $result The result of the action.
     * @return void
     */
    protected function log(
        Deployment $deployment,
        Request $request,
        string $action,
        array $result,
    ): void {
        DeploymentLog::create([
            'deployment_id' => $deployment->id,
            'user_id' => $request->user()->id,
            'action' => $action,
            'output' => $result['output'],
            'success' => $result['success'],
        ]);
    }

    /**
     * Redeploy the deployment.
     *
     * @param Deployment $deployment The deployment to redeploy.
     * @param Request $request The request object.
     * @param DeploymentService $service The deployment service.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redeploy(Deployment $deployment, Request $request, DeploymentService $service)
    {
        $this->authorizeAccess($deployment, $request);

        $result = $service->queueJob($deployment, 'deploy_now', $request->user()->id);

        $this->log($deployment, $request, 'queue_deploy', $result);

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['success'] ? 'Deployment queued.' : $result['output'],
        );
    }

    /**
     * Retry the most recent failed or completed deployment job.
     */
    public function rebase(Deployment $deployment, Request $request, DeploymentService $service)
    {
        $this->authorizeAccess($deployment, $request);

        $lastJob = $deployment->jobs()
            ->whereIn('status', [DeploymentJob::STATUS_SUCCEEDED, DeploymentJob::STATUS_FAILED])
            ->latest('id')
            ->first();

        if (! $lastJob) {
            return back()->with('error', 'There is no previous deployment job to retry.');
        }

        $result = $service->queueJob($deployment, 'retry_deploy', $request->user()->id);

        if ($result['success']) {
            $result['output'] .= ' Re-running command from job #'.$lastJob->id.'.';
        }

        $this->log($deployment, $request, 'retry_deploy', $result);

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['success'] ? 'Deployment retry queued.' : $result['output'],
        );
    }

    /**
     * Update the environment variables of the deployment.
     *
     * @param Deployment $deployment The deployment to update.
     * @param Request $request The request object.
     * @param DeploymentService $service The deployment service.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateEnv(Deployment $deployment, Request $request, DeploymentService $service)
    {
        $this->authorizeAccess($deployment, $request);

        $request->validate([
            'env_contents' => ['nullable', 'string'],
        ]);

        $result = $service->updateEnv($deployment, (string) $request->input('env_contents'));

        $this->log($deployment, $request, 'update_env', $result);

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['success']
                ? '.env updated, previous version backed up.'
                : 'Could not update .env, check the log below.',
        );
    }

    /**
     * Restore the environment variables of the deployment.
     *
     * @param Deployment $deployment The deployment to restore.
     * @param Request $request The request object.
     * @param DeploymentService $service The deployment service.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restoreEnv(Deployment $deployment, Request $request, DeploymentService $service)
    {
        $this->authorizeAccess($deployment, $request);

        $request->validate([
            'backup' => ['required', 'string'],
        ]);

        $contents = $service->readEnvBackup($deployment, $request->input('backup'));

        if ($contents === null) {
            return back()->with('error', 'That backup could not be found.');
        }

        return back()
            ->with('env_preview', $contents)
            ->with('status', 'Loaded backup into the editor below, save it to apply.');
    }
}
