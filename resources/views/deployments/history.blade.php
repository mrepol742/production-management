<x-app-layout title="History - {{ $deployment->name }}">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ $deployment->name }}</h1>
            <p class="text-sm text-gray-500">Deployment history</p>
        </div>
        <a href="{{ route('deployments.show', $deployment) }}" class="text-sm text-gray-600 hover:text-gray-900">Back to deployment</a>
    </div>

    @if($logs->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-10 text-center">
            <p class="text-sm text-gray-500">No actions recorded yet.</p>
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-200">
            @foreach($logs as $log)
                <details class="px-5 py-4">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ str_replace('_', ' ', ucfirst($log->action)) }}</p>
                            <p class="text-xs text-gray-500">{{ $log->user?->name ?? 'Unknown user' }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($log->success)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Success</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent-100 text-accent-700">Failed</span>
                            @endif
                            <span class="text-xs text-gray-400">{{ $log->created_at->format('M j, Y g:i A') }}</span>
                        </div>
                    </summary>
                    <pre class="mt-3 text-xs text-gray-600 whitespace-pre-wrap">{{ $log->output }}</pre>
                </details>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    @endif
</x-app-layout>
