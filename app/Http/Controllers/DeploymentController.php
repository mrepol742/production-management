<?php

namespace App\Http\Controllers;

use App\Models\Deployment;
use App\Models\User;
use App\Services\DeploymentService;
use Illuminate\Http\Request;

class DeploymentController extends Controller
{
    /**
     * Authorize the user to access the deployment.
     *
     * @param Deployment $deployment The deployment to authorize.
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
     * Display a listing of the deployments.
     *
     * @param Request $request The request object.
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $deployments = $user->isSuperAdmin()
            ? Deployment::latest()->get()
            : $user->assignedDeployments()->latest()->get();

        return view('deployments.index', compact('deployments'));
    }

    /**
     * Show the form for creating a new deployment.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('deployments.create');
    }

    /**
     * Store a newly created deployment in storage.
     *
     * @param Request $request The request object.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:laravel,node'],
            'path' => ['required', 'string', 'max:255'],
            'repo_url' => ['nullable', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'ssh_key_name' => ['nullable', 'string', 'max:255'],
            'ssh_private_key' => ['nullable', 'string'],
            'ssh_private_key_path' => ['nullable', 'string', 'max:255'],
            'pm2_name' => ['nullable', 'string', 'max:255'],
            'pm2_instances' => ['nullable', 'string', 'max:255'],
        ]);

        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['status'] = Deployment::STATUS_UNKNOWN;
        $data['branch'] = $data['branch'] ?: 'main';

        $deployment = Deployment::create($data);

        if (filled($data['ssh_private_key_path'] ?? null) && blank($data['ssh_private_key'] ?? null)) {
            $service = app(DeploymentService::class);
            $result = $service->syncSshKeyFromPath(
                $deployment,
                $data['ssh_private_key_path'],
                $data['ssh_key_name'] ?? null,
            );

            if (! $result['success']) {
                return back()
                    ->withInput()
                    ->with('error', $result['output']);
            }
        } elseif (filled($data['ssh_private_key'] ?? null)) {
            $service = app(DeploymentService::class);
            $result = $service->updateSshKey(
                $deployment,
                $data['ssh_private_key'],
                $data['ssh_key_name'] ?? null,
                $data['ssh_private_key_path'] ?? null,
            );

            if (! $result['success']) {
                return back()
                    ->withInput()
                    ->with('error', $result['output']);
            }
        }

        return redirect()
            ->route('deployments.show', $deployment)
            ->with('status', 'Deployment created.');
    }

    /**
     * Display the specified deployment.
     *
     * @param Deployment $deployment The deployment to display.
     * @param Request $request The request object.
     * @param DeploymentService $service The deployment service.
     * @return \Illuminate\View\View
     */
    public function show(Deployment $deployment, Request $request, DeploymentService $service)
    {
        $this->authorizeAccess($deployment, $request);

        $envContents = $service->readEnv($deployment);
        $envBackups = $service->listEnvBackups($deployment);
        $admins = User::where('role', User::ROLE_ADMIN)->orderBy('name')->get();
        $assignedIds = $deployment->assignedAdmins()->pluck('users.id')->all();

        return view(
            'deployments.show',
            compact('deployment', 'envContents', 'envBackups', 'admins', 'assignedIds'),
        );
    }

    /**
     * Display the deployment history.
     *
     * @param Deployment $deployment The deployment to display.
     * @param Request $request The request object.
     * @return \Illuminate\View\View
     */
    public function history(Deployment $deployment, Request $request)
    {
        $this->authorizeAccess($deployment, $request);

        $logs = $deployment->logs()->with('user')->paginate(25);

        return view('deployments.history', compact('deployment', 'logs'));
    }

    /**
     * Assign admins to the deployment.
     *
     * @param Deployment $deployment The deployment to assign.
     * @param Request $request The request object.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assign(Deployment $deployment, Request $request)
    {
        $data = $request->validate([
            'admin_ids' => ['nullable', 'array'],
            'admin_ids.*' => ['exists:users,id'],
        ]);

        $adminIds = User::whereIn('id', $data['admin_ids'] ?? [])
            ->where('role', User::ROLE_ADMIN)
            ->pluck('id');

        $deployment->assignedAdmins()->sync($adminIds);

        return back()->with('status', 'Assignments updated.');
    }

    /**
     * Update the stored SSH key for the deployment.
     */
    public function updateSshKey(Deployment $deployment, Request $request, DeploymentService $service)
    {
        $request->validate([
            'ssh_key_name' => ['nullable', 'string', 'max:255'],
            'ssh_private_key' => ['nullable', 'string'],
            'ssh_private_key_path' => ['nullable', 'string', 'max:255'],
        ]);

        $sourcePath = $request->input('ssh_private_key_path');
        $privateKey = $request->input('ssh_private_key');

        $result = filled($sourcePath) && blank($privateKey)
            ? $service->syncSshKeyFromPath($deployment, $sourcePath, $request->input('ssh_key_name'))
            : $service->updateSshKey(
                $deployment,
                $privateKey,
                $request->input('ssh_key_name'),
                $sourcePath,
            );

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['output'],
        );
    }

    /**
     * Re-import the stored SSH key from its configured source path.
     */
    public function syncSshKey(Deployment $deployment, DeploymentService $service)
    {
        if (blank($deployment->ssh_private_key_path)) {
            return back()->with('error', 'No SSH key source path is configured for this deployment.');
        }

        $result = $service->syncSshKeyFromPath(
            $deployment,
            $deployment->ssh_private_key_path,
            $deployment->ssh_key_name,
        );

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['output'],
        );
    }

    /**
     * Remove the specified deployment from storage.
     *
     * @param Deployment $deployment The deployment to remove.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Deployment $deployment)
    {
        $deployment->delete();

        return redirect()->route('deployments.index')->with('status', 'Deployment removed.');
    }
}
