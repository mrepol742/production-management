<x-app-layout title="{{ $deployment->name }} - Melvin">
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold text-gray-900">{{ $deployment->name }}</h1>
                @if($deployment->status === 'running')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Running</span>
                @elseif($deployment->status === 'paused')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent-100 text-accent-700">Paused</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Unknown</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-1">{{ ucfirst($deployment->type) }} - {{ $deployment->path }}</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <form method="POST" action="{{ route('deployments.destroy', $deployment) }}" onsubmit="return confirm('Remove this deployment from Melvin? This will not touch files on the server.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-gray-500 hover:text-accent-600">Remove</button>
        </form>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500">Branch</p>
            <p class="text-sm font-medium text-gray-900">{{ $deployment->branch }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500">Last deployed</p>
            <p class="text-sm font-medium text-gray-900">{{ $deployment->last_deployed_at ? $deployment->last_deployed_at->diffForHumans() : 'Never' }}</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
        <p class="text-sm font-medium text-gray-900 mb-3">Actions</p>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('deployments.redeploy', $deployment) }}">
                @csrf
                <button type="submit" class="bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Redeploy
                </button>
            </form>

            <form method="POST" action="{{ route('deployments.rebase', $deployment) }}">
                @csrf
                <button type="submit" class="border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-md">
                    Update / Rebase
                </button>
            </form>

            @if($deployment->status === 'paused')
                <form method="POST" action="{{ route('deployments.resume', $deployment) }}">
                    @csrf
                    <button type="submit" class="border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-md">
                        Resume
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('deployments.pause', $deployment) }}">
                    @csrf
                    <button type="submit" class="border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-md">
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

    <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
        <p class="text-sm font-medium text-gray-900 mb-3">Environment file</p>
        <form method="POST" action="{{ route('deployments.env', $deployment) }}">
            @csrf
            <textarea name="env_contents" rows="12" spellcheck="false"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono focus:border-accent-500 focus:ring-accent-500 focus:outline-none">{{ old('env_contents', session('env_preview', $envContents)) }}</textarea>
            <div class="mt-3">
                <button type="submit" class="bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Save .env
                </button>
            </div>
        </form>

        @if(!empty($envBackups))
            <div class="mt-5 pt-5 border-t border-gray-200">
                <p class="text-xs font-medium text-gray-700 mb-2">Previous versions</p>
                <div class="space-y-2">
                    @foreach($envBackups as $backup)
                        <form method="POST" action="{{ route('deployments.env.restore', $deployment) }}" class="flex items-center justify-between">
                            @csrf
                            <input type="hidden" name="backup" value="{{ $backup['label'] }}">
                            <span class="text-xs text-gray-500">{{ \Illuminate\Support\Carbon::createFromFormat('Ymd_His', $backup['label'])->format('M j, Y g:i A') }}</span>
                            <button type="submit" class="text-xs text-accent-600 hover:text-accent-700">Load into editor</button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if(auth()->user()->isSuperAdmin())
    <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
        <p class="text-sm font-medium text-gray-900 mb-1">Assigned admins</p>
        <p class="text-xs text-gray-500 mb-3">Choose which admins can handle this deployment.</p>
        <form method="POST" action="{{ route('deployments.assign', $deployment) }}">
            @csrf
            @if($admins->isEmpty())
                <p class="text-sm text-gray-500">No admin accounts yet. Create one from Accounts.</p>
            @else
                <div class="space-y-2 mb-4">
                    @foreach($admins as $admin)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="admin_ids[]" value="{{ $admin->id }}"
                                {{ in_array($admin->id, $assignedIds) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-accent-600 focus:ring-accent-500">
                            {{ $admin->name }}
                            <span class="text-xs text-gray-400">{{ $admin->email }}</span>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Save Assignments
                </button>
            @endif
        </form>
    </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-medium text-gray-900">Recent activity</p>
            <a href="{{ route('deployments.history', $deployment) }}" class="text-sm text-accent-600 hover:text-accent-700">View full history</a>
        </div>
        @if($deployment->logs->isEmpty())
            <p class="text-sm text-gray-500">No actions recorded yet.</p>
        @else
            <div class="space-y-3">
                @foreach($deployment->logs->take(10) as $log)
                    <details class="border border-gray-200 rounded-md">
                        <summary class="flex items-center justify-between px-3 py-2 cursor-pointer text-sm">
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
                        <pre class="px-3 pb-3 text-xs text-gray-600 whitespace-pre-wrap">{{ $log->output }}</pre>
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
