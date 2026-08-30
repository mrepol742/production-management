<?php

namespace App\Http\Controllers;

use App\Models\Deployment;
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

        $result = $service->redeploy($deployment);

        $deployment->update([
            'status' => Deployment::STATUS_RUNNING,
            'last_deployed_at' => now(),
        ]);

        $this->log($deployment, $request, 'redeploy', $result);

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['success'] ? 'Redeploy finished.' : 'Redeploy failed, check the log below.',
        );
    }

    /**
     * Rebase the deployment.
     *
     * @param Deployment $deployment The deployment to rebase.
     * @param Request $request The request object.
     * @param DeploymentService $service The deployment service.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rebase(Deployment $deployment, Request $request, DeploymentService $service)
    {
        $this->authorizeAccess($deployment, $request);

        $result = $service->updateRebase($deployment);

        $this->log($deployment, $request, 'update_rebase', $result);

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['success'] ? 'Rebase finished.' : 'Rebase failed, check the log below.',
        );
    }

    /**
     * Pause the deployment.
     *
     * @param Deployment $deployment The deployment to pause.
     * @param Request $request The request object.
     * @param DeploymentService $service The deployment service.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pause(Deployment $deployment, Request $request, DeploymentService $service)
    {
        $this->authorizeAccess($deployment, $request);

        $result = $deployment->isLaravel()
            ? $service->pauseLaravel($deployment)
            : $service->pausePm2($deployment);

        if ($result['success']) {
            $deployment->update(['status' => Deployment::STATUS_PAUSED]);
        }

        $this->log($deployment, $request, 'pause', $result);

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['success'] ? 'Deployment paused.' : 'Pause failed, check the log below.',
        );
    }

    /**
     * Resume the deployment.
     *
     * @param Deployment $deployment The deployment to resume.
     * @param Request $request The request object.
     * @param DeploymentService $service The deployment service.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resume(Deployment $deployment, Request $request, DeploymentService $service)
    {
        $this->authorizeAccess($deployment, $request);

        $result = $deployment->isLaravel()
            ? $service->resumeLaravel($deployment)
            : $service->restartPm2($deployment);

        if ($result['success']) {
            $deployment->update(['status' => Deployment::STATUS_RUNNING]);
        }

        $this->log($deployment, $request, 'resume', $result);

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['success'] ? 'Deployment resumed.' : 'Resume failed, check the log below.',
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
