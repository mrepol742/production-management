<x-app-layout title="{{ $deployment->name }} - Melvin">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <h1 class="text-xl font-semibold text-gray-900 break-words">{{ $deployment->name }}</h1>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        {{ ucfirst($deployment->type) }}
                    </span>
                    @if($deployment->status === 'running')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Running</span>
                    @elseif($deployment->status === 'paused')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent-100 text-accent-700">Paused</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Unknown</span>
                    @endif
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-1 break-words">{{ $deployment->path }}</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <form method="POST" action="{{ route('deployments.destroy', $deployment) }}" onsubmit="return confirm('Remove this deployment from Melvin? This will not touch files on the server.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-gray-500 hover:text-accent-600">Remove</button>
        </form>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500">Branch</p>
            <p class="text-sm font-medium text-gray-900 break-words">{{ $deployment->branch }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500">Last deployed</p>
            <p class="text-sm font-medium text-gray-900">{{ $deployment->last_deployed_at ? $deployment->last_deployed_at->diffForHumans() : 'Never' }}</p>
        </div>
    </div>

    @if(auth()->user()->isSuperAdmin())
    <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-5 mb-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
            <div>
                <p class="text-sm font-medium text-gray-900">Git SSH key</p>
                <p class="text-xs text-gray-500 mt-1">Git fetch/rebase uses the encrypted key stored here through <code>GIT_SSH_COMMAND</code>, not the PHP user's <code>~/.ssh</code>.</p>
            </div>
            @if($deployment->ssh_private_key_path)
                <form method="POST" action="{{ route('deployments.ssh-key.sync', $deployment) }}">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-md whitespace-nowrap">
                        Sync from source path
                    </button>
                </form>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div class="border border-gray-200 rounded-md p-3">
                <p class="text-xs text-gray-500">Stored key</p>
                <p class="text-sm font-medium text-gray-900 break-words">{{ $deployment->hasStoredSshKey() ? ($deployment->ssh_key_name ?: 'Configured') : 'Not configured' }}</p>
            </div>
            <div class="border border-gray-200 rounded-md p-3">
                <p class="text-xs text-gray-500">Source path</p>
                <p class="text-sm font-medium text-gray-900 break-all">{{ $deployment->ssh_private_key_path ?: 'None' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('deployments.ssh-key', $deployment) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Key label</label>
                <input type="text" name="ssh_key_name" value="{{ old('ssh_key_name', $deployment->ssh_key_name) }}"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Import from server path</label>
                <input type="text" name="ssh_private_key_path" value="{{ old('ssh_private_key_path', $deployment->ssh_private_key_path) }}"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1 break-words">Example: <code>/home/user/.ssh/github-deploy-key</code>. Leave the textarea empty to import from this path.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Paste private key</label>
                <textarea name="ssh_private_key" rows="8" spellcheck="false" placeholder="Leave blank to keep the current stored key. Submit an empty path and empty key to remove it."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono focus:border-accent-500 focus:ring-accent-500 focus:outline-none">{{ old('ssh_private_key') }}</textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="w-full sm:w-auto bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Save SSH Key
                </button>
            </div>
        </form>

        <div class="mt-5 pt-5 border-t border-gray-200 space-y-2">
            <p class="text-xs font-medium text-gray-700">How to get a key</p>
            <p class="text-xs text-gray-500 break-words">Generate a new read-only deploy key with <code>ssh-keygen -t ed25519 -f ~/.ssh/github-deploy-key -C "melvin deploy"</code>, then add the <code>.pub</code> file to the GitHub repo deploy keys page with read-only access.</p>
            <p class="text-xs text-gray-500 break-words">To import an existing key already on the server, set the source path above or run <code>php artisan deployments:sync-ssh-key {{ $deployment->id }} --from=/home/user/.ssh/github-deploy-key</code>.</p>
        </div>
    </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-5 mb-6">
        <p class="text-sm font-medium text-gray-900 mb-3">Actions</p>
        <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3">
            <form method="POST" action="{{ route('deployments.redeploy', $deployment) }}" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="w-full sm:w-auto bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Redeploy
                </button>
            </form>

            <form method="POST" action="{{ route('deployments.rebase', $deployment) }}" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="w-full sm:w-auto border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-md">
                    Update / Rebase
                </button>
            </form>

            @if($deployment->status === 'paused')
                <form method="POST" action="{{ route('deployments.resume', $deployment) }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-md">
                        Resume
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('deployments.pause', $deployment) }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-md">
                        Pause
                    </button>
                </form>
            @endif
        </div>
        <p class="text-xs text-gray-400 mt-3">
            @if($deployment->isLaravel())
                Redeploy rebases from git then runs php artisan optimize. Pause runs php artisan down.
            @else
                Redeploy rebases from git then runs pm2 restart {{ $deployment->pm2_instances ?: $deployment->pm2_name }}. Pause runs pm2 stop.
            @endif
        </p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-5 mb-6">
        <p class="text-sm font-medium text-gray-900 mb-3">Environment file</p>
        <form method="POST" action="{{ route('deployments.env', $deployment) }}">
            @csrf
            <textarea name="env_contents" rows="12" spellcheck="false"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono focus:border-accent-500 focus:ring-accent-500 focus:outline-none">{{ old('env_contents', session('env_preview', $envContents)) }}</textarea>
            <div class="mt-3">
                <button type="submit" class="w-full sm:w-auto bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Save .env
                </button>
            </div>
        </form>

        @if(!empty($envBackups))
            <div class="mt-5 pt-5 border-t border-gray-200">
                <p class="text-xs font-medium text-gray-700 mb-2">Previous versions</p>
                <div class="space-y-2">
                    @foreach($envBackups as $backup)
                        <form method="POST" action="{{ route('deployments.env.restore', $deployment) }}" class="flex items-center justify-between gap-3">
                            @csrf
                            <input type="hidden" name="backup" value="{{ $backup['label'] }}">
                            <span class="text-xs text-gray-500">{{ \Illuminate\Support\Carbon::createFromFormat('Ymd_His', $backup['label'])->format('M j, Y g:i A') }}</span>
                            <button type="submit" class="text-xs text-accent-600 hover:text-accent-700 whitespace-nowrap">Load into editor</button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if(auth()->user()->isSuperAdmin())
    <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-5 mb-6">
        <p class="text-sm font-medium text-gray-900 mb-1">Assigned admins</p>
        <p class="text-xs text-gray-500 mb-3">Choose which admins can handle this deployment.</p>
        <form method="POST" action="{{ route('deployments.assign', $deployment) }}">
            @csrf
            @if($admins->isEmpty())
                <p class="text-sm text-gray-500">No admin accounts yet. Create one from Accounts.</p>
            @else
                <div class="space-y-2 mb-4">
                    @foreach($admins as $admin)
                        <label class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm text-gray-700">
                            <input type="checkbox" name="admin_ids[]" value="{{ $admin->id }}"
                                {{ in_array($admin->id, $assignedIds) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-accent-600 focus:ring-accent-500">
                            {{ $admin->name }}
                            <span class="text-xs text-gray-400 break-all">{{ $admin->email }}</span>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="w-full sm:w-auto bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Save Assignments
                </button>
            @endif
        </form>
    </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-3">
            <p class="text-sm font-medium text-gray-900">Recent activity</p>
            <a href="{{ route('deployments.history', $deployment) }}" class="text-sm text-accent-600 hover:text-accent-700">View full history</a>
        </div>
        @if($deployment->logs->isEmpty())
            <p class="text-sm text-gray-500">No actions recorded yet.</p>
        @else
            <div class="space-y-3">
                @foreach($deployment->logs->take(10) as $log)
                    <details class="border border-gray-200 rounded-md">
                        <summary class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between px-3 py-2 cursor-pointer text-sm">
                            <span class="font-medium text-gray-900">{{ str_replace('_', ' ', ucfirst($log->action)) }}</span>
                            <span class="flex items-center gap-3">
                                @if($log->success)
                                    <span class="text-xs text-green-700">Success</span>
                                @else
                                    <span class="text-xs text-accent-600">Failed</span>
                                @endif
                                <span class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                            </span>
                        </summary>
                        <pre class="px-3 pb-3 text-xs text-gray-600 whitespace-pre-wrap break-words overflow-x-auto">{{ $log->output }}</pre>
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
